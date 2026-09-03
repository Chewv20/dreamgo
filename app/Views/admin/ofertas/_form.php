<?php
/** @var array|null $oferta */
/** @var array $paquetes */
$oferta ??= [];
?>
<?= \App\Helpers\Csrf::field() ?>

<div class="admin-form-grid admin-form-grid--2">
  <div class="campo">
    <label for="codigo">Código</label>
    <input type="text" id="codigo" name="codigo" required maxlength="40" class="tt-upper" value="<?= htmlspecialchars($oferta['codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
  </div>
  <div class="campo">
    <label for="tipo">Tipo de descuento</label>
    <select id="tipo" name="tipo">
      <option value="porcentaje" <?= ($oferta['tipo'] ?? '') === 'porcentaje' ? 'selected' : '' ?>>Porcentaje (%)</option>
      <option value="monto_fijo" <?= ($oferta['tipo'] ?? '') === 'monto_fijo' ? 'selected' : '' ?>>Monto fijo (MXN)</option>
    </select>
  </div>
</div>

<div class="admin-form-grid admin-form-grid--2">
  <div class="campo">
    <label for="valor">Valor del descuento</label>
    <input type="number" step="0.01" min="0" id="valor" name="valor" required value="<?= htmlspecialchars((string) ($oferta['valor'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
  </div>
  <div class="campo">
    <label for="uso_maximo">Uso máximo (opcional, en blanco = ilimitado)</label>
    <input type="number" min="1" id="uso_maximo" name="uso_maximo" value="<?= htmlspecialchars((string) ($oferta['uso_maximo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
  </div>
</div>

<div class="admin-form-grid admin-form-grid--2">
  <div class="campo">
    <label for="fecha_inicio">Vigente desde</label>
    <input type="date" id="fecha_inicio" name="fecha_inicio" required value="<?= htmlspecialchars($oferta['fecha_inicio'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
  </div>
  <div class="campo">
    <label for="fecha_fin">Vigente hasta</label>
    <input type="date" id="fecha_fin" name="fecha_fin" required value="<?= htmlspecialchars($oferta['fecha_fin'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
  </div>
</div>

<div class="admin-form-grid admin-form-grid--2">
  <div class="campo">
    <label for="alcance">Alcance</label>
    <select id="alcance" name="alcance" data-toggle-target="campo-paquete" data-toggle-value="paquete">
      <option value="global" <?= ($oferta['alcance'] ?? 'global') === 'global' ? 'selected' : '' ?>>Global (todos los paquetes)</option>
      <option value="paquete" <?= ($oferta['alcance'] ?? '') === 'paquete' ? 'selected' : '' ?>>Un paquete específico</option>
    </select>
  </div>
  <div class="campo<?= ($oferta['alcance'] ?? '') === 'paquete' ? '' : ' oculto' ?>" id="campo-paquete">
    <label for="paquete_id">Paquete</label>
    <select id="paquete_id" name="paquete_id">
      <?php foreach ($paquetes as $paquete): ?>
        <option value="<?= (int) $paquete['id'] ?>" <?= (int) ($oferta['paquete_id'] ?? 0) === (int) $paquete['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($paquete['titulo'], ENT_QUOTES, 'UTF-8') ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
</div>

<div class="campo campo--check">
  <input type="checkbox" id="activo" name="activo" value="1" <?= (int) ($oferta['activo'] ?? 1) === 1 ? 'checked' : '' ?>>
  <label for="activo" class="m-0">Activo</label>
</div>

<button type="submit" class="btn btn-primario">Guardar código</button>
