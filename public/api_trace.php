<?php
// api_trace.php - Trace specific API execution time
header('Content-Type: text/plain');
echo "API Trace Results\n";
echo "================\n";

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$endpoints = [
    '/api/poets/suggested',
    '/api/poetry/trending',
    '/api/categories'
];

foreach ($endpoints as $uri) {
    echo "\nTracing: $uri\n";
    $start = microtime(true);

    try {
        $request = \Illuminate\Http\Request::create($uri, 'GET');
        $response = $kernel->handle($request);

        $end = microtime(true);
        $duration = round(($end - $start) * 1000, 2);

        echo "   Status: " . $response->getStatusCode() . "\n";
        echo "   Time:   " . $duration . " ms\n";

        if ($duration > 500) {
            echo "   ⚠️ SLOW response (>500ms)\n";
        }

    } catch (\Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
    }
}

echo "\n--- TRACE COMPLETE ---\n";
