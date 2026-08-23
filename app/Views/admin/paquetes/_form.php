<?php
/** @var array $categorias */
/** @var array|null $paquete */
$paquete ??= [];
?>
<?= \App\Helpers\Csrf::field() ?>

<div class="admin-form-grid admin-form-grid--2">
  <div class="campo">
    <label for="titulo">Titulo</label>
    <input type="text" id="titulo" name="titulo" required maxlength="180" value="<?= htmlspecialchars($paquete['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
  </div>
  <div class="campo">
    <label for="categoria_id">Categoria / destino</label>
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
  <label for="resumen">Resumen corto (para tarjetas de catalogo)</label>
  <input type="text" id="resumen" name="resumen" maxlength="300" value="<?= htmlspecialchars($paquete['resumen'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
</div>

<div class="admin-form-grid admin-form-grid--2">
  <div class="campo">
    <label for="precio_desde">Precio desde (MXN)</label>
    <input type="number" step="0.01" min="0" id="precio_desde" name="precio_desde" required value="<?= htmlspecialchars((string) ($paquete['precio_desde'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
  </div>
  <div class="campo">
    <label for="estado">Estado</label>
    <select id="estado" name="estado" required>
      <?php foreach (['borrador' => 'Borrador', 'publicado' => 'Publicado', 'archivado' => 'Archivado'] as $valor => $etiqueta): ?>
        <option value="<?= $valor ?>" <?= ($paquete['estado'] ?? 'borrador') === $valor ? 'selected' : '' ?>><?= $etiqueta ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<div class="admin-form-grid admin-form-grid--2">
  <div class="campo">
    <label for="duracion_dias">Duracion (dias)</label>
    <input type="number" min="1" id="duracion_dias" name="duracion_dias" value="<?= htmlspecialchars((string) ($paquete['duracion_dias'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
  </div>
  <div class="campo">
    <label for="duracion_noches">Duracion (noches)</label>
    <input type="number" min="0" id="duracion_noches" name="duracion_noches" value="<?= htmlspecialchars((string) ($paquete['duracion_noches'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
  </div>
</div>

<div class="campo" style="display:flex;align-items:center;gap:0.6rem;">
  <input type="checkbox" id="destacado" name="destacado" value="1" style="width:1.2rem;height:1.2rem;" <?= (int) ($paquete['destacado'] ?? 0) === 1 ? 'checked' : '' ?>>
  <label for="destacado" style="margin:0;">Mostrar como destacado en Inicio</label>
</div>

<div class="campo">
  <label for="descripcion_larga">Descripcion (HTML basico: negritas, listas, parrafos)</label>
  <textarea id="descripcion_larga" name="descripcion_larga" style="min-height:140px;"><?= htmlspecialchars($paquete['descripcion_larga'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
</div>

<div class="campo">
  <label for="itinerario">Itinerario (HTML basico)</label>
  <textarea id="itinerario" name="itinerario" style="min-height:140px;"><?= htmlspecialchars($paquete['itinerario'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
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
    <label for="meta_title">Titulo SEO (opcional)</label>
    <input type="text" id="meta_title" name="meta_title" maxlength="180" value="<?= htmlspecialchars($paquete['meta_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
  </div>
  <div class="campo">
    <label for="meta_description">Descripcion SEO (opcional)</label>
    <input type="text" id="meta_description" name="meta_description" maxlength="300" value="<?= htmlspecialchars($paquete['meta_description'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
  </div>
</div>

<div class="campo">
  <label for="imagen">Imagen de portada (JPG, PNG o WEBP)</label>
  <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp">
</div>

<button type="submit" class="btn btn-primario">Guardar paquete</button>
