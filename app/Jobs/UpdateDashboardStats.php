<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use App\Models\Poetry;
use App\Models\Couplets;
use App\Helpers\SindhiNormalizer;
use Illuminate\Support\Facades\DB;

class UpdateDashboardStats implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Calculate statistics
        $totalPoets = DB::table('poets')->count();
        $totalPoetry = Poetry::count();
        $totalUsers = DB::table('users')->count();
        $dailyViews = 0; // Placeholder

        // Calculate month-over-month changes
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        $poetsLastMonth = DB::table('poets')->where('created_at', '<=', $lastMonthEnd)->count();
        $poetsGrowth = $poetsLastMonth > 0 ? round((($totalPoets - $poetsLastMonth) / $poetsLastMonth) * 100, 1) : 0;

        $poetryLastMonth = Poetry::where('created_at', '<=', $lastMonthEnd)->count();
        $poetryGrowth = $poetryLastMonth > 0 ? round((($totalPoetry - $poetryLastMonth) / $poetryLastMonth) * 100, 1) : 0;

        $usersLastMonth = DB::table('users')->where('created_at', '<=', $lastMonthEnd)->count();
        $usersGrowth = $usersLastMonth > 0 ? round((($totalUsers - $usersLastMonth) / $usersLastMonth) * 100, 1) : 0;

        $formatNumber = function ($num) {
            if ($num >= 1000000)
                return round($num / 1000000, 1) . 'M';
            elseif ($num >= 1000)
                return round($num / 1000, 1) . 'K';
            return number_format($num);
        };

        // 1. Missing EN Poetry
        $missingEnPoetry = Poetry::whereNotNull('category_id')
            ->whereDoesntHave('translations', function ($q) {
                $q->where('lang', 'en');
            })
            ->with('info')->orderBy('id', 'desc')->limit(10)->get()
            ->map(fn($p) => ['id' => $p->id, 'title' => $p->info->title ?? 'Untitled', 'type' => 'poetry']);

        // 2. Missing EN Couplets
        $missingEnCouplets = Poetry::whereNull('category_id')->whereHas('couplets')
            ->whereDoesntHave('translations', function ($q) {
                $q->where('lang', 'en');
            })
            ->with(['couplets' => fn($q) => $q->where('lang', 'sd'), 'info'])
            ->orderBy('id', 'desc')->limit(10)->get()
            ->map(function ($p) {
                $title = $p->info->title ?? null;
                if (!$title && $p->couplets) {
                    $text = $p->couplets->first()?->couplet_text ?? '';
                    $title = explode("\n", $text)[0] ?? 'Untitled Couplet';
                }
                return ['id' => $p->id, 'title' => $title ?? 'Untitled', 'type' => 'couplet'];
            });

        // 3. Missing Tags Couplets
        $missingTagsCouplets = Couplets::where(fn($q) => $q->whereNull('couplet_tags')->orWhere('couplet_tags', '')->orWhere('couplet_tags', '[]'))
            ->with('poetry.info')->orderBy('id', 'desc')->limit(10)->get()
            ->map(function ($c) {
                $text = $c->couplet_text ?? '';
                $display = explode("\n", $text)[0] ?? 'Untitled';
                return ['id' => $c->id, 'poetry_id' => $c->poetry_id, 'title' => $display, 'type' => 'couplet_tag'];
            });

        // 4. Orthography Issues (Heavy Scan)
        $orthographyIssues = [];

        $poetIssues = DB::table('poets_detail')->where('lang', 'sd')
            ->get(['id', 'poet_id', 'poet_name', 'poet_bio'])
            ->filter(fn($p) => $p->poet_name !== SindhiNormalizer::normalize($p->poet_name) || ($p->poet_bio && $p->poet_bio !== SindhiNormalizer::normalize($p->poet_bio)))
            ->take(5)->map(fn($p) => ['id' => $p->poet_id, 'title' => 'Poet: ' . $p->poet_name, 'type' => 'poet_issue', 'edit_url' => "/admin/poets/{$p->poet_id}/edit"]);

        $orthographyIssues = array_merge($orthographyIssues, $poetIssues->toArray());

        $translationIssues = DB::table('poetry_translations')->where('lang', 'sd')
            ->orderBy('id', 'desc')->limit(500)->get(['id', 'poetry_id', 'title', 'info'])
            ->filter(fn($t) => $t->title !== SindhiNormalizer::normalize($t->title) || ($t->info && $t->info !== SindhiNormalizer::normalize($t->info)))
            ->take(5)->map(fn($t) => ['id' => $t->poetry_id, 'title' => 'Poetry: ' . ($t->title ?: 'Untitled'), 'type' => 'poetry_issue', 'edit_url' => "/admin/poetry/{$t->poetry_id}/edit"]);

        $orthographyIssues = array_merge($orthographyIssues, $translationIssues->toArray());

        $coupletIssues = DB::table('poetry_couplets')->where('lang', 'sd')
            ->orderBy('id', 'desc')->limit(1000)->get(['id', 'poetry_id', 'couplet_text'])
            ->filter(fn($c) => $c->couplet_text !== SindhiNormalizer::normalize($c->couplet_text))
            ->take(5)->map(fn($c) => ['id' => $c->id, 'poetry_id' => $c->poetry_id, 'title' => 'Couplet: ' . (mb_substr(explode("\n", $c->couplet_text)[0], 0, 30) . '...'), 'type' => 'couplet_issue', 'edit_url' => "/admin/poetry/{$c->poetry_id}/edit"]);

        $orthographyIssues = array_merge($orthographyIssues, $coupletIssues->toArray());

        // 5. Activity Graph (Last 30 Days)
        $activityGraph = [];
        $startDate = now()->subDays(29)->startOfDay();

        $activityCounts = \App\Models\ActivityLog::selectRaw('DATE(created_at) as date, count(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->pluck('count', 'date');

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $activityGraph[] = [
                'date' => now()->subDays($i)->format('M d'),
                'actions' => $activityCounts[$date] ?? 0,
            ];
        }

        // 6. Content Growth Graph (Last 30 Days)
        $contentGrowth = [];
        $poetryCounts = Poetry::selectRaw('DATE(created_at) as date, count(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->pluck('count', 'date');

        $poetCounts = DB::table('poets')->selectRaw('DATE(created_at) as date, count(*) as count')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->pluck('count', 'date');

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $contentGrowth[] = [
                'date' => now()->subDays($i)->format('M d'),
                'poetry' => $poetryCounts[$date] ?? 0,
                'poets' => $poetCounts[$date] ?? 0,
            ];
        }

        // 7. Recent Activity
        $recentActivity = \App\Models\ActivityLog::with('user')
            ->latest()
            ->take(7)
            ->get()
            ->map(fn($log) => [
                'id' => $log->id,
                'user' => $log->user ? ['name' => $log->user->name, 'avatar' => $log->user->avatar] : null,
                'action' => $log->action,
                'description' => $log->description,
                'time' => $log->created_at->diffForHumans(),
            ]);

        // 8. Recent Reports
        $recentReports = \App\Models\Report::with(['user', 'poetry.info', 'poet'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($report) {
                $target = 'Unknown';
                if ($report->poetry)
                    $target = $report->poetry->info->title ?? 'Poetry #' . $report->poem_id;
                elseif ($report->poet)
                    $target = $report->poet->poet_name ?? 'Poet #' . $report->poet_id;
                elseif ($report->url)
                    $target = $report->url;

                return [
                    'id' => $report->id,
                    'reporter' => $report->user->name ?? 'Guest',
                    'target' => $target,
                    'reason' => $report->reason,
                    'date' => $report->created_at->format('M d, Y'),
                ];
            });

        $data = [
            'stats' => [
                'total_poets' => ['value' => $formatNumber($totalPoets), 'raw_value' => $totalPoets, 'change' => ($poetsGrowth >= 0 ? '+' : '') . $poetsGrowth . '%', 'trend' => $poetsGrowth >= 0 ? 'up' : 'down'],
                'total_poetry' => ['value' => $formatNumber($totalPoetry), 'raw_value' => $totalPoetry, 'change' => ($poetryGrowth >= 0 ? '+' : '') . $poetryGrowth . '%', 'trend' => $poetryGrowth >= 0 ? 'up' : 'down'],
                'total_users' => ['value' => $formatNumber($totalUsers), 'raw_value' => $totalUsers, 'change' => ($usersGrowth >= 0 ? '+' : '') . $usersGrowth . '%', 'trend' => $usersGrowth >= 0 ? 'up' : 'down'],
                'daily_views' => ['value' => $formatNumber($dailyViews), 'raw_value' => $dailyViews, 'change' => '0%', 'trend' => 'up']
            ],
            'activity_graph' => $activityGraph,
            'content_growth' => $contentGrowth,
            'recent_activity' => $recentActivity,
            'recent_feedback' => \App\Models\Feedback::with('user')->latest()->take(5)->get()->map(fn($f) => [
                'id' => $f->id,
                'user' => $f->user ? ['name' => $f->user->name, 'avatar' => $f->user->avatar] : ['name' => 'Anonymous'],
                'message' => $f->message,
                'rating' => $f->rating,
                'time' => $f->created_at->diffForHumans()
            ]),
            'recent_reports' => $recentReports,
            'missing_en_poetry' => $missingEnPoetry,
            'missing_en_couplets' => $missingEnCouplets,
            'missing_tags_couplets' => $missingTagsCouplets,
            'orthography_issues' => array_slice($orthographyIssues, 0, 10),
            'last_updated' => now()->toIso8601String()
        ];

        Cache::put('admin_dashboard_stats', $data, now()->addHours(2));
    }
}
