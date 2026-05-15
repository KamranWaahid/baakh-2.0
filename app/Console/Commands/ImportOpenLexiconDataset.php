<?php

namespace App\Console\Commands;

use App\Models\Lemma;
use App\Models\Sense;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportOpenLexiconDataset extends Command
{
    protected $signature = 'dictionary:import-open-lexicon
        {--file=database/sindhi_open_lexicon_master_223k_final/data/sindhi_open_lexicon_master_223342.jsonl : JSONL source file, relative to base path or absolute}
        {--limit= : Stop after this many source rows, useful for verification}
        {--chunk=1000 : Rows to commit per transaction}
        {--dry-run : Parse and validate without writing to the database}';

    protected $description = 'Import the Sindhi Open Lexicon master dataset into the dictionary tables idempotently';

    public function handle(): int
    {
        $filePath = $this->resolvePath($this->option('file'));
        $limit = $this->option('limit') !== null ? max(0, (int) $this->option('limit')) : null;
        $chunkSize = max(1, min(5000, (int) $this->option('chunk')));
        $dryRun = (bool) $this->option('dry-run');

        if (!is_file($filePath)) {
            $this->error("Lexicon file not found: {$filePath}");

            return self::FAILURE;
        }

        $this->info(($dryRun ? 'Dry-running' : 'Importing') . " open lexicon from: {$filePath}");

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $this->error("Unable to open lexicon file: {$filePath}");

            return self::FAILURE;
        }

        $stats = [
            'processed' => 0,
            'skipped' => 0,
            'invalid_json' => 0,
            'lemmas_created' => 0,
            'lemmas_updated' => 0,
            'senses_created' => 0,
            'senses_updated' => 0,
        ];

        $target = $limit ?: $this->defaultTotalRows();
        if ($target > 0) {
            $this->output->progressStart($target);
        }

        $batch = [];

        while (($line = fgets($handle)) !== false) {
            if ($limit !== null && $stats['processed'] >= $limit) {
                break;
            }

            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $row = json_decode($line, true);
            if (!is_array($row)) {
                $stats['invalid_json']++;
                $stats['processed']++;
                $this->advanceProgress($target);
                continue;
            }

            $batch[] = $row;
            $stats['processed']++;

            if (count($batch) >= $chunkSize) {
                $this->importBatch($batch, $stats, $dryRun);
                $batch = [];
            }

            $this->advanceProgress($target);
        }

        if ($batch !== []) {
            $this->importBatch($batch, $stats, $dryRun);
        }

        fclose($handle);

        if ($target > 0) {
            $this->output->progressFinish();
        }

        $this->newLine();
        $this->info("Processed {$stats['processed']} rows; skipped {$stats['skipped']}; invalid JSON {$stats['invalid_json']}.");

        if (!$dryRun) {
            $this->info("Lemmas created {$stats['lemmas_created']}, updated {$stats['lemmas_updated']}.");
            $this->info("Senses created {$stats['senses_created']}, updated {$stats['senses_updated']}.");
        }

        return self::SUCCESS;
    }

    private function importBatch(array $rows, array &$stats, bool $dryRun): void
    {
        $rows = array_values(array_filter($rows, function (array $row) use (&$stats) {
            $word = $this->nullableString($row['word'] ?? null);
            $definition = $this->nullableString($row['definition'] ?? null);

            if ($word === null || $definition === null) {
                $stats['skipped']++;

                return false;
            }

            return true;
        }));

        if ($dryRun || $rows === []) {
            return;
        }

        DB::transaction(function () use ($rows, &$stats) {
            $words = collect($rows)
                ->map(fn (array $row) => $this->nullableString($row['word'] ?? null))
                ->filter()
                ->unique()
                ->values();

            $lemmasByWord = Lemma::whereIn('lemma', $words)
                ->orderBy('id')
                ->get()
                ->unique('lemma')
                ->keyBy('lemma');

            foreach ($rows as $row) {
                $word = $this->nullableString($row['word'] ?? null);
                $normalizedWord = $this->nullableString($row['normalized_word'] ?? null);
                $partOfSpeech = $this->nullableString($row['part_of_speech'] ?? null);

                $lemma = $lemmasByWord->get($word) ?: new Lemma(['lemma' => $word]);
                $wasNewLemma = !$lemma->exists;

                $lemma->normalized_lemma = $normalizedWord ?: $lemma->normalized_lemma;
                $lemma->pos = $lemma->pos ?: $partOfSpeech;

                if ($wasNewLemma || $lemma->status === 'pending') {
                    $lemma->status = 'approved';
                }

                if ($lemma->isDirty()) {
                    $lemma->save();
                    $stats[$wasNewLemma ? 'lemmas_created' : 'lemmas_updated']++;
                    $lemmasByWord->put($word, $lemma);
                }

                $lexicalId = $this->nullableString($row['lexical_id'] ?? null, 40)
                    ?: 'slx_' . md5(implode('|', [
                        $row['entry_id'] ?? '',
                        $word,
                        $row['source_dictionary'] ?? '',
                        $row['definition'] ?? '',
                    ]));

                $sense = Sense::updateOrCreate(
                    ['lexical_id' => $lexicalId],
                    [
                        'lemma_id' => $lemma->id,
                        'entry_id' => $this->nullableString($row['entry_id'] ?? null, 64),
                        'definition' => (string) ($row['definition'] ?? ''),
                        'part_of_speech' => $partOfSpeech,
                        'word_variant' => $this->nullableString($row['word_with_airab_or_variant'] ?? null),
                        'domain' => $this->nullableString($row['domain'] ?? null),
                        'language_direction' => $this->nullableString($row['language_direction'] ?? null, 100),
                        'source_dictionary' => $this->nullableString($row['source_dictionary'] ?? null, 150),
                        'normalized_definition' => $this->nullableString($row['normalized_definition'] ?? null),
                        'extra' => $this->encodedExtra($row),
                        'status' => 'approved',
                    ]
                );

                $stats[$sense->wasRecentlyCreated ? 'senses_created' : 'senses_updated']++;
            }
        });
    }

    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }

    private function defaultTotalRows(): int
    {
        $statsPath = base_path('database/sindhi_open_lexicon_master_223k_final/metadata/stats.json');
        if (!is_file($statsPath)) {
            return 0;
        }

        $stats = json_decode((string) file_get_contents($statsPath), true);

        return (int) ($stats['total_entries'] ?? 0);
    }

    private function nullableString(mixed $value, ?int $limit = null): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if ($limit !== null) {
            return function_exists('mb_substr')
                ? mb_substr($value, 0, $limit)
                : substr($value, 0, $limit);
        }

        return $value;
    }

    private function encodedExtra(array $row): ?string
    {
        $extra = [
            'extra' => $row['extra'] ?? null,
            'publisher' => $row['publisher'] ?? null,
            'publisher_url' => $row['publisher_url'] ?? null,
            'prepared_by' => $row['prepared_by'] ?? null,
        ];

        $extra = array_filter($extra, fn ($value) => $value !== null && $value !== '');

        return $extra === [] ? null : json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function advanceProgress(int $target): void
    {
        if ($target > 0) {
            $this->output->progressAdvance();
        }
    }
}
