<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemError;
use Illuminate\Http\Request;
use App\Models\ActivityLog;

class ErrorManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super_admin');
    }

    /**
     * Display a listing of captured system errors.
     */
    public function index(Request $request)
    {
        $query = SystemError::with('user')->orderBy('created_at', 'desc');

        // Search filtering
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                    ->orWhere('file', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%");
            });
        }

        // Status filtering
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Severity filtering
        if ($request->filled('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        return $query->paginate(20);
    }

    /**
     * Display the specified captured error.
     */
    public function show(SystemError $error)
    {
        return $error->load('user');
    }

    /**
     * Update the status of an error (resolve/ignore).
     */
    public function update(Request $request, SystemError $error)
    {
        $request->validate([
            'status' => 'required|string|in:pending,resolved,ignored',
        ]);

        $error->update([
            'status' => $request->status
        ]);

        ActivityLog::log('system_error_updated', $request->user(), null, "Updated system error #{$error->id} status to {$request->status}");

        return response()->json([
            'message' => 'Error status updated successfully.',
            'error' => $error
        ]);
    }

    /**
     * Remove the specified error log.
     */
    public function destroy(SystemError $error, Request $request)
    {
        $error->delete();

        ActivityLog::log('system_error_deleted', $request->user(), null, "Deleted system error log #{$error->id}");

        return response()->json(['message' => 'Error log deleted successfully.']);
    }

    /**
     * Clear all error logs or filter by status.
     */
    public function clear(Request $request)
    {
        $status = $request->input('status');

        $query = SystemError::query();

        if ($status) {
            $query->where('status', $status);
        }

        $count = $query->count();
        $query->delete();

        ActivityLog::log('system_errors_cleared', $request->user(), null, "Cleared {$count} system error logs" . ($status ? " with status: {$status}" : ""));

        return response()->json(['message' => "Successfully cleared {$count} error logs."]);
    }
}
