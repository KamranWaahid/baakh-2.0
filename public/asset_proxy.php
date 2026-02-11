<?php
// asset_proxy.php - Try to serve the asset via PHP to see if it bypasses the HTTP/2 ping error
$file = $_GET['file'] ?? '';
$path = __DIR__ . '/build/assets/' . basename($file);

if (empty($file) || !file_exists($path)) {
    header("HTTP/1.0 404 Not Found");
    echo "Asset not found.";
    exit;
}

$ext = pathinfo($path, PATHINFO_EXTENSION);
$mimes = [
    'js' => 'application/javascript',
    'css' => 'text/css',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'svg' => 'image/svg+xml'
];

header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header('Cache-Control: no-cache');

// Flush buffers to prevent PHP timeout from affecting the delivery
if (ob_get_level())
    ob_end_clean();

readfile($path);
exit;
?>