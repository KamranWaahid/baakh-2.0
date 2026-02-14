<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ServerController extends Controller
{
    /**
     * Get a list of supported artisan commands.
     */
    public function index()
    {
        $commands = [
            [
                'id' => 'cache:static-update',
                'name' => 'Refresh Static Cache',
                'description' => 'Manually refresh all static data (poets, poetry, tags) across languages.',
                'command' => 'cache:static-update',
                'category' => 'Cache',
                'danger' => false,
            ],
            [
                'id' => 'cache:clear',
                'name' => 'Clear Application Cache',
                'description' => 'Flush the application cache.',
                'command' => 'cache:clear',
                'category' => 'Cache',
                'danger' => true,
            ],
            [
                'id' => 'config:cache',
                'name' => 'Cache Configuration',
                'description' => 'Create a cache file for faster configuration loading.',
                'command' => 'config:cache',
                'category' => 'Optimization',
                'danger' => false,
            ],
            [
                'id' => 'config:clear',
                'name' => 'Clear Configuration Cache',
                'description' => 'Remove the configuration cache file.',
                'command' => 'config:clear',
                'category' => 'Optimization',
                'danger' => false,
            ],
            [
                'id' => 'route:cache',
                'name' => 'Cache Routes',
                'description' => 'Create a route cache file for faster route registration.',
                'command' => 'route:cache',
                'category' => 'Optimization',
                'danger' => false,
            ],
            [
                'id' => 'route:clear',
                'name' => 'Clear Route Cache',
                'description' => 'Remove the route cache file.',
                'command' => 'route:clear',
                'category' => 'Optimization',
                'danger' => false,
            ],
            [
                'id' => 'view:cache',
                'name' => 'Cache Views',
                'description' => 'Compile all Blade templates for faster rendering.',
                'command' => 'view:cache',
                'category' => 'Optimization',
                'danger' => false,
            ],
            [
                'id' => 'view:clear',
                'name' => 'Clear Compiled Views',
                'description' => 'Clear all compiled view files.',
                'command' => 'view:clear',
                'category' => 'Optimization',
                'danger' => false,
            ],
            [
                'id' => 'optimize',
                'name' => 'Optimize Application',
                'description' => 'Cache framework bootstrap files (config, routes, etc.)',
                'command' => 'optimize',
                'category' => 'Optimization',
                'danger' => false,
            ],
            [
                'id' => 'optimize:clear',
                'name' => 'Clear Optimization',
                'description' => 'Remove all cached bootstrap files.',
                'command' => 'optimize:clear',
                'category' => 'Optimization',
                'danger' => false,
            ],
            [
                'id' => 'down',
                'name' => 'Maintenance Mode (Down)',
                'description' => 'Put the application into maintenance mode.',
                'command' => 'down',
                'category' => 'Maintenance',
                'danger' => true,
            ],
            [
                'id' => 'up',
                'name' => 'Live Mode (Up)',
                'description' => 'Bring the application out of maintenance mode.',
                'command' => 'up',
                'category' => 'Maintenance',
                'danger' => false,
            ],
            [
                'id' => 'storage:link',
                'name' => 'Link Storage',
                'description' => 'Create the symbolic links configured for the application.',
                'command' => 'storage:link',
                'category' => 'System',
                'danger' => false,
            ]
        ];

        return response()->json($commands);
    }

    /**
     * Get system statistics.
     */
    public function stats()
    {
        $stats = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_os' => PHP_OS . ' (' . php_uname('s') . ' ' . php_uname('r') . ')',
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'uptime' => @shell_exec('uptime') ?: 'Unable to fetch uptime',
            'memory_usage' => $this->formatSizeUnits(memory_get_usage(true)),
            'disk' => [
                'free' => $this->formatSizeUnits(disk_free_space(base_path())),
                'total' => $this->formatSizeUnits(disk_total_space(base_path())),
                'used' => $this->formatSizeUnits(disk_total_space(base_path()) - disk_free_space(base_path())),
                'percent' => round((1 - (disk_free_space(base_path()) / disk_total_space(base_path()))) * 100, 2)
            ],
            'is_down' => app()->isDownForMaintenance()
        ];

        return response()->json($stats);
    }

    /**
     * Get the latest application logs.
     */
    public function logs()
    {
        $logPath = storage_path('logs/laravel.log');

        if (!file_exists($logPath)) {
            return response()->json(['logs' => 'Log file not found.']);
        }

        // Read last 200 lines
        $file = new \SplFileObject($logPath, 'r');
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();

        $lines = [];
        $startLine = max(0, $totalLines - 200);
        $file->seek($startLine);

        while (!$file->eof()) {
            $line = $file->current();
            if ($line)
                $lines[] = $line;
            $file->next();
        }

        return response()->json([
            'logs' => implode('', array_reverse($lines)),
            'path' => $logPath
        ]);
    }

    /**
     * Run a specific artisan command.
     */
    public function run(Request $request)
    {
        $request->validate([
            'command' => 'required|string',
        ]);

        $allowedCommands = [
            'cache:static-update',
            'cache:clear',
            'config:cache',
            'config:clear',
            'route:cache',
            'route:clear',
            'view:cache',
            'view:clear',
            'optimize',
            'optimize:clear',
            'down',
            'up',
            'storage:link',
        ];

        $command = $request->input('command');

        if (!in_array($command, $allowedCommands)) {
            return response()->json(['message' => 'Command not allowed'], 403);
        }

        try {
            // Log the attempt
            ActivityLog::log('system_command_run', $request->user(), null, "Attempted to run artisan command: {$command}");

            // Set time limit for long-running commands
            set_time_limit(300);

            // Run the command
            Artisan::call($command);
            $output = Artisan::output();

            // Log the success
            ActivityLog::log('system_command_run_success', $request->user(), null, "Successfully ran artisan command: {$command}");

            return response()->json([
                'message' => "Command '{$command}' executed successfully.",
                'output' => $output ?: 'Success (no output returned).'
            ]);
        } catch (\Exception $e) {
            Log::error("Error running command {$command}: " . $e->getMessage());

            return response()->json([
                'message' => "Failed to run command '{$command}'.",
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Execute a safe shell command.
     */
    public function shell(Request $request)
    {
        $request->validate([
            'command' => 'required|string',
        ]);

        $input = $request->input('command');
        $parts = explode(' ', trim($input));
        $baseCommand = $parts[0];

        $allowedBaseCommands = [
            'ls',
            'whoami',
            'uptime',
            'df',
            'free',
            'pwd',
            'date',
            'du'
        ];

        if (!in_array($baseCommand, $allowedBaseCommands)) {
            return response()->json(['message' => 'Shell command not allowed'], 403);
        }

        // Prevent injection and piping
        if (strpbrk($input, ';|&><$`')) {
            return response()->json(['message' => 'Complex shell commands (pipes, redirects, etc.) are restricted for security.'], 403);
        }

        try {
            $output = shell_exec($input . ' 2>&1');

            ActivityLog::log('shell_command_run', $request->user(), null, "Ran shell command: {$input}");

            return response()->json([
                'command' => $input,
                'output' => $output ?: 'Success (no output).'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Format bytes to human readable units.
     */
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
