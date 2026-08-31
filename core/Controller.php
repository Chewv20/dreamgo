<?php

declare(strict_types=1);

namespace Core;

abstract class Controller
{
    protected \PDO $db;
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->db = Database::connection();
        $this->request = $request;
    }

    protected function view(string $view, array $data = [], array $meta = [], string $layout = 'public'): void
    {
        $viewFile = BASE_PATH . '/app/Views/' . str_replace('.', '/', $view) . '.php';

        if (!is_file($viewFile)) {
            throw new Exceptions\NotFoundException("Vista no encontrada: {$view}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        $meta = array_merge([
            'title' => 'Dream Go Operadora Turistica',
            'description' => 'Excursiones y paquetes de viaje nacionales e internacionales.',
            'ogImage' => '/assets/img/logo.avif',
        ], $meta);

        require BASE_PATH . '/app/Views/layouts/' . $layout . '.php';
    }

    protected function redirect(string $path): never
    {
        Response::redirect($path);
    }

    protected function json(mixed $data, int $status = 200): never
    {
        Response::json($data, $status);
    }

    protected function xml(string $body, int $status = 200): never
    {
        Response::xml($body, $status);
    }

    protected function abort(int $status, string $message = ''): never
    {
        Response::status($status);
        $view = match ($status) {
            403 => 'errors/403',
            404 => 'errors/404',
            429 => 'errors/429',
            default => 'errors/500',
        };
        $this->view($view, ['message' => $message]);
        exit;
    }

    protected function verifyCsrf(): void
    {
        if (!\App\Helpers\Csrf::verify($this->request->input('_csrf'))) {
            $this->abort(403, 'Token de seguridad invalido. Actualiza la pagina e intenta de nuevo.');
        }
    }
}
