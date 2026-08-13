<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;

/**
 * Restricts /kiosk/* and /api/scan/* to the physical kiosk terminal's
 * IP address(es), configured via KIOSK_ALLOWED_IPS in .env. An empty
 * allowlist disables the check — the default for local development,
 * since your dev machine's IP changes across networks.
 */
final class KioskMiddleware
{
    public function handle(Request $request): bool
    {
        $allowed = config('app.kiosk_allowed_ips', []);

        if (empty($allowed)) {
            return true;
        }

        if (in_array($request->ip(), $allowed, true)) {
            return true;
        }

        http_response_code(403);
        echo 'This terminal is not authorized to access the kiosk.';
        return false;
    }
}
