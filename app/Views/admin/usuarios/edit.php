<?php /** @var array $usuario */ /** @var array $roles */ ?>
<div class="admin-panel ancho-520">
  <form method="post" action="/admin/usuarios/<?= (int) $usuario['id'] ?>/editar" class="admin-form-grid">
    <?= \App\Helpers\Csrf::field() ?>
    <div class="campo">
      <label for="nombre">Nombre completo</label>
      <input type="text" id="nombre" name="nombre" required value="<?= htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="campo">
      <label for="email_ro">Correo</label>
      <input type="email" id="email_ro" value="<?= htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8') ?>" disabled>
    </div>
    <?php $etiquetaPassword = 'Nueva contraseña (déjala en blanco para no cambiarla)'; $passwordRequerida = false; require __DIR__ . '/../partials/_campo_password.php'; ?>
    <div class="campo campo--check">
      <input type="checkbox" id="debe_cambiar_password" name="debe_cambiar_password" value="1" <?= (int) ($usuario['debe_cambiar_password'] ?? 0) === 1 ? 'checked' : '' ?>>
      <label for="debe_cambiar_password" class="m-0">Pedir cambio de contraseña en el próximo inicio de sesión</label>
    </div>
    <div class="campo">
      <label for="rol_id">Rol</label>
      <select id="rol_id" name="rol_id" required>
        <?php foreach ($roles as $rol): ?>
          <option value="<?= (int) $rol['id'] ?>" <?= (int) $usuario['rol_id'] === (int) $rol['id'] ? 'selected' : '' ?>><?= htmlspecialchars($rol['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="campo campo--check">
      <input type="checkbox" name="activo" id="activo" value="1" <?= (int) $usuario['activo'] === 1 ? 'checked' : '' ?>>
      <label for="activo" class="m-0">Usuario activo</label>
    </div>
    <button type="submit" class="btn btn-primario">Guardar cambios</button>
  </form>
</div>
