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
        ]) . PHP_EOL);

        $this->artisan('dictionary:import-open-lexicon', [
            '--file' => $file,
            '--chunk' => 1,
        ])->assertExitCode(0);

        $this->artisan('dictionary:import-open-lexicon', [
            '--file' => $file,
            '--chunk' => 1,
        ])->assertExitCode(0);

        $this->assertDatabaseCount('lemmas', 1);
        $this->assertDatabaseCount('senses', 2);
        $this->assertDatabaseHas('lemmas', [
            'lemma' => 'test-word',
            'normalized_lemma' => 'test-word',
            'pos' => 'noun',
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('senses', [
            'lexical_id' => 'slx_test_1',
            'definition' => 'first definition',
            'word_variant' => 'test-word:',
            'source_dictionary' => 'Test Source',
            'status' => 'approved',
        ]);

        @unlink($file);
    }

    private function createDictionarySchema(): void
    {
        Schema::dropAllTables();

        Schema::create('lemmas', function ($table) {
            $table->id();
            $table->string('lemma')->index();
            $table->string('normalized_lemma')->nullable()->index();
            $table->string('transliteration')->nullable();
            $table->string('pos')->nullable()->index();
            $table->decimal('frequency', 8, 4)->default(0);
            $table->string('status')->default('pending')->index();
            $table->timestamps();
        });

        Schema::create('senses', function ($table) {
            $table->id();
            $table->string('lexical_id', 40)->nullable()->unique();
            $table->string('entry_id', 64)->nullable()->index();
            $table->foreignId('lemma_id')->constrained()->onDelete('cascade');
            $table->text('definition');
            $table->text('definition_en')->nullable();
            $table->text('definition_sd')->nullable();
            $table->string('part_of_speech')->nullable()->index();
            $table->string('word_variant')->nullable();
            $table->string('domain')->nullable()->index();
            $table->string('language_direction', 100)->nullable()->index();
            $table->string('source_dictionary', 150)->nullable()->index();
            $table->text('normalized_definition')->nullable();
            $table->longText('extra')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }
}
