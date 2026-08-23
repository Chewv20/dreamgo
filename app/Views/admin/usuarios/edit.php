<?php /** @var array $usuario */ /** @var array $roles */ ?>
<div class="admin-panel" style="max-width:520px;">
  <form method="post" action="/admin/usuarios/<?= (int) $usuario['id'] ?>/editar" class="admin-form-grid">
    <?= \App\Helpers\Csrf::field() ?>
    <div class="campo">
      <label for="nombre">Nombre completo</label>
      <input type="text" id="nombre" name="nombre" required value="<?= htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="campo">
      <label>Correo</label>
      <input type="email" value="<?= htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8') ?>" disabled>
    </div>
    <div class="campo">
      <label for="password">Nueva contrasena (dejar en blanco para no cambiar)</label>
      <input type="password" id="password" name="password" minlength="8">
    </div>
    <div class="campo">
      <label for="rol_id">Rol</label>
      <select id="rol_id" name="rol_id" required>
        <?php foreach ($roles as $rol): ?>
          <option value="<?= (int) $rol['id'] ?>" <?= (int) $usuario['rol_id'] === (int) $rol['id'] ? 'selected' : '' ?>><?= htmlspecialchars($rol['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="campo" style="display:flex;align-items:center;gap:0.6rem;">
      <input type="checkbox" name="activo" id="activo" value="1" style="width:1.2rem;height:1.2rem;" <?= (int) $usuario['activo'] === 1 ? 'checked' : '' ?>>
      <label for="activo" style="margin:0;">Usuario activo</label>
    </div>
    <button type="submit" class="btn btn-primario">Guardar cambios</button>
  </form>
</div>
