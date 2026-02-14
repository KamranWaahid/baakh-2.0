<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Display a listing of the reports.
     */
    public function index(Request $request)
    {
        $query = Report::with(['user', 'poetry.info', 'poet']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        return $query->latest()->paginate(20);
    }

    /**
     * Display the specified report.
     */
    public function show(Report $report)
    {
        return $report->load(['user', 'poetry.info', 'poet']);
    }

    /**
     * Update the specified report (e.g., mark as resolved).
     */
    public function update(Request $request, Report $report)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,resolved,ignored',
        ]);

        $report->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Report status updated successfully.',
            'data' => $report
        ]);
    }

    /**
     * Remove the specified report.
     */
    public function destroy(Report $report)
    {
        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Report deleted successfully.'
        ]);
    }
}
