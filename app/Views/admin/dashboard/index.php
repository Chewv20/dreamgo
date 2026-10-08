<?php
/**
 * @var int $totalPaquetes
 * @var int $totalPaquetesPublicados
 * @var string $periodoDesde
 * @var string $periodoHasta
 * @var array<string, array{0: string, 1: string}> $atajos
 */
$atajos ??= [];
$dinero = static fn (float $v): string => '$' . number_format($v, 0, '.', ',');

// Variación contra el periodo anterior de igual duración. Sin base (0 → algo) no hay %.
$variacion = static function (float $actual, float $anterior): string {
    if ($anterior <= 0) {
        return $actual > 0 ? '<span class="kpi__var kpi__var--sube">Nuevo vs. periodo anterior</span>' : '<span class="kpi__var">Sin movimiento</span>';
    }
    $pct = ($actual - $anterior) / $anterior * 100;
    $clase = $pct > 0.5 ? 'sube' : ($pct < -0.5 ? 'baja' : 'igual');
    $flecha = ['sube' => '▲', 'baja' => '▼', 'igual' => '='][$clase];

    return '<span class="kpi__var kpi__var--' . $clase . '">' . $flecha . ' ' . number_format(abs($pct), 0) . '% vs. periodo anterior</span>';
};
$rangoActivo = null;
foreach ($atajos as $etiqueta => [$d, $h]) {
    if ($d === $periodoDesde && $h === $periodoHasta) {
        $rangoActivo = $etiqueta;
    }
}
$hayPendientes = isset($totalCotizacionesNuevas) || isset($totalReservasPendientes);
?>

<?php if ($hayPendientes): ?>
<section aria-labelledby="titulo-atender">
  <h2 id="titulo-atender" class="dashboard__titulo">Por atender</h2>
  <div class="dashboard__kpis">
    <?php if (isset($totalCotizacionesNuevas)): ?>
      <a class="admin-panel kpi kpi--enlace<?= $totalCotizacionesNuevas > 0 ? ' kpi--alerta' : '' ?>" href="/admin/cotizaciones">
        <span class="kpi__etiqueta">Cotizaciones nuevas</span>
        <span class="kpi__valor"><?= (int) $totalCotizacionesNuevas ?></span>
        <span class="kpi__pie">Sin contactar todavía</span>
      </a>
      <a class="admin-panel kpi kpi--enlace<?= (int) $seguimientosVencidos > 0 ? ' kpi--alerta' : '' ?>" href="/admin/cotizaciones?seguimiento=vencidos">
        <span class="kpi__etiqueta">Seguimientos vencidos</span>
        <span class="kpi__valor"><?= (int) $seguimientosVencidos ?></span>
        <span class="kpi__pie">Fecha de contacto ya pasó</span>
      </a>
    <?php endif; ?>
    <?php if (isset($totalReservasPendientes)): ?>
      <a class="admin-panel kpi kpi--enlace<?= $totalReservasPendientes > 0 ? ' kpi--alerta' : '' ?>" href="/admin/reservas?estado=pendiente">
        <span class="kpi__etiqueta">Reservas pendientes</span>
        <span class="kpi__valor"><?= (int) $totalReservasPendientes ?></span>
        <span class="kpi__pie">Esperando el anticipo</span>
      </a>
      <a class="admin-panel kpi kpi--enlace" href="/admin/reservas?estado=confirmada&amp;salida=proximas">
        <span class="kpi__etiqueta">Saldos por cobrar</span>
        <span class="kpi__valor"><?= $dinero($saldosPorCobrar['monto']) ?></span>
        <span class="kpi__pie"><?= (int) $saldosPorCobrar['reservas'] ?> <?= $saldosPorCobrar['reservas'] === 1 ? 'reserva' : 'reservas' ?> con salida próxima</span>
      </a>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<section aria-labelledby="titulo-periodo">
  <div class="dashboard__cabecera">
    <h2 id="titulo-periodo" class="dashboard__titulo">Resultados del periodo</h2>
    <form method="get" action="/admin" class="dashboard__periodo">
      <div class="dashboard__atajos" role="group" aria-label="Periodo">
        <?php foreach ($atajos as $etiqueta => [$d, $h]): ?>
          <a href="/admin?desde=<?= $d ?>&amp;hasta=<?= $h ?>" class="chip-periodo<?= $rangoActivo === $etiqueta ? ' is-activo' : '' ?>"<?= $rangoActivo === $etiqueta ? ' aria-current="true"' : '' ?>><?= htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8') ?></a>
        <?php endforeach; ?>
      </div>
      <details class="dashboard__rango"<?= $rangoActivo === null ? ' open' : '' ?>>
        <summary>Otro rango</summary>
        <div class="dashboard__rango-campos">
          <label for="desde" class="sr-only">Desde</label>
          <input type="date" id="desde" name="desde" value="<?= htmlspecialchars($periodoDesde) ?>">
          <span aria-hidden="true">–</span>
          <label for="hasta" class="sr-only">Hasta</label>
          <input type="date" id="hasta" name="hasta" value="<?= htmlspecialchars($periodoHasta) ?>">
          <button type="submit" class="btn btn-secundario">Aplicar</button>
        </div>
      </details>
    </form>
  </div>
  <p class="dashboard__rango-texto"><?= \App\Helpers\Fecha::corta($periodoDesde) ?> – <?= \App\Helpers\Fecha::corta($periodoHasta) ?></p>

  <div class="dashboard__kpis">
    <?php if (isset($cobradoPeriodo)): ?>
      <div class="admin-panel kpi">
        <span class="kpi__etiqueta">Cobrado</span>
        <span class="kpi__valor"><?= $dinero($cobradoPeriodo) ?></span>
        <?= $variacion($cobradoPeriodo, $cobradoAnterior) ?>
        <span class="kpi__pie">Anticipos, abonos y pagos manuales recibidos</span>
      </div>
      <div class="admin-panel kpi">
        <span class="kpi__etiqueta">Ventas confirmadas</span>
        <span class="kpi__valor"><?= $dinero($ingresosPeriodo) ?></span>
        <?= $variacion($ingresosPeriodo, $ingresosAnterior) ?>
        <span class="kpi__pie">Valor total de las reservas confirmadas</span>
      </div>
      <div class="admin-panel kpi">
        <span class="kpi__etiqueta">Reservas confirmadas</span>
        <span class="kpi__valor"><?= (int) $confirmadasPeriodo ?></span>
        <?= $variacion((float) $confirmadasPeriodo, (float) $confirmadasAnterior) ?>
      </div>
    <?php endif; ?>
    <?php if (isset($conversion)): ?>
      <div class="admin-panel kpi">
        <span class="kpi__etiqueta">Conversión de cotizaciones</span>
        <span class="kpi__valor"><?= number_format($conversion['tasa'], 1) ?>%</span>
        <?= $variacion((float) $conversion['tasa'], (float) $conversionAnterior['tasa']) ?>
        <span class="kpi__pie"><?= (int) $conversion['convertidas'] ?> de <?= (int) $conversion['total'] ?> cotizaciones recibidas</span>
      </div>
    <?php endif; ?>
  </div>
</section>

<div class="dashboard__columnas">
<?php if (isset($proximasSalidas)): ?>
<div class="admin-panel">
  <h2 class="mt-0">Ocupación de próximas salidas</h2>
  <?php if ($proximasSalidas === []): ?>
    <p class="op-70">No hay salidas futuras registradas.</p>
  <?php else: ?>
  <div class="admin-tabla-wrap">
    <table class="admin-tabla admin-tabla--compacta">
      <thead>
        <tr>
          <th>Paquete</th>
          <th>Salida</th>
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

<?php if (isset($cotizacionesPorOrigen)): ?>
<div class="admin-panel">
  <h2 class="mt-0">Cotizaciones por origen</h2>
  <?php if ($cotizacionesPorOrigen === []): ?>
    <p class="op-70">No hay cotizaciones en el periodo seleccionado.</p>
  <?php else: ?>
  <div class="admin-tabla-wrap">
    <table class="admin-tabla admin-tabla--compacta">
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
</div>

<div class="admin-panel">
  <h2 class="mt-0">Accesos rápidos</h2>
  <p class="op-70">Catálogo: <?= (int) $totalPaquetesPublicados ?> de <?= (int) $totalPaquetes ?> paquetes publicados.</p>
  <div class="admin-acciones">
    <?php if (\Core\Auth::hasPermission('reservas.crear')): ?><a href="/admin/reservas/crear" class="btn btn-primario">Nueva reserva</a><?php endif; ?>
    <?php if (\Core\Auth::hasPermission('paquetes.crear')): ?><a href="/admin/paquetes/crear" class="btn btn-secundario">Nuevo paquete</a><?php endif; ?>
    <?php if (\Core\Auth::hasPermission('paquetes.ver')): ?><a href="/admin/paquetes" class="btn btn-secundario">Ver paquetes</a><?php endif; ?>
  </div>
</div>
