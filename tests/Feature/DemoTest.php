<?php

namespace Tests\Feature;

use App\Enums\BlockType;
use App\Enums\EventType;
use App\Enums\PageStatus;
use App\Models\AnalyticsDaily;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\ThemePresetSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * La demo pública (bioinfo.pablomandile.com.ar) corre el mismo código que la
 * productiva con `APP_DEMO=true`. Lo que se prueba acá es que el flag encienda
 * lo que tiene que encender y, sobre todo, que **apagado no cambie nada**: el
 * riesgo real de este modo es que algo se filtre a la productiva.
 */
class DemoTest extends TestCase
{
    use RefreshDatabase;

    private function enModoDemo(): void
    {
        config()->set('demo.enabled', true);
    }

    private function sembrarDemo(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(ThemePresetSeeder::class);
        $this->seed(DemoSeeder::class);
    }

    // ---------------------------------------------------------------- el flag

    public function test_sin_el_flag_el_login_no_comparte_credenciales(): void
    {
        $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('demo', null));
    }

    public function test_con_el_flag_el_login_comparte_las_credenciales(): void
    {
        $this->enModoDemo();

        $this->get('/login')->assertInertia(
            fn (Assert $page) => $page
                ->where('demo.email', config('demo.email'))
                ->where('demo.password', config('demo.password')),
        );
    }

    // ------------------------------------------------------- demo:reset

    public function test_demo_reset_se_niega_sin_el_flag(): void
    {
        $user = User::factory()->create();

        $this->assertSame(1, Artisan::call('demo:reset', ['--force' => true]));

        // Lo importante: no borró nada.
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    /*
     * El camino feliz de `demo:reset` no se puede ejercer acá: llama a
     * `migrate:fresh`, que en SQLite hace un VACUUM, y VACUUM no corre dentro
     * de la transacción de RefreshDatabase («cannot VACUUM from within a
     * transaction»). La suite entera usa SQLite en memoria (ver phpunit.xml),
     * así que lo que se prueba acá es la salvaguarda —que es la parte
     * peligrosa— y, por separado, el resultado de los seeders que el comando
     * encadena. El comando completo se verifica corriéndolo en el server.
     */

    public function test_la_demo_no_tiene_ningun_administrador(): void
    {
        $this->sembrarDemo();

        $this->assertNotEmpty(User::all(), 'El seeder no creó ningún usuario.');

        foreach (User::all() as $user) {
            $this->assertFalse($user->isAdmin(), "El usuario {$user->email} quedó como admin en la demo.");
        }
    }

    // ------------------------------------------------------------ el seeder

    public function test_el_seeder_crea_la_cuenta_con_las_credenciales_de_la_config(): void
    {
        $this->sembrarDemo();

        $this->post('/login', [
            'email' => config('demo.email'),
            'password' => config('demo.password'),
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_el_seeder_publica_la_pagina_y_queda_visible(): void
    {
        $this->sembrarDemo();

        $page = Page::query()->where('slug', 'home')->firstOrFail();

        $this->assertSame(PageStatus::Published, $page->status);
        $this->assertTrue($page->is_primary);

        $this->get('/'.config('demo.username'))->assertOk();
    }

    public function test_el_seeder_usa_todos_los_tipos_de_bloque(): void
    {
        $this->sembrarDemo();

        $page = Page::query()->where('slug', 'home')->firstOrFail();
        $tipos = $page->blocks->pluck('type')->unique();

        foreach (BlockType::cases() as $tipo) {
            $this->assertTrue(
                $tipos->contains($tipo),
                "La demo no muestra ningún bloque de tipo {$tipo->value}.",
            );
        }
    }

    public function test_el_seeder_deja_la_analitica_lista_para_el_panel(): void
    {
        $this->sembrarDemo();

        $page = Page::query()->where('slug', 'home')->firstOrFail();
        $daily = AnalyticsDaily::query()->where('page_id', $page->id)->get();

        // El panel dibuja una serie de 30 días: tiene que haber al menos eso.
        $dias = $daily->where('type', EventType::PageView)->pluck('date')->unique();
        $this->assertGreaterThanOrEqual(30, $dias->count());

        // Y clics atribuidos a bloques, que es lo que alimenta el top de enlaces.
        $this->assertTrue(
            $daily->where('type', EventType::LinkClick)->whereNotNull('block_id')->isNotEmpty(),
            'Sin clics por bloque, el «top de enlaces» del panel sale vacío.',
        );
    }

    public function test_el_panel_de_analitica_muestra_numeros(): void
    {
        $this->sembrarDemo();

        $user = User::query()->where('email', config('demo.email'))->firstOrFail();
        $page = Page::query()->where('slug', 'home')->firstOrFail();

        $this->actingAs($user)
            ->get(route('pages.analytics', $page))
            ->assertInertia(function (Assert $inertia) {
                $inertia->where('totals.views', fn ($v) => $v > 0);
                $inertia->where('totals.clicks', fn ($v) => $v > 0);
                $inertia->has('series', 30);
                $inertia->has('topBlocks');
            });
    }

    /**
     * El cron horario corre `analytics:rollup`, que hace `updateOrCreate` sobre
     * los últimos días. Si el seeder escribiera esos días directamente como
     * agregados, el primer rollup los recalcularía desde unos eventos que no
     * existen y los dejaría en cero: la demo se iría degradando sola hora a
     * hora. Por eso los días recientes se siembran como eventos crudos, y acá
     * se comprueba lo que de verdad importa:
     *
     *  - el rollup **no toca** los días históricos, y
     *  - es idempotente: una segunda corrida no cambia ningún número.
     */
    public function test_el_rollup_horario_no_degrada_la_analitica(): void
    {
        $this->sembrarDemo();

        $page = Page::query()->where('slug', 'home')->firstOrFail();
        $limite = now()->subDays(4)->toDateString();
        $historicoAntes = (int) AnalyticsDaily::query()
            ->where('page_id', $page->id)
            ->where('date', '<=', $limite)
            ->sum('count');

        Artisan::call('analytics:rollup');

        $historicoDespues = (int) AnalyticsDaily::query()
            ->where('page_id', $page->id)
            ->where('date', '<=', $limite)
            ->sum('count');

        $this->assertSame($historicoAntes, $historicoDespues, 'El rollup pisó días que ya estaban consolidados.');
        $this->assertGreaterThan(0, $historicoAntes);

        // Idempotencia: ya consolidado, volver a correrlo no mueve nada.
        $total = (int) AnalyticsDaily::query()->where('page_id', $page->id)->sum('count');

        Artisan::call('analytics:rollup');

        $this->assertSame($total, (int) AnalyticsDaily::query()->where('page_id', $page->id)->sum('count'));
    }

    // ----------------------------------------------------- ProtegerDemo

    public function test_en_la_demo_el_registro_lleva_al_login(): void
    {
        $this->enModoDemo();

        $this->get('/register')->assertRedirect(route('login'));
        $this->get('/auth/google/redirect')->assertRedirect(route('login'));
        $this->get('/forgot-password')->assertRedirect(route('login'));
    }

    public function test_en_la_demo_no_se_puede_cambiar_la_contrasena(): void
    {
        $this->sembrarDemo();
        $this->enModoDemo();

        $user = User::query()->where('email', config('demo.email'))->firstOrFail();

        $this->actingAs($user)
            ->from('/settings/password')
            ->put('/settings/password', [
                'current_password' => config('demo.password'),
                'password' => 'otra-clave-larga',
                'password_confirmation' => 'otra-clave-larga',
            ])
            ->assertRedirect('/settings/password')
            ->assertSessionHasErrors('current_password');

        // La clave original sigue sirviendo.
        $this->assertTrue(Hash::check(config('demo.password'), $user->refresh()->password));
    }

    public function test_en_la_demo_no_se_puede_cambiar_el_perfil_ni_borrar_la_cuenta(): void
    {
        $this->sembrarDemo();
        $this->enModoDemo();

        $user = User::query()->where('email', config('demo.email'))->firstOrFail();

        $this->actingAs($user)
            ->from('/settings/profile')
            ->patch('/settings/profile', ['name' => 'Otro', 'email' => 'otro@example.com'])
            ->assertRedirect('/settings/profile')
            ->assertSessionHasErrors('email');

        $this->actingAs($user)
            ->from('/settings/profile')
            ->delete('/settings/profile', ['password' => config('demo.password')])
            ->assertRedirect('/settings/profile');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => config('demo.email')]);
    }

    /**
     * Lo que el modo demo **no** debe cambiar: con el flag apagado, todo lo que
     * ProtegerDemo intercepta sigue funcionando como siempre.
     */
    public function test_sin_el_flag_el_perfil_se_puede_editar_normalmente(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/settings/profile', ['name' => 'Nombre Nuevo', 'email' => 'nuevo@example.com'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Nombre Nuevo', $user->refresh()->name);
    }

    /**
     * El reset nocturno está registrado siempre (el scheduler lista todas las
     * tareas, con filtro o sin él), así que lo que hay que comprobar es que su
     * `when()` lo deje pasar **solo** en la demo: en la productiva una tarea
     * que llama a `migrate:fresh` no puede ejecutarse nunca.
     */
    public function test_el_reset_nocturno_no_pasa_el_filtro_fuera_de_la_demo(): void
    {
        $this->assertFalse($this->elResetPasaElFiltro(), 'El reset nocturno se ejecutaría en la productiva.');

        $this->enModoDemo();

        $this->assertTrue($this->elResetPasaElFiltro(), 'El reset nocturno no se ejecutaría en la demo.');
    }

    private function elResetPasaElFiltro(): bool
    {
        foreach (app(Schedule::class)->events() as $evento) {
            if (str_contains($evento->command ?? '', 'demo:reset')) {
                return $evento->filtersPass($this->app);
            }
        }

        $this->fail('La tarea demo:reset no está registrada en el scheduler.');
    }
}
