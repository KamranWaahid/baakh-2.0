<?php

namespace Tests\Feature;

use Tests\TestCase;

class NormalizeSeoUrlsTest extends TestCase
{
    public function test_legacy_poets_index_redirects_to_sd(): void
    {
        $response = $this->get('/poets');

        $response->assertRedirect('/sd/poets');
        $response->assertStatus(301);
    }

    public function test_legacy_couplets_index_redirects_to_sd(): void
    {
        $response = $this->get('/couplets');

        $response->assertRedirect('/sd/couplets');
        $response->assertStatus(301);
    }

    public function test_lang_query_param_is_stripped_into_path_prefix(): void
    {
        $response = $this->get('/poets?lang=en');

        $response->assertRedirect('/en/poets');
        $response->assertStatus(301);
    }

    public function test_double_slash_path_collapses(): void
    {
        $response = $this->get('/sd/poet/test//poem');

        $response->assertRedirect('/sd/poet/test/poem');
        $response->assertStatus(301);
    }

    public function test_og_image_missing_slug_does_not_500(): void
    {
        $response = $this->get('/og-image/poetry/this-slug-does-not-exist-xyz');

        $this->assertNotEquals(500, $response->status());
        $response->assertHeader('Content-Type', 'image/png');
    }

    public function test_periods_plural_redirects_to_singular(): void
    {
        $response = $this->get('/periods');

        $response->assertRedirect('/sd/period');
        $response->assertStatus(301);
    }

    public function test_lang_periods_plural_redirects_to_singular(): void
    {
        $response = $this->get('/sd/periods');

        $response->assertRedirect('/sd/period');
        $response->assertStatus(301);
    }

    public function test_poet_seo_survives_broken_birth_place_chain(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('poets')) {
            $this->markTestSkipped('poets table unavailable');
        }
        if (!\Illuminate\Support\Facades\Schema::hasTable('poets_detail')) {
            $this->markTestSkipped('poets_detail table unavailable');
        }

        $poet = \App\Models\Poets::query()->create([
            'poet_slug' => 'seo-nullsafe-poet-' . uniqid(),
            'poet_pic' => '/assets/images/poets/default.webp',
            'visibility' => 1,
        ]);

        \App\Models\PoetsDetail::query()->create([
            'poet_id' => $poet->id,
            'poet_name' => 'Nullsafe Poet',
            'poet_laqab' => 'Nullsafe',
            'poet_bio' => 'Bio',
            'birth_place' => 999999001, // missing city → must not 500
            'death_place' => null,
            'lang' => 'sd',
        ]);

        $response = $this->get('/sd/poet/' . $poet->poet_slug);

        $this->assertNotEquals(500, $response->status());
        $response->assertOk();
    }

    public function test_missing_poet_returns_http_404(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('poets')) {
            \Illuminate\Support\Facades\Schema::create('poets', function ($table) {
                $table->id();
                $table->string('poet_slug')->unique();
                $table->string('poet_pic')->nullable();
                $table->boolean('visibility')->default(1);
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!\Illuminate\Support\Facades\Schema::hasTable('poetry')) {
            \Illuminate\Support\Facades\Schema::create('poetry', function ($table) {
                $table->id();
                $table->string('poetry_slug')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        $response = $this->get('/sd/poet/definitely-missing-poet-slug-xyz');

        $response->assertStatus(404);
    }
}
