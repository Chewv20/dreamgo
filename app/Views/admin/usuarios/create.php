<?php /** @var array $roles */ ?>
<div class="admin-panel ancho-520">
  <form method="post" action="/admin/usuarios" class="admin-form-grid">
    <?= \App\Helpers\Csrf::field() ?>
    <div class="campo">
      <label for="nombre">Nombre completo</label>
      <input type="text" id="nombre" name="nombre" required>
    </div>
    <div class="campo">
      <label for="email">Correo</label>
      <input type="email" id="email" name="email" required>
    </div>
    <div class="campo">
      <label for="password">Contrasena</label>
      <input type="password" id="password" name="password" required minlength="8">
    </div>
    <div class="campo">
      <label for="rol_id">Rol</label>
      <select id="rol_id" name="rol_id" required>
        <?php foreach ($roles as $rol): ?>
          <option value="<?= (int) $rol['id'] ?>"><?= htmlspecialchars($rol['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-primario">Crear usuario</button>
  </form>
</div>
