<?php
declare(strict_types=1);

namespace App\Core;

use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\KioskMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\ThrottleMiddleware;

/**
 * Matches the current request against app/Config/routes.php and
 * dispatches to the target controller method, running any declared
 * middleware first. Route keys look like:
 *
 *   'POST /kiosk/dropoff/scan' => [Controller::class, 'method', ['kiosk','csrf']]
 *
 * {param} segments become positional arguments to the controller
 * method, passed after the Request object.
 */
final class Router
{
    private const MIDDLEWARE_MAP = [
        'auth'     => AuthMiddleware::class,
        'csrf'     => CsrfMiddleware::class,
        'kiosk'    => KioskMiddleware::class,
        'throttle' => ThrottleMiddleware::class,
        // 'role:admin' is handled separately below — RoleMiddleware
        // needs the role name, which the plain map above can't carry.
    ];

    public function dispatch(Request $request): void
    {
        $routes = config('routes');
        $method = $request->method();
        $path   = $request->path();

        foreach ($routes as $pattern => $definition) {
            [$routeMethod, $routePath] = explode(' ', $pattern, 2);
            if ($routeMethod !== $method) {
                continue;
            }

            $params = $this->match($routePath, $path);
            if ($params === null) {
                continue;
            }

            [$controllerClass, $action, $middleware] = array_pad($definition, 3, []);

            foreach ($middleware as $rule) {
                if (!$this->runMiddleware($rule, $request)) {
                    return; // middleware already sent its own response
                }
            }

            if (!class_exists($controllerClass)) {
                $this->notImplemented($controllerClass);
                return;
            }

            $controller = new $controllerClass();
            $controller->$action($request, ...$params);
            return;
        }

        $this->notFound();
    }

    /** Returns captured {param} values in order, or null if no match. */
    private function match(string $routePath, string $requestPath): ?array
    {
        $paramNames = [];
        $regex = preg_replace_callback('/\{(\w+)\}/', function ($m) use (&$paramNames) {
            $paramNames[] = $m[1];
            return '([^/]+)';
        }, $routePath);

        if (!preg_match('#^' . $regex . '$#', $requestPath, $matches)) {
            return null;
        }

        array_shift($matches);
        return array_values($matches);
    }

    private function runMiddleware(string $rule, Request $request): bool
    {
        if (str_starts_with($rule, 'role:')) {
            [, $role] = explode(':', $rule, 2);
            return (new RoleMiddleware())->handle($request, $role);
        }

        $class = self::MIDDLEWARE_MAP[$rule] ?? null;
        if ($class === null || !class_exists($class)) {
            // Unknown middleware name is a config error, not a request
            // error — fail closed rather than silently letting it through.
            http_response_code(500);
            echo "Unknown middleware: {$rule}";
            return false;
        }

        return (new $class())->handle($request);
    }

    private function notFound(): void
    {
        http_response_code(404);
        $path = BASE_PATH . '/app/Views/errors/404.php';
        if (is_file($path)) {
            require $path;
        } else {
            echo '404 Not Found';
        }
    }

    private function notImplemented(string $controllerClass): void
    {
        http_response_code(501);
        if ((bool) config('app.debug', false)) {
            echo "Route matched but controller not implemented yet: {$controllerClass}";
        } else {
            echo 'This page is not available yet.';
        }
    }
}
