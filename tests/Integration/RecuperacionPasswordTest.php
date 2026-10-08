<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\RecuperacionPasswordService;
use RuntimeException;

/**
 * "¿Olvidaste tu contraseña?": token de un solo uso, con vencimiento, guardado sólo como
 * hash, y sin revelar si un correo existe.
 */
final class RecuperacionPasswordTest extends IntegrationTestCase
{
    private const USUARIO_ID = 50;

    /** @var list<array{usuario: array, url: string, vigencia: int}> */
    private array $correos = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->correos = [];
        self::$db->exec("INSERT INTO roles (id, nombre, es_sistema) VALUES (50, 'Pruebas', 0)");
        self::$db->exec(
            "INSERT INTO usuarios_admin (id, nombre, email, password_hash, rol_id, activo)
             VALUES (" . self::USUARIO_ID . ", 'Ana', 'ana@dreamgo.test', '" . password_hash('Vieja1234', PASSWORD_DEFAULT) . "', 50, 1)"
        );
    }

    protected static function tablasALimpiar(): array
    {
        return array_merge(['password_resets', 'usuarios_admin', 'roles'], parent::tablasALimpiar());
    }

    private function servicio(): RecuperacionPasswordService
    {
        return new RecuperacionPasswordService(self::$db);
    }

    private function solicitar(string $email): ?string
    {
        return $this->servicio()->solicitar($email, '127.0.0.1', function (array $usuario, string $url, int $vigencia): void {
            $this->correos[] = ['usuario' => $usuario, 'url' => $url, 'vigencia' => $vigencia];
        }, 'https://dreamgo.test');
    }

    private function hashActual(): string
    {
        return (string) self::$db->query('SELECT password_hash FROM usuarios_admin WHERE id = ' . self::USUARIO_ID)->fetchColumn();
    }

    public function testSolicitarEnviaEnlaceYGuardaSoloElHash(): void
    {
        $token = $this->solicitar('ANA@dreamgo.test');

        self::assertNotNull($token);
        self::assertCount(1, $this->correos);
        self::assertSame('https://dreamgo.test/admin/restablecer/' . $token, $this->correos[0]['url']);
        self::assertSame(0, $this->contar('password_resets', "token_hash = '" . $token . "'"));
        self::assertSame(1, $this->contar('password_resets', "token_hash = '" . hash('sha256', $token) . "'"));
    }

    public function testCorreoInexistenteOInactivoNoEnviaNada(): void
    {
        self::assertNull($this->solicitar('nadie@dreamgo.test'));

        self::$db->exec('UPDATE usuarios_admin SET activo = 0 WHERE id = ' . self::USUARIO_ID);
        self::assertNull($this->solicitar('ana@dreamgo.test'));

        self::assertSame([], $this->correos);
        self::assertSame(0, $this->contar('password_resets'));
    }

    public function testRestablecerCambiaLaContrasenaYConsumeElToken(): void
    {
        $token = $this->solicitar('ana@dreamgo.test');

        self::assertSame(self::USUARIO_ID, $this->servicio()->restablecer($token, 'Nueva5678', 'Nueva5678'));
        self::assertTrue(password_verify('Nueva5678', $this->hashActual()));
        self::assertNull($this->servicio()->usuarioDeToken($token));

        $this->expectException(RuntimeException::class);
        $this->servicio()->restablecer($token, 'Otra9999x', 'Otra9999x');
    }

    public function testPedirOtroEnlaceInvalidaElAnterior(): void
    {
        $primero = $this->solicitar('ana@dreamgo.test');
        $segundo = $this->solicitar('ana@dreamgo.test');

        self::assertNull($this->servicio()->usuarioDeToken($primero));
        self::assertNotNull($this->servicio()->usuarioDeToken($segundo));
    }

    public function testTokenVencidoNoSirve(): void
    {
        $token = $this->solicitar('ana@dreamgo.test');
        self::$db->exec('UPDATE password_resets SET expira_en = NOW() - INTERVAL 1 MINUTE');

        self::assertNull($this->servicio()->usuarioDeToken($token));
        try {
            $this->servicio()->restablecer($token, 'Nueva5678', 'Nueva5678');
            self::fail('Se esperaba RuntimeException por token vencido');
        } catch (RuntimeException $e) {
            self::assertSame(RecuperacionPasswordService::MENSAJE_INVALIDO, $e->getMessage());
        }
        self::assertTrue(password_verify('Vieja1234', $this->hashActual()));
    }

    public function testPoliticaYConfirmacionSeValidanSinConsumirElToken(): void
    {
        $token = $this->solicitar('ana@dreamgo.test');

        foreach ([['corta', 'corta'], ['Nueva5678', 'Distinta5678']] as [$nueva, $confirmacion]) {
            try {
                $this->servicio()->restablecer($token, $nueva, $confirmacion);
                self::fail('Se esperaba RuntimeException');
            } catch (RuntimeException) {
            }
        }

        self::assertNotNull($this->servicio()->usuarioDeToken($token));
        self::assertTrue(password_verify('Vieja1234', $this->hashActual()));
    }

    public function testTokenConFormatoInvalidoSeRechazaSinConsultar(): void
    {
        self::assertNull($this->servicio()->usuarioDeToken("' OR 1=1 --"));
        self::assertNull($this->servicio()->usuarioDeToken(str_repeat('z', 64)));
    }
}
