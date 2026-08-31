<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Helpers\Csrf;
use App\Helpers\Flash;
use App\Helpers\PasswordPolicy;
use App\Models\Usuario;
use Core\Auth;
use Core\Controller;

class AuthController extends Controller
{
    public function mostrarLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/admin');
        }

        $this->view('admin/auth/login', [], ['title' => 'Iniciar sesion | Dream Go'], 'blank');
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
            : 'Correo o contrasena incorrectos.';

        Flash::set('error', $mensaje);
        $this->view('admin/auth/login', ['email' => $email], ['title' => 'Iniciar sesion | Dream Go'], 'blank');
    }

    public function logout(): void
    {
        $this->verifyCsrf();
        Auth::logout();
        $this->redirect('/admin/login');
    }

    public function cambiarPasswordForm(): void
    {
        $this->view('admin/auth/cambiar_password', [], ['title' => 'Cambiar contrasena | Dream Go'], 'blank');
    }

    public function cambiarPassword(): void
    {
        $this->verifyCsrf();

        $actual = (string) $this->request->input('password_actual', '');
        $nueva = (string) $this->request->input('password_nueva', '');
        $confirmacion = (string) $this->request->input('password_confirmacion', '');

        $usuario = Usuario::find((int) Auth::id());

        if ($usuario === false || !password_verify($actual, $usuario['password_hash'])) {
            Flash::set('error', 'La contrasena actual no es correcta.');
            $this->redirect('/admin/cambiar-password');
        }

        if (!PasswordPolicy::esValida($nueva)) {
            Flash::set('error', PasswordPolicy::mensaje());
            $this->redirect('/admin/cambiar-password');
        }

        if ($nueva !== $confirmacion) {
            Flash::set('error', 'La confirmacion no coincide con la nueva contrasena.');
            $this->redirect('/admin/cambiar-password');
        }

        if (password_verify($nueva, $usuario['password_hash'])) {
            Flash::set('error', 'La nueva contrasena debe ser distinta a la actual.');
            $this->redirect('/admin/cambiar-password');
        }

        Usuario::update((int) $usuario['id'], [
            'password_hash' => password_hash($nueva, PASSWORD_DEFAULT),
            'debe_cambiar_password' => 0,
        ]);

        // forzarCierre() invalida la sesion admin sin destruir la sesion HTTP,
        // para que el mensaje de exito siga visible en la pagina de login.
        Auth::forzarCierre();
        Flash::set('exito', 'Contrasena actualizada. Inicia sesion con tu nueva contrasena.');
        $this->redirect('/admin/login');
    }
}
