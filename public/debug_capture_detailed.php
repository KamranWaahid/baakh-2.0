<?php
// debug_capture_detailed.php - Debug why Request::capture() hangs
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "DEBUG: Detailed Capture Trace<br>";

require __DIR__ . '/../vendor/autoload.php';

echo "DEBUG: Loading App...<br>";
$app = require_once __DIR__ . '/../bootstrap/app.php';
echo "DEBUG: App loaded.<br>";

echo "DEBUG: Making Kernel...<br>";
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
echo "DEBUG: Kernel created.<br>";

echo "DEBUG: Attempting manual Symfony Request creation...<br>";
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
$symfonyRequest = SymfonyRequest::createFromGlobals();
echo "DEBUG: Symfony Request created.<br>";

echo "DEBUG: Attempting Laravel Request::createFromBase...<br>";
$laravelRequest = \Illuminate\Http\Request::createFromBase($symfonyRequest);
echo "DEBUG: Laravel Request created.<br>";

echo "DEBUG: Final check - Request::capture()...<br>";
$request = \Illuminate\Http\Request::capture();
echo "DEBUG: Capture successful: " . $request->fullUrl() . "<br>";
?>