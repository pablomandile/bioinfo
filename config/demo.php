<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Modo demo
    |--------------------------------------------------------------------------
    |
    | Con el flag apagado nada de lo que cuelga de acá actúa: el mismo código
    | va a la productiva (bioinfo.bambudev.com.ar) y a la copia pública de
    | demostración (bioinfo.pablomandile.com.ar), y es el `.env` de cada una el
    | que decide. En la productiva no se define `APP_DEMO`, así que queda en
    | false.
    |
    | Lo que el flag enciende:
    |   - el recuadro «Probá la demo» en el login, con las credenciales a la vista;
    |   - el middleware ProtegerDemo, que bloquea registro, Google, recuperar
    |     contraseña y cualquier cambio sobre la cuenta demo;
    |   - la tarea programada `demo:reset`, que devuelve los datos a su estado
    |     original cada noche.
    |
    */

    'enabled' => (bool) env('APP_DEMO', false),

    /*
    | Credenciales de la cuenta de demostración. Se muestran en el login, así
    | que son públicas a propósito: es una cuenta sin datos reales que se
    | reconstruye entera cada noche.
    */

    'email' => env('DEMO_EMAIL', 'demo@pablomandile.com.ar'),

    'password' => env('DEMO_PASSWORD', 'demo1234'),

    /*
    | Nombre de usuario de la cuenta demo, que es también su URL pública
    | (`/{username}`).
    */

    'username' => env('DEMO_USERNAME', 'demo'),

    /*
    | Hora del reset nocturno, en la zona horaria de la app (UTC).
    */

    'reset_at' => env('DEMO_RESET_AT', '04:00'),

];
