<?php
/** @var array $paquetes */
/** @var array $categorias */
/** @var array{q:string, estado:string, categoria:string} $filtros */
$filtros ??= ['q' => '', 'estado' => '', 'categoria' => ''];
$categorias ??= [];
$queryExtra = array_filter($filtros, static fn ($v) => $v !== '');
$hayFiltros = $queryExtra !== [];
$puedeSalidas = \Core\Auth::hasPermission('salidas.gestionar');
$badges = ['publicado' => 'verde', 'borrador' => 'ambar', 'archivado' => 'gris'];
?>
<div class="admin-filtros">
  <?php if (\Core\Auth::hasPermission('paquetes.crear')): ?>
    <a href="/admin/paquetes/crear" class="btn btn-primario">Nuevo paquete</a>
  <?php endif; ?>
</div>

<form method="get" action="/admin/paquetes" class="admin-panel admin-buscador" role="search">
  <div class="campo admin-buscador__q">
    <label for="q">Buscar</label>
    <input type="search" id="q" name="q" placeholder="Título del paquete" value="<?= htmlspecialchars($filtros['q'], ENT_QUOTES, 'UTF-8') ?>">
  </div>
  <div class="campo">
    <label for="estado">Estado</label>
    <select id="estado" name="estado" data-autosubmit>
      <option value="">Todos</option>
      <?php foreach (\App\Models\Paquete::ESTADOS as $estado): ?>
        <option value="<?= $estado ?>" <?= $filtros['estado'] === $estado ? 'selected' : '' ?>><?= ucfirst($estado) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="campo">
    <label for="categoria">Destino</label>
    <select id="categoria" name="categoria" data-autosubmit>
      <option value="">Todos</option>
      <?php foreach ($categorias as $c): ?>
        <option value="<?= (int) $c['id'] ?>" <?= $filtros['categoria'] === (string) $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="admin-acciones">
    <button type="submit" class="btn btn-primario">Buscar</button>
    <?php if ($hayFiltros): ?><a href="/admin/paquetes" class="btn btn-secundario">Limpiar</a><?php endif; ?>
  </div>
</form>

<div class="admin-panel">
  <p class="admin-conteo"><?= (int) $paginador->total ?> <?= (int) $paginador->total === 1 ? 'paquete' : 'paquetes' ?><?= $hayFiltros ? ' con estos filtros' : '' ?></p>
  <div class="admin-tabla-wrap">
    <table class="admin-tabla">
      <thead><tr><th><span class="sr-only">Portada</span></th><th>Título</th><th>Precio</th><th>Próximas salidas</th><th>Estado</th><th>Destacado</th><th>Acciones</th></tr></thead>
      <tbody>
        <?php foreach ($paquetes as $paquete): ?>
          <tr>
            <td class="admin-miniatura-celda">
              <?php if (!empty($paquete['imagen_portada'])): ?>
                <img class="admin-miniatura" src="<?= htmlspecialchars($paquete['imagen_portada'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy">
              <?php else: ?>
                <span class="admin-miniatura admin-miniatura--vacia" title="Sin imagen"></span>
              <?php endif; ?>
            </td>
            <td>
              <?= htmlspecialchars($paquete['titulo'], ENT_QUOTES, 'UTF-8') ?>
              <br><small class="op-70"><?= htmlspecialchars($paquete['categoria_nombre'], ENT_QUOTES, 'UTF-8') ?></small>
            </td>
            <td class="num">$<?= number_format((float) $paquete['precio_desde'], 0, '.', ',') ?> <?= htmlspecialchars($paquete['moneda'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= (int) $paquete['salidas_futuras'] > 0 ? (int) $paquete['salidas_futuras'] : '<span class="op-70">Sin fechas</span>' ?></td>
            <td><span class="admin-badge admin-badge--<?= $badges[$paquete['estado']] ?? 'gris' ?>"><?= ucfirst($paquete['estado']) ?></span></td>
            <td><?= (int) $paquete['destacado'] === 1 ? 'Sí' : '—' ?></td>
            <td class="admin-acciones">
              <a href="/admin/paquetes/<?= (int) $paquete['id'] ?>/editar" class="btn btn-secundario">Editar</a>
              <?php if ($puedeSalidas): ?>
                <a href="/admin/paquetes/<?= (int) $paquete['id'] ?>/salidas" class="btn btn-secundario">Fechas</a>
              <?php endif; ?>
              <?php if ($paquete['estado'] === 'publicado'): ?>
                <a href="/paquetes/<?= urlencode($paquete['slug']) ?>" class="btn btn-secundario" target="_blank" rel="noopener" aria-label="Ver <?= htmlspecialchars($paquete['titulo'], ENT_QUOTES, 'UTF-8') ?> en el sitio (pestaña nueva)">Ver ↗</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($paquetes)): ?>
          <tr><td colspan="7"><?= $hayFiltros ? 'Ningún paquete coincide con la búsqueda.' : 'Aún no hay paquetes.' ?></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php $rutaBase = '/admin/paquetes'; require __DIR__ . '/../../partials/paginacion.php'; ?>
</div>
