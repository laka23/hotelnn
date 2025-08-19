<?php

class BaseController {
    protected function render(string $viewPath, array $params = []): string {
        extract($params, EXTR_SKIP);
        ob_start();
        include __DIR__ . '/../views/layout/header.php';
        include __DIR__ . '/../views/' . $viewPath . '.php';
        include __DIR__ . '/../views/layout/footer.php';
        return ob_get_clean();
    }

    protected function json($data, int $status = 200): string {
        http_response_code($status);
        header('Content-Type: application/json');
        return json_encode($data);
    }

    protected function redirect(string $url): void {
        header('Location: ' . $url);
        exit;
    }
}

