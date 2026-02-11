<?php
// debug_request_flow.php - Trace the request flow through the kernel
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "DEBUG: Starting Request Flow Trace<br>";

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "DEBUG: Kernel obtained.<br>";

echo "DEBUG: Binding Dummy Request... ";
$app->instance('request', \Illuminate\Http\Request::create('https://beta.baakh.com'));
echo "Done.<br>";

echo "DEBUG: Bootstrapping...<br>";
// Manually run bootstrappers to see which one hangs
$bootstrappers = [
    \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
    \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
    \Illuminate\Foundation\Bootstrap\HandleExceptions::class,
    \Illuminate\Foundation\Bootstrap\RegisterFacades::class,
    \Illuminate\Foundation\Bootstrap\RegisterProviders::class,
    \Illuminate\Foundation\Bootstrap\BootProviders::class,
];

foreach ($bootstrappers as $bootstrapper) {
    echo "   -> " . (new ReflectionClass($bootstrapper))->getShortName() . "... ";
    $app->make($bootstrapper)->bootstrap($app);
    echo "Done.<br>";
}

echo "DEBUG: Bootstrapped successfully.<br>";

echo "DEBUG: Capturing Request...<br>";
$request = Illuminate\Http\Request::capture();
echo "DEBUG: Request captured: " . $request->fullUrl() . "<br>";

echo "DEBUG: Sending Request into Pipeline...<br>";

// Manual pipeline trace to see where it hangs
$middleware = [
    \App\Http\Middleware\TrustProxies::class,
    \Illuminate\Http\Middleware\HandleCors::class,
    \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
    \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
    \App\Http\Middleware\TrimStrings::class,
    \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
];

$pipeline = new \Illuminate\Pipeline\Pipeline($app);

try {
    echo "DEBUG: Running Global Middleware...<br>";
    $response = $pipeline->send($request)
        ->through($middleware)
        ->then(function ($request) {
            echo "DEBUG: Global Middleware Finished. Proceeding to Route...<br>";
            return "PASSED_GLOBAL";
        });
    echo "DEBUG: Global Pipeline Result: $response<br>";

    if ($response === 'PASSED_GLOBAL') {
        echo "DEBUG: Dispatching to Router...<br>";
        $router = $app->make('router');
        $route = $router->getRoutes()->match($request);
        echo "DEBUG: Matched Route: " . ($route ? $route->getName() : 'NONE') . "<br>";

        echo "DEBUG: Running Route Middlewares...<br>";
        $response = $router->dispatch($request);
        echo "DEBUG: Router Dispatch Finished.<br>";
        echo "DEBUG: Status: " . $response->getStatusCode() . "<br>";
        echo "DEBUG: Headers: <pre>" . print_r($response->headers->all(), true) . "</pre><br>";

        $content = $response->getContent();
        echo "DEBUG: Content size: " . strlen($content) . " bytes<br>";

        if (strlen($content) > 0) {
            echo "DEBUG: Content Preview (Top 500 chars): <pre>" . htmlspecialchars(substr($content, 0, 500)) . "</pre><br>";
        } else {
            echo "DEBUG: CONTENT IS EMPTY!<br>";
        }

        echo "DEBUG: Attempting to send response...<br>";
        try {
            // Use a simpler approach than ->send() to avoid potential hangups
            $response->sendHeaders();
            echo $response->getContent();
            echo "<br>DEBUG: Response sent manually.<br>";
        } catch (\Throwable $e) {
            echo "DEBUG: FAILED TO SEND: " . $e->getMessage() . "<br>";
        }
    }

} catch (\Throwable $e) {
    echo "<h1>CRASH IN FLOW</h1>";
    echo "Message: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>