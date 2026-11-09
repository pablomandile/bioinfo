<?php

namespace Database\Seeders;

use App\Enums\BlockSize;
use App\Enums\BlockType;
use App\Enums\EventType;
use App\Enums\PageLayout;
use App\Enums\PageStatus;
use App\Enums\Role;
use App\Enums\SocialPlatform;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsEvent;
use App\Models\Block;
use App\Models\Page;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Datos de la demo pública (bioinfo.pablomandile.com.ar).
 *
 * Distinto de DemoUserSeeder, que es el usuario de prueba del entorno local:
 * acá las credenciales salen de config/demo.php, el perfil tiene contenido
 * variado (un bloque de cada tipo) y hay dos meses de analítica para que el
 * panel no se vea vacío.
 *
 * Dos decisiones que hacen que la demo se vea siempre al día:
 *
 *  - **Fechas relativas a hoy.** Nada de fechas fijas: el reset corre cada
 *    noche y la serie de 30 días del panel tiene que terminar siempre hoy.
 *  - **Semilla fija** (`mt_srand`), para que la forma de la curva no cambie
 *    entre resets y la demo se vea igual cada vez que alguien entra.
 */
class DemoSeeder extends Seeder
{
    /**
     * Días de historia que se siembran en `analytics_daily`.
     *
     * El panel muestra una serie de 30 días; se siembra el doble para que los
     * totales acumulados tengan sentido y el gráfico no arranque en un borde.
     */
    private const DIAS_DE_HISTORIA = 60;

    /**
     * Días recientes que se siembran como eventos crudos en vez de agregados.
     *
     * `analytics:rollup` corre cada hora sobre los últimos 2 días y hace
     * `updateOrCreate` sobre `analytics_daily`: si esos días se sembraran
     * directo como agregados, el primer rollup los recalcularía desde los
     * eventos (que no existirían) y los dejaría en cero. Así que los últimos
     * días se siembran como eventos y el agregado lo produce el rollup real,
     * que el propio comando `demo:reset` ejecuta al final.
     */
    private const DIAS_EN_CRUDO = 3;

    public function run(): void
    {
        mt_srand(20261109);

        $user = $this->crearUsuario();
        $page = $this->crearPaginaPrincipal($user);

        $bloques = $this->crearBloques($page);
        $this->crearRedes($page);
        $this->crearPaginaBorrador($user);

        $this->sembrarAnalitica($page, $bloques);
    }

    private function crearUsuario(): User
    {
        $user = User::updateOrCreate(
            ['email' => config('demo.email')],
            [
                'name' => 'Vera Lombardi',
                'username' => config('demo.username'),
                'password' => Hash::make(config('demo.password')),
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $user->syncRoles([Role::User->value]);

        return $user;
    }

    private function crearPaginaPrincipal(User $user): Page
    {
        $theme = Theme::query()->where('is_preset', true)->where('name', 'Sunset')->first();

        $page = Page::updateOrCreate(
            ['user_id' => $user->id, 'slug' => 'home'],
            [
                'title' => 'Vera Lombardi',
                'bio' => 'Ilustradora y diseñadora editorial · Buenos Aires',
                'layout' => PageLayout::List->value,
                'status' => PageStatus::Published->value,
                'is_primary' => true,
                'published_at' => now()->subMonths(8),
                'meta_title' => 'Vera Lombardi · Ilustración y diseño editorial',
                'meta_description' => 'Enlaces a mi portfolio, la tienda de láminas y los encargos abiertos.',
                'theme' => [
                    'presetId' => $theme?->slug,
                    'mode' => 'dark',
                    'tokens' => [],
                ],
            ],
        );

        $page->clearMediaCollection('avatar');
        $page->addMediaFromString($this->dibujarAvatar('VL'))
            ->usingFileName('avatar-demo.jpg')
            ->toMediaCollection('avatar');

        return $page;
    }

    /**
     * Un bloque de cada tipo, con contenido que se entiende solo: la demo es lo
     * que alguien mira para decidir si la herramienta le sirve.
     *
     * @return array<int, Block> los bloques de tipo link, que son los que
     *                           reciben clics en la analítica
     */
    private function crearBloques(Page $page): array
    {
        $page->blocks()->delete();

        $imagen = $this->urlDeLaImagenDeObra();

        $definiciones = [
            [BlockType::Heading, ['text' => 'Mi trabajo'], BlockSize::Md],
            [BlockType::Link, ['label' => 'Portfolio completo', 'url' => 'https://example.com/vera/portfolio'], BlockSize::Lg],
            [BlockType::Link, ['label' => 'Tienda de láminas', 'url' => 'https://example.com/vera/tienda'], BlockSize::Md],
            [BlockType::Link, ['label' => 'Pedir un encargo', 'url' => 'https://example.com/vera/encargos'], BlockSize::Md],
            [BlockType::Image, ['url' => $imagen, 'alt' => 'Lámina «Ciudad de papel», serie 2026', 'href' => 'https://example.com/vera/tienda'], BlockSize::Lg],
            [BlockType::Divider, [], BlockSize::Sm],
            [BlockType::Heading, ['text' => 'Novedades'], BlockSize::Md],
            [BlockType::Text, ['text' => 'Abrí la lista de espera para los encargos de diciembre. Son seis lugares y se asignan por orden de pedido.'], BlockSize::Md],
            [BlockType::Embed, ['provider' => 'youtube', 'id' => 'aqz-KE-bpKQ'], BlockSize::Lg],
            [BlockType::Link, ['label' => 'Newsletter mensual', 'url' => 'https://example.com/vera/newsletter'], BlockSize::Sm],
        ];

        $links = [];

        foreach ($definiciones as $i => [$tipo, $data, $size]) {
            $block = $page->blocks()->create([
                'type' => $tipo->value,
                'data' => $data,
                'position' => $i + 1,
                'size' => $size->value,
                'is_visible' => true,
            ]);

            if ($tipo === BlockType::Link) {
                $links[] = $block;
            }
        }

        return $links;
    }

    private function crearRedes(Page $page): void
    {
        $page->socialLinks()->delete();

        $redes = [
            [SocialPlatform::Instagram, 'https://instagram.com/example'],
            [SocialPlatform::TikTok, 'https://tiktok.com/@example'],
            [SocialPlatform::YouTube, 'https://youtube.com/@example'],
            [SocialPlatform::Email, 'mailto:hola@example.com'],
            [SocialPlatform::Website, 'https://example.com/vera'],
        ];

        foreach ($redes as $i => [$plataforma, $url]) {
            $page->socialLinks()->create([
                'platform' => $plataforma->value,
                'url' => $url,
                'position' => $i + 1,
            ]);
        }
    }

    /**
     * Una segunda página en borrador: muestra que se pueden tener varias y que
     * hay estados, que de otro modo no se ven en una demo con una sola.
     */
    private function crearPaginaBorrador(User $user): void
    {
        $page = Page::updateOrCreate(
            ['user_id' => $user->id, 'slug' => 'taller'],
            [
                'title' => 'Taller de ilustración 2027',
                'bio' => 'Página en preparación para el taller del verano.',
                'layout' => PageLayout::Grid->value,
                'status' => PageStatus::Draft->value,
                'is_primary' => false,
            ],
        );

        if ($page->blocks()->count() === 0) {
            $page->blocks()->create([
                'type' => BlockType::Heading->value,
                'data' => ['text' => 'Pronto: fechas y cupos'],
                'position' => 1,
            ]);
        }
    }

    /**
     * @param  array<int, Block>  $links
     */
    private function sembrarAnalitica(Page $page, array $links): void
    {
        $page->analyticsEvents()->delete();
        AnalyticsDaily::query()->where('page_id', $page->id)->delete();

        $agregados = [];
        $eventos = [];

        for ($diasAtras = self::DIAS_DE_HISTORIA; $diasAtras >= 0; $diasAtras--) {
            $dia = Carbon::today()->subDays($diasAtras);
            $visitas = $this->visitasDelDia($dia, $diasAtras);
            $clics = (int) round($visitas * mt_rand(18, 34) / 100);

            if ($diasAtras < self::DIAS_EN_CRUDO) {
                // Días recientes: eventos crudos. El agregado lo hace el rollup.
                foreach ($this->momentosDelDia($dia, $visitas) as $momento) {
                    $eventos[] = $this->evento($page, null, EventType::PageView, $momento);
                }

                // Mismo reparto por pesos que los días históricos: si acá se
                // repartieran por turnos, el «top de enlaces» de los últimos
                // días saldría plano y no coincidiría con el del resto.
                $porEnlace = $this->repartirClics($clics, count($links));
                $momentos = $this->momentosDelDia($dia, $clics);
                $n = 0;

                foreach ($porEnlace as $i => $cuenta) {
                    for ($c = 0; $c < $cuenta; $c++) {
                        $eventos[] = $this->evento($page, $links[$i], EventType::LinkClick, $momentos[$n]);
                        $n++;
                    }
                }

                continue;
            }

            $agregados[] = [
                'page_id' => $page->id,
                'block_id' => null,
                'type' => EventType::PageView->value,
                'date' => $dia->toDateString(),
                'count' => $visitas,
            ];

            // Los clics se reparten entre los enlaces con pesos decrecientes:
            // el portfolio se lleva la mayor parte, y el último, unos pocos.
            foreach ($this->repartirClics($clics, count($links)) as $i => $cuenta) {
                if ($cuenta === 0) {
                    continue;
                }

                $agregados[] = [
                    'page_id' => $page->id,
                    'block_id' => $links[$i]->id,
                    'type' => EventType::LinkClick->value,
                    'date' => $dia->toDateString(),
                    'count' => $cuenta,
                ];
            }
        }

        foreach (array_chunk($agregados, 500) as $lote) {
            AnalyticsDaily::query()->insert($lote);
        }

        foreach (array_chunk($eventos, 500) as $lote) {
            AnalyticsEvent::query()->insert($lote);
        }
    }

    /**
     * Curva de visitas: los fines de semana bajan, hay una tendencia suave de
     * crecimiento y un pico puntual (la nota que la citó) para que el gráfico
     * tenga algo que contar.
     */
    private function visitasDelDia(Carbon $dia, int $diasAtras): int
    {
        $base = 40 + (int) round((self::DIAS_DE_HISTORIA - $diasAtras) * 0.8);

        if ($dia->isWeekend()) {
            $base = (int) round($base * 0.55);
        }

        // El pico: tres días consecutivos a mitad de la serie visible.
        if ($diasAtras >= 12 && $diasAtras <= 14) {
            $base *= 4;
        }

        return max(3, $base + mt_rand(-8, 12));
    }

    /**
     * Reparte N clics entre los enlaces con pesos 8/4/2/1: así el «top de
     * enlaces» del panel tiene un orden claro y no cuatro barras iguales.
     *
     * @return array<int, int>
     */
    private function repartirClics(int $total, int $cantidad): array
    {
        $pesos = [];
        for ($i = 0; $i < $cantidad; $i++) {
            $pesos[$i] = 2 ** ($cantidad - 1 - $i);
        }

        $suma = array_sum($pesos);
        $reparto = [];
        $asignado = 0;

        for ($i = 0; $i < $cantidad; $i++) {
            $reparto[$i] = (int) floor($total * $pesos[$i] / $suma);
            $asignado += $reparto[$i];
        }

        // El resto va al primero, para que los totales cierren exactos.
        $reparto[0] += $total - $asignado;

        return $reparto;
    }

    /**
     * Momentos del día para los eventos crudos, concentrados en las horas de
     * más tráfico y sin pasarse de «ahora» si el día es hoy.
     *
     * @return array<int, Carbon>
     */
    private function momentosDelDia(Carbon $dia, int $cantidad): array
    {
        $momentos = [];
        $limite = $dia->isToday() ? now() : $dia->copy()->endOfDay();

        for ($i = 0; $i < $cantidad; $i++) {
            $momento = $dia->copy()
                ->setHour(mt_rand(8, 23))
                ->setMinute(mt_rand(0, 59))
                ->setSecond(mt_rand(0, 59));

            if ($momento->greaterThan($limite)) {
                $momento = $limite->copy()->subMinutes(mt_rand(1, 240));
            }

            if ($momento->lessThan($dia)) {
                $momento = $dia->copy()->addMinutes(mt_rand(1, 60));
            }

            $momentos[] = $momento;
        }

        return $momentos;
    }

    /**
     * @return array<string, mixed>
     */
    private function evento(Page $page, ?Block $block, EventType $tipo, Carbon $momento): array
    {
        $dispositivos = ['mobile', 'mobile', 'mobile', 'desktop', 'tablet'];
        $navegadores = ['Chrome', 'Safari', 'Edge', 'Firefox'];
        $sistemas = ['Android', 'iOS', 'Windows', 'macOS'];
        $referrers = ['instagram.com', 'instagram.com', 't.co', 'google.com', null];

        return [
            'page_id' => $page->id,
            'block_id' => $block?->id,
            'type' => $tipo->value,
            'ip_hash' => hash('sha256', 'demo-'.mt_rand(1, 400)),
            'country_code' => mt_rand(1, 10) > 2 ? 'AR' : 'UY',
            'device_type' => $dispositivos[mt_rand(0, count($dispositivos) - 1)],
            'browser' => $navegadores[mt_rand(0, count($navegadores) - 1)],
            'os' => $sistemas[mt_rand(0, count($sistemas) - 1)],
            'referrer_host' => $referrers[mt_rand(0, count($referrers) - 1)],
            'target_url' => $block?->data['url'] ?? null,
            'created_at' => $momento->toDateTimeString(),
        ];
    }

    /**
     * La imagen del bloque de tipo `image` se dibuja acá y se guarda en el
     * disco público: así la demo no depende de ningún servicio externo que
     * pueda caerse o cambiar la foto. `demo:reset` borra los uploads antes de
     * sembrar, por eso se regenera en cada corrida.
     */
    private function urlDeLaImagenDeObra(): string
    {
        $ruta = 'demo/obra.jpg';

        Storage::disk('public')->put($ruta, $this->dibujarObra());

        // Mismo resultado que `Storage::disk('public')->url()`, pero sin
        // depender de un método que el contrato Filesystem no declara.
        return asset('storage/'.$ruta);
    }

    private function dibujarAvatar(string $iniciales): string
    {
        $tamanio = 400;
        $img = imagecreatetruecolor($tamanio, $tamanio);

        // Degradado diagonal en los tonos del preset Sunset.
        for ($y = 0; $y < $tamanio; $y++) {
            for ($x = 0; $x < $tamanio; $x += 8) {
                $t = ($x + $y) / ($tamanio * 2);
                $color = imagecolorallocate(
                    $img,
                    (int) (240 - 60 * $t),
                    (int) (120 + 40 * $t),
                    (int) (60 + 120 * $t),
                );
                imagefilledrectangle($img, $x, $y, $x + 8, $y, $color);
            }
        }

        $blanco = imagecolorallocate($img, 255, 255, 255);
        // Fuente bitmap incorporada: no hace falta ningún .ttf en el repo.
        $escala = 7;
        $ancho = imagefontwidth(5) * $escala * strlen($iniciales);
        $alto = imagefontheight(5) * $escala;
        $capa = imagecreatetruecolor(imagefontwidth(5) * strlen($iniciales), imagefontheight(5));
        $fondo = imagecolorallocate($capa, 0, 0, 0);
        imagecolortransparent($capa, $fondo);
        imagefilledrectangle($capa, 0, 0, imagesx($capa), imagesy($capa), $fondo);
        imagestring($capa, 5, 0, 0, $iniciales, imagecolorallocate($capa, 255, 255, 255));
        imagecopyresized(
            $img, $capa,
            (int) (($tamanio - $ancho) / 2), (int) (($tamanio - $alto) / 2),
            0, 0,
            $ancho, $alto,
            imagesx($capa), imagesy($capa),
        );
        unset($capa, $blanco);

        return $this->aJpeg($img);
    }

    private function dibujarObra(): string
    {
        $ancho = 1200;
        $alto = 800;
        $img = imagecreatetruecolor($ancho, $alto);

        $cielo = imagecolorallocate($img, 28, 32, 64);
        imagefilledrectangle($img, 0, 0, $ancho, $alto, $cielo);

        // Una «ciudad de papel»: rectángulos de alturas variables, que es lo
        // que el alt del bloque promete.
        $x = -20;
        $tono = 0;
        while ($x < $ancho) {
            $w = mt_rand(60, 140);
            $h = mt_rand(150, 520);
            $tono = ($tono + 1) % 3;
            $color = imagecolorallocate($img, 60 + $tono * 35, 70 + $tono * 40, 120 + $tono * 45);
            imagefilledrectangle($img, $x, $alto - $h, $x + $w, $alto, $color);

            // Ventanas.
            $luz = imagecolorallocate($img, 250, 214, 140);
            for ($vy = $alto - $h + 18; $vy < $alto - 20; $vy += 34) {
                for ($vx = $x + 12; $vx < $x + $w - 14; $vx += 26) {
                    if (mt_rand(0, 2) > 0) {
                        imagefilledrectangle($img, $vx, $vy, $vx + 10, $vy + 16, $luz);
                    }
                }
            }

            $x += $w + mt_rand(4, 18);
        }

        $luna = imagecolorallocate($img, 255, 244, 214);
        imagefilledellipse($img, (int) ($ancho * 0.8), 140, 120, 120, $luna);

        return $this->aJpeg($img);
    }

    /**
     * @param  \GdImage  $img
     */
    private function aJpeg($img): string
    {
        ob_start();
        imagejpeg($img, null, 82);

        // Sin imagedestroy(): está deprecado desde PHP 8.4 y el recurso se
        // libera solo al salir de ámbito.
        return (string) ob_get_clean();
    }
}
