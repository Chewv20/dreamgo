<?php

namespace Core\Middleware;

use Core\Auth;
use Core\Exceptions\ForbiddenException;

final class PermissionMiddleware
{
    public static function handle(string $permiso): void
    {
        if (!Auth::hasPermission($permiso)) {
            throw new ForbiddenException("No tienes permiso para: {$permiso}");
        }
    }
}
