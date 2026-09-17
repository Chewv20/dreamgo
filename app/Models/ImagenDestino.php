<?php

declare(strict_types=1);

namespace App\Models;

class ImagenDestino extends GaleriaImagen
{
    protected static string $table = 'imagenes_destino';
    protected static string $columnaPadre = 'categoria_id';
}
