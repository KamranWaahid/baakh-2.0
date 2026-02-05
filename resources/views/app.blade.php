<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Baakh - Sindhi Literature Portal</title>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/web/main.jsx'])
</head>

<body class="antialiased font-sans">
    <div id="root"></div>
</body>

</html>