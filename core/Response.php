<?php

namespace Core;

final class Response
{
    public static function redirect(string $path): never
    {
        if (defined('BASE_URL_PATH') && BASE_URL_PATH !== '' && str_starts_with($path, '/')) {
            $path = BASE_URL_PATH . $path;
        }

        header('Location: ' . $path);
        exit;
    }

    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function status(int $status): void
    {
        http_response_code($status);
    }
}
