<?php
// clear_cache.php - Standalone script to clear Laravel cache on server without SSH
echo "<h1>Clearing Caches</h1>";
echo "<pre>";
// Using shell_exec to avoid booting the potentially crashed Laravel app
echo "Config: " . shell_exec('php artisan config:clear 2>&1') . "\n";
echo "Route: " . shell_exec('php artisan route:clear 2>&1') . "\n";
echo "View: " . shell_exec('php artisan view:clear 2>&1') . "\n";
echo "Cache: " . shell_exec('php artisan cache:clear 2>&1') . "\n";
echo "</pre>";
echo "Done!";
echo "<hr>";
echo "<p style='color:red;'><strong>Security Warning:</strong> Delete this file immediately after use!</p>";
