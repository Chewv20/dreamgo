<?php /** @var array $salida */ ?>
<div class="admin-panel" style="max-width:520px;">
  <p>Fecha de salida: <strong><?= \App\Helpers\Fecha::corta($salida['fecha_salida']) ?></strong> &mdash; Cupo disponible: <strong><?= (int) $salida['cupo_disponible'] ?></strong></p>
  <form method="post" action="/admin/reservas" class="admin-form-grid">
    <?= \App\Helpers\Csrf::field() ?>
    <input type="hidden" name="salida_id" value="<?= (int) $salida['id'] ?>">
    <div class="campo">
      <label for="nombre">Nombre del cliente</label>
      <input type="text" id="nombre" name="nombre" required>
    </div>
    <div class="campo">
      <label for="email">Correo</label>
      <input type="email" id="email" name="email" required>
    </div>
    <div class="campo">
      <label for="telefono">Telefono</label>
      <input type="tel" id="telefono" name="telefono" required>
    </div>
    <div class="campo">
      <label for="num_personas">Numero de personas</label>
      <input type="number" id="num_personas" name="num_personas" min="1" max="<?= (int) $salida['cupo_disponible'] ?>" required value="1">
    </div>
    <div class="campo">
      <label for="codigo_descuento">Codigo de descuento (opcional)</label>
      <input type="text" id="codigo_descuento" name="codigo_descuento" placeholder="Ej. VERANO2026">
    </div>
    <button type="submit" class="btn btn-primario">Crear reserva</button>
  </form>
</div>
