<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES);
    }

    protected function redirect(string $path): void
    {
        redirect($path);
    }

    protected function flash(string $type, string $message): void
    {
        Session::flash($type, $message);
    }

    /**
     * Renders app/Views/{view}.php wrapped in the given layout.
     * $data keys are extracted into local variables for the view file.
     * Layouts are plain PHP too — they `echo $content` where the child
     * view's output should appear.
     */
    protected function view(string $view, array $data = [], string $layout = 'kiosk'): void
    {
        $viewPath = BASE_PATH . "/app/Views/{$view}.php";
        if (!is_file($viewPath)) {
            throw new \RuntimeException("View not found: {$view}");
        }

        $flashes = Session::pullFlashes();
        $csrfToken = Csrf::token();

        extract($data, EXTR_SKIP);
        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        $layoutPath = BASE_PATH . "/app/Views/layouts/{$layout}.php";
        if (is_file($layoutPath)) {
            require $layoutPath;
        } else {
            echo $content;
        }
    }
}
