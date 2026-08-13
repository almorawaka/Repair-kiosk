<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;

/**
 * File-based rate limiter for unauthenticated public endpoints
 * (/track/{token}, /api/jobs/{token}/status). Good enough for a
 * single-server kiosk deployment; swap for Redis if this ever runs
 * behind a load balancer.
 */
final class ThrottleMiddleware
{
    private const MAX_REQUESTS = 60;   // per window, per IP
    private const WINDOW_SECONDS = 60;

    public function handle(Request $request): bool
    {
        $dir = BASE_PATH . '/storage/cache/throttle';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $key = md5($request->ip());
        $file = "{$dir}/{$key}.json";

        $now = time();
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;

        if (!is_array($data) || ($now - ($data['window_start'] ?? 0)) > self::WINDOW_SECONDS) {
            $data = ['window_start' => $now, 'count' => 0];
        }

        $data['count']++;

        if ($data['count'] > self::MAX_REQUESTS) {
            http_response_code(429);
            echo 'Too many requests. Please try again shortly.';
            return false;
        }

        @file_put_contents($file, json_encode($data));
        return true;
    }
}
