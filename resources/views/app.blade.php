<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Baakh - Sindhi Literature Portal</title>

    <!-- Font Optimization -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="preload" href="/assets/fonts/SF-Arabic.woff2" as="font" type="font/woff2" crossorigin>

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/web/main.jsx'])
</head>

<body class="antialiased font-sans">
    <div id="root"></div>
</body>

</html>