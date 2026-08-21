<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, follow">
    <title>404 Not Found | Baakh</title>
    <link rel="describedby" href="{{ url('/llms.txt') }}">
</head>
<body>
    <h1>404 Not Found</h1>
    <p>This path is not a page on Baakh.</p>
    @if(!empty($path))
        <p>Requested: <code>/{{ ltrim($path, '/') }}</code></p>
    @endif
    <p>Agents: use the indexes below instead of probing arbitrary paths.</p>
    <ul>
        <li><a href="{{ url('/llms.txt') }}">llms.txt</a> — agent-readable site index</li>
        <li><a href="{{ url('/sitemap.xml') }}">sitemap.xml</a> — all public URLs</li>
        <li><a href="{{ url('/en/contact') }}">Contact</a></li>
        <li><a href="{{ url('/sd') }}">Sindhi home</a></li>
        <li><a href="{{ url('/en') }}">English home</a></li>
    </ul>
</body>
</html>
