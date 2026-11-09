<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * En la demo pública la cuenta es compartida y las credenciales están a la
 * vista, así que hay dos cosas que no pueden pasar:
 *
 *  - que alguien **se quede** con la cuenta (cambiándole la contraseña o el
 *    email) o la borre, dejando la demo inservible hasta el reset de la noche;
 *  - que se creen cuentas nuevas, o que se pida un mail de recuperación a una
 *    casilla que no es de quien lo pide.
 *
 * Lo primero se contesta volviendo atrás con un error en el formulario; lo
 * segundo, mandando al login, que es donde está el botón de la demo.
 *
 * Con `APP_DEMO` apagado el middleware no hace nada, así que el mismo código
 * corre igual en la productiva.
 */
class ProtegerDemo
{
    /**
     * Rutas que en la demo no existen: llevan al login.
     *
     * Registro y Google crearían cuentas sueltas en una base que se borra cada
     * noche; «olvidé mi contraseña» mandaría un mail a un tercero (y además el
     * correo está en `log`, así que nunca llegaría).
     */
    private const FUERA_DE_LA_DEMO = [
        'register',
        'google.redirect',
        'google.callback',
        'password.request',
        'password.email',
        'password.reset',
        'password.store',
    ];

    /**
     * Rutas que tocan la cuenta compartida: vuelven atrás con el aviso.
     */
    private const NO_SE_PUEDE_TOCAR = [
        'password.update',
        'profile.update',
        'profile.destroy',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('demo.enabled')) {
            return $next($request);
        }

        $ruta = $request->route()?->getName();

        if (in_array($ruta, self::FUERA_DE_LA_DEMO, true)) {
            return redirect()->route('login');
        }

        if (in_array($ruta, self::NO_SE_PUEDE_TOCAR, true)) {
            return back()->withErrors([
                $this->campoDelAviso($ruta) => 'Esto es una demo: la cuenta es compartida, así que no se puede modificar ni borrar. Todos los datos se reinician cada noche.',
            ]);
        }

        return $next($request);
    }

    /**
     * El error se cuelga del campo que el formulario de cada pantalla muestra,
     * para que el aviso aparezca donde la persona está mirando.
     */
    private function campoDelAviso(string $ruta): string
    {
        return match ($ruta) {
            'password.update' => 'current_password',
            'profile.destroy' => 'password',
            default => 'email',
        };
    }
}
