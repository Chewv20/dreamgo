<?php /** @var array $usuarios */ ?>
<div class="admin-acciones" style="margin-bottom:1.25rem;">
  <a href="/admin/usuarios/crear" class="btn btn-primario">Nuevo usuario</a>
</div>

<div class="admin-panel">
  <div class="admin-tabla-wrap">
    <table class="admin-tabla">
      <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Ultimo acceso</th><th>Acciones</th></tr></thead>
      <tbody>
        <?php foreach ($usuarios as $usuario): ?>
          <tr>
            <td><?= htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($usuario['rol_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <?php if ((int) $usuario['activo'] === 1): ?>
                <span class="admin-badge admin-badge--verde">Activo</span>
              <?php else: ?>
                <span class="admin-badge admin-badge--rojo">Inactivo</span>
              <?php endif; ?>
            </td>
            <td><?= $usuario['ultimo_login'] ? \App\Helpers\Fecha::cortaHora($usuario['ultimo_login']) : 'Nunca' ?></td>
            <td class="admin-acciones">
              <a href="/admin/usuarios/<?= (int) $usuario['id'] ?>/editar" class="btn btn-secundario">Editar</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
