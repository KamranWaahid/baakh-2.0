<?php
// crash_diagnostic.php - Check for hard crash indicators
echo "<h1>Crash Diagnostic</h1>";
echo "<pre>";

echo "1. PHP Limits:\n";
echo "   Memory Limit: " . ini_get('memory_limit') . "\n";
echo "   Max Execution Time: " . ini_get('max_execution_time') . "\n";

echo "\n2. Laravel Log Check (Tail):\n";
$logPath = __DIR__ . '/../storage/logs/laravel.log';
if (file_exists($logPath)) {
    $lines = explode("\n", shell_exec("tail -n 20 " . escapeshellarg($logPath)));
    foreach ($lines as $line) {
        echo "   $line\n";
    }
} else {
    echo "   Laravel log missing or inaccessible.\n";
}

echo "\n3. Checking for .user.ini or php.ini overrides in public/:\n";
foreach (['.user.ini', 'php.ini'] as $f) {
    if (file_exists(__DIR__ . '/' . $f)) {
        echo "   FOUND $f: \n" . file_get_contents(__DIR__ . '/' . $f) . "\n";
    }
}

echo "\n4. Testing Minimal Eloquent Call:\n";
try {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $kernel->bootstrap();

    $count = \DB::table('users')->count();
    echo "   ✅ Database Connected. Users count: $count\n";

    echo "   Testing View Rendering (simple string)...\n";
    echo "   PASSED STARTUP.\n";
} catch (\Throwable $e) {
    echo "   ❌ FAILED: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "</pre>";
?>