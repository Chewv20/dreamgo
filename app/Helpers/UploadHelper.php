<?php

declare(strict_types=1);

namespace App\Helpers;

final class UploadHelper
{
    /**
     * PHP entrega `name="imagenes[]" multiple` como $_FILES['imagenes'] en formato
     * "columnar" (name/tmp_name/error/size son arreglos indexados por posicion, no una
     * lista de archivos). Esto lo convierte en una lista de arreglos "por archivo",
     * listos para pasarle uno por uno a ImageUploadService::procesar(). Los slots vacios
     * (el usuario selecciono menos archivos de los que el input permite) se descartan.
     *
     * @return list<array{name: string, type: string, tmp_name: string, error: int, size: int}>
     */
    public static function listaArchivos(?array $campo): array
    {
        if ($campo === null || !isset($campo['name']) || !is_array($campo['name'])) {
            return [];
        }

        $lista = [];
        foreach ($campo['name'] as $i => $nombre) {
            $error = $campo['error'][$i] ?? UPLOAD_ERR_NO_FILE;
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $lista[] = [
                'name' => (string) $nombre,
                'type' => (string) ($campo['type'][$i] ?? ''),
                'tmp_name' => (string) ($campo['tmp_name'][$i] ?? ''),
                'error' => (int) $error,
                'size' => (int) ($campo['size'][$i] ?? 0),
            ];
        }

        return $lista;
    }
}
