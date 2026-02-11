<?php
// debug_kernel.php - Debug middleare by middleware
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Laravel Middleware Debugger</h1>";
echo "<pre>";

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::capture();

echo "DEBUG: Starting manual PIPELINE...\n";

$pipeline = new \Illuminate\Pipeline\Pipeline($app);

// Global middleware
$middleware = [
    \App\Http\Middleware\TrustProxies::class,
    \Illuminate\Http\Middleware\HandleCors::class,
    \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
    \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
    \App\Http\Middleware\TrimStrings::class,
    \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
];

try {
    $response = $pipeline->send($request)
        ->through($middleware)
        ->then(function ($request) {
            echo "✅ Global Middleware PASSED\n";
            return "SUCCESS";
        });

    echo "Result: $response\n";

    echo "\nDEBUG: Testing 'web' group...\n";
    $webMiddleware = [
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\VerifyCsrfToken::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ];

    $response = $pipeline->send($request)
        ->through($webMiddleware)
        ->then(function ($request) {
            echo "✅ Web Middleware Group PASSED\n";
            return "SUCCESS";
        });

    echo "Result: $response\n";

} catch (\Throwable $e) {
    echo "\n❌ CRASH in Middleware!\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "</pre>";
?>