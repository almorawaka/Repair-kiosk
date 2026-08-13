<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;

final class RoleMiddleware
{
    public function handle(Request $request, string $role): bool
    {
        if (Auth::hasRole($role)) {
            return true;
        }
        http_response_code(403);
        echo "You don't have permission to view this page.";
        return false;
    }
}
