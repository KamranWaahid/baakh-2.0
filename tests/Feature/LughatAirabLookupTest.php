<?php

namespace Tests\Feature;

use App\Models\LughatLemma;
use App\Services\LughatLemmaJsonImportService;
use App\Services\LughatPoetryRomanService;
use App\Support\DictionaryText;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LughatAirabLookupTest extends TestCase
{
    private const UNMARKED = 'ڪھي';

    private const MARKED = "ڪ\u{064E}ھي";

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

    public function test_unmarked_poetry_token_resolves_unique_zabar_lemma(): void
    {
        $this->assertNotSame(self::UNMARKED, self::MARKED);
        $this->assertSame(DictionaryText::lookupBase(self::UNMARKED), DictionaryText::lookupBase(self::MARKED));

        LughatLemma::query()->create([
            'lemma' => self::MARKED,
            'normalized_lemma' => DictionaryText::normalizeForIdentity(self::MARKED),
            'lookup_base' => null,
            'homograph_number' => 1,
            'language' => 'sd',
            'transliteration' => 'kahe',
            'status' => 'approved',
            'completion_status' => 'pending',
        ]);

        $roman = app(LughatPoetryRomanService::class);
        $resolved = $roman->resolveLemma(self::UNMARKED);

        $this->assertSame('lookup_base_unique', $resolved['matched_via']);
        $this->assertSame(self::MARKED, $resolved['lemma']->lemma);

        $check = $roman->check('', self::UNMARKED);
        $this->assertSame(0, $check['unresolved_count']);
        $this->assertSame('ok', $check['words'][0]['status'] ?? null);

        $stub = $roman->resolveOrCreateStub(self::UNMARKED);
        $this->assertFalse($stub['created']);
        $this->assertSame($resolved['lemma']->id, $stub['lemma_id']);
    }

    public function test_json_import_on_unmarked_stub_retargets_existing_marked_lemma(): void
    {
        $existing = LughatLemma::query()->create([
            'lemma' => self::MARKED,
            'normalized_lemma' => DictionaryText::normalizeForIdentity(self::MARKED),
            'lookup_base' => DictionaryText::lookupBase(self::MARKED),
            'homograph_number' => 1,
            'language' => 'sd',
            'transliteration' => 'kahe',
            'status' => 'approved',
            'completion_status' => 'pending',
        ]);

        $stub = LughatLemma::query()->create([
            'lemma' => self::UNMARKED,
            'normalized_lemma' => DictionaryText::normalizeForIdentity(self::UNMARKED),
            'lookup_base' => DictionaryText::lookupBase(self::UNMARKED),
            'homograph_number' => 1,
            'language' => 'sd',
            'status' => 'pending',
            'completion_status' => 'pending',
        ]);

        $service = app(LughatLemmaJsonImportService::class);
        $method = new \ReflectionMethod(LughatLemmaJsonImportService::class, 'retargetToExistingHeadword');
        $payload = ['lemma' => self::MARKED];
        $args = [$stub, &$payload];
        $target = $method->invokeArgs($service, $args);

        $this->assertSame($existing->id, $target->id);
        $this->assertNull(LughatLemma::find($stub->id));
    }

    public function test_marked_rueendee_attaches_to_existing_unmarked_lemma(): void
    {
        $unmarked = 'رئيندي';
        $marked = "ر\u{064F}ئيندي";
        $this->assertNotSame($unmarked, $marked);
        $this->assertSame(DictionaryText::lookupBase($unmarked), DictionaryText::lookupBase($marked));

        $existing = LughatLemma::query()->create([
            'lemma' => $unmarked,
            'normalized_lemma' => DictionaryText::normalizeForIdentity($unmarked),
            'lookup_base' => DictionaryText::lookupBase($unmarked),
            'homograph_number' => 1,
            'language' => 'sd',
            'transliteration' => 'rueendee',
            'status' => 'approved',
            'completion_status' => 'pending',
        ]);

        $resolved = app(LughatPoetryRomanService::class)->resolveLemma($marked);
        $this->assertSame('lookup_base_unique', $resolved['matched_via']);
        $this->assertSame($existing->id, $resolved['lemma']->id);

        $stub = app(LughatPoetryRomanService::class)->resolveOrCreateStub($marked);
        $this->assertFalse($stub['created']);
        $this->assertSame($existing->id, $stub['lemma_id']);
    }

    public function test_json_import_marked_rueendee_retargets_unmarked_and_keeps_airab_identity(): void
    {
        $unmarked = 'رئيندي';
        $partial = "ر\u{064F}ئيندي";
        $marked = "ر\u{064F}ئِندِي";

        $existing = LughatLemma::query()->create([
            'lemma' => $unmarked,
            'normalized_lemma' => DictionaryText::normalizeForIdentity($unmarked),
            'lookup_base' => DictionaryText::lookupBase($unmarked),
            'homograph_number' => 1,
            'language' => 'sd',
            'status' => 'approved',
            'completion_status' => 'pending',
        ]);

        $stub = LughatLemma::query()->create([
            'lemma' => $partial,
            'normalized_lemma' => DictionaryText::normalizeForIdentity($partial),
            'lookup_base' => DictionaryText::lookupBase($partial),
            'homograph_number' => 1,
            'language' => 'sd',
            'status' => 'pending',
            'completion_status' => 'pending',
        ]);

        $service = app(LughatLemmaJsonImportService::class);
        $retarget = new \ReflectionMethod(LughatLemmaJsonImportService::class, 'retargetToExistingHeadword');
        $payload = [
            'lemma' => $marked,
            'normalized_lemma' => $unmarked,
        ];
        $args = [$stub, &$payload];
        $target = $retarget->invokeArgs($service, $args);

        $this->assertSame($existing->id, $target->id);
        $this->assertNull(LughatLemma::find($stub->id));

        $apply = new \ReflectionMethod(LughatLemmaJsonImportService::class, 'applyLemmaFields');
        $apply->invoke($service, $target, $payload);
        $target->refresh();

        $this->assertSame($marked, $target->lemma);
        $this->assertSame(DictionaryText::normalizeForIdentity($marked), $target->normalized_lemma);
        $this->assertNotSame($unmarked, $target->normalized_lemma);
        $this->assertSame(1, LughatLemma::query()->count());
    }

    public function test_two_airab_variants_stay_ambiguous_for_unmarked_token(): void
    {
        LughatLemma::query()->create([
            'lemma' => "ن\u{064E}ھن",
            'normalized_lemma' => DictionaryText::normalizeForIdentity("ن\u{064E}ھن"),
            'lookup_base' => DictionaryText::lookupBase("ن\u{064E}ھن"),
            'homograph_number' => 1,
            'language' => 'sd',
            'transliteration' => 'nahan',
            'status' => 'approved',
        ]);
        LughatLemma::query()->create([
            'lemma' => "ن\u{064F}ھن",
            'normalized_lemma' => DictionaryText::normalizeForIdentity("ن\u{064F}ھن"),
            'lookup_base' => DictionaryText::lookupBase("ن\u{064F}ھن"),
            'homograph_number' => 1,
            'language' => 'sd',
            'transliteration' => 'nuhan',
            'status' => 'approved',
        ]);

        $resolved = app(LughatPoetryRomanService::class)->resolveLemma('نھن');
        $this->assertSame('ambiguous', $resolved['matched_via']);
        $this->assertNull($resolved['lemma']);
        $this->assertCount(2, $resolved['candidates'] ?? []);
    }

    private function createSchema(): void
    {
        Schema::dropIfExists('lughat_variants');
        Schema::dropIfExists('lughat_lemmas');

        Schema::create('lughat_lemmas', function ($table) {
            $table->id();
            $table->string('public_id')->nullable();
            $table->string('lemma');
            $table->string('normalized_lemma')->nullable();
            $table->string('lookup_base')->nullable();
            $table->unsignedSmallInteger('homograph_number')->default(1);
            $table->string('language', 8)->default('sd');
            $table->string('transliteration')->nullable();
            $table->string('romanization_status')->nullable();
            $table->string('status')->nullable();
            $table->string('completion_status')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamps();
            $table->unique(['language', 'normalized_lemma', 'homograph_number'], 'lughat_lemmas_lexeme_unique');
        });

        Schema::create('lughat_variants', function ($table) {
            $table->id();
            $table->unsignedBigInteger('lemma_id');
            $table->string('public_id')->nullable();
            $table->string('variant');
            $table->string('normalized_variant')->nullable();
            $table->string('type')->nullable();
            $table->string('note')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();
        });
    }
}
