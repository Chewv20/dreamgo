<?php /** @var array $roles */ /** @var array $captura */ $captura ??= []; ?>
<div class="admin-panel ancho-520">
  <form method="post" action="/admin/usuarios" class="admin-form-grid">
    <?= \App\Helpers\Csrf::field() ?>
    <div class="campo">
      <label for="nombre">Nombre completo</label>
      <input type="text" id="nombre" name="nombre" required autocomplete="off" value="<?= htmlspecialchars((string) ($captura['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="campo">
      <label for="email">Correo</label>
      <input type="email" id="email" name="email" required autocomplete="off" value="<?= htmlspecialchars((string) ($captura['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <?php $etiquetaPassword = 'Contraseña inicial'; $passwordRequerida = true; require __DIR__ . '/../partials/_campo_password.php'; ?>
    <div class="campo campo--check">
      <input type="checkbox" id="debe_cambiar_password" name="debe_cambiar_password" value="1" <?= ($captura['debe_cambiar_password'] ?? '1') === '1' ? 'checked' : '' ?>>
      <label for="debe_cambiar_password" class="m-0">Pedir que la cambie al iniciar sesión por primera vez</label>
    </div>
    <div class="campo">
      <label for="rol_id">Rol</label>
      <select id="rol_id" name="rol_id" required>
        <?php foreach ($roles as $rol): ?>
          <option value="<?= (int) $rol['id'] ?>" <?= (string) ($captura['rol_id'] ?? '') === (string) $rol['id'] ? 'selected' : '' ?>><?= htmlspecialchars($rol['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-primario">Crear usuario</button>
  </form>
</div>
