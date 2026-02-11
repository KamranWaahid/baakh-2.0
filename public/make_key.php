<?php
// make_key.php - Standalone script to generate a Laravel APP_KEY
$key = base64_encode(random_bytes(32));
echo "<h1>Your New APP_KEY:</h1>";
echo "<pre>base64:$key</pre>";
echo "<p>Copy the line above and paste it into your .env file on the server.</p>";
echo "<hr>";
echo "<p style='color:red;'><strong>Security Warning:</strong> Delete this file immediately after use!</p>";
