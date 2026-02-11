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

echo "4. Attempting to render a simple Blade view... ";
try {
    $view = $app->make('view');
    // Create a temporary view file to avoid manifest issues for a second
    file_put_contents(resource_path('views/debug_test.blade.php'), "HELLO FROM DEBUG VIEW: {{ app()->getLocale() }}");
    echo "   Rendering debug_test...\n";
    echo "   Output: [" . $view->make('debug_test')->render() . "]\n";
    echo "✅ Success.\n";
} catch (\Throwable $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n";
}

echo "5. Testing Vite resolution in isolation... ";
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