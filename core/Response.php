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
        fputcsv($out, array_map([self::class, 'sanearCeldaCsv'], $encabezados), ',', '"', '\\');
        foreach ($filas as $fila) {
            fputcsv($out, array_map([self::class, 'sanearCeldaCsv'], $fila), ',', '"', '\\');
        }
        fclose($out);
        exit;
    }

    /**
     * Defensa contra inyeccion de formulas (CSV injection): Excel/LibreOffice/Sheets ejecutan
     * el contenido de una celda que empieza con = + @ o un caracter de control. Como los
     * listados exportados incluyen texto que viene de formularios publicos anonimos (nombre,
     * mensaje, referrer, utm_*), se antepone un apostrofo a esas celdas para forzarlas a
     * texto. El '-' solo se neutraliza si no es un numero negativo legitimo, para no convertir
     * montos como -100 en texto.
     */
    private static function sanearCeldaCsv(mixed $valor): mixed
    {
        if (!is_string($valor) || $valor === '') {
            return $valor;
        }

        $primero = $valor[0];
        $esFormula = in_array($primero, ['=', '+', '@', "\t", "\r", "\n"], true)
            || ($primero === '-' && !is_numeric($valor));

        return $esFormula ? "'" . $valor : $valor;
    }

    /**
     * Devuelve bytes crudos como descarga (PDF, etc.). $contenido ya viene generado en memoria.
     */
    public static function archivo(string $nombreArchivo, string $contenido, string $mime): never
    {
        // El rewrite de rutas de public/index.php (ob_start solo activo si BASE_URL_PATH != '')
        // opera sobre texto HTML; se descartan los buffers pendientes para no arriesgar el binario.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code(200);
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Content-Length: ' . strlen($contenido));
        echo $contenido;
        exit;
    }
}
