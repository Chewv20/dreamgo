<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class ImageUploadService
{
    private const MIME_PERMITIDOS = ['image/jpeg', 'image/png', 'image/webp'];
    private const ANCHO_MAX_ORIGINAL = 1600;
    private const ANCHO_MAX_THUMB = 480;
    private const CALIDAD_JPEG = 82;

    /**
     * Tope de pixeles totales (ancho x alto) aceptados antes de decodificar. Frena las "bombas
     * de descompresion": un PNG de pocos KB puede declarar 30000x30000 y reventar la memoria
     * en imagecreatefrompng(). 40 MP cubre de sobra cualquier foto real (~7000x5700).
     */
    private const PIXELES_MAX = 40_000_000;

    /**
     * Procesa un archivo subido ($_FILES[campo]) y devuelve las rutas publicas
     * (relativas, listas para guardarse en BD) de la imagen original redimensionada y su miniatura.
     *
     * @param string $carpeta subcarpeta bajo public/uploads/ (p. ej. 'paquetes', 'articulos').
     *   Se crea si no existe. El .htaccess anti-ejecucion de public/uploads/ la cubre por
     *   herencia de Apache.
     * @return array{original: string, thumb: string}
     */
    public function procesar(array $archivo, string $slugBase, string $carpeta = 'paquetes'): array
    {
        $carpeta = trim($carpeta, '/');
        if (preg_match('/^[a-z0-9_-]+$/', $carpeta) !== 1) {
            throw new RuntimeException('Carpeta de destino invalida.');
        }

        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se pudo subir el archivo.');
        }

        $rutaTemporal = (string) ($archivo['tmp_name'] ?? '');
        if ($rutaTemporal === '' || !is_uploaded_file($rutaTemporal)) {
            throw new RuntimeException('El archivo no es una subida valida.');
        }

        $mime = mime_content_type($rutaTemporal);
        if (!in_array($mime, self::MIME_PERMITIDOS, true)) {
            throw new RuntimeException('Formato de imagen no permitido. Usa JPG, PNG o WEBP.');
        }

        $dimensiones = @getimagesize($rutaTemporal);
        if ($dimensiones === false) {
            throw new RuntimeException('No se pudo leer la imagen.');
        }
        if ((int) $dimensiones[0] * (int) $dimensiones[1] > self::PIXELES_MAX) {
            throw new RuntimeException('La imagen tiene dimensiones excesivas.');
        }

        $origen = $this->crearImagenDesdeArchivo($rutaTemporal, $mime);
        $nombreArchivo = $this->nombreUnico($slugBase);

        $rutaOriginal = 'uploads/' . $carpeta . '/original/' . $nombreArchivo;
        $rutaThumb = 'uploads/' . $carpeta . '/thumbs/' . $nombreArchivo;

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
        $directorio = dirname($rutaDestino);
        if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
            throw new RuntimeException('No se pudo crear el directorio de destino de la imagen.');
        }

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
