<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Valida la URL de un bloque de enlace. Además de http(s), acepta los esquemas
 * habituales en un link-in-bio:
 *   - mailto:correo@dominio.com  (opcionalmente con ?subject=…&body=…)
 *   - tel:+5491112345678
 */
class LinkUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            $fail('El enlace es obligatorio.');

            return;
        }

        $value = trim($value);

        if (Str::startsWith($value, 'mailto:')) {
            // El correo puede venir con parámetros: mailto:correo@dominio.com?subject=Hola
            $email = Str::of($value)->after('mailto:')->before('?')->toString();

            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return;
            }

            $fail('El correo del enlace mailto: no es válido.');

            return;
        }

        if (Str::startsWith($value, 'tel:')) {
            $number = Str::after($value, 'tel:');

            if (preg_match('/^\+?[0-9().\s-]{3,}$/', $number) === 1) {
                return;
            }

            $fail('El teléfono del enlace tel: no es válido.');

            return;
        }

        if (Validator::make(['value' => $value], ['value' => 'url:http,https'])->passes()) {
            return;
        }

        $fail('Ingresá una URL válida (http/https), un correo (mailto:) o un teléfono (tel:).');
    }
}
