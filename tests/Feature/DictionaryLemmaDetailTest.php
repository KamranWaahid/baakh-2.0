<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DictionaryLemmaDetailTest extends TestCase
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

    public function test_lemma_detail_exposes_open_lexicon_source_metadata(): void
    {
        $this->withoutMiddleware();

        DB::table('lemmas')->insert([
            'id' => 1,
            'lemma' => '(At this rate)',
            'normalized_lemma' => 'at this rate',
            'transliteration' => null,
            'pos' => null,
            'frequency' => 0,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('senses')->insert([
            'id' => 10,
            'lexical_id' => 'slx_3a43f420a28ef566',
            'entry_id' => '57952',
            'lemma_id' => 1,
            'definition' => 'انهيءَ نرخ، انهيءَ حساب',
            'definition_en' => null,
            'definition_sd' => null,
            'part_of_speech' => null,
            'word_variant' => null,
            'domain' => 'English → Sindhi',
            'language_direction' => 'english',
            'source_dictionary' => 'English → Sindhi',
            'normalized_definition' => 'انهيء نرخ انهيء حساب',
            'extra' => json_encode([
                'publisher' => 'SindhiLanguage.org',
                'publisher_url' => 'https://sindhilanguage.org/',
                'prepared_by' => 'Amar Fayaz Buriro (امر فياض ٻرڙو)',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/admin/dictionary/lemmas/1');

        $response
            ->assertOk()
            ->assertJsonPath('source_summary.is_open_lexicon', true)
            ->assertJsonPath('source_summary.is_source_term', true)
            ->assertJsonPath('source_summary.word_label', 'English Source Term')
            ->assertJsonPath('source_summary.source_words.0', '(At this rate)')
            ->assertJsonPath('source_summary.normalized_words.0', 'at this rate')
            ->assertJsonPath('source_summary.lexical_ids.0', 'slx_3a43f420a28ef566')
            ->assertJsonPath('senses.0.source_metadata.source_dictionary', 'English → Sindhi')
            ->assertJsonPath('senses.0.source_metadata.normalized_definition', 'انهيء نرخ انهيء حساب')
            ->assertJsonPath('has_real_morphology', false);
    }

    public function test_lemma_detail_merges_read_only_imported_variants(): void
    {
        $this->withoutMiddleware();

        DB::table('lemmas')->insert([
            'id' => 1,
            'lemma' => 'canonical-headword',
            'normalized_lemma' => 'canonical-headword',
            'transliteration' => null,
            'pos' => null,
            'frequency' => 0,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lemma_variants')->insert([
            'id' => 5,
            'lemma_id' => 1,
            'variant' => 'manual-variant',
            'type' => 'dialectal',
            'dialect' => 'manual',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('senses')->insert([
            'id' => 10,
            'lexical_id' => 'slx_variant_test',
            'entry_id' => '1',
            'lemma_id' => 1,
            'definition' => 'definition',
            'definition_en' => null,
            'definition_sd' => null,
            'part_of_speech' => null,
            'word_variant' => 'canonical-headword, variant-one، variant-two يا variant-three',
            'domain' => 'Test Source',
            'language_direction' => 'sindhi',
            'source_dictionary' => 'Test Source',
            'normalized_definition' => 'definition',
            'extra' => json_encode([
                'original_word' => 'canonical-headword, variant-one، variant-two يا variant-three',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/admin/dictionary/lemmas/1');

        $response
            ->assertOk()
            ->assertJsonPath('manual_variants_count', 1)
            ->assertJsonPath('imported_variants_count', 3)
            ->assertJsonPath('variants.0.variant', 'manual-variant')
            ->assertJsonPath('variants.1.variant', 'variant-one')
            ->assertJsonPath('variants.1.is_imported', true)
            ->assertJsonPath('variants.3.variant', 'variant-three');
    }

    public function test_admin_can_create_sense_for_lemma_route_without_body_lemma_id(): void
    {
        $this->withoutMiddleware();

        DB::table('lemmas')->insert([
            'id' => 156471,
            'lemma' => 'test-headword',
            'normalized_lemma' => 'test-headword',
            'transliteration' => null,
            'pos' => null,
            'frequency' => 0,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/admin/dictionary/lemmas/156471/senses?lang=sd', [
            'definition' => '  manual definition  ',
            'domain' => '',
            'language_direction' => 'sindhi',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('lemma_id', 156471)
            ->assertJsonPath('definition', 'manual definition')
            ->assertJsonPath('domain', null)
            ->assertJsonPath('language_direction', 'sindhi')
            ->assertJsonPath('status', 'pending');

        $this->assertDatabaseHas('senses', [
            'lemma_id' => 156471,
            'definition' => 'manual definition',
            'domain' => null,
            'language_direction' => 'sindhi',
            'status' => 'pending',
        ]);
    }

    public function test_admin_sense_creation_returns_validation_errors_for_blank_definition(): void
    {
        $this->withoutMiddleware();

        DB::table('lemmas')->insert([
            'id' => 156471,
            'lemma' => 'test-headword',
            'normalized_lemma' => 'test-headword',
            'transliteration' => null,
            'pos' => null,
            'frequency' => 0,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/admin/dictionary/senses?lang=sd', [
            'lemma_id' => 156471,
            'definition' => '   ',
            'domain' => '',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['definition']);

        $this->assertSame(0, DB::table('senses')->count());
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
            $table->text('word_variant')->nullable();
            $table->string('domain')->nullable()->index();
            $table->string('language_direction', 100)->nullable()->index();
            $table->string('source_dictionary', 150)->nullable()->index();
            $table->text('normalized_definition')->nullable();
            $table->longText('extra')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('sense_examples', function ($table) {
            $table->id();
            $table->foreignId('sense_id')->constrained()->onDelete('cascade');
            $table->text('sentence');
            $table->string('source')->nullable();
            $table->foreignId('corpus_sentence_id')->nullable();
            $table->timestamps();
        });

        Schema::create('morphologies', function ($table) {
            $table->id();
            $table->foreignId('lemma_id')->constrained()->onDelete('cascade');
            $table->string('root')->nullable()->index();
            $table->string('pattern')->nullable();
            $table->string('gender')->nullable();
            $table->string('number')->nullable();
            $table->string('case')->nullable();
            $table->string('aspect')->nullable();
            $table->string('tense')->nullable();
            $table->timestamps();
        });

        Schema::create('lemma_variants', function ($table) {
            $table->id();
            $table->foreignId('lemma_id')->constrained()->onDelete('cascade');
            $table->string('variant')->index();
            $table->string('type')->default('dialectal');
            $table->string('dialect')->nullable();
            $table->timestamps();
        });

        Schema::create('lemma_relations', function ($table) {
            $table->id();
            $table->foreignId('lemma_id')->constrained()->onDelete('cascade');
            $table->string('relation_type');
            $table->string('related_word');
            $table->foreignId('related_lemma_id')->nullable()->constrained('lemmas')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('baakh_roman_words', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('word_sd');
            $table->string('word_roman');
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
