<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php($bioMeta = $page['props']['meta'] ?? null)
        <title inertia>{{ $bioMeta['title'] ?? config('app.name', 'Laravel') }}</title>

        @if ($bioMeta)
            @if (!empty($bioMeta['description']))
                <meta name="description" content="{{ $bioMeta['description'] }}">
            @endif
            @if (!empty($bioMeta['noindex']))
                <meta name="robots" content="noindex">
            @endif
            <meta property="og:title" content="{{ $bioMeta['title'] }}">
            @if (!empty($bioMeta['description']))
                <meta property="og:description" content="{{ $bioMeta['description'] }}">
            @endif
            @if (!empty($bioMeta['ogImage']))
                <meta property="og:image" content="{{ $bioMeta['ogImage'] }}">
            @endif
            <meta property="og:type" content="profile">
            <meta name="twitter:card" content="{{ !empty($bioMeta['ogImage']) ? 'summary_large_image' : 'summary' }}">
        @endif

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @routes
        @vite(['resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
