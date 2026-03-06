<?php
/**
 * HESUDHAR ONE-TIME SETUP SCRIPT
 * ================================
 * Run this once via browser after deploying to cPanel.
 * Then DELETE this file immediately for security.
 * 
 * Steps:
 * 1. Upload this file to: public/run_once/hesudhar_setup.php
 * 2. Visit: https://yourdomain.com/run_once/hesudhar_setup.php
 * 3. Check output, then delete the file.
 */

// ─── Security gate ────────────────────────────────────────────────────────────
// Set a secret token here. Add ?token=YOUR_SECRET to the URL when visiting.
define('SECRET_TOKEN', 'CHANGE_THIS_SECRET_12345');

if (!isset($_GET['token']) || $_GET['token'] !== SECRET_TOKEN) {
    http_response_code(403);
    die('<b>403 Forbidden.</b> Add ?token=YOUR_SECRET to the URL.');
}
// ─────────────────────────────────────────────────────────────────────────────

// Bootstrap Laravel
require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

use Illuminate\Support\Facades\DB;

header('Content-Type: text/plain; charset=utf-8');

echo "=== HESUDHAR SETUP SCRIPT ===\n\n";

// ── STEP 1: Add is_flagged column ────────────────────────────────────────────
echo "STEP 1: Adding is_flagged column...\n";
try {
    $columns = DB::select("SHOW COLUMNS FROM baakh_hesudhars LIKE 'is_flagged'");
    if (count($columns) === 0) {
        DB::statement("ALTER TABLE baakh_hesudhars ADD COLUMN is_flagged TINYINT(1) NOT NULL DEFAULT 0 AFTER correct");
        echo "  ✓ Column 'is_flagged' added successfully.\n";
    } else {
        echo "  - Column 'is_flagged' already exists. Skipping.\n";
    }
} catch (Exception $e) {
    echo "  ✗ ERROR: " . $e->getMessage() . "\n";
}

// ── STEP 2: WordNet Phonetic Cleanse ─────────────────────────────────────────
echo "\nSTEP 2: Starting WordNet Phonetic Cleanse...\n";
$fixedCount = 0;

function cleanseString($text)
{
    if (empty($text))
        return $text;

    // 1. Kaf Standardization (Arabic ك -> Sindhi ڪ)
    $text = str_replace('ك', 'ڪ', $text);

    // 2. Yeh Standardization (Farsi ی -> Arabic ي)
    $text = str_replace('ی', 'ي', $text);

    // 3. Atomic Recomposition (Alef + Madda -> آ)
    $text = str_replace('ا' . 'ٓ', 'آ', $text);

    // 4. Collapse Legacy Trigraphs (any double terminal heh -> single ھ)
    $text = preg_replace('/[هہةەھ]{2}$/u', 'ھ', $text);

    return $text;
}

try {
    $offset = 0;
    $chunkSize = 5000;

    do {
        $rows = DB::table('baakh_hesudhars')
            ->orderBy('id')
            ->offset($offset)
            ->limit($chunkSize)
            ->get(['id', 'word', 'correct']);

        foreach ($rows as $row) {
            $newWord = cleanseString($row->word);
            $newCorrect = cleanseString($row->correct);

            if ($row->word !== $newWord || $row->correct !== $newCorrect) {
                DB::table('baakh_hesudhars')
                    ->where('id', $row->id)
                    ->update(['word' => $newWord, 'correct' => $newCorrect]);
                $fixedCount++;
            }
        }

        $offset += $chunkSize;
    } while (count($rows) === $chunkSize);

    echo "  ✓ WordNet Cleanse complete. Fixed: $fixedCount records.\n";
} catch (Exception $e) {
    echo "  ✗ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== DONE! ===\n";
echo "IMPORTANT: Delete this file now from cPanel File Manager for security!\n";
echo "Path: public/run_once/hesudhar_setup.php\n";
