<?php
// test_capture_simple.php - Isolate the Request::capture() hang
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Request Capture Isolation Test</h1>";
echo "<pre>";

echo "1. Loading Autoloader... ";
require __DIR__ . '/../vendor/autoload.php';
echo "✅ Done\n";

echo "2. Testing if class exists... ";
if (class_exists('Illuminate\Http\Request')) {
    echo "✅ Yes (Illuminate\Http\Request)\n";
} else {
    echo "❌ NO\n";
    exit;
}

echo "3. Attempting simple Request::create()... ";
try {
    $req1 = \Illuminate\Http\Request::create('https://beta.baakh.com');
    echo "✅ Success\n";
} catch (\Throwable $e) {
    echo "❌ Failed: " . $e->getMessage() . "\n";
}

echo "4. Attempting Request::capture()... ";
try {
    // We add a manual timeout check
    echo "(Starting capture...)\n";
    $request = \Illuminate\Http\Request::capture();
    echo "✅ Success! Captured URL: " . $request->fullUrl() . "\n";
} catch (\Throwable $e) {
    echo "❌ Failed: " . $e->getMessage() . "\n";
}

echo "\n--- Server Environment Snippet ---\n";
echo "PHP_SELF: " . $_SERVER['PHP_SELF'] . "\n";
echo "SERVER_NAME: " . $_SERVER['SERVER_NAME'] . "\n";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";

echo "</pre>";
?>