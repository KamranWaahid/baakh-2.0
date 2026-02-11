<?php
// debug_boot.php - Test if Laravel can boot a simple command
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

echo "<h1>Laravel Boot Test</h1>";
echo "Successfully bootstrapped Laravel!<br>";
echo "Current App Locale: " . app()->getLocale() . "<br>";
echo "Config APP_ENV: " . config('app.env') . "<br>";
?>