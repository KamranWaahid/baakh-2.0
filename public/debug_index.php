<?php
// debug_index.php - Laravel entry point with heavy debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "DEBUG: Starting index.php at " . date('Y-m-d H:i:s') . "<br>";

define('LARAVEL_START', microtime(true));

echo "DEBUG: Checking for maintenance mode...<br>";
/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
|
| If the application is in maintenance mode via manage:down command
| we will load this file so that any pre-rendered content can be shown
| instead of starting the framework, which could cause an exception.
|
*/
if (file_exists($maintenance = __DIR__ . '/../storage/framework/maintenance.php')) {
    echo "DEBUG: Loading maintenance mode file...<br>";
    require $maintenance;
}

echo "DEBUG: Loading Autoloader...<br>";
/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| this application. We just need to utilize it! We'll simply require it
| into the script here so we don't need to manually load our classes.
|
*/
require __DIR__ . '/../vendor/autoload.php';
echo "DEBUG: Autoloader loaded.<br>";

echo "DEBUG: Bootstrapping Laravel...<br>";
/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request using
| the application's HTTP kernel. Then, we will send the response back
| to this client's browser, allowing them to enjoy our application.
|
*/

$app = require_once __DIR__ . '/../bootstrap/app.php';
echo "DEBUG: App instance created.<br>";

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
echo "DEBUG: Kernel instance created.<br>";

echo "DEBUG: Handling request...<br>";
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
)->send();

echo "DEBUG: Request handled.<br>";

$kernel->terminate($request, $response);
echo "DEBUG: Done.<br>";
?>