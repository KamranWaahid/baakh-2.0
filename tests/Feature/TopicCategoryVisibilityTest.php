<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TopicCategoryVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_explore_topics_only_returns_categories_with_visible_poetry_or_couplets(): void
    {
        Storage::fake('public');

        $poetryCategoryId = $this->createTopicCategory('direct-poetry', 'Direct Poetry');
        $coupletCategoryId = $this->createTopicCategory('direct-couplet', 'Direct Couplet');
        $hiddenCategoryId = $this->createTopicCategory('hidden-topic', 'Hidden Topic');
        $this->createTopicCategory('empty-topic', 'Empty Topic');

        DB::table('poetry_main')->insert([
            'topic_category_id' => $poetryCategoryId,
            'poetry_slug' => 'visible-poetry',
            'poetry_title' => 'Visible Poetry',
            'visibility' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('poetry_couplets')->insert([
            'topic_category_id' => $coupletCategoryId,
            'couplet_slug' => 'visible-couplet',
            'couplet_text' => "First line\nSecond line",
            'lang' => 'sd',
            'visibility' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('poetry_main')->insert([
            'topic_category_id' => $hiddenCategoryId,
            'poetry_slug' => 'hidden-poetry',
            'poetry_title' => 'Hidden Poetry',
            'visibility' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->withHeader('Accept-Language', 'sd')
            ->getJson('/api/v1/explore-topics');

        $response->assertOk();

        $slugs = collect($response->json('categories'))->pluck('slug');

        $this->assertTrue($slugs->contains('direct-poetry'));
        $this->assertTrue($slugs->contains('direct-couplet'));
        $this->assertFalse($slugs->contains('hidden-topic'));
        $this->assertFalse($slugs->contains('empty-topic'));
    }

    public function test_sidebar_topics_only_returns_categories_with_visible_poetry_or_couplets(): void
    {
        $poetryCategoryId = $this->createTopicCategory('sidebar-poetry', 'Sidebar Poetry');
        $coupletCategoryId = $this->createTopicCategory('sidebar-couplet', 'Sidebar Couplet');
        $hiddenCategoryId = $this->createTopicCategory('sidebar-hidden', 'Sidebar Hidden');
        $this->createTopicCategory('sidebar-empty', 'Sidebar Empty');

        DB::table('poetry_main')->insert([
            'topic_category_id' => $poetryCategoryId,
            'poetry_slug' => 'sidebar-visible-poetry',
            'poetry_title' => 'Sidebar Visible Poetry',
            'visibility' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('poetry_couplets')->insert([
            'topic_category_id' => $coupletCategoryId,
            'couplet_slug' => 'sidebar-visible-couplet',
            'couplet_text' => "First line\nSecond line",
            'lang' => 'sd',
            'visibility' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('poetry_couplets')->insert([
            'topic_category_id' => $hiddenCategoryId,
            'couplet_slug' => 'sidebar-hidden-couplet',
            'couplet_text' => "First line\nSecond line",
            'lang' => 'sd',
            'visibility' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->withHeader('Accept-Language', 'sd')
            ->getJson('/api/v1/sidebar/topics');

        $response->assertOk();

        $slugs = collect($response->json())->pluck('slug');

        $this->assertTrue($slugs->contains('sidebar-poetry'));
        $this->assertTrue($slugs->contains('sidebar-couplet'));
        $this->assertFalse($slugs->contains('sidebar-hidden'));
        $this->assertFalse($slugs->contains('sidebar-empty'));
    }

    private function createTopicCategory(string $slug, string $name): int
    {
        $id = DB::table('topic_categories')->insertGetId([
            'slug' => $slug,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('topic_category_details')->insert([
            'topic_category_id' => $id,
            'lang' => 'sd',
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}
