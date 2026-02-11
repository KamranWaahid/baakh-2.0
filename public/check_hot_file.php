<?php
// check_hot_file.php - Check if the Vite hot file exists
$hotFile = __DIR__ . '/hot';
echo "<h1>Vite Hot File Checker</h1>";
if (file_exists($hotFile)) {
    echo "⚠️ HOT file exists at $hotFile. This forces Vite into DEV MODE!\n";
    echo "Content: " . file_get_contents($hotFile) . "\n";
    echo "This is likely why it's hanging (trying to connect to a local dev server).";
} else {
    echo "✅ No hot file found. Vite should be in production mode.";
}
?>