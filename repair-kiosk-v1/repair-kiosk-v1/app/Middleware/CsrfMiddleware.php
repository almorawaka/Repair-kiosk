<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;

final class CsrfMiddleware
{
    public function handle(Request $request): bool
    {
        if (Csrf::verify($request->input('_csrf'))) {
            return true;
        }

        Session::flash('error', 'Your session expired. Please try again.');
        http_response_code(419);
        // Best-effort redirect back; if headers already sent (shouldn't
        // happen this early in the pipeline) this still stops execution.
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $path = parse_url($referer, PHP_URL_PATH) ?: '/';
        redirect($path);
        return false;
    }
}
