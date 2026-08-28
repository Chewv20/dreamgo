<?php
/** @var array $categorias */
/** @var array $estados */
/** @var array|null $articulo */
$articulo ??= [];
?>
<?= \App\Helpers\Csrf::field() ?>

<div class="admin-form-grid admin-form-grid--2">
  <div class="campo">
    <label for="titulo">Titulo</label>
    <input type="text" id="titulo" name="titulo" required maxlength="180" value="<?= htmlspecialchars($articulo['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
  </div>
  <div class="campo">
    <label for="categoria_id">Destino relacionado (opcional)</label>
    <select id="categoria_id" name="categoria_id">
      <option value="">Sin destino</option>
      <?php foreach ($categorias as $categoria): ?>
        <option value="<?= (int) $categoria['id'] ?>" <?= (int) ($articulo['categoria_id'] ?? 0) === (int) $categoria['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<div class="campo">
  <label for="resumen">Resumen corto (para tarjetas y meta description)</label>
  <input type="text" id="resumen" name="resumen" maxlength="300" value="<?= htmlspecialchars($articulo['resumen'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
</div>

<div class="campo">
  <label for="estado">Estado</label>
  <select id="estado" name="estado" required>
    <?php foreach ($estados as $valor => $etiqueta): ?>
      <option value="<?= $valor ?>" <?= ($articulo['estado'] ?? 'borrador') === $valor ? 'selected' : '' ?>><?= htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8') ?></option>
    <?php endforeach; ?>
  </select>
</div>

<div class="campo">
  <label for="contenido">Contenido (HTML basico: parrafos, negritas, listas, subtitulos h3/h4, enlaces)</label>
  <textarea id="contenido" name="contenido" style="min-height:280px;"><?= htmlspecialchars($articulo['contenido'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
</div>

<div class="admin-form-grid admin-form-grid--2">
  <div class="campo">
    <label for="meta_title">Titulo SEO (opcional)</label>
    <input type="text" id="meta_title" name="meta_title" maxlength="180" value="<?= htmlspecialchars($articulo['meta_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
  </div>
  <div class="campo">
    <label for="meta_description">Descripcion SEO (opcional)</label>
    <input type="text" id="meta_description" name="meta_description" maxlength="300" value="<?= htmlspecialchars($articulo['meta_description'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
  </div>
</div>

<div class="campo">
  <label for="imagen">Imagen de portada (JPG, PNG o WEBP)</label>
  <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp">
  <?php if (!empty($articulo['imagen'])): ?>
    <small style="opacity:0.7;">Actual: <?= htmlspecialchars($articulo['imagen'], ENT_QUOTES, 'UTF-8') ?> — sube una nueva para reemplazarla.</small>
  <?php endif; ?>
</div>

<button type="submit" class="btn btn-primario">Guardar articulo</button>
