<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Helpers\Auditoria;
use App\Helpers\Flash;
use App\Models\Permiso;
use App\Models\Rol;

class RolAdminController extends AdminController
{
    public function index(): void
    {
        $roles = Rol::conConteoUsuarios();
        $permisosPorModulo = Permiso::agrupadosPorModulo();
        $permisosPorRol = Rol::permisosPorRoles(array_map('intval', array_column($roles, 'id')));

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

        $rolId = Rol::insert([
            'nombre' => $nombre,
            'descripcion' => trim((string) $this->request->input('descripcion', '')) ?: null,
            'es_sistema' => 0,
        ]);

        Auditoria::registrar('rol.crear', 'rol', $rolId, 'Nombre: ' . $nombre);

        Flash::set('exito', 'Rol creado correctamente. Ahora puedes asignarle permisos.');
        $this->redirect('/admin/roles');
    }

    public function eliminar(int $id): void
    {
        $this->verifyCsrf();

        $rol = $this->encontrarO404(Rol::class, $id);

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
        Auditoria::registrar('rol.eliminar', 'rol', $id, 'Nombre: ' . ($rol['nombre'] ?? ''));
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

        Auditoria::registrar('rol.permisos', 'rol', null, 'Se guardo la matriz de permisos de roles');

        Flash::set('exito', 'Permisos actualizados correctamente.');
        $this->redirect('/admin/roles');
    }
}
