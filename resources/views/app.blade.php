@php
    $seoData = $seoData ?? [];
    $lang = $seoData['lang'] ?? app()->getLocale();
    $lang = $lang === 'en' ? 'en' : 'sd';
    $title = $seoData['title'] ?? ($lang === 'sd' ? 'باک - سنڌي شاعريءَ جو آرڪائيو' : 'Baakh - Archive of Sindhi Poetry');
    $description = $seoData['description'] ?? '';
    $h1 = $seoData['h1'] ?? $title;
    $rawText = $seoData['raw_text'] ?? ($fallback['html'] ?? '');
    $canonical = $seoData['canonical'] ?? url()->current();
    $enUrl = $seoData['en_url'] ?? url('/en');
    $sdUrl = $seoData['sd_url'] ?? url('/sd');
    $image = $seoData['image'] ?? asset('assets/og/baakh-og-v2-1200x630.png');
    $ogDescription = $seoData['og_description'] ?? $description;
    $ogImageAlt = $seoData['og_image_alt'] ?? $title;
    $ogType = $seoData['og_type'] ?? 'website';
    $siteName = $seoData['site_name'] ?? ($lang === 'sd' ? 'باک' : 'Baakh');
    $robots = $seoData['robots'] ?? 'index, follow';
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}" dir="{{ $lang === 'sd' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="{{ $robots }}">

    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $canonical }}">
    <link rel="describedby" href="{{ url('/llms.txt') }}">
    <link rel="alternate" type="text/markdown" href="{{ $seoData['markdown_url'] ?? url('/index.md') }}">

    {{-- Locale-specific alternates — each hreflang must point at a real language URL --}}
    <link rel="alternate" hreflang="en" href="{{ $enUrl }}">
    <link rel="alternate" hreflang="sd" href="{{ $sdUrl }}">
    <link rel="alternate" hreflang="x-default" href="{{ $sdUrl }}">

    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $image }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $ogImageAlt }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:locale" content="{{ $seoData['og_locale'] ?? ($lang === 'sd' ? 'sd_PK' : 'en_US') }}">
    <meta property="og:locale:alternate" content="{{ $seoData['og_locale_alternate'] ?? ($lang === 'sd' ? 'en_US' : 'sd_PK') }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="{{ $seoData['twitter_site'] ?? '@BaakhConnect' }}">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $ogDescription }}">
    <meta name="twitter:image" content="{{ $image }}">
    <meta name="twitter:url" content="{{ $canonical }}">

    @foreach (($seoData['schema'] ?? []) as $graph)
        <script type="application/ld+json">{!! json_encode($graph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
    @endforeach

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
    {{-- Outside #root so React hydration does not wipe crawlable HTML --}}
    <div id="baakh-seo-fallback" class="baakh-seo-fallback">
        <h1>{{ $h1 }}</h1>
        <p>{{ $description }}</p>
        {!! $rawText !!}
    </div>
    <noscript>
        <h1>{{ $h1 }}</h1>
        <p>{{ $description }}</p>
        @if($rawText !== '')
            <div class="poetry-fallback-render">{!! $rawText !!}</div>
        @endif
    </noscript>
    <div id="root"></div>
</body>

</html>
