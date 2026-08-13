<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    private array $data;
    private string $method;
    private string $path;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        // Strip the app's base path so routing works whether the app
        // lives at the web root or in a subfolder like /repair-kiosk/public.
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if ($scriptDir !== '' && str_starts_with($uri, $scriptDir)) {
            $uri = substr($uri, strlen($scriptDir));
        }
        $this->path = '/' . ltrim($uri === '' ? '/' : $uri, '/');

        $this->data = array_merge($_GET, $_POST);

        $raw = file_get_contents('php://input') ?: '';
        if ($raw !== '' && str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $this->data = array_merge($this->data, $decoded);
            }
        }
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->data;
    }

    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
