<?php

namespace Tests\Unit\Helpers;

use App\Helpers\Auditoria;
use PHPUnit\Framework\TestCase;

/**
 * Auditoria::registrar() envuelve todo en try/catch: una bitacora rota (BD caida, tabla
 * ausente, sesion sin arrancar) nunca debe propagar una excepcion a la accion que se esta
 * auditando. Sin BD disponible en el entorno de test, la llamada debe completarse en silencio.
 */
final class AuditoriaTest extends TestCase
{
    public function testRegistrarNoLanzaSinBaseDeDatos(): void
    {
        Auditoria::registrar('test.accion', 'test', 123, 'detalle de prueba');
        Auditoria::registrar('test.minima');

        $this->assertTrue(true, 'registrar() debe completarse sin excepcion');
    }

    public function testRegistrarNoLanzaConDetalleMuyLargo(): void
    {
        Auditoria::registrar('test.accion', 'test', 1, str_repeat('x', 5000));

        $this->assertTrue(true);
    }
}
