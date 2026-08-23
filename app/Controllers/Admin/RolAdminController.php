<?php

namespace App\Controllers\Admin;

use App\Helpers\Flash;
use App\Models\Permiso;
use App\Models\Rol;

class RolAdminController extends AdminController
{
    public function index(): void
    {
        $roles = Rol::conConteoUsuarios();
        $permisosPorModulo = Permiso::agrupadosPorModulo();

        $permisosPorRol = [];
        foreach ($roles as $rol) {
            $permisosPorRol[$rol['id']] = Rol::permisosDelRol((int) $rol['id']);
        }

        $this->view('admin/roles/index', [
            'roles' => $roles,
            'permisosPorModulo' => $permisosPorModulo,
            'permisosPorRol' => $permisosPorRol,
        ], ['title' => 'Roles y permisos | Dream Go', 'heading' => 'Roles y permisos']);
    }

    public function crear(): void
    {
        $this->verifyCsrf();

        $nombre = trim((string) $this->request->input('nombre', ''));

        if ($nombre === '') {
            Flash::set('error', 'El nombre del rol es obligatorio.');
            $this->redirect('/admin/roles');
        }

        Rol::insert([
            'nombre' => $nombre,
            'descripcion' => trim((string) $this->request->input('descripcion', '')) ?: null,
            'es_sistema' => 0,
        ]);

        Flash::set('exito', 'Rol creado correctamente. Ahora puedes asignarle permisos.');
        $this->redirect('/admin/roles');
    }

    public function eliminar(int $id): void
    {
        $this->verifyCsrf();

        $rol = Rol::find($id);
        if (!$rol) {
            $this->abort(404);
        }

        if ((int) $rol['es_sistema'] === 1) {
            Flash::set('error', 'El rol Administrador no puede eliminarse.');
            $this->redirect('/admin/roles');
        }

        $stmt = $this->db->prepare('SELECT COUNT(*) FROM usuarios_admin WHERE rol_id = :id');
        $stmt->execute(['id' => $id]);
        if ((int) $stmt->fetchColumn() > 0) {
            Flash::set('error', 'No puedes eliminar un rol que tiene usuarios asignados.');
            $this->redirect('/admin/roles');
        }

        Rol::delete($id);
        Flash::set('exito', 'Rol eliminado.');
        $this->redirect('/admin/roles');
    }

    public function guardarMatriz(): void
    {
        $this->verifyCsrf();

        $seleccion = $this->request->input('permisos', []);
        if (!is_array($seleccion)) {
            $seleccion = [];
        }

        $roles = Rol::all();
        foreach ($roles as $rol) {
            if ((int) $rol['es_sistema'] === 1) {
                continue;
            }

            $permisoIds = array_map('intval', $seleccion[$rol['id']] ?? []);
            Rol::sincronizarPermisos((int) $rol['id'], $permisoIds);
        }

        Flash::set('exito', 'Permisos actualizados correctamente.');
        $this->redirect('/admin/roles');
    }
}
