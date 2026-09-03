<?php
/** @var array $roles */
/** @var array $permisosPorModulo */
/** @var array $permisosPorRol */
/** @var array<int,bool>|null $permisosAsignables  null = editor con rol de sistema (puede asignar todo) */
?>
<div class="admin-panel">
  <h2 class="mt-0">Nuevo rol</h2>
  <form method="post" action="/admin/roles" class="admin-form-grid admin-form-grid--2">
    <?= \App\Helpers\Csrf::field() ?>
    <div class="campo">
      <label for="nombre">Nombre del rol</label>
      <input type="text" id="nombre" name="nombre" required placeholder="Ej. Contador">
    </div>
    <div class="campo">
      <label for="descripcion">Descripción (opcional)</label>
      <input type="text" id="descripcion" name="descripcion" placeholder="Acceso de solo lectura a reservas">
    </div>
    <div>
      <button type="submit" class="btn btn-primario">Crear rol</button>
    </div>
  </form>
</div>

<div class="admin-panel">
  <h2 class="mt-0">Roles existentes</h2>
  <div class="admin-tabla-wrap">
    <table class="admin-tabla">
      <thead><tr><th>Rol</th><th>Descripción</th><th>Usuarios</th><th>Acciones</th></tr></thead>
      <tbody>
        <?php foreach ($roles as $rol): ?>
          <tr>
            <td><?= htmlspecialchars($rol['nombre'], ENT_QUOTES, 'UTF-8') ?> <?php if ((int) $rol['es_sistema'] === 1): ?><span class="admin-badge admin-badge--gris">Sistema</span><?php endif; ?></td>
            <td><?= htmlspecialchars($rol['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= (int) $rol['total_usuarios'] ?></td>
            <td>
              <?php if ((int) $rol['es_sistema'] !== 1): ?>
                <form method="post" action="/admin/roles/<?= (int) $rol['id'] ?>/eliminar" data-confirm="¿Eliminar este rol?">
                  <?= \App\Helpers\Csrf::field() ?>
                  <button type="submit" class="btn btn-secundario btn--xs">Eliminar</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="admin-panel">
  <h2 class="mt-0">Matriz de permisos</h2>
  <p class="op-75">Marca los permisos que tendrá cada rol. El rol Administrador siempre tiene acceso total.</p>
  <?php if ($permisosAsignables !== null): ?>
    <p class="op-75">Solo puedes asignar permisos que tu propio rol ya tiene; los demás aparecen bloqueados.</p>
  <?php endif; ?>
  <form method="post" action="/admin/roles/matriz">
    <?= \App\Helpers\Csrf::field() ?>
    <div class="admin-matriz-wrap">
      <table class="admin-matriz">
        <thead>
          <tr>
            <th>Permiso</th>
            <?php foreach ($roles as $rol): ?>
              <th><?= htmlspecialchars($rol['nombre'], ENT_QUOTES, 'UTF-8') ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($permisosPorModulo as $modulo => $permisos): ?>
            <tr><td colspan="<?= count($roles) + 1 ?>" class="matriz-grupo"><?= htmlspecialchars($modulo, ENT_QUOTES, 'UTF-8') ?></td></tr>
            <?php foreach ($permisos as $permiso): ?>
              <tr>
                <td><?= htmlspecialchars($permiso['descripcion'] ?? $permiso['clave'], ENT_QUOTES, 'UTF-8') ?></td>
                <?php foreach ($roles as $rol): ?>
                  <?php
                  $esSistema = (int) $rol['es_sistema'] === 1;
                  $fueraDeAlcance = $permisosAsignables !== null && !isset($permisosAsignables[(int) $permiso['id']]);
                  ?>
                  <td>
                    <input
                      type="checkbox"
                      name="permisos[<?= (int) $rol['id'] ?>][]"
                      value="<?= (int) $permiso['id'] ?>"
                      <?= (in_array((int) $permiso['id'], $permisosPorRol[$rol['id']] ?? [], true) || $esSistema) ? 'checked' : '' ?>
                      <?= ($esSistema || $fueraDeAlcance) ? 'disabled' : '' ?>
                    >
                  </td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <button type="submit" class="btn btn-primario mt-1">Guardar permisos</button>
  </form>
</div>
