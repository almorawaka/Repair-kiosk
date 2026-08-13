<?php
declare(strict_types=1);

/**
 * app/Helpers/functions.php
 * ---------------------------------------------------------------------
 * Global helpers loaded once at bootstrap (see public/index.php).
 * Kept deliberately small — this is not a framework, just the handful
 * of things every view and controller needs.
 * ---------------------------------------------------------------------
 */

/** Read a value from the parsed .env file, with a default fallback. */
function env(string $key, mixed $default = null): mixed
{
    if (array_key_exists($key, $_ENV)) {
        $value = $_ENV[$key];
    } else {
        $value = getenv($key);
        if ($value === false) {
            return $default;
        }
    }

    return match (strtolower((string) $value)) {
        'true', '(true)'   => true,
        'false', '(false)' => false,
        'null', '(null)'   => null,
        'empty', '(empty)' => '',
        default => $value,
    };
}

/**
 * Load one of the app/Config/*.php files and read a dot-notation key
 * out of it, e.g. config('app.kiosk_idle_seconds') or config('database').
 * Files are cached per-request so a view rendering ten times doesn't
 * re-`require` the same config array ten times.
 */
function config(string $key, mixed $default = null): mixed
{
    static $cache = [];

    [$file, $path] = array_pad(explode('.', $key, 2), 2, null);

    if (!isset($cache[$file])) {
        $configPath = BASE_PATH . "/app/Config/{$file}.php";
        $cache[$file] = is_file($configPath) ? require $configPath : [];
    }

    if ($path === null) {
        return $cache[$file];
    }

    $value = $cache[$file];
    foreach (explode('.', $path) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

/** Shorthand for htmlspecialchars() with sane defaults, used in every view. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Build an absolute URL from a path, using APP_URL as the base. */
function url(string $path = ''): string
{
    $base = rtrim((string) config('app.url', ''), '/');
    return $base . '/' . ltrim($path, '/');
}

/** Build a URL to a file under public/assets. */
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/** Redirect the browser and stop execution. */
function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}
