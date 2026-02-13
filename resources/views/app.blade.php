@inject('seo', 'Artesaos\SEOTools\Contracts\SEOTools')
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'sd' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>باک - سنڌي شاعري جو آرڪائيو</title>

    <!-- Font Optimization -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="preload" href="/assets/fonts/SF-Arabic.woff2" as="font" type="font/woff2" crossorigin>

    {!! $seo->generate() !!}

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/web/main.jsx'])
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
</head>

<body class="antialiased font-sans">
    <div id="root"></div>
</body>

</html>