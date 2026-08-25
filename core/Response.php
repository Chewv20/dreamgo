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

    /**
     * @param list<string> $encabezados
     * @param list<list<scalar|null>> $filas
     */
    public static function csv(string $nombreArchivo, array $encabezados, array $filas): never
    {
        http_response_code(200);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');

        // BOM UTF-8: sin esto Excel interpreta el archivo como ANSI y rompe los acentos.
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');
        // Escape explicito: PHP 8.4+ marca como deprecated el default implicito de $escape.
        fputcsv($out, $encabezados, ',', '"', '\\');
        foreach ($filas as $fila) {
            fputcsv($out, $fila, ',', '"', '\\');
        }
        fclose($out);
        exit;
    }
}
