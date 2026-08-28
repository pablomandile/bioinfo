<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Inertia\Support\Header;
use Symfony\Component\HttpFoundation\Response;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Ajusta los headers de caché de la respuesta que arma Inertia.
     *
     * Va acá y no en un middleware aparte: el middleware de Inertia setea el
     * Vary y puede reemplazar la respuesta entera en onVersionChange(), así que
     * cualquier middleware posterior en el grupo `web` correría antes y quedaría
     * pisado.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = parent::handle($request, $next);

        // Inertia ya lo pone y el CDN de Hostinger lo borra al comprimir con
        // brotli, pero se declara igual: es lo correcto y lo respeta cualquier
        // intermediario que no lo rompa.
        $response->headers->set('Vary', Header::INERTIA.', Accept-Encoding');

        /*
         * `no-store`, no `no-cache`: `no-cache` permite guardar la respuesta y
         * solo obliga a revalidar, y una navegación de historial (restaurar una
         * pestaña descartada, el botón "atrás") saltea la revalidación. Sin
         * `no-store` el navegador reusa el JSON guardado y lo muestra crudo.
         *
         * Y solo sobre la respuesta XHR, NUNCA sobre el HTML: `no-store` en el
         * documento principal desactiva el back/forward cache de Chrome y
         * convierte cada "atrás" en una ida completa a la red.
         */
        if ($request->header(Header::INERTIA)) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return array_merge(parent::share($request), [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
                'isAdmin' => (bool) $request->user()?->isAdmin(),
            ],
        ]);
    }
}
