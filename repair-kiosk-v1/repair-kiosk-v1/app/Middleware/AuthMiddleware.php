<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;

final class AuthMiddleware
{
    public function handle(Request $request): bool
    {
        if (Auth::check()) {
            return true;
        }
        redirect('/staff/login');
        return false;
    }
}
