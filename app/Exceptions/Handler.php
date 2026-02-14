<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use App\Models\SystemError;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            try {
                // Prevent recursive calls if error tracking itself fails
                if ($e instanceof \Illuminate\Database\QueryException && str_contains($e->getMessage(), 'system_errors')) {
                    return;
                }
                if ($e instanceof \Illuminate\Database\QueryException && str_contains($e->getMessage(), 'admin_notifications')) {
                    return;
                }

                SystemError::create([
                    'message' => $e->getMessage() ?: get_class($e),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'url' => Request::fullUrl(),
                    'method' => Request::method(),
                    'user_agent' => Request::header('User-Agent'),
                    'ip' => Request::ip(),
                    'user_id' => Auth::id(),
                    'environment' => app()->environment(),
                    'severity' => $this->shouldBeHighSeverity($e) ? 'high' : 'medium',
                ]);

                // Notify super admin
                \App\Models\AdminNotification::create([
                    'type' => 'system_error',
                    'title' => 'System Error Captured',
                    'message' => \Illuminate\Support\Str::limit($e->getMessage(), 120),
                    'icon' => 'Bug',
                    'color' => $this->shouldBeHighSeverity($e) ? 'red' : 'orange',
                    'link' => '/admin/system/errors',
                ]);
            } catch (Throwable $reportError) {
                // Fallback to default reporting if our custom logger fails
            }
        });
    }

    /**
     * Determine if an exception should be considered high severity.
     */
    private function shouldBeHighSeverity(Throwable $e): bool
    {
        return $e instanceof \Symfony\Component\ErrorHandler\Error\FatalError ||
            $e instanceof \Error ||
            $e instanceof \Illuminate\Database\QueryException;
    }
}
