<?php

declare(strict_types=1);

namespace App;

final class View
{
    public static function render(string $template, array $data = [], int $status = 200): never
    {
        http_response_code($status);
        extract($data, EXTR_SKIP);

        $viewFile = __DIR__ . '/Views/' . $template . '.php';

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require __DIR__ . '/Views/layout.php';
        exit;
    }
}
