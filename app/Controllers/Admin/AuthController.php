<?php

namespace App\Controllers\Admin;

use App\Helpers\Csrf;
use App\Helpers\Flash;
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

        if (Auth::attempt($email, $password)) {
            $this->redirect('/admin');
        }

        Flash::set('error', 'Correo o contrasena incorrectos.');
        $this->view('admin/auth/login', ['email' => $email], ['title' => 'Iniciar sesion | Dream Go'], 'blank');
    }

    public function logout(): void
    {
        $this->verifyCsrf();
        Auth::logout();
        $this->redirect('/admin/login');
    }
}
