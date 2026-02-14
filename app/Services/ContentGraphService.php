<?php

namespace App\Services;

use App\Models\Categories;
use App\Models\ContentGraph;
use App\Models\Couplets;
use App\Models\MokhiiPageMeta;
use App\Models\Poetry;
use App\Models\Poets;
use App\Models\Tags;
use App\Models\TopicCategory;
use App\Models\UserLikes;
use App\Models\UserBookmark;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContentGraphService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('app.url'), '/');
    }

    /**
     * Rebuild the entire knowledge graph.
     */
    public function buildGraph(): array
    {
        $stats = ['edges_created' => 0, 'pages_computed' => 0];

        // Clear tables BEFORE transaction (TRUNCATE causes implicit commit in MySQL)
        ContentGraph::query()->delete();
        MokhiiPageMeta::query()->delete();

        DB::beginTransaction();
        try {
            // Build relationships
            $stats['edges_created'] += $this->buildPoetRelations();
            $stats['edges_created'] += $this->buildPoetryRelations();
            $stats['edges_created'] += $this->buildTagRelations();
            $stats['edges_created'] += $this->buildCategoryRelations();

            // Compute page meta
            $stats['pages_computed'] += $this->computePageMeta();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Mokhii graph build failed: ' . $e->getMessage());
            throw $e;
        }

        return $stats;
    }

    /**
     * Poet → Poetry relationships (author_of).
     */
    private function buildPoetRelations(): int
    {
        $count = 0;

        // Get poet_id → poetry count for weight calculation
        $poetryCounts = Poetry::selectRaw('poet_id, COUNT(*) as cnt')
            ->groupBy('poet_id')
            ->pluck('cnt', 'poet_id');

        $maxCount = $poetryCounts->max() ?: 1;

        $poets = Poets::select('id')->get();

        foreach ($poets as $poet) {
            $poetryCount = $poetryCounts[$poet->id] ?? 0;
            if ($poetryCount === 0)
                continue;

            $weight = min($poetryCount / $maxCount, 1.0);

            // Poet → each poetry entry (batched)
            $poetryIds = Poetry::where('poet_id', $poet->id)->pluck('id');

            $batch = [];
            foreach ($poetryIds as $poetryId) {
                $batch[] = [
                    'source_id' => $poet->id,
                    'source_type' => 'poet',
                    'target_id' => $poetryId,
                    'target_type' => 'poetry',
                    'relation_type' => 'author_of',
                    'relation_weight' => round($weight, 3),
                    'semantic_score' => 1.000,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $count++;
            }

            // Insert in chunks to avoid memory issues
            foreach (array_chunk($batch, 500) as $chunk) {
                ContentGraph::insert($chunk);
            }
        }

        return $count;
    }

    /**
     * Poetry → Category relationships (belongs_to).
     */
    private function buildPoetryRelations(): int
    {
        $count = 0;

        $poetry = Poetry::select('id', 'category_id', 'topic_category_id')
            ->whereNotNull('category_id')
            ->cursor();

        $batch = [];
        foreach ($poetry as $poem) {
            // Poetry → Category
            $batch[] = [
                'source_id' => $poem->id,
                'source_type' => 'poetry',
                'target_id' => $poem->category_id,
                'target_type' => 'category',
                'relation_type' => 'belongs_to',
                'relation_weight' => 0.500,
                'semantic_score' => 0.800,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $count++;

            // Poetry → TopicCategory (if exists)
            if ($poem->topic_category_id) {
                $batch[] = [
                    'source_id' => $poem->id,
                    'source_type' => 'poetry',
                    'target_id' => $poem->topic_category_id,
                    'target_type' => 'topic_category',
                    'relation_type' => 'belongs_to',
                    'relation_weight' => 0.600,
                    'semantic_score' => 0.900,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $count++;
            }

            if (count($batch) >= 500) {
                ContentGraph::insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            ContentGraph::insert($batch);
        }

        return $count;
    }

    /**
     * Tag relationships (tagged_with).
     */
    private function buildTagRelations(): int
    {
        $count = 0;

        // Tags → TopicCategory
        $tags = Tags::select('id', 'topic_category_id')
            ->whereNotNull('topic_category_id')
            ->get();

        $batch = [];
        foreach ($tags as $tag) {
            $batch[] = [
                'source_id' => $tag->id,
                'source_type' => 'tag',
                'target_id' => $tag->topic_category_id,
                'target_type' => 'topic_category',
                'relation_type' => 'belongs_to',
                'relation_weight' => 0.700,
                'semantic_score' => 0.850,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $count++;
        }

        if (!empty($batch)) {
            foreach (array_chunk($batch, 500) as $chunk) {
                ContentGraph::insert($chunk);
            }
        }

        return $count;
    }

    /**
     * Category relationships.
     */
    private function buildCategoryRelations(): int
    {
        // Categories are top-level entities — no parent needed
        // But we connect them to poets who have poetry in them
        $count = 0;

        $catPoets = Poetry::selectRaw('category_id, poet_id')
            ->distinct()
            ->whereNotNull('category_id')
            ->cursor();

        $batch = [];
        foreach ($catPoets as $cp) {
            $batch[] = [
                'source_id' => $cp->poet_id,
                'source_type' => 'poet',
                'target_id' => $cp->category_id,
                'target_type' => 'category',
                'relation_type' => 'writes_in',
                'relation_weight' => 0.400,
                'semantic_score' => 0.600,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $count++;

            if (count($batch) >= 500) {
                ContentGraph::insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            ContentGraph::insert($batch);
        }

        return $count;
    }

    /**
     * Compute page meta (priority scores) for all content.
     */
    private function computePageMeta(): int
    {
        $count = 0;
        $now = now();
        $maxAge = 365; // days for freshness calculation

        // ── Poets ───────────────────────────────────
        $poets = Poets::select('id', 'poet_slug', 'updated_at')->get();
        foreach ($poets as $poet) {
            $url = "{$this->baseUrl}/sd/poet/{$poet->poet_slug}";
            $freshness = $this->freshness($poet->updated_at, $maxAge);
            $linkCount = ContentGraph::from('poet', $poet->id)->count();
            $graphWeight = min($linkCount / 100, 1.0);
            $engagement = $this->poetEngagement($poet->id);

            $meta = MokhiiPageMeta::create([
                'url' => $url,
                'entity_id' => $poet->id,
                'entity_type' => 'poet',
                'freshness_score' => $freshness,
                'internal_link_count' => $linkCount,
                'graph_weight' => $graphWeight,
                'engagement_score' => $engagement,
                'computed_at' => $now,
            ]);
            $meta->priority_score = $meta->computePriority();
            $meta->save();
            $count++;
        }

        // ── Categories ──────────────────────────────
        $categories = Categories::select('id', 'slug', 'updated_at')->get();
        foreach ($categories as $cat) {
            $url = "{$this->baseUrl}/sd/{$cat->slug}";
            $freshness = $this->freshness($cat->updated_at, $maxAge);
            $linkCount = ContentGraph::to('category', $cat->id)->count();
            $graphWeight = min($linkCount / 200, 1.0);

            $meta = MokhiiPageMeta::create([
                'url' => $url,
                'entity_id' => $cat->id,
                'entity_type' => 'category',
                'freshness_score' => $freshness,
                'internal_link_count' => $linkCount,
                'graph_weight' => $graphWeight,
                'engagement_score' => 0.5,
                'computed_at' => $now,
            ]);
            $meta->priority_score = $meta->computePriority();
            $meta->save();
            $count++;
        }

        // ── Tags ────────────────────────────────────
        $tags = Tags::select('id', 'slug', 'updated_at')->get();
        foreach ($tags as $tag) {
            $url = "{$this->baseUrl}/sd/tag/{$tag->slug}";
            $freshness = $this->freshness($tag->updated_at, $maxAge);
            $linkCount = ContentGraph::from('tag', $tag->id)->count();
            $graphWeight = min($linkCount / 50, 1.0);

            $meta = MokhiiPageMeta::create([
                'url' => $url,
                'entity_id' => $tag->id,
                'entity_type' => 'tag',
                'freshness_score' => $freshness,
                'internal_link_count' => $linkCount,
                'graph_weight' => $graphWeight,
                'engagement_score' => 0.3,
                'computed_at' => $now,
            ]);
            $meta->priority_score = $meta->computePriority();
            $meta->save();
            $count++;
        }

        // ── Topic Categories ────────────────────────
        $topics = TopicCategory::select('id', 'slug', 'updated_at')->get();
        foreach ($topics as $topic) {
            $url = "{$this->baseUrl}/sd/topic/{$topic->slug}";
            $freshness = $this->freshness($topic->updated_at, $maxAge);
            $linkCount = ContentGraph::to('topic_category', $topic->id)->count();
            $graphWeight = min($linkCount / 100, 1.0);

            $meta = MokhiiPageMeta::create([
                'url' => $url,
                'entity_id' => $topic->id,
                'entity_type' => 'topic_category',
                'freshness_score' => $freshness,
                'internal_link_count' => $linkCount,
                'graph_weight' => $graphWeight,
                'engagement_score' => 0.4,
                'computed_at' => $now,
            ]);
            $meta->priority_score = $meta->computePriority();
            $meta->save();
            $count++;
        }

        return $count;
    }

    /**
     * Calculate freshness score (0-1) based on days since last update.
     */
    private function freshness($updatedAt, int $maxAge): float
    {
        if (!$updatedAt)
            return 0.0;

        $daysOld = now()->diffInDays($updatedAt);
        return round(max(0, 1.0 - ($daysOld / $maxAge)), 3);
    }

    /**
     * Calculate engagement score for a poet (0-1).
     */
    private function poetEngagement(int $poetId): float
    {
        $poetryIds = Poetry::where('poet_id', $poetId)->pluck('id');
        if ($poetryIds->isEmpty())
            return 0.0;

        $likes = 0;
        $bookmarks = 0;

        if (\Illuminate\Support\Facades\Schema::hasTable('user_likes')) {
            $likes = DB::table('user_likes')
                ->where('likeable_type', 'App\\Models\\Poetry')
                ->whereIn('likeable_id', $poetryIds)
                ->count();
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('user_bookmarks')) {
            $bookmarks = DB::table('user_bookmarks')
                ->where('bookmarkable_type', 'App\\Models\\Poetry')
                ->whereIn('bookmarkable_id', $poetryIds)
                ->count();
        }

        $total = $likes + $bookmarks;
        return round(min($total / 100, 1.0), 3);
    }
}
