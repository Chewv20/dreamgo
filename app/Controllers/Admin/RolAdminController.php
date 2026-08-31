<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Helpers\Auditoria;
use App\Helpers\Flash;
use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use Core\Auth;

class RolAdminController extends AdminController
{
    public function index(): void
    {
        $roles = Rol::conConteoUsuarios();
        $permisosPorModulo = Permiso::agrupadosPorModulo();
        $permisosPorRol = Rol::permisosPorRoles(array_map('intval', array_column($roles, 'id')));

        // Hallazgo SEG-01: un editor sin rol de sistema solo puede togglear los permisos que
        // el mismo tiene; el resto se muestran bloqueados y guardarMatriz() los ignora.
        // null = editor con rol de sistema (Administrador): puede asignar cualquiera.
        $permisosAsignables = Usuario::esRolDeSistema((int) Auth::rolId())
            ? null
            : array_flip(Permiso::idsPorClaves(Auth::permisos()));

        $this->view('admin/roles/index', [
            'roles' => $roles,
            'permisosPorModulo' => $permisosPorModulo,
            'permisosPorRol' => $permisosPorRol,
            'permisosAsignables' => $permisosAsignables,
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

        // Auditoria 2026-09, hallazgo SEG-01: un editor con `roles.gestionar` pero sin rol de
        // sistema solo puede asignar permisos que el mismo posee. Sin esto podia anadir
        // `usuarios.gestionar`, `configuracion.gestionar`, etc. a su propio rol y escalar
        // privilegios. Un Administrador (rol de sistema) sigue pudiendo asignar cualquiera.
        $editorEsSistema = Usuario::esRolDeSistema((int) Auth::rolId());
        $asignables = $editorEsSistema ? null : array_flip(Permiso::idsPorClaves(Auth::permisos()));

        $roles = Rol::all();
        $previosPorRol = Rol::permisosPorRoles(array_map('intval', array_column($roles, 'id')));

        foreach ($roles as $rol) {
            if ((int) $rol['es_sistema'] === 1) {
                continue;
            }

            $rolId = (int) $rol['id'];
            $elegidos = array_map('intval', $seleccion[$rolId] ?? []);

            if ($asignables !== null) {
                // Se conservan los permisos que el rol ya tenia y el editor no puede tocar
                // (las casillas le aparecen bloqueadas, asi que no viajan en el POST); de la
                // seleccion nueva solo se aceptan los permisos que el editor posee.
                $previos = $previosPorRol[$rolId] ?? [];
                $conservados = array_filter($previos, static fn (int $id): bool => !isset($asignables[$id]));
                $nuevos = array_filter($elegidos, static fn (int $id): bool => isset($asignables[$id]));
                $elegidos = [...$conservados, ...$nuevos];
            }

            Rol::sincronizarPermisos($rolId, array_values(array_unique($elegidos)));
        }

        Auditoria::registrar('rol.permisos', 'rol', null, 'Se guardo la matriz de permisos de roles');

        Flash::set('exito', 'Permisos actualizados correctamente.');
        $this->redirect('/admin/roles');
    }
}
