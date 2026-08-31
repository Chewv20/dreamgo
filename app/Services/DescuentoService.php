<?php

declare(strict_types=1);

namespace App\Services;

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
     *
     * Bloquea la fila con FOR UPDATE (igual que ReservaService con salidas) para que
     * dos reservas concurrentes con el mismo codigo, cerca de uso_maximo, no puedan
     * ambas pasar la validacion antes de que cualquiera incremente usos_actuales.
     * Solo tiene efecto real cuando se llama dentro de una transaccion (asi lo hace
     * el unico llamador, ReservaService::crear); fuera de una, MySQL la libera de
     * inmediato al terminar el SELECT.
     */
    public function validar(string $codigo, int $paqueteId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM codigos_descuento WHERE codigo = :codigo FOR UPDATE');
        $stmt->execute(['codigo' => strtoupper($codigo)]);
        $descuento = $stmt->fetch();

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
