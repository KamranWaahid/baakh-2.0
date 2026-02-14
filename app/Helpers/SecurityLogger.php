<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class SecurityLogger
{
    /**
     * Log a security event to the dedicated security log channel.
     *
     * @param string $event
     * @param array $context
     * @param string $level
     * @return void
     */
    public static function log($event, $context = [], $level = 'warning')
    {
        // Add IP and User Agent to context
        $context['ip'] = request()->ip();
        $context['user_agent'] = request()->userAgent();
        $context['user_id'] = auth()->id() ?? 'guest';

        Log::channel('security')->log($level, $event, $context);
    }
}
