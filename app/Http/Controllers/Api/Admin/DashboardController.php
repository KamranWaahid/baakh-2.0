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
            ->with(['couplets' => function($q) {
                $q->where('lang', 'sd'); // Get main Sindhi text
            }, 'info'])
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
        $missingTagsCouplets = Couplets::where(function($q) {
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
            'missing_en_poetry' => $missingEnPoetry,
            'missing_en_couplets' => $missingEnCouplets,
            'missing_tags_couplets' => $missingTagsCouplets
        ]);
    }
}
