<?php
// nuclear_fix.php - Forcefully remove known troublemakers and clear EVERYTHING
echo "<h1>Nuclear Fix</h1>";
echo "<pre>";

$publicHot = __DIR__ . '/hot';
if (file_exists($publicHot)) {
    echo "Attempting to delete 'hot' file... ";
    if (unlink($publicHot)) {
        echo "✅ DELETED\n";
    } else {
        echo "❌ FAILED. Permissions? " . substr(sprintf('%o', fileperms($publicHot)), -4) . "\n";
    }
} else {
    echo "ℹ️ 'hot' file does not exist.\n";
}

$bootstrapCache = __DIR__ . '/../bootstrap/cache';
echo "Clearing bootstrap/cache... ";
foreach (glob("$bootstrapCache/*.php") as $file) {
    if (basename($file) == 'config.php' || basename($file) == 'routes-v7.php' || basename($file) == 'services.php' || basename($file) == 'packages.php') {
        unlink($file);
        echo "Deleted " . basename($file) . " ";
    }
}
echo "✅ Done\n";

$storageViews = __DIR__ . '/../storage/framework/views';
echo "Clearing storage/framework/views... ";
foreach (glob("$storageViews/*.php") as $file) {
    unlink($file);
}
echo "✅ Done\n";

echo "\n--- SYSTEM CHECK ---\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "User: " . get_current_user() . "\n";
echo "Disk Free Space: " . round(disk_free_space("/") / (1024 * 1024 * 1024), 2) . " GB\n";

echo "\nAll systems cleared. Please try the main site now.";
echo "</pre>";
?>