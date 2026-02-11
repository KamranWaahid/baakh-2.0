<?php
// deep_render_trace.php - Step-by-step trace of the rendering process
echo "<h1>Deep Render Trace</h1>";
echo "<pre>";

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "1. Manual Request Capture... ";
$request = \Illuminate\Http\Request::create('https://beta.baakh.com');
$app->instance('request', $request);
echo "✅\n";

echo "2. Bootstrapping Kernel... ";
$kernel->bootstrap();
echo "✅\n";

echo "3. Identifying URL Resolution... ";
$app->resolving('url', function () {
    echo "   URL service is being resolved now.\n";
    // echo "   Trace:\n" . (new Exception())->getTraceAsString() . "\n";
});

echo "4. Checking Session Driver: " . config('session.driver') . "\n";

echo "5. Attempting to render the REAL 'app' view... ";
try {
    $view = $app->make('view');
    echo "   Rendering 'app'...\n";
    // We pass a dummy 'page' variable if it's an Inertia/SPA setup
    $output = $view->make('app', ['page' => ['props' => []]])->render();
    echo "   ✅ Success. Output Size: " . strlen($output) . " bytes\n";
    echo "   Preview: " . htmlspecialchars(substr($output, 0, 200)) . "...\n";
} catch (\Throwable $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "6. Testing Global Middleware Pipeline... ";
try {
    $pipeline = new \Illuminate\Pipeline\Pipeline($app);
    $middleware = [
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    $response = $pipeline->send($request)
        ->through($middleware)
        ->then(function ($request) {
            return "PIPELINE_PASSED";
        });
    echo "✅ Result: $response\n";
} catch (\Throwable $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n";
}

echo "7. Testing Session Start (Often where hangs occur)... ";
try {
    $session = $app->make('session');
    $session->driver()->start();
    echo "✅ Session Started. ID: " . $session->getId() . "\n";
} catch (\Throwable $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n";
}

echo "8. Testing Full Kernel Handle... ";
try {
    // This is the closest to real index.php
    $response = $kernel->handle($request);
    echo "✅ Kernel Handled Status: " . $response->getStatusCode() . "\n";
    $content = $response->getContent();
    echo "   Content size: " . strlen($content) . " bytes\n";
} catch (\Throwable $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "9. Testing Vite resolution in isolation... ";
try {
    $vite = app(\Illuminate\Foundation\Vite::class);
    echo "   Vite helper resolved. Testing @vite output...\n";
    // This is often where it hangs
    $tags = $vite(['resources/css/app.css', 'resources/js/web/main.jsx']);
    echo "   ✅ Generated Tags: " . htmlspecialchars($tags) . "\n";
} catch (\Throwable $e) {
    echo "   ❌ VITE FAILED: " . $e->getMessage() . "\n";
}

echo "\n--- TRACE COMPLETE ---";
echo "</pre>";
?>