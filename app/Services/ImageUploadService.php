<?php

namespace App\Services;

use RuntimeException;

final class ImageUploadService
{
    private const MIME_PERMITIDOS = ['image/jpeg', 'image/png', 'image/webp'];
    private const ANCHO_MAX_ORIGINAL = 1600;
    private const ANCHO_MAX_THUMB = 480;
    private const CALIDAD_JPEG = 82;

    /**
     * Procesa un archivo subido ($_FILES[campo]) y devuelve las rutas publicas
     * (relativas, listas para guardarse en BD) de la imagen original redimensionada y su miniatura.
     *
     * @return array{original: string, thumb: string}
     */
    public function procesar(array $archivo, string $slugBase): array
    {
        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se pudo subir el archivo.');
        }

        $mime = mime_content_type($archivo['tmp_name']);
        if (!in_array($mime, self::MIME_PERMITIDOS, true)) {
            throw new RuntimeException('Formato de imagen no permitido. Usa JPG, PNG o WEBP.');
        }

        $origen = $this->crearImagenDesdeArchivo($archivo['tmp_name'], $mime);
        $nombreArchivo = $this->nombreUnico($slugBase);

        $rutaOriginal = 'uploads/paquetes/original/' . $nombreArchivo;
        $rutaThumb = 'uploads/paquetes/thumbs/' . $nombreArchivo;

        $this->guardarRedimensionado($origen, BASE_PATH . '/public/' . $rutaOriginal, self::ANCHO_MAX_ORIGINAL);
        $this->guardarRedimensionado($origen, BASE_PATH . '/public/' . $rutaThumb, self::ANCHO_MAX_THUMB);

        return [
            'original' => '/' . $rutaOriginal,
            'thumb' => '/' . $rutaThumb,
        ];
    }

    private function crearImagenDesdeArchivo(string $rutaTemporal, string $mime): \GdImage
    {
        $imagen = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($rutaTemporal),
            'image/png' => imagecreatefrompng($rutaTemporal),
            'image/webp' => imagecreatefromwebp($rutaTemporal),
            default => throw new RuntimeException('Formato no soportado.'),
        };

        if ($imagen === false) {
            throw new RuntimeException('No se pudo procesar la imagen.');
        }

        return $imagen;
    }

    private function guardarRedimensionado(\GdImage $origen, string $rutaDestino, int $anchoMax): void
    {
        $anchoOriginal = imagesx($origen);
        $altoOriginal = imagesy($origen);

        $escala = min(1, $anchoMax / $anchoOriginal);
        $anchoDestino = (int) round($anchoOriginal * $escala);
        $altoDestino = (int) round($altoOriginal * $escala);

        $lienzo = imagecreatetruecolor($anchoDestino, $altoDestino);
        $blanco = imagecolorallocate($lienzo, 255, 255, 255);
        imagefill($lienzo, 0, 0, $blanco);

        imagecopyresampled($lienzo, $origen, 0, 0, 0, 0, $anchoDestino, $altoDestino, $anchoOriginal, $altoOriginal);
        imagejpeg($lienzo, $rutaDestino, self::CALIDAD_JPEG);
    }

    private function nombreUnico(string $slugBase): string
    {
        return $slugBase . '-' . bin2hex(random_bytes(6)) . '.jpg';
    }
}
