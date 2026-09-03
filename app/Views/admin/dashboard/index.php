<?php
/**
 * @var int $totalPaquetes
 * @var int $totalPaquetesPublicados
 * @var string $periodoDesde
 * @var string $periodoHasta
 */
?>
<div class="admin-panel">
  <form method="get" action="/admin" class="admin-form-grid admin-form-grid--2 admin-form-grid--end">
    <div>
      <label for="desde">Desde</label>
      <input type="date" id="desde" name="desde" value="<?= htmlspecialchars($periodoDesde) ?>">
    </div>
    <div>
      <label for="hasta">Hasta</label>
      <input type="date" id="hasta" name="hasta" value="<?= htmlspecialchars($periodoHasta) ?>">
    </div>
    <div class="admin-acciones">
      <button type="submit" class="btn btn-primario">Filtrar</button>
      <a href="/admin" class="btn btn-secundario">Restablecer</a>
    </div>
  </form>
</div>

<div class="grid-tarjetas">
  <div class="admin-panel">
    <p class="stat-label">Paquetes totales</p>
    <p class="stat-num"><?= $totalPaquetes ?></p>
  </div>
  <div class="admin-panel">
    <p class="stat-label">Paquetes publicados</p>
    <p class="stat-num"><?= $totalPaquetesPublicados ?></p>
  </div>
  <?php if (isset($totalCotizacionesNuevas)): ?>
  <div class="admin-panel">
    <p class="stat-label">Cotizaciones nuevas</p>
    <p class="stat-num"><?= $totalCotizacionesNuevas ?></p>
  </div>
  <?php endif; ?>
  <?php if (isset($seguimientosVencidos)): ?>
  <div class="admin-panel">
    <p class="stat-label">Seguimientos vencidos</p>
    <p class="stat-num"><?= (int) $seguimientosVencidos ?></p>
    <?php if ((int) $seguimientosVencidos > 0): ?>
      <p class="stat-sub"><a href="/admin/cotizaciones?seguimiento=vencidos">Ver cotizaciones</a></p>
    <?php endif; ?>
  </div>
  <?php endif; ?>
  <?php if (isset($conversion)): ?>
  <div class="admin-panel">
    <p class="stat-label">Tasa de conversión (periodo)</p>
    <p class="stat-num"><?= number_format($conversion['tasa'], 1) ?>%</p>
    <p class="stat-sub"><?= $conversion['convertidas'] ?> de <?= $conversion['total'] ?> cotizaciones</p>
  </div>
  <?php endif; ?>
  <?php if (isset($totalReservasPendientes)): ?>
  <div class="admin-panel">
    <p class="stat-label">Reservas pendientes</p>
    <p class="stat-num"><?= $totalReservasPendientes ?></p>
  </div>
  <div class="admin-panel">
    <p class="stat-label">Reservas confirmadas</p>
    <p class="stat-num"><?= $totalReservasConfirmadas ?></p>
  </div>
  <div class="admin-panel">
    <p class="stat-label">Ingresos del periodo</p>
    <p class="stat-num">$<?= number_format($ingresosPeriodo, 2) ?></p>
  </div>
  <?php endif; ?>
</div>

<?php if (isset($cotizacionesPorOrigen)): ?>
<div class="admin-panel">
  <h2 class="mt-0">Cotizaciones por origen (periodo)</h2>
  <?php if ($cotizacionesPorOrigen === []): ?>
    <p class="op-70">No hay cotizaciones en el periodo seleccionado.</p>
  <?php else: ?>
  <div class="admin-tabla-wrap">
    <table class="admin-tabla">
      <thead><tr><th>Origen</th><th>Cotizaciones</th></tr></thead>
      <tbody>
        <?php foreach ($cotizacionesPorOrigen as $fila): ?>
          <tr>
            <td><?= htmlspecialchars($fila['origen'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= (int) $fila['total'] ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if (isset($proximasSalidas)): ?>
<div class="admin-panel">
  <h2 class="mt-0">Ocupación de próximas salidas</h2>
  <?php if ($proximasSalidas === []): ?>
    <p class="op-70">No hay salidas futuras registradas.</p>
  <?php else: ?>
  <div class="admin-tabla-wrap">
    <table class="admin-tabla">
      <thead>
        <tr>
          <th>Paquete</th>
          <th>Fecha de salida</th>
          <th>Cupo</th>
          <th>Ocupación</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($proximasSalidas as $salida): ?>
          <?php
            $ocupados = (int) $salida['cupo_maximo'] - (int) $salida['cupo_disponible'];
            $porcentaje = (int) $salida['cupo_maximo'] > 0
              ? round($ocupados / (int) $salida['cupo_maximo'] * 100)
              : 0;
            $badge = 'admin-badge--verde';
            if ($porcentaje > 90) {
              $badge = 'admin-badge--rojo';
            } elseif ($porcentaje >= 70) {
              $badge = 'admin-badge--ambar';
            }
          ?>
          <tr>
            <td><?= htmlspecialchars($salida['paquete_titulo']) ?></td>
            <td><?= \App\Helpers\Fecha::corta($salida['fecha_salida']) ?></td>
            <td><?= $ocupados ?> / <?= $salida['cupo_maximo'] ?></td>
            <td><span class="admin-badge <?= $badge ?>"><?= $porcentaje ?>%</span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="admin-panel">
  <h2 class="mt-0">Accesos rápidos</h2>
  <div class="admin-acciones">
    <a href="/admin/paquetes/crear" class="btn btn-primario">Nuevo paquete</a>
    <a href="/admin/paquetes" class="btn btn-secundario">Ver paquetes</a>
  </div>
</div>
