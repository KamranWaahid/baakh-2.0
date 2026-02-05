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
        $disk = Storage::disk(config('backup.backup.destination.disks')[0]);
        $files = $disk->files(config('backup.backup.name'));

        $backups = [];
        foreach ($files as $k => $f) {
            if (substr($f, -4) == '.zip' && $disk->exists($f)) {
                $backups[] = [
                    'file_path' => $f,
                    'file_name' => str_replace(config('backup.backup.name') . '/', '', $f),
                    'file_size' => $this->formatSizeUnits($disk->size($f)),
                    'last_modified' => date('Y-m-d H:i:s', $disk->lastModified($f)),
                    'download_link' => route('backup.download', ['file_name' => str_replace(config('backup.backup.name') . '/', '', $f)]),
                ];
            }
        }

        $backups = array_reverse($backups);

        return response()->json($backups);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Run the backup command
            Artisan::call('backup:run', ['--only-db' => true]);
            $output = Artisan::output();

            ActivityLog::log('created_backup', $request->user(), null, "Created new database backup");

            return response()->json([
                'message' => 'Backup created successfully',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Backup failed',
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
        $disk = Storage::disk(config('backup.backup.destination.disks')[0]);
        $path = config('backup.backup.name') . '/' . $fileName;

        if ($disk->exists($path)) {
            // For local disk, we can use response()->download
            if (config('backup.backup.destination.disks')[0] === 'local') {
                return response()->download($disk->path($path));
            }
            return $disk->download($path);
        }

        return response()->json(['message' => 'File not found'], 404);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($file_name)
    {
        $disk = Storage::disk(config('backup.backup.destination.disks')[0]);
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
