@inject('seo', 'Artesaos\SEOTools\Contracts\SEOTools')
<!DOCTYPE html>
<html lang="{{ $locale ?? 'sd' }}" dir="{{ ($locale ?? 'sd') === 'sd' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#FDE3E9">

    {!! $seo->generate() !!}

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@400;600;700&family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=IBM+Plex+Sans+Arabic:wght@300;400;500;600;700&family=Noto+Naskh+Arabic:wght@400;500;600;700&family=Nunito:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/lyrics/main.jsx'], 'build')
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">

    <script>
        window.__BAAKH_LYRICS__ = {
            mainSiteUrl: @json($mainSiteUrl ?? 'https://baakh.com'),
            lyricsSiteUrl: @json($lyricsSiteUrl ?? 'https://lyrics.baakh.com'),
            locale: @json($locale ?? 'sd'),
        };
    </script>
</head>

<body class="antialiased lyrics-site">
    <div id="lyrics-root"></div>
</body>

</html>
