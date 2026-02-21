<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CorpusStat;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function frequency(Request $request)
    {
        $limit = $request->get('limit', 50);
        $page = $request->get('page', 1);

        $stats = CorpusStat::orderBy('frequency', 'desc')->paginate($limit, ['*'], 'page', $page);

        return response()->json($stats);
    }
}
