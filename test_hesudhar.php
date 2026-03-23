<?php

use App\Services\Hesudhar\HesudharPipeline;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Sample WordNet entries (from Python reference)
$sampleWordnet = [
    'تنھن' => 'تنهن',
    'بھارون' => 'بهارون',
    'تنھنجا' => 'تنهنجا',
    'منھنجي' => 'منهنجي',
    'پھاڙن' => 'پهاڙن',
    'ٺھي' => 'ٺهي',
    'بلڪه' => 'بلڪہ',
];

$pipeline = new HesudharPipeline(function ($word) use ($sampleWordnet) {
    return $sampleWordnet[$word] ?? null;
});

// Test cases from the Python reference
$testCases = [
    ["ملفوظي test", "هڪ ڪلاڪ جون اهم واقعا"],
    ["وسرڳي test", "سگھن ٿا گھٽ ۾ گھٽ"],
    ["مختفي test", "الجزيرہ باضابطہ تہ بہ"],
    ["Trigraph hack", "گروھہ تباھہ"],
    ["WordNet test", "تنھن منھنجي بھارون"],
    ["Jokhio rule", "ڳهي ڏهه"],
    ["Full sentence", "گذريل ھڪ ڪلاڪ جون اھم اڳڀريون"],
    ["Alawah fix", "علاوہ علاوه علاوه"], // Should normalize to علاوہ with hook
];

echo "============================================================\n";
echo "HESUDHAR PHP PORT SELF-TEST RESULTS\n";
echo "============================================================\n";

foreach ($testCases as $case) {
    [$label, $text] = $case;
    $result = $pipeline->process($text);
    echo "\n[$label]\n";
    echo "  INPUT:  " . $text . "\n";
    echo "  OUTPUT: " . $result->correctedText . "\n";
    if (!empty($result->changesLog)) {
        foreach ($result->changesLog as $ch) {
            echo "  CHANGE (" . $ch['source'] . "): '" . $ch['original'] . "' → '" . $ch['corrected'] . "'\n";
        }
    }
}

echo "\n============================================================\n";
