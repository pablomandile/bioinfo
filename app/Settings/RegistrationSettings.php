<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class RegistrationSettings extends Settings
{
    /**
     * Si es true, el registro público de nuevas cuentas está habilitado.
     */
    public bool $open;

    public static function group(): string
    {
        return 'registration';
    }
}
