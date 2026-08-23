<?php

namespace Core;

use PDO;

final class Auth
{
    private const SESSION_KEY = 'admin_id';
    private const SESSION_ROL = 'admin_rol_id';
    private const SESSION_PERMISOS = 'admin_permisos';
    private const SESSION_NOMBRE = 'admin_nombre';

    public static function attempt(string $email, string $password): bool
    {
        $db = Database::connection();

        $stmt = $db->prepare(
            'SELECT id, nombre, password_hash, rol_id, activo FROM usuarios_admin WHERE email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $usuario = $stmt->fetch();

        if (!$usuario || (int) $usuario['activo'] !== 1) {
            return false;
        }

        if (!password_verify($password, $usuario['password_hash'])) {
            return false;
        }

        self::login($usuario, $db);

        return true;
    }

    private static function login(array $usuario, PDO $db): void
    {
        session_regenerate_id(true);

        $_SESSION[self::SESSION_KEY] = (int) $usuario['id'];
        $_SESSION[self::SESSION_NOMBRE] = $usuario['nombre'];
        $_SESSION[self::SESSION_ROL] = (int) $usuario['rol_id'];
        $_SESSION[self::SESSION_PERMISOS] = self::cargarPermisos((int) $usuario['rol_id'], $db);

        $update = $db->prepare('UPDATE usuarios_admin SET ultimo_login = NOW() WHERE id = :id');
        $update->execute(['id' => $usuario['id']]);
    }

    private static function cargarPermisos(int $rolId, PDO $db): array
    {
        $stmt = $db->prepare(
            'SELECT p.clave FROM permisos p
             INNER JOIN rol_permiso rp ON rp.permiso_id = p.id
             WHERE rp.rol_id = :rol_id'
        );
        $stmt->execute(['rol_id' => $rolId]);

        return array_column($stmt->fetchAll(), 'clave');
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION[self::SESSION_KEY]);
    }

    public static function id(): ?int
    {
        return $_SESSION[self::SESSION_KEY] ?? null;
    }

    public static function nombre(): ?string
    {
        return $_SESSION[self::SESSION_NOMBRE] ?? null;
    }

    public static function hasPermission(string $clave): bool
    {
        $permisos = $_SESSION[self::SESSION_PERMISOS] ?? [];

        return in_array($clave, $permisos, true);
    }
}
