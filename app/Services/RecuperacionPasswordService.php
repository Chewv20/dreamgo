<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\PasswordPolicy;
use Core\Database;
use PDO;
use RuntimeException;

/**
 * "¿Olvidaste tu contraseña?" del panel.
 *
 * - El token viaja sólo en el correo; en la BD se guarda su SHA-256 (password_resets).
 * - Vence a los VIGENCIA_MINUTOS, sirve una sola vez y pedir otro invalida los anteriores.
 * - solicitar() no revela si el correo existe: el controlador responde siempre lo mismo.
 * - Al restablecer cambia usuarios_admin.actualizado_en, así que Auth::sesionVigente()
 *   cierra cualquier sesión abierta con la contraseña anterior.
 */
final class RecuperacionPasswordService
{
    public const VIGENCIA_MINUTOS = 60;

    public function __construct(private PDO $db)
    {
    }

    /**
     * Genera el token y manda el correo si el correo pertenece a un usuario activo. Devuelve
     * el token en claro (o null si no se envió nada), sólo para pruebas: el controlador no
     * lo usa ni lo muestra.
     *
     * @param callable(array, string, int): mixed $enviarCorreo recibe (usuario, url, vigencia
     *        en minutos); en producción, MailerService::enviarRecuperacionPassword
     */
    public function solicitar(string $email, ?string $ip, callable $enviarCorreo, string $appUrl): ?string
    {
        $stmt = $this->db->prepare('SELECT id, nombre, email FROM usuarios_admin WHERE email = :email AND activo = 1 LIMIT 1');
        $stmt->execute(['email' => mb_strtolower(trim($email))]);
        $usuario = $stmt->fetch();

        if (!$usuario) {
            return null;
        }

        $token = bin2hex(random_bytes(32));

        Database::transaction($this->db, function () use ($usuario, $token, $ip): void {
            // Sólo el enlace más reciente sirve.
            $this->db->prepare('UPDATE password_resets SET usado_en = NOW() WHERE usuario_id = :id AND usado_en IS NULL')
                ->execute(['id' => $usuario['id']]);
            // Limpieza: los vencidos hace más de un día ya no aportan nada.
            $this->db->prepare('DELETE FROM password_resets WHERE usuario_id = :id AND expira_en < NOW() - INTERVAL 1 DAY')
                ->execute(['id' => $usuario['id']]);

            $this->db->prepare(
                'INSERT INTO password_resets (usuario_id, token_hash, expira_en, ip)
                 VALUES (:id, :hash, NOW() + INTERVAL ' . self::VIGENCIA_MINUTOS . ' MINUTE, :ip)'
            )->execute(['id' => $usuario['id'], 'hash' => self::hash($token), 'ip' => $ip]);
        });

        $url = rtrim($appUrl, '/') . '/admin/restablecer/' . $token;
        $enviarCorreo($usuario, $url, self::VIGENCIA_MINUTOS);

        return $token;
    }

    /** @return array{id: int, nombre: string, email: string}|null usuario dueño de un token vigente */
    public function usuarioDeToken(string $token): ?array
    {
        if (!self::formatoValido($token)) {
            return null;
        }

        $stmt = $this->db->prepare(
            'SELECT u.id, u.nombre, u.email
             FROM password_resets pr
             INNER JOIN usuarios_admin u ON u.id = pr.usuario_id
             WHERE pr.token_hash = :hash AND pr.usado_en IS NULL AND pr.expira_en > NOW() AND u.activo = 1
             LIMIT 1'
        );
        $stmt->execute(['hash' => self::hash($token)]);
        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    /**
     * Cambia la contraseña con un token vigente y lo consume.
     *
     * @return int id del usuario
     * @throws RuntimeException con un mensaje listo para mostrar
     */
    public function restablecer(string $token, string $nueva, string $confirmacion): int
    {
        if (!PasswordPolicy::esValida($nueva)) {
            throw new RuntimeException(PasswordPolicy::mensaje());
        }
        if ($nueva !== $confirmacion) {
            throw new RuntimeException('La confirmación no coincide con la nueva contraseña.');
        }
        if (!self::formatoValido($token)) {
            throw new RuntimeException(self::MENSAJE_INVALIDO);
        }

        return Database::transaction($this->db, function () use ($token, $nueva): int {
            $stmt = $this->db->prepare(
                'SELECT pr.id, pr.usuario_id
                 FROM password_resets pr
                 INNER JOIN usuarios_admin u ON u.id = pr.usuario_id
                 WHERE pr.token_hash = :hash AND pr.usado_en IS NULL AND pr.expira_en > NOW() AND u.activo = 1
                 LIMIT 1
                 FOR UPDATE'
            );
            $stmt->execute(['hash' => self::hash($token)]);
            $fila = $stmt->fetch();

            if (!$fila) {
                throw new RuntimeException(self::MENSAJE_INVALIDO);
            }

            $usuarioId = (int) $fila['usuario_id'];

            $this->db->prepare(
                'UPDATE usuarios_admin SET password_hash = :hash, debe_cambiar_password = 0 WHERE id = :id'
            )->execute(['hash' => password_hash($nueva, PASSWORD_DEFAULT), 'id' => $usuarioId]);

            // Consume este token y cualquier otro pendiente del mismo usuario.
            $this->db->prepare('UPDATE password_resets SET usado_en = NOW() WHERE usuario_id = :id AND usado_en IS NULL')
                ->execute(['id' => $usuarioId]);

            return $usuarioId;
        });
    }

    public const MENSAJE_INVALIDO = 'El enlace para restablecer la contraseña no es válido o ya venció. Solicita uno nuevo.';

    private static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    private static function formatoValido(string $token): bool
    {
        return preg_match('/^[0-9a-f]{64}$/', $token) === 1;
    }
}
