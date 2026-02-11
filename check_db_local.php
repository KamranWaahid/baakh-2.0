<?php
// check_db_local.php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

echo "Active DB: " . config('database.default') . "\n";
if (config('database.default') === 'mysql') {
    echo "MySQL DB: " . config('database.connections.mysql.database') . "\n";
} else {
    echo "SQLite Path: " . config('database.connections.sqlite.database') . "\n";
}
