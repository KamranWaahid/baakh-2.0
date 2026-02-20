<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super_admin');
    }

    /**
     * Get database and migration status.
     */
    public function status()
    {
        try {
            $databaseName = config('database.connections.mysql.database');
            $tables = DB::select('SHOW TABLES');

            $tableList = array_map(function ($table) use ($databaseName) {
                $prop = "Tables_in_{$databaseName}";
                return $table->$prop ?? json_encode($table);
            }, $tables);

            Artisan::call('migrate:status');
            $migrateStatus = Artisan::output();

            return response()->json([
                'database' => $databaseName,
                'tables' => $tableList,
                'migration_status' => $migrateStatus,
                'notifications_table_exists' => in_array('notifications', $tableList),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $backupDisk = config('backup.backup.destination.disks')[0] ?? 'local';
        $disk = Storage::disk($backupDisk);
        $backupName = config('backup.backup.name');

        // List files in the backup directory
        $files = $disk->files($backupName);

        $backups = [];
        foreach ($files as $f) {
            if (str_ends_with($f, '.zip') && $disk->exists($f)) {
                $backups[] = [
                    'file_path' => $f,
                    'file_name' => basename($f),
                    'file_size' => $this->formatSizeUnits($disk->size($f)),
                    'last_modified' => date('Y-m-d H:i:s', $disk->lastModified($f)),
                ];
            }
        }

        return response()->json(array_reverse($backups));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Run the backup command - this might take time
            // Set time limit for this request if possible
            set_time_limit(300);

            Artisan::call('backup:run', ['--only-db' => true]);
            $output = Artisan::output();

            ActivityLog::log('created_backup', $request->user(), null, "Created new database backup");

            return response()->json([
                'message' => 'Backup created successfully',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            \Log::error('Backup failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Backup failed. Ensure mysqldump is in your system path.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download the specified resource.
     */
    public function download(Request $request)
    {
        $fileName = $request->file_name;
        $backupDisk = config('backup.backup.destination.disks')[0] ?? 'local';
        $disk = Storage::disk($backupDisk);
        $path = config('backup.backup.name') . '/' . $fileName;

        if ($disk->exists($path)) {
            // For local disk, we can use response()->download with absolute path
            // to ensure headers like Content-Type are set correctly for the zip
            if ($backupDisk === 'local') {
                return response()->download(storage_path('app/' . $path), $fileName);
            }

            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
            return $disk->download($path, $fileName);
        }

        return response()->json(['message' => 'File not found at: ' . $path], 404);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($file_name)
    {
        $backupDisk = config('backup.backup.destination.disks')[0] ?? 'local';
        $disk = Storage::disk($backupDisk);
        $path = config('backup.backup.name') . '/' . $file_name;

        if ($disk->exists($path)) {
            $disk->delete($path);

            ActivityLog::log('deleted_backup', request()->user(), null, "Deleted backup: {$file_name}");

            return response()->json(['message' => 'Backup deleted successfully']);
        }

        return response()->json(['message' => 'Backup not found'], 404);
    }

    /**
     * Run database migrations.
     */
    public function migrate(Request $request)
    {
        try {
            set_time_limit(300);
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();

            ActivityLog::log('ran_migrations', $request->user(), null, "Ran database migrations from admin panel");

            return response()->json([
                'message' => 'Migrations executed successfully',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            \Log::error('Migration failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Migration failed',
                'error' => $e->getMessage(),
                'details' => $e->getFile() . ' L' . $e->getLine()
            ], 500);
        }
    }

    /**
     * Repair Admin Permissions by resetting cache and re-seeding.
     */
    public function repairPermissions(Request $request)
    {
        try {
            set_time_limit(300);

            // 1. Clear Spatie Cache
            Artisan::call('permission:cache-reset');
            $output = "Permission cache reset.\n";

            // 2. Re-seed Roles and Permissions
            Artisan::call('db:seed', ['--class' => 'RolesAndPermissionsSeeder', '--force' => true]);
            $output .= Artisan::output();

            ActivityLog::log('repaired_permissions', $request->user(), null, "Repaired admin permissions from admin panel");

            return response()->json([
                'message' => 'Permissions repaired and seeded successfully',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            \Log::error('Permission repair failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Permission repair failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear all application caches.
     */
    public function clearCache(Request $request)
    {
        try {
            set_time_limit(300);

            // Comprehensive clear
            Artisan::call('optimize:clear');
            $output = Artisan::output();

            // Specific clear for dashboard stats if it exists
            \Illuminate\Support\Facades\Cache::forget('admin_dashboard_stats');
            $output .= "\nDashboard stats cache cleared.";

            ActivityLog::log('cleared_cache', $request->user(), null, "Cleared application cache from admin panel");

            return response()->json([
                'message' => 'Application cache cleared successfully',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            \Log::error('Cache clear failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Cache clear failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }
}
