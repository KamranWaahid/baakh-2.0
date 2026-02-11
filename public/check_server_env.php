<?php
// check_server_env.php - Sanely check the server's .env file
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Sanitized Server .env Checker</h1>";
echo "<pre>";

$envFile = __DIR__ . '/../.env';

if (file_exists($envFile)) {
    $lines = file($envFile);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0)
            continue;

        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = $parts[0];
            $value = $parts[1];

            // Mask sensitive keys
            if (preg_match('/(KEY|PASSWORD|SECRET|TOKEN|SALT)/i', $key)) {
                $value = '[MASKED] (Length: ' . strlen($value) . ')';
            }

            echo "$key=$value\n";
        }
    }
} else {
    echo "❌ .env file NOT found at $envFile";
}

echo "</pre>";
?>