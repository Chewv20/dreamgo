<?php

namespace Core;

use App\Models\IntentoLogin;
use PDO;

final class Auth
{
    private const SESSION_KEY = 'admin_id';
    private const SESSION_ROL = 'admin_rol_id';
    private const SESSION_PERMISOS = 'admin_permisos';
    private const SESSION_NOMBRE = 'admin_nombre';
    private const SESSION_USUARIO_SELLO = 'admin_usuario_sello';
    private const SESSION_PERMISOS_SELLO = 'admin_permisos_sello';
    private const SESSION_DEBE_CAMBIAR_PASSWORD = 'admin_debe_cambiar_password';

    private const VENTANA_MINUTOS = 15;
    private const MAX_INTENTOS_EMAIL = 5;
    private const MAX_INTENTOS_IP = 15;

    /**
     * Ventana movil: cada intento (incluso mientras esta bloqueado) queda
     * registrado, asi que insistir extiende el bloqueo en vez de acortarlo.
     */
    public static function bloqueado(string $email, string $ip): bool
    {
        return IntentoLogin::fallidosPorEmail($email, self::VENTANA_MINUTOS) >= self::MAX_INTENTOS_EMAIL
            || IntentoLogin::fallidosPorIp($ip, self::VENTANA_MINUTOS) >= self::MAX_INTENTOS_IP;
    }

    public static function attempt(string $email, string $password, string $ip): bool
    {
        if (self::bloqueado($email, $ip)) {
            IntentoLogin::registrar($email, $ip, false);

            return false;
        }

        $db = Database::connection();

        $stmt = $db->prepare(
            'SELECT id, nombre, password_hash, rol_id, activo, actualizado_en, debe_cambiar_password FROM usuarios_admin WHERE email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $usuario = $stmt->fetch();

        if (!$usuario || (int) $usuario['activo'] !== 1 || !password_verify($password, $usuario['password_hash'])) {
            IntentoLogin::registrar($email, $ip, false);

            return false;
        }

        IntentoLogin::registrar($email, $ip, true);
        self::login($usuario, $db);

        return true;
    }

    private static function login(array $usuario, PDO $db): void
    {
        session_regenerate_id(true);

        $update = $db->prepare('UPDATE usuarios_admin SET ultimo_login = NOW() WHERE id = :id');
        $update->execute(['id' => $usuario['id']]);

        // El UPDATE anterior tambien recalcula actualizado_en (ON UPDATE CURRENT_TIMESTAMP),
        // asi que releemos el valor final para que el sello guardado en sesion coincida
        // con el que vera sesionVigente() en la siguiente peticion.
        $selloActual = $db->prepare('SELECT actualizado_en FROM usuarios_admin WHERE id = :id');
        $selloActual->execute(['id' => $usuario['id']]);

        $_SESSION[self::SESSION_KEY] = (int) $usuario['id'];
        $_SESSION[self::SESSION_NOMBRE] = $usuario['nombre'];
        $_SESSION[self::SESSION_ROL] = (int) $usuario['rol_id'];
        $_SESSION[self::SESSION_PERMISOS] = self::cargarPermisos((int) $usuario['rol_id'], $db);
        $_SESSION[self::SESSION_USUARIO_SELLO] = $selloActual->fetchColumn();
        $_SESSION[self::SESSION_PERMISOS_SELLO] = self::permisosSello((int) $usuario['rol_id'], $db);
        $_SESSION[self::SESSION_DEBE_CAMBIAR_PASSWORD] = (bool) $usuario['debe_cambiar_password'];
    }

    /**
     * Verifica que la sesion siga reflejando el estado actual del usuario y
     * su rol (activo, rol asignado, permisos del rol). Si algo cambio desde
     * el login, cierra la sesion para forzar que se autentique de nuevo con
     * los datos frescos.
     */
    public static function sesionVigente(): bool
    {
        if (!self::check()) {
            return false;
        }

        $db = Database::connection();
        $stmt = $db->prepare(
            'SELECT rol_id, activo, actualizado_en, debe_cambiar_password FROM usuarios_admin WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => self::id()]);
        $usuario = $stmt->fetch();

        if (!$usuario || (int) $usuario['activo'] !== 1) {
            return false;
        }

        if ((int) $usuario['rol_id'] !== ($_SESSION[self::SESSION_ROL] ?? null)) {
            return false;
        }

        if ($usuario['actualizado_en'] !== ($_SESSION[self::SESSION_USUARIO_SELLO] ?? null)) {
            return false;
        }

        if (self::permisosSello((int) $usuario['rol_id'], $db) !== ($_SESSION[self::SESSION_PERMISOS_SELLO] ?? null)) {
            return false;
        }

        // Se refresca en cada request para reflejar de inmediato un cambio de password.
        $_SESSION[self::SESSION_DEBE_CAMBIAR_PASSWORD] = (bool) $usuario['debe_cambiar_password'];

        return true;
    }

    private static function permisosSello(int $rolId, PDO $db): ?string
    {
        $stmt = $db->prepare('SELECT permisos_actualizado_en FROM roles WHERE id = :rol_id');
        $stmt->execute(['rol_id' => $rolId]);

        return $stmt->fetchColumn() ?: null;
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

    /**
     * Cierra la sesion administrativa sin destruir la sesion HTTP completa,
     * para poder mostrar un mensaje flash en la siguiente carga (login).
     * Usado cuando se detecta que los permisos o el rol del usuario cambiaron.
     */
    public static function forzarCierre(): void
    {
        unset(
            $_SESSION[self::SESSION_KEY],
            $_SESSION[self::SESSION_NOMBRE],
            $_SESSION[self::SESSION_ROL],
            $_SESSION[self::SESSION_PERMISOS],
            $_SESSION[self::SESSION_USUARIO_SELLO],
            $_SESSION[self::SESSION_PERMISOS_SELLO]
        );
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

    public static function debeCambiarPassword(): bool
    {
        return (bool) ($_SESSION[self::SESSION_DEBE_CAMBIAR_PASSWORD] ?? false);
    }
}
