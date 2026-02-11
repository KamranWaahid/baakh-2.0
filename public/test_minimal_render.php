<?php
// test_minimal_render.php - Test if Laravel can render a simple string through the full kernel
echo "<h1>Minimal Render Trace</h1>";
echo "<pre>";

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "1. Bootstrapping... ";
$app->instance('request', \Illuminate\Http\Request::create('https://beta.baakh.com/test_minimal_render'));
$kernel->bootstrap();
echo "✅\n";

echo "2. Checking Session Path: ";
$sessionPath = storage_path('framework/sessions');
echo $sessionPath . " - " . (is_writable($sessionPath) ? "✅ WRITABLE" : "❌ NOT WRITABLE") . "\n";

echo "3. Testing Minimal Route via Router... ";
try {
    $router = $app->make('router');
    $response = $router->get('/test_minimal_render', function () {
        return "HELLO FROM MINIMAL ROUTE";
    })->run(); // Direct run to skip full pipeline for a moment

    echo "✅ Response: " . $response->getContent() . "\n";

    echo "4. Testing Full Pipeline with Minimal Route... ";
    $request = \Illuminate\Http\Request::create('https://beta.baakh.com/test_minimal_render');
    $response = $kernel->handle($request);
    echo "✅ Full Pipeline Status: " . $response->getStatusCode() . "\n";
    echo "   Content: " . $response->getContent() . "\n";

} catch (\Throwable $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "</pre>";
?>