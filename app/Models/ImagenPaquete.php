<?php

declare(strict_types=1);

namespace App\Models;

class ImagenPaquete extends GaleriaImagen
{
    protected static string $table = 'imagenes_paquete';
    protected static string $columnaPadre = 'paquete_id';
}
