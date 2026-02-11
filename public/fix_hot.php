<?php
// fix_hot.php - Remove the Vite 'hot' file that blocks production assets
$hotFile = __DIR__ . '/hot';
echo "<h1>Vite 'Hot' File Fix</h1>";
echo "<pre>";

if (file_exists($hotFile)) {
    if (unlink($hotFile)) {
        echo "✅ SUCCESS: The 'hot' file has been deleted.\n";
        echo "Laravel will now use the production manifest.json instead.";
    } else {
        echo "❌ ERROR: Could not delete the 'hot' file. Check file permissions.";
    }
} else {
    echo "ℹ️ INFO: No 'hot' file found. You are already in production mode.";
}

echo "\n\n--- Clearing Caches ---\n";
// Always good to clear config after deleting 'hot'
shell_exec('php artisan config:clear');
echo "Done.";
echo "</pre>";
?>