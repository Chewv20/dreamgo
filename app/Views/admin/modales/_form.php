<?php
/** @var array $paquetes */
/** @var array<string,string> $alcances */
/** @var array|null $modal */
$modal ??= [];
?>
<?= \App\Helpers\Csrf::field() ?>

<div class="campo">
  <label for="titulo">Título del modal</label>
  <input type="text" id="titulo" name="titulo" required maxlength="120" value="<?= htmlspecialchars($modal['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
</div>

<div class="campo">
  <label for="cuerpo">Texto (opcional; se muestra bajo el título)</label>
  <textarea id="cuerpo" name="cuerpo" maxlength="500"><?= htmlspecialchars($modal['cuerpo'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
</div>

<div class="admin-form-grid admin-form-grid--2">
  <div class="campo">
    <label for="paquete_id">Paquete al que lleva el botón</label>
    <select id="paquete_id" name="paquete_id" required>
      <option value="">Elige un paquete…</option>
      <?php foreach ($paquetes as $p): ?>
        <option value="<?= (int) $p['id'] ?>" <?= (int) ($modal['paquete_id'] ?? 0) === (int) $p['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($p['titulo'], ENT_QUOTES, 'UTF-8') ?><?= $p['estado'] !== 'publicado' ? ' (' . htmlspecialchars($p['estado'], ENT_QUOTES, 'UTF-8') . ')' : '' ?>
        </option>
      <?php endforeach; ?>
    </select>
    <small class="op-70">El modal no se muestra si el paquete no está publicado.</small>
  </div>
  <div class="campo">
    <label for="texto_boton">Texto del botón</label>
    <input type="text" id="texto_boton" name="texto_boton" maxlength="40" value="<?= htmlspecialchars($modal['texto_boton'] ?? 'Ver paquete', ENT_QUOTES, 'UTF-8') ?>">
  </div>
</div>

<div class="campo">
  <label for="imagen">Imagen (opcional; JPG, PNG o WEBP)</label>
  <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp">
  <?php if (!empty($modal['imagen'])): ?>
    <small class="op-70">Actual: <?= htmlspecialchars($modal['imagen'], ENT_QUOTES, 'UTF-8') ?> — sube una nueva para reemplazarla.</small>
  <?php endif; ?>
</div>

<div class="admin-form-grid admin-form-grid--2">
  <div class="campo">
    <label for="mostrar_en">¿Dónde se muestra?</label>
    <select id="mostrar_en" name="mostrar_en" required>
      <?php foreach ($alcances as $valor => $etiqueta): ?>
        <option value="<?= htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') ?>" <?= ($modal['mostrar_en'] ?? 'inicio') === $valor ? 'selected' : '' ?>><?= htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8') ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="campo">
    <label for="prioridad">Prioridad (mayor gana si hay varios vigentes)</label>
    <input type="number" id="prioridad" name="prioridad" min="0" max="65535" value="<?= (int) ($modal['prioridad'] ?? 0) ?>">
  </div>
</div>

<div class="admin-form-grid admin-form-grid--2">
  <div class="campo">
    <label for="fecha_inicio">Se muestra desde (opcional)</label>
    <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?= htmlspecialchars($modal['fecha_inicio'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
  </div>
  <div class="campo">
    <label for="fecha_fin">Se muestra hasta (opcional)</label>
    <input type="date" id="fecha_fin" name="fecha_fin" value="<?= htmlspecialchars($modal['fecha_fin'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
  </div>
</div>

<div class="campo campo--check">
  <input type="checkbox" id="activo" name="activo" value="1" <?= (int) ($modal['activo'] ?? 1) === 1 ? 'checked' : '' ?>>
  <label for="activo" class="m-0">Activo (visible en el sitio)</label>
</div>

<button type="submit" class="btn btn-primario">Guardar modal</button>
