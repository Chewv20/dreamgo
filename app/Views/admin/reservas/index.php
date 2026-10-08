<?php
/** @var array $reservas */
/** @var array{q:string, estado:string, salida:string} $filtros */
$filtros ??= ['q' => '', 'estado' => '', 'salida' => ''];
$queryExtra = array_filter($filtros, static fn ($v) => $v !== '');
$hayFiltros = $queryExtra !== [];
$badges = ['pendiente' => 'ambar', 'confirmada' => 'verde', 'cancelada' => 'gris', 'expirada' => 'rojo'];
?>
<div class="admin-filtros">
  <?php if (\Core\Auth::hasPermission('reservas.crear')): ?>
    <a href="/admin/reservas/crear" class="btn btn-primario">Nueva reserva</a>
  <?php endif; ?>
  <a href="/admin/reservas/calendario" class="btn btn-secundario">Ver calendario</a>
  <a href="/admin/reservas/exportar" class="btn btn-secundario">Exportar CSV</a>
</div>

<form method="get" action="/admin/reservas" class="admin-panel admin-buscador" role="search">
  <div class="campo admin-buscador__q">
    <label for="q">Buscar</label>
    <input type="search" id="q" name="q" placeholder="Código, nombre o correo del cliente" value="<?= htmlspecialchars($filtros['q'], ENT_QUOTES, 'UTF-8') ?>">
  </div>
  <div class="campo">
    <label for="estado">Estado</label>
    <select id="estado" name="estado" data-autosubmit>
      <option value="">Todos</option>
      <?php foreach (\App\Models\Reserva::ESTADOS as $estado): ?>
        <option value="<?= $estado ?>" <?= $filtros['estado'] === $estado ? 'selected' : '' ?>><?= ucfirst($estado) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="campo">
    <label for="salida">Salida</label>
    <select id="salida" name="salida" data-autosubmit>
      <option value="">Todas</option>
      <option value="proximas" <?= $filtros['salida'] === 'proximas' ? 'selected' : '' ?>>Próximas</option>
      <option value="pasadas" <?= $filtros['salida'] === 'pasadas' ? 'selected' : '' ?>>Pasadas</option>
    </select>
  </div>
  <div class="admin-acciones">
    <button type="submit" class="btn btn-primario">Buscar</button>
    <?php if ($hayFiltros): ?><a href="/admin/reservas" class="btn btn-secundario">Limpiar</a><?php endif; ?>
  </div>
</form>

<div class="admin-panel">
  <p class="admin-conteo"><?= (int) $paginador->total ?> <?= (int) $paginador->total === 1 ? 'reserva' : 'reservas' ?><?= $hayFiltros ? ' con estos filtros' : '' ?></p>
  <div class="admin-tabla-wrap">
    <table class="admin-tabla">
      <thead><tr><th>Código</th><th>Cliente</th><th>Paquete</th><th>Salida</th><th>Personas</th><th>Total</th><th>Pagado</th><th>Estado</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($reservas as $r): ?>
          <?php $saldo = max(0, (float) $r['precio_total'] - (float) $r['monto_pagado']); ?>
          <tr>
            <td><?= htmlspecialchars($r['codigo_reserva'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <?= htmlspecialchars($r['cliente_nombre'], ENT_QUOTES, 'UTF-8') ?>
              <br><small class="op-70"><?= htmlspecialchars($r['cliente_email'], ENT_QUOTES, 'UTF-8') ?></small>
            </td>
            <td><?= htmlspecialchars($r['paquete_titulo'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= \App\Helpers\Fecha::corta($r['fecha_salida']) ?></td>
            <td><?= (int) $r['num_personas'] ?></td>
            <td class="num">$<?= number_format((float) $r['precio_total'], 0, '.', ',') ?></td>
            <td class="num">
              $<?= number_format((float) $r['monto_pagado'], 0, '.', ',') ?>
              <?php if ($saldo > 0 && $r['estado'] !== 'cancelada' && $r['estado'] !== 'expirada'): ?>
                <br><small class="op-70">Saldo $<?= number_format($saldo, 0, '.', ',') ?></small>
              <?php endif; ?>
            </td>
            <td><span class="admin-badge admin-badge--<?= $badges[$r['estado']] ?? 'gris' ?>"><?= ucfirst($r['estado']) ?></span></td>
            <td><a href="/admin/reservas/<?= (int) $r['id'] ?>" class="btn btn-secundario">Ver</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($reservas)): ?>
          <tr><td colspan="9"><?= $hayFiltros ? 'Ninguna reserva coincide con la búsqueda.' : 'Aún no hay reservas registradas.' ?></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php $rutaBase = '/admin/reservas'; require __DIR__ . '/../../partials/paginacion.php'; ?>
</div>
