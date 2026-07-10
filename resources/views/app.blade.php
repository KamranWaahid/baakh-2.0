@inject('seo', 'Artesaos\SEOTools\Contracts\SEOTools')
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'sd' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {!! $seo->generate() !!}

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/web/main.jsx'], 'build')
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="preload" href="/api/v1/feed" as="fetch" crossorigin="anonymous">

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-0GPQC53GE1"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'G-0GPQC53GE1');
    </script>
</head>

<body class="antialiased font-sans">
    <noscript>
        @if(isset($fallback))
            <h1>{{ $fallback['title'] ?? '' }}</h1>
            <p>{{ $fallback['description'] ?? '' }}</p>
            {!! $fallback['html'] ?? '' !!}
        @endif
    </noscript>
    <div id="root"></div>
</body>

</html>