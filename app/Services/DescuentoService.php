<?php

namespace App\Services;

use App\Models\CodigoDescuento;
use PDO;
use RuntimeException;

final class DescuentoService
{
    public function __construct(private readonly PDO $db)
    {
    }

    /**
     * Valida un codigo de descuento contra un paquete especifico. Lanza RuntimeException
     * con un mensaje entendible por el usuario si no es aplicable.
     */
    public function validar(string $codigo, int $paqueteId): array
    {
        $descuento = CodigoDescuento::porCodigo($codigo);

        if (!$descuento) {
            throw new RuntimeException('El codigo de descuento no existe.');
        }

        if ((int) $descuento['activo'] !== 1) {
            throw new RuntimeException('Este codigo ya no esta activo.');
        }

        $hoy = date('Y-m-d');
        if ($hoy < $descuento['fecha_inicio'] || $hoy > $descuento['fecha_fin']) {
            throw new RuntimeException('Este codigo no esta vigente en la fecha actual.');
        }

        if ($descuento['alcance'] === 'paquete' && (int) $descuento['paquete_id'] !== $paqueteId) {
            throw new RuntimeException('Este codigo no aplica para el paquete seleccionado.');
        }

        if ($descuento['uso_maximo'] !== null && (int) $descuento['usos_actuales'] >= (int) $descuento['uso_maximo']) {
            throw new RuntimeException('Este codigo alcanzo su limite de usos.');
        }

        return $descuento;
    }

    public function calcularPrecioConDescuento(float $precioOriginal, array $descuento): float
    {
        if ($descuento['tipo'] === 'porcentaje') {
            $precioConDescuento = $precioOriginal - ($precioOriginal * ((float) $descuento['valor'] / 100));
        } else {
            $precioConDescuento = $precioOriginal - (float) $descuento['valor'];
        }

        return max(0, round($precioConDescuento, 2));
    }

    public function registrarUso(int $descuentoId): void
    {
        $stmt = $this->db->prepare('UPDATE codigos_descuento SET usos_actuales = usos_actuales + 1 WHERE id = :id');
        $stmt->execute(['id' => $descuentoId]);
    }
}
