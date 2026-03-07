<?php
/**
 * HESUDHAR FULL RESTORE SCRIPT FROM WORDS.DIC
 * ============================================
 * REASON FOR THIS SCRIPT:
 * The previous phonetic cleanse corrupted the WordNet table by removing valid
 * aspiration markers (ھ). The local `words.dic` file has the correct data.
 * This script TRUNCATES the live `baakh_hesudhars` table and completely
 * RESTORES it using the data from `public/vendor/hesudhar/words.dic`.
 *
 * HOW TO USE:
 * 1. Ensure `public/vendor/hesudhar/words.dic` on your server has the correct data.
 *    (It should be synced from the git tracking if it was correct locally).
 * 2. Upload this file to: public/run_once/restore_dic.php
 * 3. Visit: https://yourdomain.com/run_once/restore_dic.php?token=YOUR_SECRET
 * 4. Check output, then delete the file.
 * 
 * WARNING: This will delete newly added words that were NOT saved to words.dic
 * before the last "Refresh Dictionary" click.
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

echo "=== HESUDHAR DATABASE RESTORE SCRIPT ===\n\n";

$dicPath = public_path('vendor/hesudhar/words.dic');

if (!file_exists($dicPath)) {
    die("✗ ERROR: Dictionary file not found at $dicPath\n");
}

$lines = explode(PHP_EOL, file_get_contents($dicPath));
$totalLines = count($lines);
echo "1. Found dictionary file: {$dicPath} ({$totalLines} lines).\n";

echo "2. Truncating current corrupted baakh_hesudhars table...\n";
DB::table('baakh_hesudhars')->truncate();
echo "   ✓ Table truncated successfully.\n";

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
echo "Path: public/run_once/restore_dic.php\n";
