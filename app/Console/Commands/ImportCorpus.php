<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CorpusSentence;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportCorpus extends Command
{
    protected $signature = 'corpus:import {--limit=100 : Limit the number of files to process}';
    protected $description = 'Import tokenized Sindhi corpus and decode tokens';

    private $vocab = [];
    private $directory = '/Users/kamranwahid/Baakh - GIT/Baakh/118-Million-Sindhi-tokens/117.9 M Tokens';

    public function handle()
    {
        $this->info('Loading vocabulary...');
        $this->loadVocabulary();

        $files = File::files($this->directory);
        $jsonFiles = array_filter($files, fn($file) => $file->getExtension() === 'json');

        $limit = (int) $this->option('limit');
        $processed = 0;

        $this->info('Found ' . count($jsonFiles) . ' files. Processing first ' . $limit . '...');

        foreach ($jsonFiles as $file) {
            if ($processed >= $limit)
                break;

            $this->info("Processing: " . $file->getFilename());
            $content = json_decode(File::get($file->getPathname()), true);

            if (!$content) {
                $this->error("Failed to decode JSON: " . $file->getFilename());
                continue;
            }

            $batch = [];
            foreach ($content as $entry) {
                $tokens = $entry['encoded_tokens'];
                $decoded = $this->decodeTokens($tokens);

                $batch[] = [
                    'sentence' => $decoded,
                    'source' => $entry['source'] ?? $this->deriveSource($file->getFilename()),
                    'category' => $entry['category'] ?? null,
                    'tokens' => json_encode($tokens),
                    'token_count' => count($tokens),
                    'external_id' => $entry['id'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($batch) >= 500) {
                    DB::table('corpus_sentences')->insert($batch);
                    $batch = [];
                }
            }

            if (count($batch) > 0) {
                DB::table('corpus_sentences')->insert($batch);
            }

            $processed++;
        }

        $this->info('Import completed successfully!');
    }

    private function loadVocabulary()
    {
        $vocabPath = $this->directory . '/Model Files/QA_Pair_sindhi_tokenizer.vocab';
        $lines = explode("\n", File::get($vocabPath));

        foreach ($lines as $line) {
            if (empty(trim($line)))
                continue;

            // Format: word\t-index
            $parts = explode("\t", $line);
            if (count($parts) >= 2) {
                $word = $parts[0];
                $index = abs((int) $parts[1]);
                $this->vocab[$index] = $word;
            }
        }
    }

    private function decodeTokens($tokens)
    {
        $decoded = '';
        foreach ($tokens as $tokenId) {
            if (isset($this->vocab[$tokenId])) {
                $piece = $this->vocab[$tokenId];
                // Handle subword prefix (U+2581)
                if (mb_substr($piece, 0, 1) === '▁') {
                    $decoded .= ' ' . mb_substr($piece, 1);
                } else {
                    $decoded .= $piece;
                }
            }
        }
        return trim($decoded);
    }

    private function deriveSource($filename)
    {
        if (str_contains($filename, 'salamat'))
            return 'Sindh Salamat';
        if (str_contains($filename, 'wikipedia'))
            return 'Sindhi Wikipedia';
        if (str_contains($filename, 'altaf'))
            return 'Altaf Shaikh';
        return 'General Corpus';
    }
}
