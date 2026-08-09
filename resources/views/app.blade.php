@inject('seo', 'Artesaos\SEOTools\Contracts\SEOTools')
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'sd' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

    {!! $seo->generate() !!}

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/web/main.jsx'], 'build')
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    @if(!empty($feedPreloadUrl))
        <link rel="preload" href="{{ $feedPreloadUrl }}" as="fetch" crossorigin="anonymous">
    @endif
    @if(!empty($bootstrapFeed))
        <script>
            window.__BAAKH_BOOTSTRAP_FEED__ = @json($bootstrapFeed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        </script>
    @endif

    <!-- Google tag (gtag.js) — deferred so it does not compete with LCP -->
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      window.addEventListener('load', function () {
        var s = document.createElement('script');
        s.async = true;
        s.src = 'https://www.googletagmanager.com/gtag/js?id=G-0GPQC53GE1';
        s.onload = function () {
          gtag('js', new Date());
          gtag('config', 'G-0GPQC53GE1');
        };
        document.head.appendChild(s);
      });
    </script>
    <style>
        /* Crawlable for bots; visually hidden for users (React owns the UI). */
        .baakh-seo-fallback {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
    </style>
</head>

<body class="antialiased font-sans">
    @if(isset($fallback))
        <div id="baakh-seo-fallback" class="baakh-seo-fallback">
            <h1>{{ $fallback['title'] ?? '' }}</h1>
            <p>{{ $fallback['description'] ?? '' }}</p>
            {!! $fallback['html'] ?? '' !!}
        </div>
    @endif
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
