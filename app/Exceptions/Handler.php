<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;
use App\Models\SystemError;
use App\Models\AdminNotification;
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
        $this->renderable(function (Throwable $e, $request) {
            if (!$request->is('api/*')) {
                return null;
            }

            if ($request->is('api/v1/poets') || $request->is('api/v1/poets/*')) {
                $page = (int) $request->query('page', 1);
                $perPage = (int) $request->query('per_page', 20);

                return response()->json([
                    'data' => [],
                    'current_page' => $page,
                    'last_page' => 1,
                    'total' => 0,
                    'per_page' => $perPage,
                    'from' => null,
                    'to' => null,
                ], 200);
            }

            if ($request->is('api/v1/poet-tags')) {
                return response()->json([], 200);
            }

            if ($request->is('api/auth/me')) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return null;
        });

        $this->reportable(function (Throwable $e) {
            try {
                // Prevent recursive calls if error tracking itself fails
                if ($e instanceof \Illuminate\Database\QueryException && str_contains($e->getMessage(), 'system_errors')) {
                    return;
                }
                if ($e instanceof \Illuminate\Database\QueryException && str_contains($e->getMessage(), 'admin_notifications')) {
                    return;
                }

                // Vercel / serverless: DB may be unreachable; logging to stderr avoids cascading DB errors.
                if (getenv('VERCEL')) {
                    Log::error($e->getMessage(), [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);

                    return;
                }

                $summary = $this->summarizeException($e);
                $path = parse_url((string) Request::fullUrl(), PHP_URL_PATH) ?: '';
                $severity = $this->shouldBeHighSeverity($e) ? 'high' : 'medium';

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
                    'severity' => $severity,
                ]);

                $fingerprint = md5($summary . '|' . $path);
                $existing = AdminNotification::query()
                    ->where('type', 'system_error')
                    ->where('created_at', '>=', now()->subMinutes(30))
                    ->where('data->fingerprint', $fingerprint)
                    ->latest('id')
                    ->first();

                if ($existing) {
                    $count = (int) data_get($existing->data, 'count', 1) + 1;
                    $existing->update([
                        'message' => $this->notificationMessage($summary, $path, $count),
                        'color' => $severity === 'high' ? 'red' : 'orange',
                        'data' => array_merge($existing->data ?? [], [
                            'fingerprint' => $fingerprint,
                            'count' => $count,
                            'path' => $path,
                            'exception' => class_basename($e),
                        ]),
                        'read_at' => null,
                    ]);

                    return;
                }

                AdminNotification::create([
                    'type' => 'system_error',
                    'title' => 'System Error Captured',
                    'message' => $this->notificationMessage($summary, $path),
                    'icon' => 'Bug',
                    'color' => $severity === 'high' ? 'red' : 'orange',
                    'link' => '/admin/system/errors',
                    'data' => [
                        'fingerprint' => $fingerprint,
                        'count' => 1,
                        'path' => $path,
                        'exception' => class_basename($e),
                    ],
                ]);
            } catch (Throwable $reportError) {
                // Fallback to default reporting if our custom logger fails
            }
        });
    }

    /**
     * Build a short, human-readable exception summary for the admin bell.
     */
    private function summarizeException(Throwable $e): string
    {
        $message = trim((string) $e->getMessage());

        if ($e instanceof \Illuminate\Database\QueryException) {
            if (preg_match("/Table ['\`](?:[^'\`]+\\.)?([^'\`]+)['\`] doesn't exist/i", $message, $m)) {
                return "Missing database table: {$m[1]}";
            }
            if (preg_match("/Duplicate entry ['\`]([^'\`]+)['\`] for key/i", $message, $m)) {
                return "Duplicate database entry: {$m[1]}";
            }
            if (preg_match('/Base table or view already exists:\s*\d+\s+Table [\'\`](?:[^\'\`]+\\.)?([^\'\`]+)[\'\`]/i', $message, $m)) {
                return "Database table already exists: {$m[1]}";
            }
            if (preg_match('/Duplicate key name [\'\`]([^\'\`]+)[\'\`]/i', $message, $m)) {
                return "Duplicate database index: {$m[1]}";
            }
            if (preg_match('/Identifier name [\'\`]([^\'\`]+)[\'\`] is too long/i', $message, $m)) {
                return "Database index name too long: {$m[1]}";
            }

            return 'Database query failed';
        }

        if ($message === '') {
            return class_basename($e) ?: 'Unknown error';
        }

        // Avoid dumping huge SQL / stack fragments into the bell.
        $message = preg_replace('/\s+/', ' ', $message) ?: $message;

        return Str::limit($message, 140);
    }

    private function notificationMessage(string $summary, string $path = '', int $count = 1): string
    {
        $parts = [$summary];

        if ($path !== '') {
            $parts[] = $path;
        }

        if ($count > 1) {
            $parts[] = "×{$count}";
        }

        return implode(' · ', $parts);
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
