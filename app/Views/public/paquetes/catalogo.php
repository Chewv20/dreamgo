<?php
/** @var array $paquetes */
/** @var array $categorias */
/** @var string $categoriaActiva */
/** @var string $tipoActivo */
/** @var string $qActivo */
/** @var int|string $precioMinActivo */
/** @var int|string $precioMaxActivo */
/** @var string $duracionActiva */
/** @var string $ordenActivo */
/** @var int $totalResultados */
/** @var array|null $intro */

use App\Models\Paquete;

$introVisible = $intro && (int) $intro['visible'] === 1;

$duracionEtiquetas = [
    '1-3' => '1 a 3 días',
    '4-7' => '4 a 7 días',
    '8-14' => '8 a 14 días',
    '15+' => '15 días o más',
];
$ordenEtiquetas = [
    'recientes' => 'Más recientes',
    'precio_asc' => 'Precio: menor a mayor',
    'precio_desc' => 'Precio: mayor a menor',
    'duracion_asc' => 'Duración: más corta',
    'mejor_valorados' => 'Mejor valorados',
];
$categoriaNombre = '';
foreach ($categorias as $c) {
    if ($c['slug'] === $categoriaActiva) {
        $categoriaNombre = $c['nombre'];
        break;
    }
}

// Parametros de filtro activos (sin `orden`, que es el control de ordenamiento, no un filtro).
$filtrosActivos = array_filter([
    'q' => $qActivo,
    'categoria' => $categoriaActiva,
    'tipo' => $tipoActivo,
    'precio_min' => (string) $precioMinActivo,
    'precio_max' => (string) $precioMaxActivo,
    'duracion' => $duracionActiva,
], static fn ($v) => $v !== '' && $v !== null);

// URL del catalogo quitando un filtro (conserva el resto + el orden).
$urlSinFiltro = static function (string $clave) use ($filtrosActivos, $ordenActivo): string {
    $params = $filtrosActivos;
    unset($params[$clave]);
    if ($ordenActivo !== Paquete::ORDEN_DEFECTO) {
        $params['orden'] = $ordenActivo;
    }
    return '/paquetes' . ($params ? '?' . http_build_query($params) : '');
};

$chips = [];
if (!empty($filtrosActivos['q'])) {
    $chips[] = ['texto' => '"' . $qActivo . '"', 'url' => $urlSinFiltro('q')];
}
if (!empty($filtrosActivos['categoria'])) {
    $chips[] = ['texto' => $categoriaNombre ?: $categoriaActiva, 'url' => $urlSinFiltro('categoria')];
}
if (!empty($filtrosActivos['tipo'])) {
    $chips[] = ['texto' => $tipoActivo === 'internacional' ? 'Internacional' : 'Nacional', 'url' => $urlSinFiltro('tipo')];
}
if (isset($filtrosActivos['precio_min'])) {
    $chips[] = ['texto' => 'Desde $' . number_format((float) $precioMinActivo, 0, '.', ','), 'url' => $urlSinFiltro('precio_min')];
}
if (isset($filtrosActivos['precio_max'])) {
    $chips[] = ['texto' => 'Hasta $' . number_format((float) $precioMaxActivo, 0, '.', ','), 'url' => $urlSinFiltro('precio_max')];
}
if (!empty($filtrosActivos['duracion'])) {
    $chips[] = ['texto' => $duracionEtiquetas[$duracionActiva] ?? $duracionActiva, 'url' => $urlSinFiltro('duracion')];
}
?>
<section class="seccion contenedor">
  <?php $migas = [['texto' => 'Inicio', 'url' => '/'], ['texto' => 'Paquetes']]; require __DIR__ . '/../../partials/_breadcrumbs.php'; ?>

  <h1><?= htmlspecialchars($introVisible && !empty($intro['titulo']) ? $intro['titulo'] : 'Paquetes y excursiones', ENT_QUOTES, 'UTF-8') ?></h1>
  <?php if ($introVisible && !empty($intro['subtitulo'])): ?>
    <p><?= htmlspecialchars($intro['subtitulo'], ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <form method="get" action="/paquetes" class="filtros">
    <div class="campo filtros__q">
      <label for="q">Buscar</label>
      <input type="search" name="q" id="q" placeholder="Ej. Cancún, playa, aventura..." value="<?= htmlspecialchars((string) $qActivo, ENT_QUOTES, 'UTF-8') ?>" maxlength="120">
    </div>
    <div class="campo filtros__campo">
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
    <div class="campo filtros__campo">
      <label for="tipo">Tipo</label>
      <select name="tipo" id="tipo">
        <option value="">Nacional e internacional</option>
        <option value="nacional" <?= $tipoActivo === 'nacional' ? 'selected' : '' ?>>Nacional</option>
        <option value="internacional" <?= $tipoActivo === 'internacional' ? 'selected' : '' ?>>Internacional</option>
      </select>
    </div>
    <div class="campo filtros__campo--chico">
      <label for="precio_min">Precio mín.</label>
      <input type="number" name="precio_min" id="precio_min" min="0" step="1" placeholder="$0" value="<?= htmlspecialchars((string) $precioMinActivo, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="campo filtros__campo--chico">
      <label for="precio_max">Precio máx.</label>
      <input type="number" name="precio_max" id="precio_max" min="0" step="1" placeholder="$" value="<?= htmlspecialchars((string) $precioMaxActivo, ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="campo filtros__campo">
      <label for="duracion">Duración</label>
      <select name="duracion" id="duracion">
        <option value="">Cualquiera</option>
        <?php foreach ($duracionEtiquetas as $valor => $etiqueta): ?>
          <option value="<?= $valor ?>" <?= $duracionActiva === $valor ? 'selected' : '' ?>><?= $etiqueta ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <input type="hidden" name="orden" value="<?= htmlspecialchars($ordenActivo, ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit" class="btn btn-primario">Filtrar</button>
  </form>

  <div class="catalogo-barra">
    <p class="catalogo-barra__conteo">
      <strong><?= (int) $totalResultados ?></strong> <?= (int) $totalResultados === 1 ? 'paquete' : 'paquetes' ?>
      <?php if ($chips): ?>
        &middot; <a href="/paquetes">Limpiar filtros</a>
      <?php endif; ?>
    </p>
    <form method="get" action="/paquetes" class="catalogo-orden">
      <?php foreach ($filtrosActivos as $clave => $valor): ?>
        <input type="hidden" name="<?= htmlspecialchars($clave, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') ?>">
      <?php endforeach; ?>
      <label for="orden">Ordenar por</label>
      <select name="orden" id="orden" data-autosubmit>
        <?php foreach ($ordenEtiquetas as $valor => $etiqueta): ?>
          <option value="<?= $valor ?>" <?= $ordenActivo === $valor ? 'selected' : '' ?>><?= $etiqueta ?></option>
        <?php endforeach; ?>
      </select>
      <noscript><button type="submit" class="btn btn-secundario btn--chico">Ordenar</button></noscript>
    </form>
  </div>

  <?php if ($chips): ?>
    <div class="chips-filtro">
      <?php foreach ($chips as $chip): ?>
        <a class="chip-filtro" href="<?= htmlspecialchars($chip['url'], ENT_QUOTES, 'UTF-8') ?>">
          <span><?= htmlspecialchars($chip['texto'], ENT_QUOTES, 'UTF-8') ?></span>
          <span class="chip-filtro__x" aria-hidden="true">&times;</span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (empty($paquetes)): ?>
    <?php
    $titulo = 'Sin resultados';
    $texto = $chips
        ? 'No encontramos paquetes con esos filtros. Prueba con otra combinación.'
        : 'Todavía no hay paquetes publicados. Vuelve pronto.';
    $cta = $chips ? ['url' => '/paquetes', 'texto' => 'Limpiar filtros'] : null;
    require __DIR__ . '/../../partials/_estado_vacio.php';
    ?>
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
      'orden' => $ordenActivo !== Paquete::ORDEN_DEFECTO ? $ordenActivo : '',
  ], static fn ($valor) => $valor !== '' && $valor !== null);
  require __DIR__ . '/../../partials/paginacion.php';
  ?>
</section>
