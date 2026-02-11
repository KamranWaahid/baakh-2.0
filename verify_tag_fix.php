<?php

use Illuminate\Support\Facades\Http;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function test_endpoint($url)
{
    echo "Testing: $url\n";
    try {
        $response = Http::get($url);
        echo "Status: " . $response->status() . "\n";
        if ($response->successful()) {
            $data = $response->json();
            echo "Success! Found " . (count($data['tags'] ?? []) ?: count($data)) . " items.\n";
            // Check for 'tag' field in poetry show response
            if (isset($data['tags'][0])) {
                echo "Sample Tag: " . json_encode($data['tags'][0]) . "\n";
            }
        } else {
            echo "Error: " . $response->body() . "\n";
        }
    } catch (\Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
    echo "-------------------\n";
}

$baseUrl = 'http://localhost:8000/api'; // Adjust based on local server

// Since I can't easily start a server and hit it, I'll use direct controller calls or logic audit
// Actually, let's just do a logic audit of the most critical parts or run a small PHP script that boots Laravel.

use App\Http\Controllers\PoetryController;
use App\Models\Poetry;
use Illuminate\Http\Request;

$slug = 'ae-daahir'; // A slug that exists locally
$request = Request::create('/api/v1/poetry/' . $slug, 'GET', ['lang' => 'sd']);

echo "Simulating PoetryController@apiShow for slug: $slug\n";
$controller = app(PoetryController::class);
try {
    $response = $controller->apiShow($request, $slug);
    echo "Status code: " . $response->status() . "\n";
    $content = json_decode($response->content(), true);
    if (isset($content['tags'])) {
        echo "Tags found: " . count($content['tags']) . "\n";
        foreach ($content['tags'] as $tag) {
            echo " - " . $tag['tag'] . " (" . $tag['slug'] . ")\n";
        }
    } else {
        echo "No tags found or error in response.\n";
    }
} catch (\Exception $e) {
    echo "Crash: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
