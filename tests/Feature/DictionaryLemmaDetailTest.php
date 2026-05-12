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

    public function test_lemma_detail_handles_malformed_imported_metadata_without_500(): void
    {
        $this->withoutMiddleware();

        DB::table('lemmas')->insert([
            'id' => 2974,
            'lemma' => 'اڙٻنگ-عربي-درآمد',
            'normalized_lemma' => null,
            'transliteration' => null,
            'pos' => null,
            'frequency' => 0,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('senses')->insert([
            'id' => 29740,
            'lexical_id' => 'slx_malformed_2974',
            'entry_id' => '2974',
            'lemma_id' => 2974,
            'definition' => 'عجيب ۽ ڊگهي تعريف جيڪا درآمد ٿيل ماخذ مان آئي.',
            'definition_en' => null,
            'definition_sd' => null,
            'part_of_speech' => null,
            'word_variant' => 'اڙٻنگ-عربي-درآمد، اڙٻنگ variant يا عربي source',
            'domain' => 'Arabic → Sindhi',
            'language_direction' => 'arabic',
            'source_dictionary' => 'Arabic → Sindhi',
            'normalized_definition' => null,
            'extra' => json_encode([
                'original_word' => [
                    'raw' => 'اڙٻنگ-عربي-درآمد، خراب شڪل',
                    'notes' => ['nested metadata should not be treated as a scalar'],
                ],
                'original_normalized_word' => ['not', 'a', 'string'],
                'publisher' => ['unexpected' => 'array'],
                'extra' => [
                    'source' => ['nested' => 'object'],
                    'raw' => 'retained for diagnostics',
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/admin/dictionary/lemmas/2974?lang=sd');

        $response
            ->assertOk()
            ->assertJsonPath('id', 2974)
            ->assertJsonPath('source_summary.is_open_lexicon', true)
            ->assertJsonPath('source_summary.is_source_term', true)
            ->assertJsonPath('source_summary.primary_language', 'Arabic')
            ->assertJsonPath('senses.0.source_metadata.source_word', 'اڙٻنگ-عربي-درآمد')
            ->assertJsonPath('senses.0.source_metadata.publisher', null)
            ->assertJsonPath('senses.0.source_metadata.source_extra.raw', 'retained for diagnostics')
            ->assertJsonPath('imported_variants_count', 2)
            ->assertJsonPath('variants.0.variant', 'اڙٻنگ variant')
            ->assertJsonPath('variants.1.variant', 'عربي source')
            ->assertJsonPath('has_real_morphology', false);
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

    public function test_admin_persists_english_meaning_and_diacritic_variants_are_lookupable(): void
    {
        $this->withoutMiddleware();

        DB::table('lemmas')->insert([
            'id' => 1,
            'public_id' => 'lem_diacritic',
            'lemma' => 'canonical-entry',
            'normalized_lemma' => 'canonical-entry',
            'transliteration' => null,
            'pos' => 'pronoun',
            'frequency' => 0,
            'status' => 'approved',
            'completion_status' => 'complete',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/admin/dictionary/senses', [
            'lemma_id' => 1,
            'definition' => 'بنيادي سنڌي تعريف',
            'definition_en' => 'one; a single thing',
            'definition_sd' => 'هڪ شئي',
            'status' => 'approved',
            'review_status' => 'reviewed',
        ])
            ->assertCreated()
            ->assertJsonPath('definition_en', 'one; a single thing');

        $this->postJson('/api/admin/dictionary/lemmas/1/variants', [
            'variant' => 'ھِڪَ',
            'type' => 'diacritic',
            'dialect' => 'airab',
            'source' => 'editor',
            'review_status' => 'reviewed',
        ])
            ->assertCreated()
            ->assertJsonPath('variant', 'ھِڪَ')
            ->assertJsonPath('type', 'diacritic');

        $this->getJson('/api/admin/dictionary/lemmas/1')
            ->assertOk()
            ->assertJsonPath('senses.0.definition_en', 'one; a single thing')
            ->assertJsonPath('variants.0.variant', 'ھِڪَ')
            ->assertJsonPath('variants.0.type', 'diacritic');

        $this->getJson('/api/v1/word/' . rawurlencode('ھڪ'))
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('public_id', 'lem_diacritic')
            ->assertJsonPath('meanings_en.0', 'one; a single thing')
            ->assertJsonPath('variants.0.variant', 'ھِڪَ');
    }

    public function test_completion_endpoint_blocks_incomplete_lemmas_and_marks_ready_lemmas_complete(): void
    {
        $this->withoutMiddleware();

        DB::table('lemmas')->insert([
            'id' => 1,
            'lemma' => 'completion-word',
            'normalized_lemma' => 'completion-word',
            'transliteration' => null,
            'pos' => null,
            'frequency' => 0,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('senses')->insert([
            'id' => 10,
            'lexical_id' => 'slx_completion',
            'entry_id' => '10',
            'lemma_id' => 1,
            'definition' => 'curated definition',
            'part_of_speech' => null,
            'domain' => 'Test Source',
            'language_direction' => 'test',
            'source_dictionary' => 'Test Source',
            'status' => 'approved',
            'review_status' => 'reviewed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/admin/dictionary/lemmas/1/completion')
            ->assertOk()
            ->assertJsonPath('is_complete', false)
            ->assertJsonPath('checks.has_pos.passed', false);

        $this->patchJson('/api/admin/dictionary/lemmas/1/completion', [
            'completion_status' => 'complete',
        ])
            ->assertStatus(422)
            ->assertJsonPath('completion.checks.has_pos.passed', false);

        DB::table('lemmas')->where('id', 1)->update(['pos' => 'noun']);

        $this->patchJson('/api/admin/dictionary/lemmas/1/completion', [
            'completion_status' => 'complete',
            'completion_notes' => 'Reviewed by editor.',
        ])
            ->assertOk()
            ->assertJsonPath('lemma.completion_status', 'complete')
            ->assertJsonPath('completion.is_complete', true);

        $this->assertDatabaseHas('lemmas', [
            'id' => 1,
            'completion_status' => 'complete',
            'completion_score' => 100,
            'completion_notes' => 'Reviewed by editor.',
        ]);
    }

    public function test_public_lookup_returns_structured_senses_and_variant_matches(): void
    {
        DB::table('lemmas')->insert([
            'id' => 1,
            'public_id' => 'lem_1',
            'lemma' => 'canonical-word',
            'normalized_lemma' => 'canonical-word',
            'transliteration' => 'canonical',
            'pos' => 'noun',
            'frequency' => 0,
            'status' => 'approved',
            'completion_status' => 'complete',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('lemma_variants')->insert([
            'id' => 2,
            'public_id' => 'var_2',
            'lemma_id' => 1,
            'variant' => 'variant-word',
            'type' => 'dialectal',
            'dialect' => 'test',
            'review_status' => 'reviewed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('senses')->insert([
            'id' => 10,
            'public_id' => 'sen_10',
            'lexical_id' => 'slx_lookup',
            'entry_id' => '10',
            'lemma_id' => 1,
            'definition' => 'primary definition',
            'short_gloss' => 'short gloss',
            'definition_en' => 'English definition',
            'part_of_speech' => 'noun',
            'domain' => 'Test Domain',
            'language_direction' => 'test',
            'source_dictionary' => 'Test Source',
            'source' => 'Test Source',
            'source_entry_id' => '10',
            'status' => 'approved',
            'review_status' => 'reviewed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/v1/word/variant-word')
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('public_id', 'lem_1')
            ->assertJsonPath('variants.0.variant', 'variant-word')
            ->assertJsonPath('senses.0.public_id', 'sen_10')
            ->assertJsonPath('senses.0.short_gloss', 'short gloss')
            ->assertJsonPath('senses.0.source', 'Test Source')
            ->assertJsonPath('meanings.0', 'primary definition');
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

        Schema::create('sense_examples', function ($table) {
            $table->id();
            $table->string('public_id')->nullable()->unique();
            $table->foreignId('sense_id')->constrained()->onDelete('cascade');
            $table->text('sentence');
            $table->text('translation')->nullable();
            $table->string('source')->nullable();
            $table->string('citation')->nullable();
            $table->string('quality_flag')->default('unreviewed')->index();
            $table->string('review_status')->default('unreviewed')->index();
            $table->foreignId('corpus_sentence_id')->nullable();
            $table->timestamps();
        });

        Schema::create('morphologies', function ($table) {
            $table->id();
            $table->string('public_id')->nullable()->unique();
            $table->foreignId('lemma_id')->constrained()->onDelete('cascade');
            $table->string('root')->nullable()->index();
            $table->string('pattern')->nullable();
            $table->string('gender')->nullable();
            $table->string('number')->nullable();
            $table->string('case')->nullable();
            $table->string('aspect')->nullable();
            $table->string('tense')->nullable();
            $table->string('review_status')->default('unreviewed')->index();
            $table->timestamps();
        });

        Schema::create('lemma_variants', function ($table) {
            $table->id();
            $table->string('public_id')->nullable()->unique();
            $table->foreignId('lemma_id')->constrained()->onDelete('cascade');
            $table->string('variant')->index();
            $table->string('type')->default('dialectal');
            $table->string('dialect')->nullable();
            $table->string('source')->nullable();
            $table->string('source_entry_id', 100)->nullable()->index();
            $table->string('review_status')->default('unreviewed')->index();
            $table->timestamps();
        });

        Schema::create('lemma_relations', function ($table) {
            $table->id();
            $table->string('public_id')->nullable()->unique();
            $table->foreignId('lemma_id')->constrained()->onDelete('cascade');
            $table->string('relation_type');
            $table->string('related_word');
            $table->foreignId('related_lemma_id')->nullable()->constrained('lemmas')->nullOnDelete();
            $table->string('source')->nullable();
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
