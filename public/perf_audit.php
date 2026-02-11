<?php
// perf_audit.php - Audit performance bottlenecks
echo "<h1>Performance Audit</h1>";
echo "<pre>";

// 1. Environment
echo "1. Environment Settings:\n";
echo "   APP_ENV: " . env('APP_ENV') . "\n";
echo "   APP_DEBUG: " . (env('APP_DEBUG') ? "⚠️ TRUE (Slow)" : "✅ FALSE") . "\n";
echo "   CACHE_DRIVER: " . env('CACHE_DRIVER') . "\n";
echo "   SESSION_DRIVER: " . env('SESSION_DRIVER') . "\n";

// 2. Asset Sizes
echo "\n2. Asset Sizes (Build):\n";
$buildPath = __DIR__ . '/build/assets/';
if (is_dir($buildPath)) {
    $files = scandir($buildPath);
    $total = 0;
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $size = filesize($buildPath . $file);
            $total += $size;
            if ($size > 100000) { // > 100KB
                echo "   ⚠️ Large File: $file (" . round($size / 1024, 2) . " KB)\n";
            }
        }
    }
    echo "   Total Build Size: " . round($total / (1024 * 1024), 2) . " MB\n";
} else {
    echo "   ❌ Build directory not found.\n";
}

// 3. Database Check
echo "\n3. Database Latency Test:\n";
try {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $kernel->bootstrap();

    $start = microtime(true);
    \DB::table('users')->count();
    $end = microtime(true);
    echo "   DB Query Time: " . round(($end - $start) * 1000, 2) . " ms\n";
} catch (\Exception $e) {
    echo "   ❌ DB Error: " . $e->getMessage() . "\n";
}

// 4. OPcache
echo "\n4. PHP OPcache:\n";
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status();
    echo "   OPcache Enabled: " . ($status['opcache_enabled'] ? "✅ YES" : "❌ NO (Very Slow)") . "\n";
} else {
    echo "   ❌ OPcache extension not available.\n";
}

echo "</pre>";
