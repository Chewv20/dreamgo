<?php /** @var array $paquete */ /** @var array $salida */ ?>
<div class="admin-panel ancho-520">
  <form method="post" action="/admin/paquetes/<?= (int) $paquete['id'] ?>/salidas/<?= (int) $salida['id'] ?>/editar" class="admin-form-grid">
    <?= \App\Helpers\Csrf::field() ?>
    <div class="campo">
      <label for="fecha_salida">Fecha de salida</label>
      <input type="date" id="fecha_salida" name="fecha_salida" required value="<?= htmlspecialchars($salida['fecha_salida'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="campo">
      <label for="fecha_regreso">Fecha de regreso (opcional)</label>
      <input type="date" id="fecha_regreso" name="fecha_regreso" value="<?= htmlspecialchars($salida['fecha_regreso'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="campo">
      <label for="cupo_maximo">Cupo maximo</label>
      <input type="number" min="0" id="cupo_maximo" name="cupo_maximo" required value="<?= (int) $salida['cupo_maximo'] ?>">
      <small class="op-70">Cupo disponible actual: <?= (int) $salida['cupo_disponible'] ?></small>
    </div>
    <div class="campo">
      <label for="precio_override">Precio especial para esta fecha (opcional)</label>
      <input type="number" step="0.01" min="0" id="precio_override" name="precio_override" value="<?= htmlspecialchars((string) ($salida['precio_override'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      <small class="op-70">En la moneda del paquete: <?= htmlspecialchars($paquete['moneda'], ENT_QUOTES, 'UTF-8') ?></small>
    </div>
    <div class="campo">
      <label for="estado">Estado</label>
      <select id="estado" name="estado">
        <?php foreach (['abierta' => 'Abierta', 'cerrada' => 'Cerrada', 'cancelada' => 'Cancelada'] as $valor => $etiqueta): ?>
          <option value="<?= $valor ?>" <?= $salida['estado'] === $valor ? 'selected' : '' ?>><?= $etiqueta ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-primario">Guardar cambios</button>
  </form>
</div>
