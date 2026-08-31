<?php
/**
 * @var int $totalPaquetes
 * @var int $totalPaquetesPublicados
 * @var string $periodoDesde
 * @var string $periodoHasta
 */
?>
<div class="admin-panel">
  <form method="get" action="/admin" class="admin-form-grid admin-form-grid--2" style="align-items:end;">
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
    <p style="margin:0;opacity:0.7;">Paquetes totales</p>
    <p style="font-family:var(--fuente-titulos);font-size:2rem;margin:0.25rem 0 0;"><?= $totalPaquetes ?></p>
  </div>
  <div class="admin-panel">
    <p style="margin:0;opacity:0.7;">Paquetes publicados</p>
    <p style="font-family:var(--fuente-titulos);font-size:2rem;margin:0.25rem 0 0;"><?= $totalPaquetesPublicados ?></p>
  </div>
  <?php if (isset($totalCotizacionesNuevas)): ?>
  <div class="admin-panel">
    <p style="margin:0;opacity:0.7;">Cotizaciones nuevas</p>
    <p style="font-family:var(--fuente-titulos);font-size:2rem;margin:0.25rem 0 0;"><?= $totalCotizacionesNuevas ?></p>
  </div>
  <?php endif; ?>
  <?php if (isset($seguimientosVencidos)): ?>
  <div class="admin-panel">
    <p style="margin:0;opacity:0.7;">Seguimientos vencidos</p>
    <p style="font-family:var(--fuente-titulos);font-size:2rem;margin:0.25rem 0 0;"><?= (int) $seguimientosVencidos ?></p>
    <?php if ((int) $seguimientosVencidos > 0): ?>
      <p style="margin:0.25rem 0 0;font-size:0.85rem;"><a href="/admin/cotizaciones?seguimiento=vencidos">Ver cotizaciones</a></p>
    <?php endif; ?>
  </div>
  <?php endif; ?>
  <?php if (isset($conversion)): ?>
  <div class="admin-panel">
    <p style="margin:0;opacity:0.7;">Tasa de conversion (periodo)</p>
    <p style="font-family:var(--fuente-titulos);font-size:2rem;margin:0.25rem 0 0;"><?= number_format($conversion['tasa'], 1) ?>%</p>
    <p style="margin:0.25rem 0 0;opacity:0.6;font-size:0.85rem;"><?= $conversion['convertidas'] ?> de <?= $conversion['total'] ?> cotizaciones</p>
  </div>
  <?php endif; ?>
  <?php if (isset($totalReservasPendientes)): ?>
  <div class="admin-panel">
    <p style="margin:0;opacity:0.7;">Reservas pendientes</p>
    <p style="font-family:var(--fuente-titulos);font-size:2rem;margin:0.25rem 0 0;"><?= $totalReservasPendientes ?></p>
  </div>
  <div class="admin-panel">
    <p style="margin:0;opacity:0.7;">Reservas confirmadas</p>
    <p style="font-family:var(--fuente-titulos);font-size:2rem;margin:0.25rem 0 0;"><?= $totalReservasConfirmadas ?></p>
  </div>
  <div class="admin-panel">
    <p style="margin:0;opacity:0.7;">Ingresos del periodo</p>
    <p style="font-family:var(--fuente-titulos);font-size:2rem;margin:0.25rem 0 0;">$<?= number_format($ingresosPeriodo, 2) ?></p>
  </div>
  <?php endif; ?>
</div>

<?php if (isset($cotizacionesPorOrigen)): ?>
<div class="admin-panel">
  <h2 style="margin-top:0;">Cotizaciones por origen (periodo)</h2>
  <?php if ($cotizacionesPorOrigen === []): ?>
    <p style="opacity:0.7;">No hay cotizaciones en el periodo seleccionado.</p>
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
  <h2 style="margin-top:0;">Ocupacion de proximas salidas</h2>
  <?php if ($proximasSalidas === []): ?>
    <p style="opacity:0.7;">No hay salidas futuras registradas.</p>
  <?php else: ?>
  <div class="admin-tabla-wrap">
    <table class="admin-tabla">
      <thead>
        <tr>
          <th>Paquete</th>
          <th>Fecha de salida</th>
          <th>Cupo</th>
          <th>Ocupacion</th>
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
  <h2 style="margin-top:0;">Accesos rapidos</h2>
  <div class="admin-acciones">
    <a href="/admin/paquetes/crear" class="btn btn-primario">Nuevo paquete</a>
    <a href="/admin/paquetes" class="btn btn-secundario">Ver paquetes</a>
  </div>
</div>
