<?php
/**
 * HESUDHAR ONE-TIME REPAIR SCRIPT FOR ASPIRATION HEH (ھ)
 * ======================================================
 * REASON FOR THIS SCRIPT:
 * The previous phonetic cleanse run had an over-aggressive regex that
 * stripped the required aspiration marker (ھ) before word-final weak hehs.
 * This script puts the ھ back in for the corrupted words identified.
 *
 * HOW TO USE:
 * 1. Upload this file to: public/run_once/fix_heh_aspiration.php
 * 2. Visit: https://yourdomain.com/run_once/fix_heh_aspiration.php?token=YOUR_SECRET
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

echo "=== HESUDHAR ASPIRATION HEH REPAIR SCRIPT ===\n\n";

// These are the words reported to have been mistakenly collapsed from [Consonant]ھہ to [Consonant]ہ.
// Example: ڏيھہ (Dhey + Yeh + Aspiration Heh + Weak Heh) became ڏيہ (Dhey + Yeh + Weak Heh).
$corrupted_words = [
    'مونجہ' => 'مونجھہ',
    'ڏيہ' => 'ڏيھہ',
    'ڇہ' => 'ڇھہ',
    'ولہ' => 'ولھہ'
];

$fixedCount = 0;

foreach ($corrupted_words as $corrupted => $repaired) {
    // We update BOTH the 'word' and 'correct' columns because the previous 
    // cleanseString() ran on both columns indiscriminately.

    // First, let's process rows where 'correct' column matches the corrupted word.
    $rowsToFix = DB::table('baakh_hesudhars')
        ->where('correct', $corrupted)
        ->orWhere('word', $corrupted)
        ->get();

    foreach ($rowsToFix as $row) {
        $updatePayload = [];

        if ($row->word === $corrupted) {
            $updatePayload['word'] = $repaired;
        }

        if ($row->correct === $corrupted) {
            $updatePayload['correct'] = $repaired;
        }

        if (!empty($updatePayload)) {
            DB::table('baakh_hesudhars')
                ->where('id', $row->id)
                ->update($updatePayload);
            $fixedCount++;
            echo "Repaired ID {$row->id}: {$corrupted} -> {$repaired}\n";
        }
    }
}

echo "\n  ✓ Repair complete. Fixed: {$fixedCount} database entries.\n";
echo "\n=== DONE! ===\n";
echo "IMPORTANT: Delete this file now from cPanel File Manager for security!\n";
echo "Path: public/run_once/fix_heh_aspiration.php\n";
