<?php

use App\Http\Middleware\HandleInertiaRequests;

/**
 * La versión del asset. Sin ella el middleware de Inertia contesta 409 en vez
 * de la página, y el test parece roto cuando no lo está.
 */
function versionDeInertia(): string
{
    return (string) app(HandleInertiaRequests::class)->version(request());
}

it('prohíbe guardar la respuesta XHR de Inertia', function () {
    $respuesta = $this->get('/login', [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => versionDeInertia(),
    ]);

    $respuesta->assertOk();
    expect($respuesta->headers->get('Content-Type'))->toContain('application/json');
    expect($respuesta->headers->get('Cache-Control'))->toContain('no-store');
});

it('deja cacheable el documento HTML, para no perder el bfcache', function () {
    $respuesta = $this->get('/login');

    $respuesta->assertOk();
    expect($respuesta->headers->get('Content-Type'))->toContain('text/html');
    expect($respuesta->headers->get('Cache-Control'))->not->toContain('no-store');
});
