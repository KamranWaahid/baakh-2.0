<?php
// debug_boot_v2.php - Test Laravel bootstrap with error catching
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Laravel Deep Bootstrap Test</h1>";
echo "<pre>";

try {
    echo "1. Requiring Autoloader... ";
    require __DIR__ . '/../vendor/autoload.php';
    echo "✅ Done\n";

    echo "2. Requiring App... ";
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    echo "✅ Done (App Class: " . get_class($app) . ")\n";

    echo "3. Making Kernel... ";
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    echo "✅ Done (Kernel Class: " . get_class($kernel) . ")\n";

    echo "4. Bootstrapping...\n";
    // We'll manually call the bootstrap classes to see which one fails
    $bootstrappers = [
        \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
        \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
        \Illuminate\Foundation\Bootstrap\HandleExceptions::class,
        \Illuminate\Foundation\Bootstrap\RegisterFacades::class,
        \Illuminate\Foundation\Bootstrap\RegisterProviders::class,
        \Illuminate\Foundation\Bootstrap\BootProviders::class,
    ];

    foreach ($bootstrappers as $bootstrapper) {
        $className = (new ReflectionClass($bootstrapper))->getShortName();
        echo "   -> Running $className... ";
        $app->make($bootstrapper)->bootstrap($app);
        echo "✅\n";
    }

    echo "\n🎉 SUCCESS! Laravel fully bootstrapped.\n";
    echo "Current Locale: " . app()->getLocale() . "\n";

} catch (\Throwable $e) {
    echo "\n\n❌ CRASH DETECTED!\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
?>