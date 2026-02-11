<?php
// test_laravel_db.php - Test DB connection using Laravel's DB facade
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Laravel DB Test</h1>";
echo "<pre>";

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

echo "1. Binding Request... ";
$app->instance('request', \Illuminate\Http\Request::create('https://beta.baakh.com'));
echo "✅\n";

echo "2. Bootstrapping Laravel... ";
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();
echo "✅\n";

echo "3. Testing Database Query... ";
try {
    $result = \Illuminate\Support\Facades\DB::select('SELECT 1 as test');
    echo "✅ Success! Result: " . $result[0]->test . "\n";

    echo "4. Checking Tables... ";
    $tables = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
    echo "✅ Found " . count($tables) . " tables.\n";

} catch (\Throwable $e) {
    echo "\n❌ DB CRASH!\n";
    echo "Message: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>