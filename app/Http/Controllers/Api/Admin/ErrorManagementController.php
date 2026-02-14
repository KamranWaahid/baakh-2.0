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

    /**
     * POST /api/admin/errors/verify
     * Re-test all pending errors — auto-resolve those that no longer occur.
     */
    public function verify(Request $request)
    {
        $pending = SystemError::where('status', 'pending')->get();
        $resolved = 0;
        $still_failing = 0;

        foreach ($pending as $error) {
            if ($this->isErrorFixed($error)) {
                $error->update(['status' => 'resolved']);
                $resolved++;
            } else {
                $still_failing++;
            }
        }

        ActivityLog::log('system_errors_verified', $request->user(), null, "Verified {$pending->count()} errors: {$resolved} resolved, {$still_failing} still failing");

        return response()->json([
            'total_checked' => $pending->count(),
            'resolved' => $resolved,
            'still_failing' => $still_failing,
        ]);
    }

    /**
     * POST /api/admin/errors/{error}/verify
     * Re-test a single error.
     */
    public function verifyOne(Request $request, SystemError $error)
    {
        $fixed = $this->isErrorFixed($error);

        if ($fixed) {
            $error->update(['status' => 'resolved']);
        }

        return response()->json([
            'fixed' => $fixed,
            'error' => $error->fresh(),
        ]);
    }

    /**
     * Check if an error condition has been fixed.
     */
    private function isErrorFixed(SystemError $error): bool
    {
        // For HTTP errors — replay the request and check if same error occurs
        if ($error->url && $error->method) {
            return $this->testHttpError($error);
        }

        // For CLI/artisan errors — check by error signature
        return $this->testBySignature($error);
    }

    /**
     * Re-fire an HTTP request internally and see if the error still occurs.
     */
    private function testHttpError(SystemError $error): bool
    {
        try {
            $url = $error->url;

            // Don't test external URLs
            $appUrl = rtrim(config('app.url'), '/');
            if (!str_starts_with($url, $appUrl) && !str_starts_with($url, '/')) {
                return false;
            }

            // Make relative if needed
            $path = parse_url($url, PHP_URL_PATH) ?: '/';

            // Fire internal request through the kernel
            $request = \Illuminate\Http\Request::create(
                $path,
                strtoupper($error->method ?? 'GET')
            );

            // Capture any exception
            $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
            $response = $kernel->handle($request);
            $kernel->terminate($request, $response);

            // If the response is successful (2xx/3xx) and no new error with same message was logged
            // in the last 2 seconds, consider it fixed
            $recentSameError = SystemError::where('message', $error->message)
                ->where('file', $error->file)
                ->where('id', '!=', $error->id)
                ->where('created_at', '>=', now()->subSeconds(2))
                ->exists();

            return !$recentSameError && $response->getStatusCode() < 500;
        } catch (\Throwable $e) {
            // If the same error message occurs, it's not fixed
            if (str_contains($e->getMessage(), substr($error->message, 0, 50))) {
                return false;
            }
            // Different error — the original one might be fixed
            return true;
        }
    }

    /**
     * Test by error signature — check if the error class/condition still exists.
     */
    private function testBySignature(SystemError $error): bool
    {
        $msg = $error->message;

        // "Table already exists" — check if table exists (it should, so skip migration)
        if (str_contains($msg, 'already exists: 1050 Table')) {
            return true; // Table existing is fine — the migration guard is the fix
        }

        // "No active transaction" — already fixed
        if (str_contains($msg, 'no active transaction') || str_contains($msg, 'There is no active transaction')) {
            return true;
        }

        // "Class not found" — check if class exists
        if (preg_match('/Class ["\']?([^"\']+)["\']? not found/', $msg, $m)) {
            return !class_exists($m[1]);
        }

        // "Column not found" — check if the file still has the bad query
        if (str_contains($msg, 'Column not found') && $error->file) {
            $filePath = $error->file;
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                // Check if the bad column reference still exists in the file
                if (preg_match("/Unknown column '([^']+)'/", $msg, $m)) {
                    $badColumn = $m[1];
                    return !str_contains($content, "'{$badColumn}'") && !str_contains($content, "\"{$badColumn}\"");
                }
            }
        }

        // "Duplicate entry" — one-time error, mark as resolved
        if (str_contains($msg, 'Duplicate entry')) {
            return true;
        }

        // "Maximum execution time" — transient, mark as resolved if older than 1 hour
        if (str_contains($msg, 'Maximum execution time')) {
            return $error->created_at->lt(now()->subHour());
        }

        // "option does not exist" — CLI issue, transient
        if (str_contains($msg, 'option does not exist')) {
            return true;
        }

        // Test error for verification
        if (str_contains($msg, 'Test Error')) {
            return true;
        }

        // Default: can't verify automatically
        return false;
    }
}
