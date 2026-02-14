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
        $stats = \Illuminate\Support\Facades\Cache::get('admin_dashboard_stats');

        if (!$stats) {
            // If cache is missing, run the job synchronously to generate data immediately
            \App\Jobs\UpdateDashboardStats::dispatchSync();
            $stats = \Illuminate\Support\Facades\Cache::get('admin_dashboard_stats');
        }

        return response()->json($stats);
    }
}
