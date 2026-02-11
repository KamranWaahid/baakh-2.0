<?php
// inspect_html.php - Capture the rendered HTML of the home page
echo "<h1>Rendered HTML Inspection</h1>";
echo "<pre>";

// Mock a request to the home page inside the script or just include the index.php?
// Safer to just try to capture the output of the home route
try {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';

    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle(
        $request = Illuminate\Http\Request::capture()
    );

    $html = $response->getContent();

    // Look for script and link tags
    echo "Found <script> tags:\n";
    preg_match_all('/<script.*?>.*?<\/script>/is', $html, $scripts);
    print_r($scripts[0]);

    echo "\nFound <link> tags (CSS):\n";
    preg_match_all('/<link rel="stylesheet".*?>/is', $html, $links);
    print_r($links[0]);

    echo "\nRoot Div:\n";
    preg_match('/<div id="root">.*?<\/div>/is', $html, $root);
    print_r($root[0] ?? 'MISSING');

} catch (\Exception $e) {
    echo "Error capturing HTML: " . $e->getMessage();
}

echo "</pre>";
?>