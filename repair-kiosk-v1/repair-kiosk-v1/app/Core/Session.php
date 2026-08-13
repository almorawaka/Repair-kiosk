<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Thin wrapper around PHP's native session, plus one-shot "flash"
 * messages (errors/success banners that survive exactly one redirect).
 */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name((string) config('app.session.name', 'repair_kiosk_session'));
        session_set_cookie_params([
            'lifetime' => (int) config('app.session.lifetime_minutes', 120) * 60,
            'path'     => '/',
            'secure'   => (bool) config('app.session.secure_cookie', false),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function clear(): void
    {
        $_SESSION = [];
    }

    /** Store a message that survives exactly one subsequent request. */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][$type] = $message;
    }

    /** Read + clear all flash messages for rendering in a view. */
    public static function pullFlashes(): array
    {
        $flashes = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flashes;
    }

    /**
     * Kiosk multi-step wizards store their in-progress data here rather
     * than in the DB, so an abandoned drop-off never leaves a half-built
     * row behind. Cleared explicitly after the job is created or when
     * the kiosk returns to its idle screen.
     */
    public static function wizard(string $flow): array
    {
        return $_SESSION['_wizard'][$flow] ?? [];
    }

    public static function wizardPut(string $flow, string $key, mixed $value): void
    {
        $_SESSION['_wizard'][$flow][$key] = $value;
    }

    public static function wizardClear(string $flow): void
    {
        unset($_SESSION['_wizard'][$flow]);
    }
}
