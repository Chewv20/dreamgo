<?php /** @var array $paquete */ ?>
<div class="admin-panel" style="max-width:520px;">
  <form method="post" action="/admin/paquetes/<?= (int) $paquete['id'] ?>/salidas" class="admin-form-grid">
    <?= \App\Helpers\Csrf::field() ?>
    <div class="campo">
      <label for="fecha_salida">Fecha de salida</label>
      <input type="date" id="fecha_salida" name="fecha_salida" required>
    </div>
    <div class="campo">
      <label for="fecha_regreso">Fecha de regreso (opcional)</label>
      <input type="date" id="fecha_regreso" name="fecha_regreso">
    </div>
    <div class="campo">
      <label for="cupo_maximo">Cupo maximo</label>
      <input type="number" min="1" id="cupo_maximo" name="cupo_maximo" required>
    </div>
    <div class="campo">
      <label for="precio_override">Precio especial para esta fecha (opcional)</label>
      <input type="number" step="0.01" min="0" id="precio_override" name="precio_override">
      <small style="opacity:0.7;">En la moneda del paquete: <?= htmlspecialchars($paquete['moneda'], ENT_QUOTES, 'UTF-8') ?></small>
    </div>
    <button type="submit" class="btn btn-primario">Crear fecha</button>
  </form>
</div>
