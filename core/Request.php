<?php

declare(strict_types=1);

namespace Core;

final class Request
{
    private array $query;
    private array $body;
    private array $server;

    public function __construct()
    {
        $this->query = $_GET;
        $this->body = $_POST;
        $this->server = $_SERVER;
    }

    public function method(): string
    {
        $method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');

        if ($method === 'POST' && isset($this->body['_method'])) {
            $override = strtoupper((string) $this->body['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $override;
            }
        }

        return $method;
    }

    public function uri(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';

        if (defined('BASE_URL_PATH') && BASE_URL_PATH !== '' && str_starts_with($path, BASE_URL_PATH)) {
            $path = substr($path, strlen(BASE_URL_PATH));
        }

        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        return $path === '' ? '/' : $path;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    public function ip(): string
    {
        // Por defecto es REMOTE_ADDR; solo mira X-Forwarded-For si TRUSTED_PROXIES esta
        // configurado y la conexion entra por uno de esos rangos (Auditoria 2026-08-31, SEG-01).
        return \App\Helpers\ProxyConfianza::ipCliente($this->server);
    }

    public function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }
}
