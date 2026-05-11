<?php

namespace App\Console\Commands;

use App\Models\Lemma;
use App\Models\Sense;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportOpenLexiconDataset extends Command
{
    private const DEFAULT_SOURCE_FILE = 'database/sindhi_open_lexicon_master_223k_final/data/sindhi_open_lexicon_master_223342.jsonl';
    private const DEFAULT_STRING_COLUMN_LIMIT = 191;
    private const INDEXED_TEXT_LIMIT = self::DEFAULT_STRING_COLUMN_LIMIT;
    private const LEMMA_COLUMN_LIMIT = self::DEFAULT_STRING_COLUMN_LIMIT;

    protected $signature = 'dictionary:import-open-lexicon
        {--path= : JSONL or JSONL.GZ source file, relative to base path or absolute. Overrides --file}
        {--file=database/sindhi_open_lexicon_master_223k_final/data/sindhi_open_lexicon_master_223342.jsonl : JSONL source file, relative to base path or absolute}
        {--limit= : Stop after this many source rows, useful for verification}
        {--chunk=1000 : Rows to commit per transaction}
        {--dry-run : Parse and validate without writing to the database}';

    protected $description = 'Import the Sindhi Open Lexicon master dataset into the dictionary tables idempotently';

    public function handle(): int
    {
        $requestedPath = $this->option('path') ?: $this->option('file') ?: self::DEFAULT_SOURCE_FILE;
        $filePath = $this->resolvePath($requestedPath);
        $limit = $this->option('limit') !== null ? max(0, (int) $this->option('limit')) : null;
        $chunkSize = max(1, min(5000, (int) $this->option('chunk')));
        $dryRun = (bool) $this->option('dry-run');

        if (!is_file($filePath)) {
            $this->error("Lexicon file not found: {$filePath}");
            $this->line('Expected the bundled compressed file at: ' . base_path(self::DEFAULT_SOURCE_FILE . '.gz'));
            $this->line('Or pass an explicit source with: php artisan dictionary:import-open-lexicon --path=/absolute/path/to/sindhi_open_lexicon_master_223342.jsonl');
            $this->line('The --path option also accepts .jsonl.gz files.');

            return self::FAILURE;
        }

        $this->info(($dryRun ? 'Dry-running' : 'Importing') . " open lexicon from: {$filePath}");

        $handle = $this->openSourceFile($filePath);
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

        while (($line = $this->readSourceLine($handle, $filePath)) !== false) {
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

        $this->closeSourceFile($handle, $filePath);

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
            $preparedRows = collect($rows)
                ->map(fn (array $row) => $this->prepareRow($row))
                ->filter()
                ->values();

            $lexicalIds = $preparedRows->pluck('lexical_id')->unique()->values();
            $existingSensesByLexicalId = Sense::with('lemma')
                ->whereIn('lexical_id', $lexicalIds)
                ->get()
                ->keyBy('lexical_id');

            $words = $preparedRows
                ->pluck('lemma')
                ->merge($existingSensesByLexicalId->pluck('lemma.lemma'))
                ->filter()
                ->unique()
                ->values();

            $lemmasByWord = Lemma::whereIn('lemma', $words)
                ->orderBy('id')
                ->get()
                ->unique('lemma')
                ->keyBy('lemma');

            foreach ($preparedRows as $row) {
                $existingSense = $existingSensesByLexicalId->get($row['lexical_id']);
                $lemma = $existingSense?->lemma
                    ?: $lemmasByWord->get($row['lemma'])
                    ?: new Lemma(['lemma' => $row['lemma']]);
                $wasNewLemma = !$lemma->exists;

                $lemma->normalized_lemma = $row['normalized_lemma'] ?: $lemma->normalized_lemma;
                $lemma->pos = $lemma->pos ?: $row['part_of_speech'];

                if ($wasNewLemma || $lemma->status === 'pending') {
                    $lemma->status = 'approved';
                }

                if ($lemma->isDirty()) {
                    $lemma->save();
                    $stats[$wasNewLemma ? 'lemmas_created' : 'lemmas_updated']++;
                    $lemmasByWord->put($lemma->lemma, $lemma);
                }

                $sense = Sense::updateOrCreate(
                    ['lexical_id' => $row['lexical_id']],
                    [
                        'lemma_id' => $lemma->id,
                        'entry_id' => $row['entry_id'],
                        'definition' => $row['definition'],
                        'part_of_speech' => $row['part_of_speech'],
                        'word_variant' => $row['word_variant'],
                        'domain' => $row['domain'],
                        'language_direction' => $row['language_direction'],
                        'source_dictionary' => $row['source_dictionary'],
                        'normalized_definition' => $row['normalized_definition'],
                        'extra' => $row['extra'],
                        'status' => 'approved',
                    ]
                );

                $stats[$sense->wasRecentlyCreated ? 'senses_created' : 'senses_updated']++;
            }
        });
    }

    private function prepareRow(array $row): ?array
    {
        $sourceWord = $this->nullableString($row['word'] ?? null);
        $definition = $this->nullableString($row['definition'] ?? null);

        if ($sourceWord === null || $definition === null) {
            return null;
        }

        $sourceWordHasVariants = $this->hasLemmaVariants($sourceWord);
        $lemma = $this->canonicalLemma($sourceWord);
        if ($lemma === null) {
            return null;
        }

        $sourceNormalizedWord = $this->nullableString($row['normalized_word'] ?? null);
        $normalizedLemma = null;
        if ($sourceNormalizedWord !== null) {
            $normalizedLemma = $sourceWordHasVariants
                ? $lemma
                : $this->canonicalLemma($sourceNormalizedWord);
        }
        $wordVariant = $this->nullableString($row['word_with_airab_or_variant'] ?? null)
            ?: ($lemma !== $sourceWord ? $sourceWord : null);

        return [
            'lexical_id' => $this->lexicalId($row, $sourceWord),
            'entry_id' => $this->nullableString($row['entry_id'] ?? null, 64),
            'lemma' => $lemma,
            'normalized_lemma' => $normalizedLemma,
            'definition' => (string) ($row['definition'] ?? ''),
            'part_of_speech' => $this->nullableString($row['part_of_speech'] ?? null, self::INDEXED_TEXT_LIMIT),
            'word_variant' => $wordVariant,
            'domain' => $this->nullableString($row['domain'] ?? null, self::INDEXED_TEXT_LIMIT),
            'language_direction' => $this->nullableString($row['language_direction'] ?? null, 100),
            'source_dictionary' => $this->nullableString($row['source_dictionary'] ?? null, 150),
            'normalized_definition' => $this->nullableString($row['normalized_definition'] ?? null),
            'extra' => $this->encodedExtra($row, $lemma, $normalizedLemma),
        ];
    }

    private function lexicalId(array $row, string $sourceWord): string
    {
        return $this->nullableString($row['lexical_id'] ?? null, 40)
            ?: 'slx_' . md5(implode('|', [
                $row['entry_id'] ?? '',
                $sourceWord,
                $row['source_dictionary'] ?? '',
                $row['definition'] ?? '',
            ]));
    }

    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $this->resolveCompressedFallback($path);
        }

        return $this->resolveCompressedFallback(base_path($path));
    }

    private function resolveCompressedFallback(string $path): string
    {
        if (is_file($path)) {
            return $path;
        }

        if (!str_ends_with($path, '.gz') && is_file($path . '.gz')) {
            return $path . '.gz';
        }

        return $path;
    }

    private function openSourceFile(string $path)
    {
        return str_ends_with($path, '.gz') ? gzopen($path, 'rb') : fopen($path, 'r');
    }

    private function readSourceLine($handle, string $path): string|false
    {
        return str_ends_with($path, '.gz') ? gzgets($handle) : fgets($handle);
    }

    private function closeSourceFile($handle, string $path): void
    {
        if (str_ends_with($path, '.gz')) {
            gzclose($handle);

            return;
        }

        fclose($handle);
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

    private function canonicalLemma(string $value): ?string
    {
        foreach ($this->lemmaCandidates($value) as $candidate) {
            if ($this->stringLength($candidate) <= self::LEMMA_COLUMN_LIMIT) {
                return $candidate;
            }

            return $this->truncateString($candidate, self::LEMMA_COLUMN_LIMIT);
        }

        return null;
    }

    private function lemmaCandidates(string $value): array
    {
        $value = $this->nullableString($value);
        if ($value === null) {
            return [];
        }

        $parts = preg_split('/(?:\s*[,،;؛\/|]+\s*|\s+يا\s+)/u', $value) ?: [$value];
        $candidates = [];

        foreach ($parts as $part) {
            $candidate = $this->nullableString($part);

            if ($candidate !== null) {
                $candidates[] = $candidate;
            }
        }

        return $candidates !== [] ? $candidates : [$value];
    }

    private function hasLemmaVariants(string $value): bool
    {
        return count($this->lemmaCandidates($value)) > 1;
    }

    private function encodedExtra(array $row, string $lemma, ?string $normalizedLemma): ?string
    {
        $extra = [
            'extra' => $row['extra'] ?? null,
            'publisher' => $row['publisher'] ?? null,
            'publisher_url' => $row['publisher_url'] ?? null,
            'prepared_by' => $row['prepared_by'] ?? null,
        ];

        $sourceWord = $this->nullableString($row['word'] ?? null);
        if ($sourceWord !== null && $sourceWord !== $lemma) {
            $extra['original_word'] = $sourceWord;
        }

        $sourceNormalizedWord = $this->nullableString($row['normalized_word'] ?? null);
        if ($sourceNormalizedWord !== null && $sourceNormalizedWord !== $normalizedLemma) {
            $extra['original_normalized_word'] = $sourceNormalizedWord;
        }

        $extra = array_filter($extra, fn ($value) => $value !== null && $value !== '');

        return $extra === [] ? null : json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function stringLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }

    private function truncateString(string $value, int $limit): string
    {
        return function_exists('mb_substr')
            ? mb_substr($value, 0, $limit)
            : substr($value, 0, $limit);
    }

    private function advanceProgress(int $target): void
    {
        if ($target > 0) {
            $this->output->progressAdvance();
        }
    }
}
