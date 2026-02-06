<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Period;
use Illuminate\Http\Request;

class PeriodController extends Controller
{
    public function index(Request $request)
    {
        $periods = Period::orderBy('order', 'asc')->get();
        return response()->json($periods);
    }

    public function poets(Request $request, $id)
    {
        $lang = $request->get('lang', 'sd');
        $period = Period::findOrFail($id);

        // Parse date range
        $range = explode('-', $period->date_range);
        $startYear = trim($range[0]);
        $endYearRaw = trim($range[1]);
        $endYear = ($endYearRaw === 'Present') ? date('Y') : $endYearRaw;

        $poets = \App\Models\Poets::with([
            'details' => function ($q) use ($lang) {
                $q->where('lang', $lang);
            }
        ])
            ->where(function ($query) use ($startYear, $endYear) {
                // Poet was alive during the period if:
                // 1. Their birth was before or during the period end
                // 2. Their death was after or during the period start (or they are still alive)
                $query->whereYear('date_of_birth', '<=', $endYear)
                    ->where(function ($q) use ($startYear) {
                    $q->whereYear('date_of_death', '>=', $startYear)
                        ->orWhereNull('date_of_death');
                });
            })
            ->where('visibility', 1)
            ->get()
            ->map(function ($poet) use ($lang) {
                return [
                    'id' => $poet->id,
                    'name' => $poet->details->poet_name ?? $poet->poet_slug,
                    'laqab' => $poet->details->poet_laqab ?? '',
                    'image' => $poet->poet_pic,
                    'slug' => $poet->poet_slug
                ];
            });

        return response()->json($poets);
    }
}
