<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poetry;
use App\Models\Couplets;
use App\Helpers\SindhiNormalizer;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Calculate statistics
        $totalPoets = \DB::table('poets')->count();
        $totalPoetry = Poetry::count();
        $totalUsers = \DB::table('users')->count();

        // Calculate daily views (assuming you have a views tracking system)
        // For now, we'll use a placeholder or you can implement actual view tracking
        $dailyViews = 0; // TODO: Implement view tracking

        // Calculate month-over-month changes
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();
        $currentMonthStart = now()->startOfMonth();

        // Poets growth
        $poetsLastMonth = \DB::table('poets')
            ->where('created_at', '<=', $lastMonthEnd)
            ->count();
        $poetsGrowth = $poetsLastMonth > 0
            ? round((($totalPoets - $poetsLastMonth) / $poetsLastMonth) * 100, 1)
            : 0;

        // Poetry growth
        $poetryLastMonth = Poetry::where('created_at', '<=', $lastMonthEnd)->count();
        $poetryGrowth = $poetryLastMonth > 0
            ? round((($totalPoetry - $poetryLastMonth) / $poetryLastMonth) * 100, 1)
            : 0;

        // Users growth
        $usersLastMonth = \DB::table('users')
            ->where('created_at', '<=', $lastMonthEnd)
            ->count();
        $usersGrowth = $usersLastMonth > 0
            ? round((($totalUsers - $usersLastMonth) / $usersLastMonth) * 100, 1)
            : 0;

        // Format numbers for display
        $formatNumber = function ($num) {
            if ($num >= 1000000) {
                return round($num / 1000000, 1) . 'M';
            } elseif ($num >= 1000) {
                return round($num / 1000, 1) . 'K';
            }
            return number_format($num);
        };


        // 1. Poetries (with categories) missing EN content
        $missingEnPoetry = Poetry::whereNotNull('category_id')
            ->whereDoesntHave('translations', function ($q) {
                $q->where('lang', 'en');
            })
            ->with('info') // Assuming title is in info
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'title' => $p->info->title ?? 'Untitled',
                    'type' => 'poetry'
                ];
            });

        // 2. Couplets (without categories) missing EN content
        // Assuming Independent Couplets are Poetry records with NULL category_id AND contain couplets
        $missingEnCouplets = Poetry::whereNull('category_id')
            ->whereHas('couplets')
            ->whereDoesntHave('translations', function ($q) {
                $q->where('lang', 'en');
            })
            ->with([
                'couplets' => function ($q) {
                    $q->where('lang', 'sd'); // Get main Sindhi text
                },
                'info'
            ])
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($p) {
                // If title exists use it, otherwise use couplet text
                $title = $p->info->title ?? null;
                if (!$title && $p->couplets) {
                    $text = $p->couplets->first()?->couplet_text ?? '';
                    $lines = explode("\n", $text);
                    $title = $lines[0] ?? 'Untitled Couplet';
                }
                return [
                    'id' => $p->id, // Poetry ID (parent)
                    'title' => $title ?? 'Untitled',
                    'type' => 'couplet'
                ];
            });

        // 3. Couplets (all) missing tags
        $missingTagsCouplets = Couplets::where(function ($q) {
            $q->whereNull('couplet_tags')
                ->orWhere('couplet_tags', '')
                ->orWhere('couplet_tags', '[]');
        })
            ->with('poetry.info') // To get title if needed, or just use text
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($c) {
                $text = $c->couplet_text ?? '';
                $lines = explode("\n", $text);
                $display = $lines[0] ?? 'Untitled';
                return [
                    'id' => $c->id, // Couplet ID
                    'poetry_id' => $c->poetry_id,
                    'title' => $display,
                    'type' => 'couplet_tag'
                ];
            });

        // 4. Scan for Orthography/Spelling Issues (Sindhi only)
        $orthographyIssues = [];

        // Scan poets_detail
        $poetIssues = \DB::table('poets_detail')
            ->where('lang', 'sd')
            ->get(['id', 'poet_id', 'poet_name', 'poet_bio'])
            ->filter(function ($p) {
                return $p->poet_name !== SindhiNormalizer::normalize($p->poet_name) ||
                    ($p->poet_bio && $p->poet_bio !== SindhiNormalizer::normalize($p->poet_bio));
            })
            ->take(5)
            ->map(function ($p) {
                return [
                    'id' => $p->poet_id,
                    'title' => 'Poet: ' . $p->poet_name,
                    'type' => 'poet_issue',
                    'edit_url' => "/admin/poets/{$p->poet_id}/edit"
                ];
            });
        $orthographyIssues = array_merge($orthographyIssues, $poetIssues->toArray());

        // Scan poetry_translations (Titles and Info)
        $translationIssues = \DB::table('poetry_translations')
            ->where('lang', 'sd')
            ->orderBy('id', 'desc')
            ->limit(500) // Sample last 500 for performance
            ->get(['id', 'poetry_id', 'title', 'info'])
            ->filter(function ($t) {
                return $t->title !== SindhiNormalizer::normalize($t->title) ||
                    ($t->info && $t->info !== SindhiNormalizer::normalize($t->info));
            })
            ->take(5)
            ->map(function ($t) {
                return [
                    'id' => $t->poetry_id,
                    'title' => 'Poetry: ' . ($t->title ?: 'Untitled'),
                    'type' => 'poetry_issue',
                    'edit_url' => "/admin/poetry/{$t->poetry_id}/edit"
                ];
            });
        $orthographyIssues = array_merge($orthographyIssues, $translationIssues->toArray());

        // Scan poetry_couplets
        $coupletIssues = \DB::table('poetry_couplets')
            ->where('lang', 'sd')
            ->orderBy('id', 'desc')
            ->limit(1000) // Sample last 1000
            ->get(['id', 'poetry_id', 'couplet_text'])
            ->filter(function ($c) {
                return $c->couplet_text !== SindhiNormalizer::normalize($c->couplet_text);
            })
            ->take(5)
            ->map(function ($c) {
                $lines = explode("\n", $c->couplet_text);
                return [
                    'id' => $c->id,
                    'poetry_id' => $c->poetry_id,
                    'title' => 'Couplet: ' . (mb_substr($lines[0], 0, 30) . '...'),
                    'type' => 'couplet_issue',
                    'edit_url' => "/admin/poetry/{$c->poetry_id}/edit"
                ];
            });
        $orthographyIssues = array_merge($orthographyIssues, $coupletIssues->toArray());

        return response()->json([
            'stats' => [
                'total_poets' => [
                    'value' => $formatNumber($totalPoets),
                    'raw_value' => $totalPoets,
                    'change' => ($poetsGrowth >= 0 ? '+' : '') . $poetsGrowth . '%',
                    'trend' => $poetsGrowth >= 0 ? 'up' : 'down'
                ],
                'total_poetry' => [
                    'value' => $formatNumber($totalPoetry),
                    'raw_value' => $totalPoetry,
                    'change' => ($poetryGrowth >= 0 ? '+' : '') . $poetryGrowth . '%',
                    'trend' => $poetryGrowth >= 0 ? 'up' : 'down'
                ],
                'total_users' => [
                    'value' => $formatNumber($totalUsers),
                    'raw_value' => $totalUsers,
                    'change' => ($usersGrowth >= 0 ? '+' : '') . $usersGrowth . '%',
                    'trend' => $usersGrowth >= 0 ? 'up' : 'down'
                ],
                'daily_views' => [
                    'value' => $formatNumber($dailyViews),
                    'raw_value' => $dailyViews,
                    'change' => '0%',
                    'trend' => 'up'
                ]
            ],
            'missing_en_poetry' => $missingEnPoetry,
            'missing_en_couplets' => $missingEnCouplets,
            'missing_tags_couplets' => $missingTagsCouplets,
            'orthography_issues' => array_slice($orthographyIssues, 0, 10)
        ]);
    }
}
