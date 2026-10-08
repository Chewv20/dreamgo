<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Helpers\Auditoria;
use App\Helpers\Csrf;
use App\Helpers\Flash;
use App\Helpers\PasswordPolicy;
use App\Helpers\RateLimiter;
use App\Services\MailerService;
use App\Services\RecuperacionPasswordService;
use App\Models\Usuario;
use Core\Auth;
use Core\Controller;
use Core\Response;
use RuntimeException;

class AuthController extends Controller
{
    public function mostrarLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/admin');
        }

        $this->view('admin/auth/login', [], ['title' => 'Iniciar sesión | Dream Go'], 'blank');
    }

    public function login(): void
    {
        $this->verifyCsrf();

        $email = (string) $this->request->input('email', '');
        $password = (string) $this->request->input('password', '');
        $ip = $this->request->ip();

        $bloqueadoAntes = Auth::bloqueado($email, $ip);

        if (Auth::attempt($email, $password, $ip)) {
            $this->redirect('/admin');
        }

        $mensaje = $bloqueadoAntes
            ? 'Demasiados intentos fallidos. Espera unos minutos antes de volver a intentar.'
            : 'Correo o contraseña incorrectos.';

        Flash::set('error', $mensaje);
        $this->view('admin/auth/login', ['email' => $email], ['title' => 'Iniciar sesión | Dream Go'], 'blank');
    }

    public function logout(): void
    {
        $this->verifyCsrf();
        Auth::logout();
        $this->redirect('/admin/login');
    }

    public function cambiarPasswordForm(): void
    {
        // También se llega desde el menú de usuario: ahí el cambio es voluntario y se puede cancelar.
        $usuario = Usuario::find((int) Auth::id());
        $this->view('admin/auth/cambiar_password', [
            'forzado' => $usuario !== false && (int) $usuario['debe_cambiar_password'] === 1,
            'politica' => PasswordPolicy::mensaje(),
        ], ['title' => 'Cambiar contraseña | Dream Go'], 'blank');
    }

    public function cambiarPassword(): void
    {
        $this->verifyCsrf();

        $actual = (string) $this->request->input('password_actual', '');
        $nueva = (string) $this->request->input('password_nueva', '');
        $confirmacion = (string) $this->request->input('password_confirmacion', '');

        $usuario = Usuario::find((int) Auth::id());

        if ($usuario === false || !password_verify($actual, $usuario['password_hash'])) {
            Flash::set('error', 'La contraseña actual no es correcta.');
            $this->redirect('/admin/cambiar-password');
        }

        if (!PasswordPolicy::esValida($nueva)) {
            Flash::set('error', PasswordPolicy::mensaje());
            $this->redirect('/admin/cambiar-password');
        }

        if ($nueva !== $confirmacion) {
            Flash::set('error', 'La confirmación no coincide con la nueva contraseña.');
            $this->redirect('/admin/cambiar-password');
        }

        if (password_verify($nueva, $usuario['password_hash'])) {
            Flash::set('error', 'La nueva contraseña debe ser distinta a la actual.');
            $this->redirect('/admin/cambiar-password');
        }

        Usuario::update((int) $usuario['id'], [
            'password_hash' => password_hash($nueva, PASSWORD_DEFAULT),
            'debe_cambiar_password' => 0,
        ]);

        // forzarCierre() invalida la sesion admin sin destruir la sesion HTTP,
        // para que el mensaje de exito siga visible en la pagina de login.
        Auth::forzarCierre();
        Flash::set('exito', 'Contraseña actualizada. Inicia sesión con tu nueva contraseña.');
        $this->redirect('/admin/login');
    }

    // --- ¿Olvidaste tu contraseña? -------------------------------------------------------

    public function recuperarForm(): void
    {
        if (Auth::check()) {
            $this->redirect('/admin');
        }

        $this->view('admin/auth/recuperar', [], ['title' => 'Recuperar contraseña | Dream Go'], 'blank');
    }

    public function recuperar(): void
    {
        $this->verifyCsrf();

        $email = trim((string) $this->request->input('email', ''));
        $ip = $this->request->ip();

        if (RateLimiter::demasiados('recuperar_password', mb_strtolower($email), $ip)) {
            RateLimiter::registrar('recuperar_password', mb_strtolower($email), $ip);
            $this->abort(429, 'Demasiadas solicitudes. Espera unos minutos e intenta de nuevo.');
        }
        RateLimiter::registrar('recuperar_password', mb_strtolower($email), $ip);

        // Mismo mensaje exista o no la cuenta (no revela qué correos están registrados), y
        // la respuesta sale antes del envío para que el tiempo tampoco lo delate.
        Flash::set('exito', 'Si el correo pertenece a una cuenta activa, te enviamos un enlace para elegir una contraseña nueva. Revisa tu bandeja (y la de spam); vence en ' . RecuperacionPasswordService::VIGENCIA_MINUTOS . ' minutos.');
        Response::redirectYContinuar('/admin/login');

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            try {
                $mailer = new MailerService($this->db);
                (new RecuperacionPasswordService($this->db))->solicitar($email, $ip, [$mailer, 'enviarRecuperacionPassword'], (string) ($_ENV['APP_URL'] ?? ''));
            } catch (\Throwable $e) {
                error_log('[AuthController] Error al solicitar recuperación de contraseña: ' . $e->getMessage());
            }
        }
        exit;
    }

    public function restablecerForm(string $token): void
    {
        $usuario = (new RecuperacionPasswordService($this->db))->usuarioDeToken($token);

        $this->view('admin/auth/restablecer', [
            'token' => $token,
            'usuario' => $usuario,
            'politica' => PasswordPolicy::mensaje(),
        ], ['title' => 'Elegir contraseña nueva | Dream Go'], 'blank');
    }

    public function restablecer(string $token): void
    {
        $this->verifyCsrf();

        try {
            $usuarioId = (new RecuperacionPasswordService($this->db))->restablecer(
                $token,
                (string) $this->request->input('password_nueva', ''),
                (string) $this->request->input('password_confirmacion', '')
            );
        } catch (RuntimeException $e) {
            Flash::set('error', $e->getMessage());
            $this->redirect('/admin/restablecer/' . rawurlencode($token));
        }

        Auditoria::registrar('usuario.restablecer_password', 'usuario', $usuarioId, 'Por enlace enviado al correo');

        if (Auth::check()) {
            Auth::forzarCierre();
        }
        Flash::set('exito', 'Listo, tu contraseña se actualizó. Ya puedes iniciar sesión.');
        $this->redirect('/admin/login');
    }
}
