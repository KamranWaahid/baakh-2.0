<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lemma;
use App\Models\Morphology;
use App\Models\LemmaRelation;
use App\Models\CorpusStat;
use Illuminate\Support\Facades\DB;

class ImportWordnetDataset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-wordnet-dataset {--file=storage/app/sindhi_wordnet/Wordnet-Corpus 10-30-25.csv : The path to the CSV file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports the Sindhi WordNet dataset into Baakh Dictionary and Corpus';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = base_path($this->option('file'));

        if (!file_exists($filePath)) {
            $this->error("File not found at: {$filePath}");
            return;
        }

        $this->info("Opening dataset: {$filePath}");

        $file = fopen($filePath, 'r');
        $header = fgetcsv($file);

        $rowCount = 0;

        $this->output->progressStart(118100); // Approximation purely for UI

        DB::beginTransaction();

        try {
            while (($data = fgetcsv($file)) !== false) {
                // Expected format: word_id, word, category, gender, invariants, tags, tenses, hyp, antonyms, synonyms
                if (count($data) < 10)
                    continue;

                $word = trim($data[1]);
                if (empty($word) || $word === '-') {
                    continue;
                }

                $category = trim($data[2]) === '-' ? null : trim($data[2]);
                $gender = trim($data[3]) === '-' ? null : trim($data[3]);
                $invariants = trim($data[4]) === '-' ? null : trim($data[4]);
                $tags = trim($data[5]) === '-' ? null : trim($data[5]);
                $tenses = trim($data[6]) === '-' ? null : trim($data[6]);

                $hyp = trim($data[7]) === '-' ? [] : array_filter(array_map('trim', explode(',', $data[7])));
                $antonyms = trim($data[8]) === '-' ? [] : array_filter(array_map('trim', explode(',', $data[8])));
                $synonyms = trim($data[9]) === '-' ? [] : array_filter(array_map('trim', explode(',', $data[9])));

                // Determine POS (prefer category, fallback to tags)
                $pos = $category ?: $tags;

                // 1. Dictionary Integration
                $lemma = Lemma::updateOrCreate(
                    ['lemma' => $word],
                    [
                        'pos' => $pos,
                        'status' => 'approved' // Auto-approve since it's an official dataset
                    ]
                );

                Morphology::updateOrCreate(
                    ['lemma_id' => $lemma->id],
                    [
                        'gender' => $gender,
                        'number' => $invariants, // Mapping invariants (singular/plural) to number
                        'tense' => $tenses,
                    ]
                );

                // Re-sync relations
                LemmaRelation::where('lemma_id', $lemma->id)->delete();
                $relationsToInsert = [];

                foreach ($synonyms as $syn) {
                    $relationsToInsert[] = [
                        'lemma_id' => $lemma->id,
                        'relation_type' => 'synonym',
                        'related_word' => $syn,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                foreach ($antonyms as $ant) {
                    $relationsToInsert[] = [
                        'lemma_id' => $lemma->id,
                        'relation_type' => 'antonym',
                        'related_word' => $ant,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                foreach ($hyp as $h) {
                    $relationsToInsert[] = [
                        'lemma_id' => $lemma->id,
                        'relation_type' => 'hypernym',
                        'related_word' => $h,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (!empty($relationsToInsert)) {
                    LemmaRelation::insert($relationsToInsert);
                }

                // 2. Corpus Integration
                CorpusStat::firstOrCreate(
                    ['word' => $word],
                    ['frequency' => 0]
                );

                $rowCount++;
                $this->output->progressAdvance();

                if ($rowCount % 1000 === 0) {
                    DB::commit();
                    DB::beginTransaction();
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error during import: " . $e->getMessage());
        }

        fclose($file);
        $this->output->progressFinish();
        $this->info("Successfully imported {$rowCount} words into Dictionary, Corpus, and Analytics models.");
    }
}
