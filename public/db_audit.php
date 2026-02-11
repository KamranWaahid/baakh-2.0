<?php
// db_audit.php - Check database freshness
header('Content-Type: text/plain');

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

echo "Database Freshness Audit\n";
echo "========================\n";

echo "Current Connection: " . config('database.default') . "\n";
if (config('database.default') === 'mysql') {
    echo "MySQL Host: " . config('database.connections.mysql.host') . "\n";
    echo "MySQL DB:   " . config('database.connections.mysql.database') . "\n";
} elseif (config('database.default') === 'sqlite') {
    echo "SQLite Path: " . config('database.connections.sqlite.database') . "\n";
}
echo "------------------------\n";

try {
    echo "1. Latest Poetry Entries:\n";
    $latestPoetry = \DB::table('poetry')->orderBy('created_at', 'desc')->limit(5)->get();
    foreach ($latestPoetry as $p) {
        echo "   [ID: {$p->id}] Title: {$p->title} (Created: {$p->created_at})\n";
    }

    echo "\n2. Latest Poets Entries:\n";
    $latestPoets = \DB::table('poets')->orderBy('created_at', 'desc')->limit(5)->get();
    foreach ($latestPoets as $p) {
        echo "   [ID: {$p->id}] Name: {$p->name} (Created: {$p->created_at})\n";
    }

    echo "\n3. Scout Search Test (Recent IDs):\n";
    // Check if the IDs above are in the search index
    if ($latestPoetry->count() > 0) {
        $testId = $latestPoetry->first()->id;
        echo "   Testing search for Poetry ID: $testId\n";
        // Directly check the database-scout sync
        $existsInSearch = \DB::table('poetry')->where('id', $testId)->exists();
        echo "   Exists in DB: " . ($existsInSearch ? "YES" : "NO") . "\n";
    }

} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n--- DB AUDIT COMPLETE ---\n";
