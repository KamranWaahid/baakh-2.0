<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentGraph;
use App\Models\MokhiiPageMeta;
use App\Models\SeoAudit;
use App\Services\ContentGraphService;
use App\Services\MokhiiResolverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class MokhiiDashboardController extends Controller
{
    /**
     * GET /api/admin/mokhii/dashboard
     * Returns everything the admin dashboard needs.
     */
    public function index(): JsonResponse
    {
        // === Health Overview ===
        $totalAudits = SeoAudit::count();
        $avgScore = round(SeoAudit::avg('score') ?? 0, 1);
        $lastCrawl = SeoAudit::max('crawled_at');

        // Score distribution
        $distribution = [
            'excellent' => SeoAudit::where('score', '>=', 90)->count(),
            'good' => SeoAudit::whereBetween('score', [70, 89.9])->count(),
            'fair' => SeoAudit::whereBetween('score', [50, 69.9])->count(),
            'poor' => SeoAudit::where('score', '<', 50)->count(),
        ];

        // === Issue Breakdown ===
        $auditsWithIssues = SeoAudit::whereNotNull('issues')->get();
        $issueBreakdown = [];
        foreach ($auditsWithIssues as $audit) {
            if (!is_array($audit->issues))
                continue;
            foreach ($audit->issues as $issue) {
                $issueBreakdown[$issue] = ($issueBreakdown[$issue] ?? 0) + 1;
            }
        }
        arsort($issueBreakdown);

        // === Top Issues (worst-scoring pages) ===
        $worstPages = SeoAudit::orderBy('score')
            ->take(10)
            ->get(['url', 'score', 'status_code', 'issues', 'response_time_ms', 'crawled_at']);

        // === Recent Crawls ===
        $recentCrawls = SeoAudit::orderByDesc('crawled_at')
            ->take(15)
            ->get(['url', 'score', 'status_code', 'response_time_ms', 'issues', 'crawled_at']);

        // === Knowledge Graph Stats ===
        $graphEdges = ContentGraph::count();
        $edgesByType = ContentGraph::selectRaw('relation_type, COUNT(*) as cnt')
            ->groupBy('relation_type')
            ->pluck('cnt', 'relation_type');

        $nodesByType = DB::table('content_graph')
            ->selectRaw('source_type as type, COUNT(DISTINCT source_id) as cnt')
            ->groupBy('source_type')
            ->pluck('cnt', 'type');

        // === Page Priority Distribution ===
        $pagesComputed = MokhiiPageMeta::count();
        $avgPriority = round(MokhiiPageMeta::avg('priority_score') ?? 0, 4);

        $priorityBuckets = [
            'high' => MokhiiPageMeta::where('priority_score', '>=', 0.7)->count(),
            'medium' => MokhiiPageMeta::whereBetween('priority_score', [0.4, 0.699])->count(),
            'low' => MokhiiPageMeta::where('priority_score', '<', 0.4)->count(),
        ];

        // === Top Priority Pages ===
        $topPriority = MokhiiPageMeta::orderByDesc('priority_score')
            ->take(10)
            ->get(['url', 'entity_type', 'priority_score', 'graph_weight', 'freshness_score', 'engagement_score']);

        // === Fixes Stats ===
        $fixedPages = MokhiiPageMeta::whereNotNull('mokhii_fixes')->count();

        return response()->json([
            'health' => [
                'overall_score' => $avgScore,
                'pages_audited' => $totalAudits,
                'last_crawl' => $lastCrawl,
                'score_distribution' => $distribution,
            ],
            'issues' => [
                'breakdown' => $issueBreakdown,
                'worst_pages' => $worstPages,
            ],
            'recent_crawls' => $recentCrawls,
            'knowledge_graph' => [
                'total_edges' => $graphEdges,
                'edges_by_type' => $edgesByType,
                'nodes_by_type' => $nodesByType,
            ],
            'priorities' => [
                'pages_computed' => $pagesComputed,
                'avg_priority' => $avgPriority,
                'distribution' => $priorityBuckets,
                'top_pages' => $topPriority,
            ],
            'fixes' => [
                'pages_fixed' => $fixedPages,
            ],
        ]);
    }

    /**
     * POST /api/admin/mokhii/crawl
     * Trigger a sync crawl (runs immediately).
     */
    public function triggerCrawl(): JsonResponse
    {
        Artisan::call('mokhii:crawl', ['--sync' => true, '--limit' => 50]);

        return response()->json([
            'status' => 'completed',
            'message' => 'Crawled up to 50 URLs.',
        ]);
    }

    /**
     * POST /api/admin/mokhii/compute
     * Trigger knowledge graph rebuild.
     */
    public function triggerCompute(ContentGraphService $service): JsonResponse
    {
        $stats = $service->buildGraph();

        return response()->json([
            'status' => 'completed',
            'stats' => $stats,
        ]);
    }

    /**
     * POST /api/admin/mokhii/autofix
     * Run Mokhii auto-resolver to fix detected issues.
     */
    public function triggerAutoFix(MokhiiResolverService $service): JsonResponse
    {
        $stats = $service->resolveAll();

        return response()->json([
            'status' => 'completed',
            'stats' => $stats,
        ]);
    }
}
