<?php

namespace Core;

use Core\Exceptions\NotFoundException;
use Core\Middleware\AuthMiddleware;
use Core\Middleware\PermissionMiddleware;

final class Router
{
    /** @var array<int, array{method: string, pattern: string, regex: string, handler: array, options: array}> */
    private array $routes = [];

    public function get(string $pattern, array $handler, array $options = []): void
    {
        $this->add('GET', $pattern, $handler, $options);
    }

    public function post(string $pattern, array $handler, array $options = []): void
    {
        $this->add('POST', $pattern, $handler, $options);
    }

    public function put(string $pattern, array $handler, array $options = []): void
    {
        $this->add('PUT', $pattern, $handler, $options);
    }

    public function delete(string $pattern, array $handler, array $options = []): void
    {
        $this->add('DELETE', $pattern, $handler, $options);
    }

    private function add(string $method, string $pattern, array $handler, array $options): void
    {
        $regex = preg_replace('#\{[a-zA-Z_]+\}#', '([^/]+)', $pattern);

        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'regex' => '#^' . $regex . '$#',
            'handler' => $handler,
            'options' => $options,
        ];
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $uri = $request->uri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['regex'], $uri, $matches)) {
                array_shift($matches);
                $this->runMiddleware($route['options']);
                $this->callHandler($route['handler'], $matches, $request);

                return;
            }
        }

        throw new NotFoundException("Ruta no encontrada: {$method} {$uri}");
    }

    private function runMiddleware(array $options): void
    {
        if (!empty($options['auth'])) {
            AuthMiddleware::handle();
        }

        if (!empty($options['permiso'])) {
            PermissionMiddleware::handle($options['permiso']);
        }
    }

    private function callHandler(array $handler, array $params, Request $request): void
    {
        [$controllerClass, $method] = $handler;

        $controller = new $controllerClass($request);
        $controller->$method(...$params);
    }
}
