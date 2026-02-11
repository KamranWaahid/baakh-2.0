<?php
// test_db.php - Standalone DB tester (No Laravel boot)
echo "<h1>Direct DB Connection Test</h1>";
echo "<pre>";

// Try to read .env manually to skip Laravel boot
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile);
    $config = [];
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || !trim($line))
            continue;
        list($key, $value) = explode('=', $line, 2) + [NULL, NULL];
        if ($key)
            $config[trim($key)] = trim($value, " \t\n\r\0\x0B\"");
    }

    $host = $config['DB_HOST'] ?? '127.0.0.1';
    $db = $config['DB_DATABASE'] ?? '';
    $user = $config['DB_USERNAME'] ?? '';
    $pass = $config['DB_PASSWORD'] ?? '';
    $port = $config['DB_PORT'] ?? '3306';

    echo "Attempting to connect to: $db on $host:$port as $user...\n";

    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo "✅ SUCCESS: Database connected successfully!";
    } catch (PDOException $e) {
        echo "❌ CONNECTION FAILED:\n" . $e->getMessage();
    }
} else {
    echo "❌ .env file not found at $envFile";
}
echo "</pre>";
?>