<?php

namespace Tests\Feature;

use App\Models\Categories;
use App\Models\Poetry;
use App\Models\PoetryTranslations;
use App\Models\Poets;
use App\Models\PoetsDetail;
use App\Services\StaticCacheService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FeedApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['scout.driver' => 'database']);
        Storage::fake('public');
        $this->createMinimalSchema();
    }

    public function test_feed_ignores_stale_static_cache_and_returns_latest_poems(): void
    {
        $cache = app(StaticCacheService::class);
        $cache->set('feed_page_1_sd', [
            ['id' => 1, 'slug' => 'cached-old-poem', 'title' => 'Cached'],
        ]);

        $this->makePoem('live-new-poem', 'Live New Poem', 'sd', now());

        $response = $this->getJson('/api/v1/feed?lang=sd&page=1');

        $response->assertOk();
        $slugs = collect($response->json('data'))->pluck('slug');
        $this->assertTrue($slugs->contains('live-new-poem'));
        $this->assertFalse($slugs->contains('cached-old-poem'));
        $this->assertSame(1, $response->json('last_page'));
        $this->assertSame(1, $response->json('total'));
    }

    public function test_feed_last_page_matches_live_count_not_a_mock(): void
    {
        for ($i = 1; $i <= 11; $i++) {
            $this->makePoem("poem-{$i}", "Poem {$i}", 'sd', now()->subMinutes(11 - $i));
        }

        $response = $this->getJson('/api/v1/feed?lang=sd&page=1');

        $response->assertOk();
        $this->assertSame(2, $response->json('last_page'));
        $this->assertSame(11, $response->json('total'));
        $this->assertCount(10, $response->json('data'));
        $this->assertSame('poem-11', $response->json('data.0.slug'));
    }

    public function test_feed_filters_by_category_slug(): void
    {
        $ghazal = Categories::withoutEvents(fn () => Categories::create(['slug' => 'ghazal']));
        $nazm = Categories::withoutEvents(fn () => Categories::create(['slug' => 'nazm']));

        $this->makePoem('ghazal-poem', 'Ghazal', 'sd', now(), $ghazal->id);
        $this->makePoem('nazm-poem', 'Nazm', 'sd', now()->subMinute(), $nazm->id);

        $response = $this->getJson('/api/v1/feed?lang=sd&page=1&category=ghazal');

        $response->assertOk();
        $slugs = collect($response->json('data'))->pluck('slug');
        $this->assertTrue($slugs->contains('ghazal-poem'));
        $this->assertFalse($slugs->contains('nazm-poem'));
    }

    private function makePoem(string $slug, string $title, string $lang, $createdAt, ?int $categoryId = null): Poetry
    {
        $poet = Poets::withoutEvents(function () use ($slug) {
            return Poets::create([
                'poet_slug' => $slug.'-poet',
                'poet_pic' => null,
                'visibility' => 1,
            ]);
        });

        PoetsDetail::create([
            'poet_id' => $poet->id,
            'poet_name' => $title.' Poet',
            'poet_laqab' => $title,
            'lang' => $lang,
        ]);

        $poetry = Poetry::withoutEvents(function () use ($poet, $slug, $title, $createdAt, $categoryId) {
            $row = Poetry::create([
                'poet_id' => $poet->id,
                'category_id' => $categoryId,
                'poetry_slug' => $slug,
                'poetry_title' => $title,
                'visibility' => 1,
            ]);
            $row->created_at = $createdAt;
            $row->updated_at = $createdAt;
            $row->save();

            return $row;
        });

        PoetryTranslations::create([
            'poetry_id' => $poetry->id,
            'lang' => $lang,
            'title' => $title,
        ]);

        return $poetry;
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
            'category_details',
            'languages',
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
            $table->boolean('is_featured')->default(0);
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

        Schema::create('categories', function ($table) {
            $table->id();
            $table->string('slug')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('category_details', function ($table) {
            $table->id();
            $table->unsignedBigInteger('cat_id')->nullable();
            $table->string('cat_name')->nullable();
            $table->string('lang', 8)->nullable();
            $table->timestamps();
        });

        Schema::create('languages', function ($table) {
            $table->id();
            $table->string('lang_code', 8)->nullable();
            $table->string('lang_dir', 8)->nullable();
            $table->timestamps();
        });

        \Illuminate\Support\Facades\DB::table('languages')->insert([
            ['lang_code' => 'sd', 'lang_dir' => 'rtl', 'created_at' => now(), 'updated_at' => now()],
            ['lang_code' => 'en', 'lang_dir' => 'ltr', 'created_at' => now(), 'updated_at' => now()],
        ]);

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
