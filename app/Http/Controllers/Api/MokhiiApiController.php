<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categories;
use App\Models\ContentGraph;
use App\Models\Couplets;
use App\Models\MokhiiPageMeta;
use App\Models\Poetry;
use App\Models\Poets;
use App\Models\PoetsDetail;
use App\Models\SeoAudit;
use App\Models\Tags;
use App\Models\TopicCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MokhiiApiController extends Controller
{
    /**
     * GET /api/mokhii/health
     * Overall SEO health summary.
     */
    public function health(): JsonResponse
    {
        $totalAudits = SeoAudit::count();
        $avgScore = SeoAudit::avg('score') ?? 0;

        // Issue breakdown
        $auditsWithIssues = SeoAudit::whereNotNull('issues')->get();
        $issueBreakdown = [];
        foreach ($auditsWithIssues as $audit) {
            foreach ($audit->issues as $issue) {
                $issueBreakdown[$issue] = ($issueBreakdown[$issue] ?? 0) + 1;
            }
        }
        arsort($issueBreakdown);

        // Score distribution
        $distribution = [
            'excellent' => SeoAudit::where('score', '>=', 90)->count(),
            'good' => SeoAudit::whereBetween('score', [70, 89.9])->count(),
            'fair' => SeoAudit::whereBetween('score', [50, 69.9])->count(),
            'poor' => SeoAudit::where('score', '<', 50)->count(),
        ];

        // Graph stats
        $graphEdges = ContentGraph::count();
        $graphNodes = DB::table('content_graph')
            ->selectRaw('COUNT(DISTINCT CONCAT(source_type, ":", source_id)) + COUNT(DISTINCT CONCAT(target_type, ":", target_id)) as total')
            ->value('total') ?? 0;

        $pagesComputed = MokhiiPageMeta::count();
        $avgPriority = MokhiiPageMeta::avg('priority_score') ?? 0;

        $lastCrawl = SeoAudit::max('crawled_at');

        return response()->json([
            'system' => 'Mokhii SEO Engine v1.0',
            'health' => [
                'overall_score' => round($avgScore, 1),
                'pages_audited' => $totalAudits,
                'score_distribution' => $distribution,
                'last_crawl' => $lastCrawl,
            ],
            'issues' => [
                'total_issues' => array_sum($issueBreakdown),
                'breakdown' => $issueBreakdown,
                'critical_pages' => SeoAudit::critical()->count(),
            ],
            'knowledge_graph' => [
                'edges' => $graphEdges,
                'unique_nodes' => $graphNodes,
                'pages_computed' => $pagesComputed,
                'avg_priority' => round($avgPriority, 4),
            ],
        ]);
    }

    /**
     * GET /api/mokhii/context/{slug}
     * Page context for a given slug.
     */
    public function context(string $slug): JsonResponse
    {
        $baseUrl = rtrim(config('app.url'), '/');

        // Try to find the entity by slug
        $entity = $this->resolveEntity($slug);
        if (!$entity) {
            return response()->json(['error' => 'Entity not found', 'slug' => $slug], 404);
        }

        $type = $entity['type'];
        $model = $entity['model'];
        $url = $entity['url'];

        // Get page meta
        $pageMeta = MokhiiPageMeta::where('entity_type', $type)
            ->where('entity_id', $model->id)
            ->first();

        // Get related entities from graph
        $graphRelations = ContentGraph::where(function ($q) use ($type, $model) {
            $q->where('source_type', $type)->where('source_id', $model->id);
        })
            ->orWhere(function ($q) use ($type, $model) {
                $q->where('target_type', $type)->where('target_id', $model->id);
            })
            ->get()
            ->map(fn($edge) => [
                'related_type' => $edge->source_id == $model->id && $edge->source_type == $type
                    ? $edge->target_type : $edge->source_type,
                'related_id' => $edge->source_id == $model->id && $edge->source_type == $type
                    ? $edge->target_id : $edge->source_id,
                'relation' => $edge->relation_type,
                'weight' => $edge->relation_weight,
            ])
            ->groupBy('relation')
            ->map(fn($group) => $group->take(10)->values());

        // Get SEO audit
        $audit = SeoAudit::forUrl($url)->first();

        // Build schema block
        $schema = $this->buildSchemaBlock($type, $model, $url);

        return response()->json([
            'page_type' => $type,
            'language' => 'sd',
            'alternate_language' => 'en',
            'url' => $url,
            'last_modified' => $model->updated_at?->toW3cString(),
            'priority_score' => $pageMeta?->priority_score ?? 0.5,
            'seo_score' => $audit?->score ?? null,
            'related_entities' => $graphRelations,
            'internal_links' => $pageMeta?->internal_link_count ?? 0,
            'schema_block' => $schema,
        ]);
    }

    /**
     * GET /api/mokhii/graph/{type}/{id}
     * Knowledge graph neighbors for an entity.
     */
    public function graph(string $type, int $id): JsonResponse
    {
        $outgoing = ContentGraph::from($type, $id)
            ->select('target_id', 'target_type', 'relation_type', 'relation_weight', 'semantic_score')
            ->orderByDesc('relation_weight')
            ->limit(50)
            ->get();

        $incoming = ContentGraph::to($type, $id)
            ->select('source_id', 'source_type', 'relation_type', 'relation_weight', 'semantic_score')
            ->orderByDesc('relation_weight')
            ->limit(50)
            ->get();

        return response()->json([
            'entity' => ['type' => $type, 'id' => $id],
            'outgoing' => $outgoing,
            'incoming' => $incoming,
            'stats' => [
                'outgoing_count' => $outgoing->count(),
                'incoming_count' => $incoming->count(),
                'avg_weight' => round($outgoing->merge($incoming)->avg('relation_weight') ?? 0, 3),
            ],
        ]);
    }

    /**
     * GET /api/mokhii/schema/{slug}
     * Full JSON-LD schema for a page.
     */
    public function schema(string $slug): JsonResponse
    {
        $entity = $this->resolveEntity($slug);
        if (!$entity) {
            return response()->json(['error' => 'Entity not found'], 404);
        }

        $schema = $this->buildSchemaBlock($entity['type'], $entity['model'], $entity['url']);

        return response()->json($schema);
    }

    /**
     * GET /api/mokhii/cluster/{topic}
     * Topic cluster: all entities, authority score, depth.
     */
    public function cluster(string $topic): JsonResponse
    {
        // Try to find topic category
        $topicCat = TopicCategory::where('slug', $topic)->first();
        if (!$topicCat) {
            return response()->json(['error' => 'Topic not found'], 404);
        }

        // Get all poetry in this topic
        $poetryInTopic = Poetry::where('topic_category_id', $topicCat->id)
            ->select('id', 'poetry_slug', 'poet_id', 'category_id', 'updated_at')
            ->get();

        // Get all tags in this topic
        $tagsInTopic = Tags::where('topic_category_id', $topicCat->id)
            ->select('id', 'slug', 'tag_name')
            ->get();

        // Get unique poets
        $poetIds = $poetryInTopic->pluck('poet_id')->unique();
        $poets = Poets::whereIn('id', $poetIds)
            ->with('details:id,poet_id,poet_name,poet_laqab')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'slug' => $p->poet_slug,
                'name' => $p->details?->poet_laqab ?? $p->poet_slug,
            ]);

        // Cluster authority = avg graph weight of all edges involving this topic
        $clusterEdges = ContentGraph::where(function ($q) use ($topicCat) {
            $q->where('target_type', 'topic_category')->where('target_id', $topicCat->id);
        })
            ->orWhere(function ($q) use ($topicCat) {
                $q->where('source_type', 'topic_category')->where('source_id', $topicCat->id);
            })
            ->get();

        $authority = $clusterEdges->avg('relation_weight') ?? 0;

        return response()->json([
            'topic' => [
                'id' => $topicCat->id,
                'slug' => $topicCat->slug,
                'name' => $topicCat->name ?? $topicCat->slug,
            ],
            'authority_score' => round($authority, 3),
            'depth' => [
                'poetry_count' => $poetryInTopic->count(),
                'poet_count' => $poets->count(),
                'tag_count' => $tagsInTopic->count(),
                'edge_count' => $clusterEdges->count(),
            ],
            'poets' => $poets,
            'tags' => $tagsInTopic->map(fn($t) => ['id' => $t->id, 'slug' => $t->slug, 'name' => $t->tag_name]),
        ]);
    }

    /**
     * GET /api/mokhii/audits
     * Recent SEO audit results.
     */
    public function audits(Request $request): JsonResponse
    {
        $query = SeoAudit::query()->orderByDesc('crawled_at');

        if ($request->has('status')) {
            if ($request->status === 'critical') {
                $query->where('score', '<', 50);
            } elseif ($request->status === 'warning') {
                $query->whereBetween('score', [50, 69.9]);
            }
        }

        if ($request->has('issue')) {
            $query->whereJsonContains('issues', $request->issue);
        }

        $audits = $query->paginate($request->get('per_page', 25));

        return response()->json($audits);
    }

    // ─── Helpers ────────────────────────────────────

    /**
     * Resolve a slug to an entity (poet, category, tag, or topic).
     */
    private function resolveEntity(string $slug): ?array
    {
        $baseUrl = rtrim(config('app.url'), '/');

        // Try poet
        $poet = Poets::where('poet_slug', $slug)->first();
        if ($poet) {
            return [
                'type' => 'poet',
                'model' => $poet,
                'url' => "{$baseUrl}/sd/poet/{$slug}",
            ];
        }

        // Try category
        $cat = Categories::where('slug', $slug)->first();
        if ($cat) {
            return [
                'type' => 'category',
                'model' => $cat,
                'url' => "{$baseUrl}/sd/{$slug}",
            ];
        }

        // Try tag
        $tag = Tags::where('slug', $slug)->first();
        if ($tag) {
            return [
                'type' => 'tag',
                'model' => $tag,
                'url' => "{$baseUrl}/sd/tag/{$slug}",
            ];
        }

        // Try topic
        $topic = TopicCategory::where('slug', $slug)->first();
        if ($topic) {
            return [
                'type' => 'topic_category',
                'model' => $topic,
                'url' => "{$baseUrl}/sd/topic/{$slug}",
            ];
        }

        return null;
    }

    /**
     * Build JSON-LD schema block for an entity.
     */
    private function buildSchemaBlock(string $type, $model, string $url): array
    {
        $baseUrl = rtrim(config('app.url'), '/');

        switch ($type) {
            case 'poet':
                $details = PoetsDetail::where('poet_id', $model->id)->first();
                return [
                    '@context' => 'https://schema.org',
                    '@type' => 'Person',
                    'name' => $details?->poet_laqab ?? $model->poet_slug,
                    'alternateName' => $details?->poet_name ?? null,
                    'url' => $url,
                    'image' => $model->poet_pic ? asset($model->poet_pic) : null,
                    'birthDate' => $model->date_of_birth,
                    'deathDate' => $model->date_of_death,
                    'description' => $details?->poet_bio ? strip_tags(mb_substr($details->poet_bio, 0, 160)) : null,
                    'knowsAbout' => 'Sindhi Poetry',
                ];

            case 'category':
                return [
                    '@context' => 'https://schema.org',
                    '@type' => 'CollectionPage',
                    'name' => $model->category_name ?? $model->slug,
                    'url' => $url,
                ];

            case 'tag':
                return [
                    '@context' => 'https://schema.org',
                    '@type' => 'CollectionPage',
                    'name' => $model->tag_name ?? $model->slug,
                    'url' => $url,
                    'about' => $model->tag_name ?? $model->slug,
                ];

            case 'topic_category':
                return [
                    '@context' => 'https://schema.org',
                    '@type' => 'CollectionPage',
                    'name' => $model->name ?? $model->slug,
                    'url' => $url,
                ];

            default:
                return [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebPage',
                    'url' => $url,
                ];
        }
    }
}
