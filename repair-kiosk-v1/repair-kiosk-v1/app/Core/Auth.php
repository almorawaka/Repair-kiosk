<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;

final class Auth
{
    private const SESSION_KEY = '_staff_user';

    public static function attempt(string $username, string $password): bool
    {
        $user = User::findByUsername($username);

        if ($user === null || (int) $user['is_active'] !== 1) {
            return false;
        }

        if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            User::registerFailedLogin((int) $user['id']);
            return false;
        }

        User::registerSuccessfulLogin((int) $user['id']);

        $_SESSION[self::SESSION_KEY] = [
            'id'        => (int) $user['id'],
            'username'  => $user['username'],
            'full_name' => $user['full_name'],
            'role'      => $user['role'],
        ];

        return true;
    }

    public static function check(): bool
    {
        return isset($_SESSION[self::SESSION_KEY]);
    }

    public static function user(): ?array
    {
        return $_SESSION[self::SESSION_KEY] ?? null;
    }

    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    public static function hasRole(string $role): bool
    {
        return (self::user()['role'] ?? null) === $role;
    }

    public static function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }
}
