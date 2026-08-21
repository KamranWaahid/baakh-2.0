<?php

namespace App\Services;

use App\Models\TagDetail;
use App\Models\Tags;
use App\Models\TopicCategory;
use App\Support\DictionaryText;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PoetryTaxonomyJsonService
{
    public const SCHEMA = 'baakh.poetry.taxonomy.v1';

    /**
     * Compact catalog of topic categories and tags already in the database.
     *
     * @return array{topic_categories: list<array<string, mixed>>, tags: list<array<string, mixed>>, tag_types: list<string>}
     */
    public function catalog(): array
    {
        $topicCategories = TopicCategory::query()
            ->with('details')
            ->orderBy('id')
            ->get()
            ->map(fn (TopicCategory $cat) => $this->serializeTopicCategory($cat))
            ->values()
            ->all();

        $tags = Tags::query()
            ->with(['details', 'topicCategory.details'])
            ->orderBy('id')
            ->get()
            ->map(fn (Tags $tag) => $this->serializeTag($tag))
            ->values()
            ->all();

        return [
            'topic_categories' => $topicCategories,
            'tags' => $tags,
            'tag_types' => Tags::TYPES,
        ];
    }

    /**
     * @param  array{topic_category_id?: mixed, tag_ids?: mixed}  $alreadySelected
     * @return array<string, mixed>
     */
    public function copyPayload(string $kind, string $title, string $text, array $alreadySelected = []): array
    {
        $kind = $kind === 'tags' ? 'tags' : ($kind === 'topic_categories' ? 'topic_categories' : 'both');
        $catalog = $this->catalog();
        $poetry = [
            'title' => trim($title),
            'text' => Str::limit(trim($text), 4000, '…'),
        ];
        $selectedTopic = is_numeric($alreadySelected['topic_category_id'] ?? null)
            ? (int) $alreadySelected['topic_category_id']
            : null;
        $selectedTags = [];
        foreach ((array) ($alreadySelected['tag_ids'] ?? []) as $id) {
            if (is_numeric($id)) {
                $selectedTags[] = (int) $id;
            }
        }
        $selectedTags = array_values(array_unique($selectedTags));

        $base = [
            '_schema' => self::SCHEMA,
            'poetry' => $poetry,
            'already_selected' => [
                'topic_category_id' => $selectedTopic,
                'tag_ids' => $selectedTags,
            ],
        ];

        if ($kind === 'topic_categories' || $kind === 'both') {
            $base['topic_categories_prompt'] = $this->topicCategoryPrompt();
            $base['existing_topic_categories'] = $catalog['topic_categories'];
            $base['topic_category_output'] = [
                'prefer' => ['topic_category' => ['existing_id' => 0]],
                'only_if_missing' => ['topic_category' => [
                    'create' => ['name_sd' => '', 'name_en' => '', 'slug' => ''],
                ]],
            ];
        }

        if ($kind === 'tags' || $kind === 'both') {
            $base['tags_prompt'] = $this->tagsPrompt();
            $base['existing_tags'] = $catalog['tags'];
            $base['tag_types'] = $catalog['tag_types'];
            $base['tags_output'] = [
                'prefer' => ['tags' => ['existing_ids' => []]],
                'only_if_missing' => ['tags' => [
                    'create' => [['name_sd' => '', 'name_en' => '', 'type' => 'Theme']],
                ]],
            ];
        }

        return $base;
    }

    /**
     * Resolve AI JSON against the catalog. Creates only when no existing row matches.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function apply(array $payload): array
    {
        if (isset($payload['data']) && is_array($payload['data'])) {
            $payload = $payload['data'];
        }

        $createdCategories = [];
        $createdTags = [];
        $topicCategoryId = null;
        $tagIds = [];
        $topicApplied = false;
        $tagsApplied = false;

        $topicBlock = $payload['topic_category'] ?? $payload['topic_categories'] ?? null;
        if ($topicBlock !== null) {
            $topicApplied = true;
            [$topicCategoryId, $createdCategory] = $this->resolveTopicCategory($topicBlock);
            if ($createdCategory) {
                $createdCategories[] = $createdCategory;
            }
        }

        $tagsBlock = $payload['tags'] ?? null;
        if ($tagsBlock !== null) {
            $tagsApplied = true;
            [$tagIds, $createdTags] = $this->resolveTags($tagsBlock, $topicCategoryId);
        }

        return [
            'topic_category_applied' => $topicApplied,
            'tags_applied' => $tagsApplied,
            'topic_category_id' => $topicCategoryId !== null ? (string) $topicCategoryId : null,
            'topic_category' => $topicCategoryId
                ? $this->serializeTopicCategory(TopicCategory::with('details')->findOrFail($topicCategoryId))
                : null,
            'poetry_tags' => array_map('strval', $tagIds),
            'created' => [
                'topic_categories' => $createdCategories,
                'tags' => $createdTags,
            ],
        ];
    }

    /**
     * @param  mixed  $block
     * @return array{0: ?int, 1: ?array<string, mixed>}
     */
    private function resolveTopicCategory(mixed $block): array
    {
        if (is_numeric($block)) {
            $found = TopicCategory::query()->find((int) $block);
            if ($found) {
                return [(int) $found->id, null];
            }
            throw new InvalidArgumentException('Topic category id '.$block.' was not found.');
        }

        if (!is_array($block)) {
            throw new InvalidArgumentException('topic_category must be an object, id, or omitted.');
        }

        $existingId = $block['existing_id'] ?? $block['id'] ?? null;
        if (is_numeric($existingId) && (int) $existingId > 0) {
            $found = TopicCategory::query()->find((int) $existingId);
            if ($found) {
                return [(int) $found->id, null];
            }
            throw new InvalidArgumentException('Topic category id '.$existingId.' was not found. Use create only when it is missing from existing_topic_categories.');
        }

        $create = is_array($block['create'] ?? null) ? $block['create'] : $block;
        $matched = $this->findTopicCategory(
            (string) ($create['slug'] ?? $block['slug'] ?? ''),
            (string) ($create['name_sd'] ?? $create['name'] ?? $block['name_sd'] ?? $block['name'] ?? ''),
            (string) ($create['name_en'] ?? $block['name_en'] ?? '')
        );
        if ($matched) {
            return [(int) $matched->id, null];
        }

        $hasCreate = isset($block['create']) || filled($create['name_sd'] ?? $create['name'] ?? null);
        if (!$hasCreate) {
            return [null, null];
        }

        $created = $this->createTopicCategory($create);

        return [(int) $created['id'], $created];
    }

    /**
     * @param  mixed  $block
     * @return array{0: list<int>, 1: list<array<string, mixed>>}
     */
    private function resolveTags(mixed $block, ?int $topicCategoryId): array
    {
        $ids = [];
        $created = [];

        if (is_array($block) && array_is_list($block)) {
            $block = ['existing_ids' => $block];
        }

        if (!is_array($block)) {
            throw new InvalidArgumentException('tags must be an object or a list of ids.');
        }

        $existingIds = $block['existing_ids'] ?? $block['ids'] ?? [];
        if (!is_array($existingIds)) {
            $existingIds = [];
        }

        foreach ($existingIds as $rawId) {
            if (is_array($rawId)) {
                $rawId = $rawId['existing_id'] ?? $rawId['id'] ?? null;
            }
            if (!is_numeric($rawId)) {
                continue;
            }
            $tag = Tags::query()->find((int) $rawId);
            if (!$tag) {
                throw new InvalidArgumentException('Tag id '.$rawId.' was not found. Prefer existing_tags ids; use create only when missing.');
            }
            $ids[] = (int) $tag->id;
        }

        $createRows = $block['create'] ?? [];
        if (isset($createRows['name_sd']) || isset($createRows['name'])) {
            $createRows = [$createRows];
        }
        if (!is_array($createRows)) {
            $createRows = [];
        }

        foreach ($createRows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $matched = $this->findTag(
                (string) ($row['slug'] ?? ''),
                (string) ($row['name_sd'] ?? $row['name'] ?? ''),
                (string) ($row['name_en'] ?? '')
            );
            if ($matched) {
                $ids[] = (int) $matched->id;
                continue;
            }
            $made = $this->createTag($row, $topicCategoryId);
            $ids[] = (int) $made['id'];
            $created[] = $made;
        }

        $ids = array_values(array_unique($ids));

        return [$ids, $created];
    }

    private function findTopicCategory(string $slug, string $nameSd, string $nameEn): ?TopicCategory
    {
        $slug = Str::slug($slug);
        if ($slug !== '') {
            $bySlug = TopicCategory::query()->where('slug', $slug)->first();
            if ($bySlug) {
                return $bySlug;
            }
        }

        $keys = array_values(array_filter([
            $this->matchKey($nameSd),
            $this->matchKey($nameEn),
        ]));
        if ($keys === []) {
            return null;
        }

        foreach (TopicCategory::query()->with('details')->cursor() as $cat) {
            foreach ($cat->details as $detail) {
                if (in_array($this->matchKey((string) $detail->name), $keys, true)) {
                    return $cat;
                }
            }
        }

        return null;
    }

    private function findTag(string $slug, string $nameSd, string $nameEn): ?Tags
    {
        $slug = Str::slug($slug);
        if ($slug !== '') {
            $bySlug = Tags::query()->where('slug', $slug)->first();
            if ($bySlug) {
                return $bySlug;
            }
        }

        $keys = array_values(array_filter([
            $this->matchKey($nameSd),
            $this->matchKey($nameEn),
        ]));
        if ($keys === []) {
            return null;
        }

        $detailIds = TagDetail::query()
            ->get(['tag_id', 'name'])
            ->filter(fn (TagDetail $d) => in_array($this->matchKey((string) $d->name), $keys, true))
            ->pluck('tag_id')
            ->unique()
            ->all();

        if ($detailIds === []) {
            return null;
        }

        return Tags::query()->whereIn('id', $detailIds)->orderBy('id')->first();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function createTopicCategory(array $row): array
    {
        $nameSd = trim((string) ($row['name_sd'] ?? $row['name'] ?? ''));
        $nameEn = trim((string) ($row['name_en'] ?? ''));
        if ($nameSd === '' && $nameEn === '') {
            throw new InvalidArgumentException('topic_category.create needs name_sd or name_en.');
        }

        $label = $nameEn !== '' ? $nameEn : $nameSd;
        $slug = $this->uniqueSlug('topic_categories', (string) ($row['slug'] ?? $label), 'topic');

        $cat = TopicCategory::create(['slug' => $slug]);
        if ($nameSd !== '') {
            $cat->details()->create(['lang' => 'sd', 'name' => $nameSd]);
        }
        if ($nameEn !== '') {
            $cat->details()->create(['lang' => 'en', 'name' => $nameEn]);
        }

        return $this->serializeTopicCategory($cat->fresh('details'));
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function createTag(array $row, ?int $fallbackTopicCategoryId): array
    {
        $nameSd = trim((string) ($row['name_sd'] ?? $row['name'] ?? ''));
        $nameEn = trim((string) ($row['name_en'] ?? ''));
        if ($nameSd === '' && $nameEn === '') {
            throw new InvalidArgumentException('tags.create needs name_sd or name_en.');
        }

        $type = (string) ($row['type'] ?? 'Theme');
        if (!in_array($type, Tags::TYPES, true)) {
            $type = 'Theme';
        }

        $topicCategoryId = is_numeric($row['topic_category_id'] ?? null)
            ? (int) $row['topic_category_id']
            : $fallbackTopicCategoryId;
        if ($topicCategoryId && !TopicCategory::query()->whereKey($topicCategoryId)->exists()) {
            $topicCategoryId = $fallbackTopicCategoryId;
        }

        $label = $nameEn !== '' ? $nameEn : $nameSd;
        $slug = $this->uniqueSlug('baakh_tags', (string) ($row['slug'] ?? $label), 'tag');

        $tag = Tags::create([
            'slug' => $slug,
            'type' => $type,
            'topic_category_id' => $topicCategoryId,
        ]);
        if ($nameSd !== '') {
            $tag->details()->create(['lang' => 'sd', 'name' => $nameSd]);
        }
        if ($nameEn !== '') {
            $tag->details()->create(['lang' => 'en', 'name' => $nameEn]);
        }

        return $this->serializeTag($tag->fresh(['details', 'topicCategory.details']));
    }

    private function uniqueSlug(string $table, string $preferred, string $prefix): string
    {
        $base = Str::slug($preferred);
        if ($base === '') {
            $base = $prefix.'-'.substr(sha1($preferred !== '' ? $preferred : uniqid('', true)), 0, 8);
        }

        $slug = $base;
        $i = 2;
        while (DB::table($table)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    private function matchKey(string $text): string
    {
        return DictionaryText::lookupBase($text);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTopicCategory(TopicCategory $cat): array
    {
        $sd = $cat->details->firstWhere('lang', 'sd')?->name;
        $en = $cat->details->firstWhere('lang', 'en')?->name;

        return [
            'id' => (int) $cat->id,
            'slug' => $cat->slug,
            'name_sd' => $sd ?: ($en ?: $cat->slug),
            'name_en' => $en ?: null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTag(Tags $tag): array
    {
        $sd = $tag->details->firstWhere('lang', 'sd')?->name;
        $en = $tag->details->firstWhere('lang', 'en')?->name;
        $topic = $tag->topicCategory;

        return [
            'id' => (int) $tag->id,
            'slug' => $tag->slug,
            'type' => $tag->type,
            'name_sd' => $sd ?: ($en ?: $tag->slug),
            'name_en' => $en ?: null,
            'topic_category_id' => $tag->topic_category_id ? (int) $tag->topic_category_id : null,
            'topic_category' => $topic?->details->firstWhere('lang', 'sd')?->name
                ?? $topic?->details->first()?->name
                ?? $topic?->slug,
        ];
    }

    private function topicCategoryPrompt(): string
    {
        return implode(' ', [
            'Pick ONE topic category for this Sindhi poem.',
            'RULE 1 — Use an id from existing_topic_categories. Never invent ids.',
            'RULE 2 — Create only if nothing in the catalog matches the poem (including Sindhi/English name).',
            'Return ONLY JSON. Prefer {"topic_category":{"existing_id":N}}.',
            'If missing, {"topic_category":{"create":{"name_sd":"...","name_en":"...","slug":"latin-slug"}}}.',
        ]);
    }

    private function tagsPrompt(): string
    {
        return implode(' ', [
            'Pick 3–8 tags for this Sindhi poem.',
            'RULE 1 — Prefer existing_ids from existing_tags. Never invent ids. Do not duplicate.',
            'RULE 2 — Create a tag only if no catalog name/slug matches.',
            'type must be one of tag_types.',
            'Return ONLY JSON: {"tags":{"existing_ids":[1,2],"create":[{"name_sd":"...","name_en":"...","type":"Theme"}]}}.',
            'Omit create when every needed tag already exists.',
        ]);
    }
}
