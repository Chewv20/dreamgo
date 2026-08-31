<?php
/** @var array<string,string> $tipos */
/** @var array|null $destino */
$destino ??= [];
?>
<?= \App\Helpers\Csrf::field() ?>

<div class="admin-form-grid admin-form-grid--2">
  <div class="campo">
    <label for="nombre">Nombre del destino</label>
    <input type="text" id="nombre" name="nombre" required maxlength="100" value="<?= htmlspecialchars($destino['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
  </div>
  <div class="campo">
    <label for="tipo">Tipo</label>
    <select id="tipo" name="tipo" required>
      <?php foreach ($tipos as $valor => $etiqueta): ?>
        <option value="<?= htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') ?>" <?= ($destino['tipo'] ?? 'nacional') === $valor ? 'selected' : '' ?>><?= htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8') ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<div class="campo">
  <label for="descripcion">Descripcion (texto plano; se muestra en la tarjeta y la ficha del destino)</label>
  <textarea id="descripcion" name="descripcion" maxlength="1000"><?= htmlspecialchars($destino['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
</div>

<div class="campo">
  <label for="imagen">Imagen de portada (JPG, PNG o WEBP)</label>
  <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp">
  <?php if (!empty($destino['imagen_portada'])): ?>
    <small class="op-70">Actual: <?= htmlspecialchars($destino['imagen_portada'], ENT_QUOTES, 'UTF-8') ?> — sube una nueva para reemplazarla.</small>
  <?php endif; ?>
</div>

<div class="campo campo--check">
  <input type="checkbox" id="activo" name="activo" value="1" <?= (int) ($destino['activo'] ?? 1) === 1 ? 'checked' : '' ?>>
  <label for="activo" class="m-0">Visible en el sitio</label>
</div>

<button type="submit" class="btn btn-primario">Guardar destino</button>
