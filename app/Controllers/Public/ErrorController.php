<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use Core\Auth;
use Core\Controller;

class ErrorController extends Controller
{
    public function notFound(): void
    {
        // Una ruta inexistente bajo /admin, con sesión iniciada, se muestra dentro del panel
        // (con su menú) en vez de mandar al usuario al sitio público.
        if ($this->enPanel()) {
            $this->view('errors/404', ['adminNombre' => Auth::nombre()], ['title' => 'Página no encontrada | Dream Go', 'heading' => 'Página no encontrada'], 'admin');

            return;
        }

        $this->view('errors/404', [], ['title' => 'Página no encontrada | Dream Go']);
    }

    private function enPanel(): bool
    {
        $uri = $this->request->uri();

        return ($uri === '/admin' || str_starts_with($uri, '/admin/')) && Auth::check();
    }

    public function forbidden(string $message = ''): void
    {
        if ($this->enPanel()) {
            $this->view('errors/403', ['message' => $message, 'adminNombre' => Auth::nombre()], ['title' => 'Acceso no autorizado | Dream Go', 'heading' => 'Acceso no autorizado'], 'admin');

            return;
        }

        $this->view('errors/403', ['message' => $message], ['title' => 'Acceso no autorizado | Dream Go']);
    }

    public function serverError(): void
    {
        $this->view('errors/500', [], ['title' => 'Error del servidor | Dream Go']);
    }
}
