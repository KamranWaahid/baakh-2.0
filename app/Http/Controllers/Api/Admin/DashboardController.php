<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poetry;
use App\Models\Couplets;
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
                    $text = $p->couplets->couplet_text ?? '';
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
            'missing_tags_couplets' => $missingTagsCouplets
        ]);
    }
}
