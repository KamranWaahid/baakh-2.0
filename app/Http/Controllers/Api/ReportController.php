<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Report;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:3',
            'url' => 'nullable|url',
            'poem_id' => 'nullable|integer',
        ]);

        $report = Report::create([
            'reason' => $validated['reason'],
            'url' => $validated['url'],
            'poem_id' => $validated['poem_id'],
            'user_id' => Auth::guard('sanctum')->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report submitted successfully.',
            'data' => $report
        ]);
    }
}
