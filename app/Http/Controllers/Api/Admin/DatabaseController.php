<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseController extends Controller
{
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
