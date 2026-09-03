<?php
/** @var array $categorias */
/** @var array|null $paquete */
/** @var array $monedas */
/** @var bool $monedaBloqueada */
$paquete ??= [];
?>
<?= \App\Helpers\Csrf::field() ?>

<div class="admin-form-grid admin-form-grid--2">
  <div class="campo">
    <label for="titulo">Título</label>
    <input type="text" id="titulo" name="titulo" required maxlength="180" value="<?= htmlspecialchars($paquete['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
  </div>
  <div class="campo">
    <label for="categoria_id">Categoría / destino</label>
    <select id="categoria_id" name="categoria_id" required>
      <?php foreach ($categorias as $categoria): ?>
        <option value="<?= (int) $categoria['id'] ?>" <?= (int) ($paquete['categoria_id'] ?? 0) === (int) $categoria['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<div class="campo">
  <label for="resumen">Resumen corto (para tarjetas de catálogo)</label>
  <input type="text" id="resumen" name="resumen" maxlength="300" value="<?= htmlspecialchars($paquete['resumen'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
</div>

<div class="admin-form-grid admin-form-grid--2">
  <div class="campo">
    <label for="precio_desde">Precio desde</label>
    <input type="number" step="0.01" min="0" id="precio_desde" name="precio_desde" required value="<?= htmlspecialchars((string) ($paquete['precio_desde'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
  </div>
  <div class="campo">
    <label for="moneda">Moneda</label>
    <?php if (!empty($monedaBloqueada)): ?>
      <input type="text" value="<?= htmlspecialchars($paquete['moneda'] ?? 'MXN', ENT_QUOTES, 'UTF-8') ?>" disabled>
      <input type="hidden" name="moneda" value="<?= htmlspecialchars($paquete['moneda'] ?? 'MXN', ENT_QUOTES, 'UTF-8') ?>">
      <small class="op-70">No se puede cambiar: este paquete ya tiene reservas.</small>
    <?php else: ?>
      <select id="moneda" name="moneda" required>
        <?php foreach ($monedas as $codigo => $etiqueta): ?>
          <option value="<?= $codigo ?>" <?= ($paquete['moneda'] ?? 'MXN') === $codigo ? 'selected' : '' ?>><?= htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>
  </div>
</div>

<div class="campo">
  <label for="estado">Estado</label>
  <select id="estado" name="estado" required>
    <?php foreach (['borrador' => 'Borrador', 'publicado' => 'Publicado', 'archivado' => 'Archivado'] as $valor => $etiqueta): ?>
      <option value="<?= $valor ?>" <?= ($paquete['estado'] ?? 'borrador') === $valor ? 'selected' : '' ?>><?= $etiqueta ?></option>
    <?php endforeach; ?>
  </select>
</div>

<div class="admin-form-grid admin-form-grid--2">
  <div class="campo">
    <label for="duracion_dias">Duración (días)</label>
    <input type="number" min="1" id="duracion_dias" name="duracion_dias" value="<?= htmlspecialchars((string) ($paquete['duracion_dias'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
  </div>
  <div class="campo">
    <label for="duracion_noches">Duración (noches)</label>
    <input type="number" min="0" id="duracion_noches" name="duracion_noches" value="<?= htmlspecialchars((string) ($paquete['duracion_noches'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
  </div>
</div>

<div class="campo campo--check">
  <input type="checkbox" id="destacado" name="destacado" value="1" <?= (int) ($paquete['destacado'] ?? 0) === 1 ? 'checked' : '' ?>>
  <label for="destacado" class="m-0">Mostrar como destacado en Inicio</label>
</div>

<div class="campo">
  <label for="descripcion_larga">Descripción (HTML básico: negritas, listas, párrafos)</label>
  <textarea id="descripcion_larga" name="descripcion_larga" class="textarea-md"><?= htmlspecialchars($paquete['descripcion_larga'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
</div>

<div class="campo">
  <label for="itinerario">Itinerario (HTML básico)</label>
  <textarea id="itinerario" name="itinerario" class="textarea-md"><?= htmlspecialchars($paquete['itinerario'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
</div>

<div class="admin-form-grid admin-form-grid--2">
  <div class="campo">
    <label for="incluye">Incluye (lista HTML: &lt;ul&gt;&lt;li&gt;...)</label>
    <textarea id="incluye" name="incluye"><?= htmlspecialchars($paquete['incluye'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
  </div>
  <div class="campo">
    <label for="no_incluye">No incluye (lista HTML)</label>
    <textarea id="no_incluye" name="no_incluye"><?= htmlspecialchars($paquete['no_incluye'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
  </div>
</div>

<div class="admin-form-grid admin-form-grid--2">
  <div class="campo">
    <label for="meta_title">Título SEO (opcional)</label>
    <input type="text" id="meta_title" name="meta_title" maxlength="180" value="<?= htmlspecialchars($paquete['meta_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
  </div>
  <div class="campo">
    <label for="meta_description">Descripción SEO (opcional)</label>
    <input type="text" id="meta_description" name="meta_description" maxlength="300" value="<?= htmlspecialchars($paquete['meta_description'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
  </div>
</div>

<div class="campo">
  <label for="imagen">Imagen de portada (JPG, PNG o WEBP)</label>
  <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp">
</div>

<button type="submit" class="btn btn-primario">Guardar paquete</button>
