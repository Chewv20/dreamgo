<?php

namespace Core\Middleware;

use Core\Auth;
use Core\Response;

final class AuthMiddleware
{
    public static function handle(): void
    {
        if (!Auth::check()) {
            Response::redirect('/admin/login');
        }
    }
}
