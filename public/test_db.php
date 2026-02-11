<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

echo "<h1>Database Connection Test</h1>";
echo "<pre>";
try {
    DB::connection()->getPdo();
    echo "Successfully connected to the database: " . DB::connection()->getDatabaseName();
} catch (\Exception $e) {
    echo "Could not connect to the database. Error:\n\n" . $e->getMessage();
}
echo "</pre>";
