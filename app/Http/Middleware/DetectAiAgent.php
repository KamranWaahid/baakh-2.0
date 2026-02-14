<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectAiAgent
{
    /**
     * Known AI/search engine bot user-agent patterns.
     */
    private const BOT_PATTERNS = [
        'GPTBot',
        'ChatGPT-User',
        'ClaudeBot',
        'Anthropic',
        'PerplexityBot',
        'Googlebot',
        'Bingbot',
        'Google-Extended',
        'CCBot',
        'FacebookExternalHit',
        'Twitterbot',
        'LinkedInBot',
        'Slurp',
        'DuckDuckBot',
        'Baiduspider',
        'YandexBot',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userAgent = $request->userAgent() ?? '';
        $isBot = false;
        $botName = null;

        foreach (self::BOT_PATTERNS as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                $isBot = true;
                $botName = $pattern;
                break;
            }
        }

        $request->attributes->set('is_ai_agent', $isBot);
        $request->attributes->set('bot_name', $botName);

        $response = $next($request);

        // Add enhanced headers for AI agents
        if ($isBot) {
            $response->headers->set('X-Mokhii-Agent-Detected', $botName);
            $response->headers->set('X-Mokhii-Structured-API', rtrim(config('app.url'), '/') . '/api/mokhii/health');
        }

        return $response;
    }
}
