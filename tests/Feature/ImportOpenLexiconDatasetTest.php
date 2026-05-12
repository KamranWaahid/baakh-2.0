<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ImportOpenLexiconDatasetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createDictionarySchema();
    }

    public function test_open_lexicon_import_is_idempotent(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'open_lexicon_');
        $longVariantList = implode(', ', array_merge(
            ['canonical-headword'],
            array_map(fn (int $index) => "variant-{$index}", range(1, 60))
        ));
        $failingSindhiVariantList = 'ايڏنئين، ايڏئن، ايڏئون، ايڏئون، ايڏنهن، ايڏهنئن، ايڏهين، ايڏهن، ايڏهون، ايڏهون، ايڏانئين، ايڏانئون، ايڏانئون، ايڏانهنئن، ايڏانهين، ايڏانهون، ايڏانهون، ايڏي، ايڏهئن، ايڏهان، ايڏهين، ايڏهون يا ايڏهون';

        $this->assertGreaterThan(255, strlen($longVariantList));
        $this->assertGreaterThan(191, mb_strlen($failingSindhiVariantList));

        file_put_contents($file, implode(PHP_EOL, [
            json_encode([
                'lexical_id' => 'slx_test_1',
                'entry_id' => 1,
                'word' => 'test-word',
                'word_with_airab_or_variant' => 'test-word:',
                'part_of_speech' => 'noun',
                'domain' => 'Test Source',
                'definition' => 'first definition',
                'language_direction' => 'test',
                'source_dictionary' => 'Test Source',
                'normalized_word' => 'test-word',
                'normalized_definition' => 'first definition',
            ]),
            json_encode([
                'lexical_id' => 'slx_test_2',
                'entry_id' => 2,
                'word' => 'test-word',
                'part_of_speech' => 'noun',
                'domain' => 'Test Source',
                'definition' => 'second definition',
                'language_direction' => 'test',
                'source_dictionary' => 'Test Source',
                'normalized_word' => 'test-word',
                'normalized_definition' => 'second definition',
            ]),
            json_encode([
                'lexical_id' => 'slx_test_3',
                'entry_id' => 3,
                'word' => $longVariantList,
                'part_of_speech' => null,
                'domain' => 'Test Source',
                'definition' => 'definition with a long variant headword',
                'language_direction' => 'test',
                'source_dictionary' => 'Test Source',
                'normalized_word' => $longVariantList,
                'normalized_definition' => 'definition with a long variant headword',
            ]),
            json_encode([
                'lexical_id' => 'slx_test_4',
                'entry_id' => 4,
                'word' => $failingSindhiVariantList,
                'part_of_speech' => null,
                'domain' => 'Test Source',
                'definition' => 'definition with the production failing Sindhi variant list',
                'language_direction' => 'test',
                'source_dictionary' => 'Test Source',
                'normalized_word' => $failingSindhiVariantList,
                'normalized_definition' => 'definition with the production failing Sindhi variant list',
            ]),
        ]) . PHP_EOL);

        $gzFile = $file . '.gz';
        file_put_contents($gzFile, gzencode((string) file_get_contents($file)));

        $this->artisan('dictionary:import-open-lexicon', [
            '--path' => $gzFile,
            '--chunk' => 1,
        ])->assertExitCode(0);

        $this->artisan('dictionary:import-open-lexicon', [
            '--file' => $file,
            '--chunk' => 1,
        ])->assertExitCode(0);

        $this->assertDatabaseCount('lemmas', 3);
        $this->assertDatabaseCount('senses', 4);
        $this->assertDatabaseHas('lemmas', [
            'lemma' => 'test-word',
            'normalized_lemma' => 'test-word',
            'pos' => 'noun',
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('lemmas', [
            'lemma' => 'canonical-headword',
            'normalized_lemma' => 'canonical-headword',
            'pos' => null,
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('senses', [
            'lexical_id' => 'slx_test_1',
            'definition' => 'first definition',
            'word_variant' => 'test-word:',
            'source_dictionary' => 'Test Source',
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('senses', [
            'lexical_id' => 'slx_test_3',
            'definition' => 'definition with a long variant headword',
            'word_variant' => $longVariantList,
            'status' => 'approved',
        ]);

        $extra = json_decode((string) DB::table('senses')->where('lexical_id', 'slx_test_3')->value('extra'), true);
        $this->assertSame($longVariantList, $extra['original_word'] ?? null);
        $this->assertSame($longVariantList, $extra['original_normalized_word'] ?? null);

        $failingLemma = DB::table('lemmas')->where('lemma', 'ايڏنئين')->first();
        $this->assertNotNull($failingLemma);
        $this->assertLessThanOrEqual(255, mb_strlen($failingLemma->lemma));
        $this->assertLessThanOrEqual(191, mb_strlen($failingLemma->lemma));
        $this->assertSame('ايڏنئين', $failingLemma->normalized_lemma);

        $failingSense = DB::table('senses')->where('lexical_id', 'slx_test_4')->first();
        $this->assertSame($failingSindhiVariantList, $failingSense->word_variant);

        $failingExtra = json_decode((string) $failingSense->extra, true);
        $this->assertSame($failingSindhiVariantList, $failingExtra['original_word'] ?? null);
        $this->assertSame($failingSindhiVariantList, $failingExtra['original_normalized_word'] ?? null);

        @unlink($file);
        @unlink($gzFile);
    }

    private function createDictionarySchema(): void
    {
        Schema::dropAllTables();

        Schema::create('lemmas', function ($table) {
            $table->id();
            $table->string('public_id')->nullable()->unique();
            $table->string('lemma')->index();
            $table->string('normalized_lemma')->nullable()->index();
            $table->string('transliteration')->nullable();
            $table->string('ipa')->nullable();
            $table->string('phonetic')->nullable();
            $table->string('audio_url')->nullable();
            $table->string('syllabification')->nullable();
            $table->string('pos')->nullable()->index();
            $table->decimal('frequency', 8, 4)->default(0);
            $table->string('status')->default('pending')->index();
            $table->string('completion_status')->default('pending')->index();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable();
            $table->text('completion_notes')->nullable();
            $table->unsignedTinyInteger('completion_score')->default(0);
            $table->json('checklist_json')->nullable();
            $table->boolean('variants_reviewed')->default(false);
            $table->boolean('examples_reviewed')->default(false);
            $table->boolean('morphology_reviewed')->default(false);
            $table->boolean('pronunciation_reviewed')->default(false);
            $table->timestamps();
        });

        Schema::create('senses', function ($table) {
            $table->id();
            $table->string('public_id')->nullable()->unique();
            $table->string('lexical_id', 40)->nullable()->unique();
            $table->string('entry_id', 64)->nullable()->index();
            $table->foreignId('lemma_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('sense_order')->default(0)->index();
            $table->text('definition');
            $table->string('short_gloss')->nullable();
            $table->text('full_definition')->nullable();
            $table->text('usage_notes')->nullable();
            $table->text('definition_en')->nullable();
            $table->text('definition_sd')->nullable();
            $table->string('part_of_speech')->nullable()->index();
            $table->text('word_variant')->nullable();
            $table->string('domain')->nullable()->index();
            $table->string('register')->nullable();
            $table->string('dialect')->nullable();
            $table->unsignedTinyInteger('confidence')->nullable();
            $table->string('language_direction', 100)->nullable()->index();
            $table->string('source_dictionary', 150)->nullable()->index();
            $table->string('source')->nullable();
            $table->string('source_entry_id', 100)->nullable()->index();
            $table->string('publisher')->nullable();
            $table->string('license')->nullable();
            $table->string('import_version')->nullable();
            $table->text('normalized_definition')->nullable();
            $table->longText('extra')->nullable();
            $table->string('status')->default('pending');
            $table->string('review_status')->default('unreviewed')->index();
            $table->timestamps();
        });
    }
}
