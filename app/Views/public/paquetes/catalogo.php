<?php /** @var array $paquetes */ /** @var array $categorias */ /** @var string $categoriaActiva */ /** @var string $tipoActivo */ /** @var string $qActivo */ /** @var int|string $precioMinActivo */ /** @var int|string $precioMaxActivo */ /** @var string $duracionActiva */ /** @var array|null $intro */ ?>
<section class="seccion contenedor">
  <?php $introVisible = $intro && (int) $intro['visible'] === 1; ?>
  <h1><?= htmlspecialchars($introVisible && !empty($intro['titulo']) ? $intro['titulo'] : 'Paquetes y excursiones', ENT_QUOTES, 'UTF-8') ?></h1>
  <?php if ($introVisible && !empty($intro['subtitulo'])): ?>
    <p><?= htmlspecialchars($intro['subtitulo'], ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <form method="get" action="/paquetes" style="display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end;margin-block:2rem;">
    <div class="campo" style="flex:2;min-width:220px;margin-bottom:0;">
      <label for="q">Buscar</label>
      <input type="search" name="q" id="q" placeholder="Ej. Cancun, playa, aventura..." value="<?= htmlspecialchars((string) $qActivo, ENT_QUOTES, 'UTF-8') ?>" maxlength="120">
    </div>
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
    <div class="campo" style="flex:1;min-width:130px;margin-bottom:0;">
      <label for="precio_min">Precio min.</label>
      <input type="number" name="precio_min" id="precio_min" min="0" step="1" placeholder="$0" value="<?= htmlspecialchars((string) $precioMinActivo, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="campo" style="flex:1;min-width:130px;margin-bottom:0;">
      <label for="precio_max">Precio max.</label>
      <input type="number" name="precio_max" id="precio_max" min="0" step="1" placeholder="$" value="<?= htmlspecialchars((string) $precioMaxActivo, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="campo" style="flex:1;min-width:170px;margin-bottom:0;">
      <label for="duracion">Duracion</label>
      <select name="duracion" id="duracion">
        <option value="">Cualquiera</option>
        <option value="1-3" <?= $duracionActiva === '1-3' ? 'selected' : '' ?>>1 a 3 dias</option>
        <option value="4-7" <?= $duracionActiva === '4-7' ? 'selected' : '' ?>>4 a 7 dias</option>
        <option value="8-14" <?= $duracionActiva === '8-14' ? 'selected' : '' ?>>8 a 14 dias</option>
        <option value="15+" <?= $duracionActiva === '15+' ? 'selected' : '' ?>>15 dias o mas</option>
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
  <?php
  $rutaBase = '/paquetes';
  $queryExtra = array_filter([
      'categoria' => $categoriaActiva,
      'tipo' => $tipoActivo,
      'q' => $qActivo,
      'precio_min' => $precioMinActivo,
      'precio_max' => $precioMaxActivo,
      'duracion' => $duracionActiva,
  ], static fn ($valor) => $valor !== '' && $valor !== null);
  require __DIR__ . '/../../partials/paginacion.php';
  ?>
</section>
