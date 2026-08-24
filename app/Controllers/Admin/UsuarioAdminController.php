<?php

namespace App\Controllers\Admin;

use App\Helpers\Flash;
use App\Helpers\PasswordPolicy;
use App\Helpers\Validator;
use App\Models\Rol;
use App\Models\Usuario;
use Core\Auth;

class UsuarioAdminController extends AdminController
{
    public function index(): void
    {
        $this->view('admin/usuarios/index', [
            'usuarios' => Usuario::conRol(),
        ], ['title' => 'Usuarios | Dream Go', 'heading' => 'Usuarios del panel']);
    }

    public function crearForm(): void
    {
        $this->view('admin/usuarios/create', [
            'roles' => Rol::all('nombre ASC'),
        ], ['title' => 'Nuevo usuario | Dream Go', 'heading' => 'Nuevo usuario']);
    }

    public function crear(): void
    {
        $this->verifyCsrf();

        $datos = $this->request->only(['nombre', 'email', 'password', 'rol_id']);

        $validator = new Validator($datos);
        $validator->requerido('nombre', 'El nombre')
            ->requerido('email', 'El correo')->email('email', 'El correo')
            ->requerido('password', 'La contrasena')
            ->requerido('rol_id', 'El rol');

        $this->redirigirSiInvalido($validator, '/admin/usuarios/crear');

        if (!PasswordPolicy::esValida((string) $datos['password'])) {
            Flash::set('error', PasswordPolicy::mensaje());
            $this->redirect('/admin/usuarios/crear');
        }

        if (Usuario::porEmail($datos['email'])) {
            Flash::set('error', 'Ya existe un usuario con ese correo.');
            $this->redirect('/admin/usuarios/crear');
        }

        Usuario::insert([
            'nombre' => $datos['nombre'],
            'email' => $datos['email'],
            'password_hash' => password_hash((string) $datos['password'], PASSWORD_DEFAULT),
            'rol_id' => (int) $datos['rol_id'],
            'activo' => 1,
        ]);

        Flash::set('exito', 'Usuario creado correctamente.');
        $this->redirect('/admin/usuarios');
    }

    public function editarForm(int $id): void
    {
        $usuario = $this->encontrarO404(Usuario::class, $id);

        $this->view('admin/usuarios/edit', [
            'usuario' => $usuario,
            'roles' => Rol::all('nombre ASC'),
        ], ['title' => 'Editar usuario | Dream Go', 'heading' => 'Editar usuario']);
    }

    public function editar(int $id): void
    {
        $this->verifyCsrf();

        $usuario = $this->encontrarO404(Usuario::class, $id);

        $datos = [
            'nombre' => trim((string) $this->request->input('nombre', '')),
            'rol_id' => (int) $this->request->input('rol_id'),
            'activo' => $this->request->input('activo') ? 1 : 0,
        ];

        if ($datos['nombre'] === '') {
            Flash::set('error', 'El nombre es obligatorio.');
            $this->redirect("/admin/usuarios/{$id}/editar");
        }

        $password = (string) $this->request->input('password', '');
        if ($password !== '') {
            if (!PasswordPolicy::esValida($password)) {
                Flash::set('error', PasswordPolicy::mensaje());
                $this->redirect("/admin/usuarios/{$id}/editar");
            }
            $datos['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        if ((int) $usuario['id'] === Auth::id() && $datos['activo'] === 0) {
            Flash::set('error', 'No puedes desactivar tu propia cuenta.');
            $this->redirect("/admin/usuarios/{$id}/editar");
        }

        Usuario::update($id, $datos);
        Flash::set('exito', 'Usuario actualizado.');
        $this->redirect('/admin/usuarios');
    }
}
