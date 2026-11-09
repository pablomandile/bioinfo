<?php

namespace App\Console\Commands;

use Database\Seeders\DemoSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\ThemePresetSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Devuelve la demo pública a su estado original: borra todo, siembra el usuario
 * demo y vuelve a consolidar su analítica.
 *
 * Corre cada noche por el scheduler (ver routes/console.php) y se puede correr
 * a mano en el server. **Nunca** contra la productiva: lo primero que hace es
 * negarse si el flag de demo no está encendido.
 */
class ResetDemo extends Command
{
    protected $signature = 'demo:reset {--force : No pedir confirmación}';

    protected $description = 'Reinicia los datos de la demo pública (solo con APP_DEMO=true)';

    public function handle(): int
    {
        if (! config('demo.enabled')) {
            $this->error('Este comando solo corre en la demo (APP_DEMO=true). No se tocó nada.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Esto borra TODOS los datos de esta base. ¿Seguir?')) {
            return self::FAILURE;
        }

        /*
         * La demo corre con APP_ENV=production, y ahí Laravel puede bloquear los
         * comandos destructivos. El bloqueo hace que `migrate:fresh` devuelva un
         * WARN en vez de un error, así que sin esto el comando seguiría de largo
         * y sembraría encima de lo que hubiera. Se desactiva recién acá, después
         * de haber comprobado el flag.
         */
        DB::prohibitDestructiveCommands(false);

        $this->components->info('Vaciando la base…');

        $salida = $this->call('migrate:fresh', ['--force' => true]);

        if ($salida !== 0) {
            $this->error("migrate:fresh terminó con código {$salida}: no se sembró nada.");

            return self::FAILURE;
        }

        $this->borrarArchivosSubidos();

        $this->components->info('Sembrando los datos de la demo…');

        // Sin AdminUserSeeder a propósito: un administrador con contraseña
        // conocida en un sitio público sería un agujero, y la demo no necesita
        // mostrar el área de administración.
        foreach ([RoleSeeder::class, ThemePresetSeeder::class, DemoSeeder::class] as $seeder) {
            $this->call('db:seed', ['--class' => $seeder, '--force' => true]);
        }

        // Los últimos días se sembraron como eventos crudos: este rollup es el
        // que produce sus agregados, igual que lo hará el cron horario después.
        $this->call('analytics:rollup', ['--days' => 3]);

        $this->components->info('Demo reiniciada.');

        return self::SUCCESS;
    }

    /**
     * Los archivos que subieron los visitantes de la demo no están en la base,
     * así que `migrate:fresh` no los toca: sin esto se acumularían para siempre.
     *
     * Se borra el contenido del disco público, no el disco entero, para no
     * llevarse por delante el symlink `public/storage`.
     */
    private function borrarArchivosSubidos(): void
    {
        $disco = Storage::disk('public');

        foreach ($disco->directories() as $carpeta) {
            $disco->deleteDirectory($carpeta);
        }

        foreach ($disco->files() as $archivo) {
            if (! str_starts_with(basename($archivo), '.')) {
                $disco->delete($archivo);
            }
        }

        $this->line('  Archivos subidos borrados.');
    }
}
