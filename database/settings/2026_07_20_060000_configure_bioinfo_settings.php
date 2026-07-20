<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Registro cerrado por defecto: el admin lo abre desde el área de administración.
        $this->migrator->add('registration.open', false);

        $this->migrator->add('site.name', 'Bioinfo');
        $this->migrator->add('site.logo', null);
    }

    public function down(): void
    {
        $this->migrator->deleteIfExists('registration.open');
        $this->migrator->deleteIfExists('site.name');
        $this->migrator->deleteIfExists('site.logo');
    }
};
