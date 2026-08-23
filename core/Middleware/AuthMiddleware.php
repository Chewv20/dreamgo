<?php

namespace Core\Middleware;

use App\Helpers\Flash;
use Core\Auth;
use Core\Response;

final class AuthMiddleware
{
    public static function handle(): void
    {
        if (!Auth::check()) {
            Response::redirect('/admin/login');
        }

        if (!Auth::sesionVigente()) {
            Auth::forzarCierre();
            Flash::set('info', 'Tu sesion se cerro porque tus permisos o tu cuenta cambiaron. Inicia sesion de nuevo.');
            Response::redirect('/admin/login');
        }
    }
}
