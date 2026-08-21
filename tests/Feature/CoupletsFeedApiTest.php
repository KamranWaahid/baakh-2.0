<?php

namespace Tests\Feature;

use App\Models\Couplets;
use App\Models\Poets;
use App\Models\PoetsDetail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CoupletsFeedApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->createMinimalSchema();
    }

    public function test_couplets_feed_returns_only_independent_two_line_baits(): void
    {
        $poet = Poets::withoutEvents(function () {
            return Poets::create([
                'poet_slug' => 'feed-poet',
                'poet_pic' => null,
                'visibility' => 1,
            ]);
        });

        PoetsDetail::create([
            'poet_id' => $poet->id,
            'poet_name' => 'Feed Poet',
            'poet_laqab' => 'Feed',
            'lang' => 'sd',
        ]);

        Couplets::withoutEvents(function () use ($poet) {
            return Couplets::create([
                'poetry_id' => 0,
                'poet_id' => $poet->id,
                'couplet_slug' => 'standalone-two-line',
                'couplet_text' => "پهريون مصرع\nٻيون مصرع",
                'lang' => 'sd',
                'visibility' => 1,
            ]);
        });

        Couplets::withoutEvents(function () use ($poet) {
            return Couplets::create([
                'poetry_id' => 0,
                'poet_id' => $poet->id,
                'couplet_slug' => 'standalone-one-line',
                'couplet_text' => 'هڪ سٽ وارو بيت',
                'lang' => 'sd',
                'visibility' => 1,
            ]);
        });

        Couplets::withoutEvents(function () use ($poet) {
            return Couplets::create([
                'poetry_id' => 0,
                'poet_id' => $poet->id,
                'couplet_slug' => 'standalone-three-line',
                'couplet_text' => "پهريون\nٻيون\nٽيون",
                'lang' => 'sd',
                'visibility' => 1,
            ]);
        });

        Couplets::withoutEvents(function () use ($poet) {
            return Couplets::create([
                'poetry_id' => 99,
                'poet_id' => $poet->id,
                'couplet_slug' => 'ghazal-verse',
                'couplet_text' => "غزل جو بند\nٻي سٽ",
                'lang' => 'sd',
                'visibility' => 1,
            ]);
        });

        $response = $this->getJson('/api/v1/couplets?lang=sd&tag=all&page=1');

        $response->assertOk();
        $slugs = collect($response->json('data'))->pluck('slug')->all();

        $this->assertSame(['standalone-two-line'], $slugs);
    }

    private function createMinimalSchema(): void
    {
        foreach (['poetry_couplets', 'poets_detail', 'poets'] as $table) {
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
    }
}
