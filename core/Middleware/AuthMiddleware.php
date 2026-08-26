<?php

namespace Core\Middleware;

use App\Helpers\Flash;
use Core\Auth;
use Core\Request;
use Core\Response;

final class AuthMiddleware
{
    private const RUTAS_PERMITIDAS_CON_CAMBIO_PENDIENTE = ['/admin/cambiar-password', '/admin/logout'];

    public static function handle(): void
    {
        // Auditoria 2026-08-25, hallazgo CFG-01: capa extra junto con la exclusion de /admin/
        // en public/sw.js -- evita que el propio navegador (bfcache, "guardar pagina", algun
        // otro service worker) conserve una copia de una pagina del panel despues del logout.
        header('Cache-Control: no-store, private');

        if (!Auth::check()) {
            Response::redirect('/admin/login');
        }

        if (!Auth::sesionVigente()) {
            Auth::forzarCierre();
            Flash::set('info', 'Tu sesion se cerro porque tus permisos o tu cuenta cambiaron. Inicia sesion de nuevo.');
            Response::redirect('/admin/login');
        }

        if (Auth::debeCambiarPassword() && !in_array((new Request())->uri(), self::RUTAS_PERMITIDAS_CON_CAMBIO_PENDIENTE, true)) {
            Response::redirect('/admin/cambiar-password');
        }
    }
}
