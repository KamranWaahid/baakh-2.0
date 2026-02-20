<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PerformanceController extends Controller
{
    /**
     * Analyze a heap snapshot file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function analyzeHeap(Request $request)
    {
        $request->validate([
            'snapshot' => 'required|file|max:102400', // Max 100MB
        ]);

        $file = $request->file('snapshot');
        $fileName = Str::uuid() . '.heapsnapshot';
        $path = $file->storeAs('temp/heaps', $fileName);
        $absolutePath = storage_path('app/' . $path);

        $pythonScript = base_path('heap_analysis/analyze_heap_snapshot.py');

        try {
            // Execute the python script
            $result = Process::run(['python3', $pythonScript, $absolutePath]);

            // Clean up the temporary file
            Storage::delete($path);

            if ($result->successful()) {
                $output = json_decode($result->output(), true);
                return response()->json($output);
            }

            return response()->json([
                'error' => 'Analysis failed',
                'details' => $result->errorOutput()
            ], 500);

        } catch (\Exception $e) {
            // Clean up on error
            Storage::delete($path);

            return response()->json([
                'error' => 'An error occurred during analysis',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Trigger the poets:optimize-images Artisan command.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function optimizeImages(Request $request)
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('poets:optimize-images');
            $output = \Illuminate\Support\Facades\Artisan::output();

            return response()->json([
                'message' => 'Image optimization completed successfully.',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred during image optimization',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
