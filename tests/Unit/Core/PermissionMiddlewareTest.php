<?php

namespace Tests\Unit\Core;

use Core\Exceptions\ForbiddenException;
use Core\Middleware\PermissionMiddleware;
use PHPUnit\Framework\TestCase;

/**
 * PermissionMiddleware es lo que hace cumplir el RBAC granular en cada ruta admin con la
 * opcion 'permiso' (ver config/routes.php). Un fallo aca abre el panel entero.
 */
final class PermissionMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testDejaPasarConElPermisoExacto(): void
    {
        $_SESSION['admin_permisos'] = ['paquetes.ver', 'reservas.ver'];

        $this->expectNotToPerformAssertions();
        PermissionMiddleware::handle('paquetes.ver');
    }

    public function testRechazaSinElPermiso(): void
    {
        $_SESSION['admin_permisos'] = ['paquetes.ver'];

        $this->expectException(ForbiddenException::class);
        PermissionMiddleware::handle('reservas.cancelar');
    }

    public function testRechazaSinNingunPermisoEnSesion(): void
    {
        $this->expectException(ForbiddenException::class);
        PermissionMiddleware::handle('paquetes.ver');
    }

    public function testNoHayCoincidenciaParcialNiPorPrefijo(): void
    {
        $_SESSION['admin_permisos'] = ['paquetes.ver'];

        $this->expectException(ForbiddenException::class);
        PermissionMiddleware::handle('paquetes');
    }
}
