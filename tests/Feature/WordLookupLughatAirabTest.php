<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\WordLookupController;
use App\Models\LughatLemma;
use App\Models\LughatSense;
use App\Support\DictionaryText;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WordLookupLughatAirabTest extends TestCase
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
        $this->createSchema();
    }

    public function test_lughat_lookup_uses_unmarked_entry_for_airab_poetry_token(): void
    {
        $unmarked = 'پرين';
        $marked = "پ\u{064E}رين";
        $this->assertNotSame($unmarked, $marked);
        $this->assertSame(DictionaryText::lookupBase($unmarked), DictionaryText::lookupBase($marked));

        $lemma = LughatLemma::query()->create([
            'lemma' => $unmarked,
            'normalized_lemma' => DictionaryText::normalizeForIdentity($unmarked),
            'lookup_base' => DictionaryText::lookupBase($unmarked),
            'homograph_number' => 1,
            'language' => 'sd',
            'pos' => 'noun',
            'status' => 'approved',
            'completion_status' => 'complete',
        ]);
        LughatSense::query()->create([
            'lemma_id' => $lemma->id,
            'definition' => 'پيارو انسان، دلبر',
            'definition_sd' => 'پيارو انسان، دلبر',
            'definition_en' => 'Beloved',
            'source_dictionary' => 'Baakh Lughat',
            'sense_order' => 1,
        ]);

        $method = new \ReflectionMethod(WordLookupController::class, 'lookupLughat');
        $payload = $method->invoke(app(WordLookupController::class), $marked, null);

        $this->assertIsArray($payload);
        $this->assertTrue($payload['found']);
        $this->assertSame('lughat', $payload['dictionary']);
        $this->assertSame('baakh_lughat', $payload['source']);
        $this->assertSame($lemma->id, $payload['id']);
        $this->assertSame($unmarked, $payload['word']);
        $this->assertSame('complete', $payload['completion_status']);
        $this->assertFalse(
            collect($payload['senses'] ?? [])->contains(
                fn ($sense) => str_contains((string) ($sense['source_dictionary'] ?? $sense['source'] ?? ''), 'جامع')
            )
        );
    }

    public function test_two_airab_lughat_variants_do_not_collapse_for_unmarked_query(): void
    {
        LughatLemma::query()->create([
            'lemma' => "ن\u{064E}ھن",
            'normalized_lemma' => DictionaryText::normalizeForIdentity("ن\u{064E}ھن"),
            'lookup_base' => DictionaryText::lookupBase("نَھن"),
            'homograph_number' => 1,
            'language' => 'sd',
            'status' => 'approved',
            'completion_status' => 'complete',
        ]);
        LughatLemma::query()->create([
            'lemma' => "ن\u{064F}ھن",
            'normalized_lemma' => DictionaryText::normalizeForIdentity("ن\u{064F}ھن"),
            'lookup_base' => DictionaryText::lookupBase("نُھن"),
            'homograph_number' => 1,
            'language' => 'sd',
            'status' => 'approved',
            'completion_status' => 'complete',
        ]);

        $method = new \ReflectionMethod(WordLookupController::class, 'lookupLughat');
        $payload = $method->invoke(app(WordLookupController::class), 'نھن', null);

        $this->assertNull($payload);
    }

    private function createSchema(): void
    {
        foreach ([
            'lughat_sense_examples', 'lughat_senses', 'lughat_morphologies', 'lughat_variants',
            'lughat_relations', 'lughat_inflections', 'lughat_idiomatic_expressions',
            'lughat_word_forms', 'lughat_expression_components', 'lughat_expressions',
            'lughat_poetry_sense_annotations', 'lughat_lemmas',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('lughat_lemmas', function ($table) {
            $table->id();
            $table->string('public_id')->nullable();
            $table->string('lemma');
            $table->string('normalized_lemma')->nullable();
            $table->string('lookup_base')->nullable();
            $table->unsignedSmallInteger('homograph_number')->default(1);
            $table->string('language', 8)->default('sd');
            $table->string('pos')->nullable();
            $table->string('transliteration')->nullable();
            $table->string('status')->nullable();
            $table->string('completion_status')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();
        });
        Schema::create('lughat_senses', function ($table) {
            $table->id();
            $table->unsignedBigInteger('lemma_id');
            $table->string('public_id')->nullable();
            $table->unsignedInteger('sense_order')->nullable();
            $table->text('definition')->nullable();
            $table->text('definition_sd')->nullable();
            $table->text('definition_en')->nullable();
            $table->text('short_gloss')->nullable();
            $table->text('full_definition')->nullable();
            $table->string('source_dictionary')->nullable();
            $table->string('source')->nullable();
            $table->json('english_equivalents')->nullable();
            $table->json('extra')->nullable();
            $table->timestamps();
        });
        foreach (['lughat_morphologies', 'lughat_variants', 'lughat_relations', 'lughat_inflections', 'lughat_idiomatic_expressions', 'lughat_word_forms', 'lughat_sense_examples'] as $table) {
            Schema::create($table, function ($t) use ($table) {
                $t->id();
                $t->unsignedBigInteger('lemma_id')->nullable();
                $t->unsignedBigInteger('sense_id')->nullable();
                if ($table === 'lughat_word_forms') {
                    $t->string('form')->nullable();
                    $t->string('normalized_form')->nullable();
                }
                if ($table === 'lughat_variants') {
                    $t->string('variant')->nullable();
                    $t->string('normalized_variant')->nullable();
                }
                if ($table === 'lughat_inflections') {
                    $t->string('form')->nullable();
                    $t->string('normalized_form')->nullable();
                }
                $t->timestamps();
            });
        }
        Schema::create('lughat_expressions', function ($table) {
            $table->id();
            $table->string('expression')->nullable();
            $table->timestamps();
        });
        Schema::create('lughat_expression_components', function ($table) {
            $table->id();
            $table->unsignedBigInteger('expression_id');
            $table->unsignedBigInteger('lemma_id')->nullable();
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();
        });
        Schema::create('lughat_poetry_sense_annotations', function ($table) {
            $table->id();
            $table->unsignedBigInteger('poetry_id');
            $table->unsignedBigInteger('lemma_id');
            $table->unsignedBigInteger('sense_id')->nullable();
            $table->string('normalized_form')->nullable();
            $table->timestamps();
        });
    }
}
