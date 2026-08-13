<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

// --- Minimal .env loader (no Composer required for v1) ----------------
$envFile = BASE_PATH . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (strlen($value) >= 2 && $value[0] === '"' && str_ends_with($value, '"')) {
            $value = substr($value, 1, -1);
        }
        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }
}

// --- Minimal PSR-4-ish autoloader (App\ -> app/) -----------------------
spl_autoload_register(function (string $class): void {
    if (!str_starts_with($class, 'App\\')) {
        return;
    }
    $relative = substr($class, strlen('App\\'));
    $path = BASE_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

require BASE_PATH . '/app/Helpers/functions.php';

date_default_timezone_set((string) config('app.timezone', 'UTC'));

if ((bool) config('app.debug', false)) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

App\Core\Session::start();

try {
    $request = new App\Core\Request();
    (new App\Core\Router())->dispatch($request);
} catch (\Throwable $e) {
    http_response_code(500);
    if ((bool) config('app.debug', false)) {
        echo '<pre>' . e($e->getMessage()) . "\n\n" . e($e->getTraceAsString()) . '</pre>';
    } else {
        echo 'Something went wrong. Please try again or contact a technician.';
    }
    error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
}
