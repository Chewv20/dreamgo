<?php /** @var array $paquetes */ /** @var array $categorias */ /** @var string $categoriaActiva */ /** @var string $tipoActivo */ ?>
<section class="seccion contenedor">
  <h1>Paquetes y excursiones</h1>
  <p>Filtra por destino o por tipo de viaje para encontrar tu proxima excursion.</p>

  <form method="get" action="/paquetes" style="display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end;margin-block:2rem;">
    <div class="campo" style="flex:1;min-width:200px;margin-bottom:0;">
      <label for="categoria">Destino</label>
      <select name="categoria" id="categoria">
        <option value="">Todos los destinos</option>
        <?php foreach ($categorias as $categoria): ?>
          <option value="<?= htmlspecialchars($categoria['slug'], ENT_QUOTES, 'UTF-8') ?>" <?= $categoriaActiva === $categoria['slug'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="campo" style="flex:1;min-width:200px;margin-bottom:0;">
      <label for="tipo">Tipo</label>
      <select name="tipo" id="tipo">
        <option value="">Nacional e internacional</option>
        <option value="nacional" <?= $tipoActivo === 'nacional' ? 'selected' : '' ?>>Nacional</option>
        <option value="internacional" <?= $tipoActivo === 'internacional' ? 'selected' : '' ?>>Internacional</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primario">Filtrar</button>
  </form>

  <?php if (empty($paquetes)): ?>
    <p>No encontramos paquetes con esos filtros. Intenta con otra combinacion.</p>
  <?php else: ?>
    <div class="grid-tarjetas">
      <?php foreach ($paquetes as $paquete): ?>
        <?php require __DIR__ . '/_tarjeta.php'; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
