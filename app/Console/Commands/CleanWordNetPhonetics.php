<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BaakhHesudhar;

class CleanWordNetPhonetics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hesudhar:cleanse';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanse WordNet database according to Phase 1 phonetic rules (Kaf, Yeh, Trigraphs, NFC)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting WordNet Cleansing Phase 1...');
        $fixedCount = 0;

        // Chunking through the database to avoid memory exhaustion
        BaakhHesudhar::chunkById(5000, function ($words) use (&$fixedCount) {
            foreach ($words as $record) {
                // Apply fixes
                $newWord = $this->cleanseString($record->word);
                $newCorrect = $this->cleanseString($record->correct);

                if ($record->word !== $newWord || $record->correct !== $newCorrect) {
                    $record->word = $newWord;
                    $record->correct = $newCorrect;
                    $record->save();
                    $fixedCount++;
                }
            }
        });

        $this->info("WordNet Cleansing Complete! Fixed $fixedCount records.");
    }

    private function cleanseString($text)
    {
        if (empty($text))
            return $text;

        // 1. Kaf Standardization (ك -> ڪ)
        $text = str_replace('ك', 'ڪ', $text);

        // 2. Yeh Standardization (ی -> ي)
        $text = str_replace('ی', 'ي', $text);

        // 3. Atomic Recomposition (Alef + Madda -> آ)
        $text = str_replace('ا' . 'ٓ', 'آ', $text);

        // 4. Collapse Legacy Trigraphs (tail hacks)
        $text = preg_replace('/[هہةەھ]{2}$/u', 'ھ', $text);

        return $text;
    }
}
