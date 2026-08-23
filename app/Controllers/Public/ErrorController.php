<?php

namespace App\Controllers\Public;

use Core\Controller;

class ErrorController extends Controller
{
    public function notFound(): void
    {
        $this->view('errors/404', [], ['title' => 'Pagina no encontrada | Dream Go']);
    }

    public function forbidden(string $message = ''): void
    {
        $this->view('errors/403', ['message' => $message], ['title' => 'Acceso no autorizado | Dream Go']);
    }

    public function serverError(): void
    {
        $this->view('errors/500', [], ['title' => 'Error del servidor | Dream Go']);
    }
}
