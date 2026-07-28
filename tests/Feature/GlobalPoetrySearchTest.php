<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\GlobalSearchController;
use App\Models\Couplets;
use App\Models\Poetry;
use App\Models\PoetryTranslations;
use App\Models\Poets;
use App\Models\PoetsDetail;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class GlobalPoetrySearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['scout.driver' => 'database']);
        $this->createMinimalSchema();
    }

    public function test_search_matches_poem_verse_text_not_only_title(): void
    {
        $poet = Poets::withoutEvents(function () {
            return Poets::create([
                'poet_slug' => 'search-poet',
                'poet_pic' => null,
                'visibility' => 1,
            ]);
        });

        PoetsDetail::create([
            'poet_id' => $poet->id,
            'poet_name' => 'Search Poet',
            'poet_laqab' => 'Search',
            'lang' => 'en',
        ]);

        $titleOnly = Poetry::withoutEvents(function () use ($poet) {
            return Poetry::create([
                'poet_id' => $poet->id,
                'poetry_slug' => 'title-only-poem',
                'visibility' => 1,
            ]);
        });
        PoetryTranslations::create([
            'poetry_id' => $titleOnly->id,
            'lang' => 'en',
            'title' => 'Morning Light',
            'info' => null,
        ]);

        $verseHit = Poetry::withoutEvents(function () use ($poet) {
            return Poetry::create([
                'poet_id' => $poet->id,
                'poetry_slug' => 'verse-hit-poem',
                'visibility' => 1,
            ]);
        });
        PoetryTranslations::create([
            'poetry_id' => $verseHit->id,
            'lang' => 'en',
            'title' => 'Unrelated Title',
            'info' => null,
        ]);
        Couplets::withoutEvents(function () use ($verseHit, $poet) {
            return Couplets::create([
                'poetry_id' => $verseHit->id,
                'poet_id' => $poet->id,
                'couplet_slug' => 'verse-hit-1',
                'couplet_text' => "The river carries saffron dust at dusk,\nAnd silence answers.",
                'lang' => 'en',
                'visibility' => 1,
            ]);
        });

        $controller = app(GlobalSearchController::class);
        $method = new ReflectionMethod(GlobalSearchController::class, 'searchPoetry');
        $method->setAccessible(true);
        $poetry = collect($method->invoke($controller, 'saffron', 'en'));

        $this->assertTrue(
            $poetry->contains(fn ($row) => ($row['slug'] ?? null) === 'verse-hit-poem'),
            'Expected poem with matching verse text in results'
        );
        $this->assertFalse(
            $poetry->contains(fn ($row) => ($row['slug'] ?? null) === 'title-only-poem'),
            'Did not expect unrelated title-only poem'
        );

        $hit = $poetry->firstWhere('slug', 'verse-hit-poem');
        $this->assertNotEmpty($hit['match_snippet'] ?? null);
        $this->assertStringContainsStringIgnoringCase('saffron', (string) $hit['match_snippet']);
    }

    private function createMinimalSchema(): void
    {
        foreach ([
            'poetry_couplets',
            'poetry_translations',
            'poetry_main',
            'poets_detail',
            'poets',
            'categories',
            'user_likes',
            'user_bookmarks',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('poets', function ($table) {
            $table->id();
            $table->string('poet_slug')->nullable();
            $table->string('poet_pic')->nullable();
            $table->boolean('visibility')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('poets_detail', function ($table) {
            $table->id();
            $table->unsignedBigInteger('poet_id');
            $table->string('poet_name')->nullable();
            $table->string('poet_laqab')->nullable();
            $table->string('lang', 8)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('poetry_main', function ($table) {
            $table->id();
            $table->unsignedBigInteger('poet_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('poetry_slug')->nullable();
            $table->string('poetry_title')->nullable();
            $table->boolean('visibility')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('poetry_translations', function ($table) {
            $table->id();
            $table->unsignedBigInteger('poetry_id');
            $table->string('lang', 8)->nullable();
            $table->string('title')->nullable();
            $table->text('info')->nullable();
            $table->string('source')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('poetry_couplets', function ($table) {
            $table->id();
            $table->unsignedBigInteger('poetry_id')->nullable();
            $table->unsignedBigInteger('poet_id')->nullable();
            $table->unsignedBigInteger('topic_category_id')->nullable();
            $table->unsignedBigInteger('book_id')->nullable();
            $table->string('couplet_slug')->nullable();
            $table->text('couplet_text')->nullable();
            $table->string('couplet_tags')->nullable();
            $table->string('lang', 8)->nullable();
            $table->integer('page_start')->nullable();
            $table->integer('page_end')->nullable();
            $table->boolean('visibility')->default(1);
            $table->boolean('is_featured')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('categories', function ($table) {
            $table->id();
            $table->string('slug')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_likes', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->morphs('likeable');
            $table->timestamps();
        });

        Schema::create('user_bookmarks', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->morphs('bookmarkable');
            $table->timestamps();
        });
    }
}
