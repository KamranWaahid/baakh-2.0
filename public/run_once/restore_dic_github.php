<?php
/**
 * HESUDHAR FULL RESTORE SCRIPT FROM GITHUB (PRISTINE BACKUP)
 * ==========================================================
 * REASON FOR THIS SCRIPT:
 * The live server's `words.dic` was accidentally overwritten with corrupted data
 * when "Refresh Dictionary" was clicked. Since Git doesn't overwrite modified
 * files unless forced, the server was stuck with the corrupted version.
 * 
 * This script bypasses the local file and downloads the pristine (uncorrupted) 
 * `words.dic` directly from the main branch of your GitHub repository, then 
 * restores the database from it.
 *
 * HOW TO USE:
 * 1. Upload this file to: public/run_once/restore_dic_github.php
 * 2. Visit: https://yourdomain.com/run_once/restore_dic_github.php?token=CHANGE_THIS_SECRET_12345
 * 3. Check output, then delete the file.
 */

// ─── Security gate ────────────────────────────────────────────────────────────
define('SECRET_TOKEN', 'CHANGE_THIS_SECRET_12345');

if (!isset($_GET['token']) || $_GET['token'] !== SECRET_TOKEN) {
    http_response_code(403);
    die('<b>403 Forbidden.</b> Add ?token=YOUR_SECRET to the URL.');
}
// ─────────────────────────────────────────────────────────────────────────────

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');
set_time_limit(300); // 5 minutes just in case

echo "=== HESUDHAR DATABASE RESTORE SCRIPT (FROM GITHUB) ===\n\n";

$githubRawUrl = 'https://raw.githubusercontent.com/KamranWaahid/baakh-2.0/main/public/vendor/hesudhar/words.dic';

echo "1. Fetching pristine dictionary directly from GitHub...\n";
echo "   URL: {$githubRawUrl}\n";

$dicContent = @file_get_contents($githubRawUrl);

if ($dicContent === false || empty($dicContent)) {
    die("✗ ERROR: Failed to download dictionary from GitHub. Check server internet connection or repository visibility.\n");
}

// Save the pristine copy over the corrupted local file to fix the server state
$localDicPath = public_path('vendor/hesudhar/words.dic');
if (!file_exists(dirname($localDicPath))) {
    mkdir(dirname($localDicPath), 0755, true);
}
file_put_contents($localDicPath, $dicContent);
echo "   ✓ Downloaded and replaced corrupted local words.dic file.\n";

$lines = explode("\n", $dicContent);
$totalLines = count($lines);
echo "   ✓ Loaded {$totalLines} lines in memory.\n\n";

echo "2. Truncating current corrupted baakh_hesudhars table...\n";
DB::table('baakh_hesudhars')->truncate();
echo "   ✓ Table truncated successfully.\n\n";

echo "3. Importing records from dictionary...\n";

$importedCount = 0;
$skippedCount = 0;
$batch = [];

foreach ($lines as $line) {
    if (empty(trim($line))) {
        continue;
    }

    $parts = explode(':', trim($line));
    if (count($parts) === 2) {
        $word = $parts[0];
        $correct = $parts[1];

        $batch[] = [
            'word' => $word,
            'correct' => $correct,
            'is_flagged' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $importedCount++;

        // Insert in batches of 1000 to avoid memory issues
        if (count($batch) >= 1000) {
            DB::table('baakh_hesudhars')->insert($batch);
            $batch = [];
        }
    } else {
        $skippedCount++;
    }
}

// Insert remaining batch
if (!empty($batch)) {
    DB::table('baakh_hesudhars')->insert($batch);
}

echo "   ✓ Import complete.\n";
echo "============================================\n";
echo "Total Imported: {$importedCount} records\n";
echo "Skipped Lines: {$skippedCount}\n";
echo "\n=== DONE! ===\n";
echo "IMPORTANT: Delete this file now from cPanel File Manager for security!\n";
echo "Path: public/run_once/restore_dic_github.php\n";
