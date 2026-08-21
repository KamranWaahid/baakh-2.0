<?php

namespace Tests\Feature;

use App\Models\Tags;
use App\Models\TopicCategory;
use App\Services\PoetryTaxonomyJsonService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PoetryTaxonomyJsonTest extends TestCase
{
    private PoetryTaxonomyJsonService $taxonomy;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'scout.driver' => 'null',
        ]);

        Schema::dropIfExists('baakh_tag_details');
        Schema::dropIfExists('baakh_tags');
        Schema::dropIfExists('topic_category_details');
        Schema::dropIfExists('topic_categories');

        Schema::create('topic_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->timestamps();
        });
        Schema::create('topic_category_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('topic_category_id');
            $table->string('lang', 5);
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('baakh_tags', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->string('type');
            $table->unsignedBigInteger('topic_category_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('baakh_tag_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tag_id');
            $table->string('lang', 5);
            $table->string('name');
            $table->timestamps();
        });

        Tags::flushEventListeners();
        TopicCategory::flushEventListeners();

        $this->taxonomy = app(PoetryTaxonomyJsonService::class);
    }

    public function test_copy_payload_includes_separate_catalogs(): void
    {
        $topic = $this->makeTopic('Love & Intimacy', 'عشق', 'love-intimacy');
        $this->makeTag('longing', 'Theme', 'تڙپ', $topic->id);

        $payload = $this->taxonomy->copyPayload('topic_categories', 'Title', 'poem text');
        $this->assertSame(PoetryTaxonomyJsonService::SCHEMA, $payload['_schema']);
        $this->assertArrayHasKey('existing_topic_categories', $payload);
        $this->assertArrayNotHasKey('existing_tags', $payload);
        $this->assertSame($topic->id, $payload['existing_topic_categories'][0]['id']);

        $tagsPayload = $this->taxonomy->copyPayload('tags', 'Title', 'poem text');
        $this->assertArrayHasKey('existing_tags', $tagsPayload);
        $this->assertArrayNotHasKey('existing_topic_categories', $tagsPayload);
        $this->assertSame('longing', $tagsPayload['existing_tags'][0]['slug']);
    }

    public function test_apply_links_existing_ids_and_does_not_duplicate(): void
    {
        $topic = $this->makeTopic('Spiritual & Mystical', 'روحاني', 'spiritual-mystical');
        $tag = $this->makeTag('faith', 'Theme', 'ايمان', $topic->id);

        $result = $this->taxonomy->apply([
            'topic_category' => ['existing_id' => $topic->id],
            'tags' => [
                'existing_ids' => [$tag->id, $tag->id],
                'create' => [
                    ['name_sd' => 'ايمان', 'name_en' => 'Faith', 'type' => 'Theme'],
                ],
            ],
        ]);

        $this->assertTrue($result['topic_category_applied']);
        $this->assertTrue($result['tags_applied']);
        $this->assertSame((string) $topic->id, $result['topic_category_id']);
        $this->assertSame([(string) $tag->id], $result['poetry_tags']);
        $this->assertSame([], $result['created']['topic_categories']);
        $this->assertSame([], $result['created']['tags']);
        $this->assertSame(1, TopicCategory::query()->count());
        $this->assertSame(1, Tags::query()->count());
    }

    public function test_apply_creates_only_when_catalog_has_no_match(): void
    {
        $existing = $this->makeTopic('Grief, Loss & Death', 'غم', 'grief-loss-death');

        $result = $this->taxonomy->apply([
            'topic_category' => [
                'create' => [
                    'name_sd' => 'نئون موضوع',
                    'name_en' => 'Dawn & Vigil',
                    'slug' => 'dawn-vigil',
                ],
            ],
            'tags' => [
                'existing_ids' => [],
                'create' => [
                    ['name_sd' => 'چاڙه', 'name_en' => 'Dawn', 'type' => 'Time Layer'],
                ],
            ],
        ]);

        $this->assertNotSame((string) $existing->id, $result['topic_category_id']);
        $this->assertSame(2, TopicCategory::query()->count());
        $this->assertSame(1, Tags::query()->count());
        $this->assertCount(1, $result['created']['topic_categories']);
        $this->assertCount(1, $result['created']['tags']);
        $this->assertSame('Dawn & Vigil', $result['created']['topic_categories'][0]['name_en']);
        $this->assertSame('Time Layer', $result['created']['tags'][0]['type']);
    }

    public function test_create_with_existing_english_name_reattaches_instead_of_insert(): void
    {
        $topic = $this->makeTopic('Love & Intimacy', 'عشق ۽ قربت', 'love-intimacy');

        $result = $this->taxonomy->apply([
            'topic_category' => [
                'create' => [
                    'name_sd' => 'عشق ۽ قربت',
                    'name_en' => 'Love & Intimacy',
                ],
            ],
        ]);

        $this->assertSame((string) $topic->id, $result['topic_category_id']);
        $this->assertSame([], $result['created']['topic_categories']);
        $this->assertSame(1, TopicCategory::query()->count());
    }

    public function test_unknown_existing_id_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->taxonomy->apply([
            'tags' => ['existing_ids' => [999]],
        ]);
    }

    private function makeTopic(string $en, string $sd, string $slug): TopicCategory
    {
        $cat = TopicCategory::create(['slug' => $slug]);
        $cat->details()->create(['lang' => 'en', 'name' => $en]);
        $cat->details()->create(['lang' => 'sd', 'name' => $sd]);

        return $cat;
    }

    private function makeTag(string $slug, string $type, string $sd, ?int $topicCategoryId): Tags
    {
        $tag = Tags::create([
            'slug' => $slug,
            'type' => $type,
            'topic_category_id' => $topicCategoryId,
        ]);
        $tag->details()->create(['lang' => 'sd', 'name' => $sd]);
        $tag->details()->create(['lang' => 'en', 'name' => ucfirst($slug)]);

        return $tag;
    }
}
