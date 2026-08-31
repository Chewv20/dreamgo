<?php
/** @var array $reserva */
/** @var array $pagos */
$pagos ??= [];
$moneda = htmlspecialchars((string) $reserva['paquete_moneda'], ENT_QUOTES, 'UTF-8');
$saldo = max(0, (float) $reserva['precio_total'] - (float) $reserva['monto_pagado']);
$conceptos = ['anticipo' => 'Anticipo', 'saldo' => 'Saldo', 'otro' => 'Otro'];
?>
<div class="admin-panel admin-panel--560">
  <p><span class="admin-badge admin-badge--<?= ['pendiente' => 'ambar', 'confirmada' => 'verde', 'cancelada' => 'gris', 'expirada' => 'rojo'][$reserva['estado']] ?>"><?= ucfirst($reserva['estado']) ?></span></p>
  <table class="admin-tabla admin-tabla--detalle">
    <tr><td>Paquete</td><td><?= htmlspecialchars($reserva['paquete_titulo'], ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr><td>Fecha de salida</td><td><?= \App\Helpers\Fecha::corta($reserva['fecha_salida']) ?></td></tr>
    <tr><td>Cliente</td><td><?= htmlspecialchars($reserva['cliente_nombre'], ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr><td>Correo</td><td><?= htmlspecialchars($reserva['cliente_email'], ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr><td>Telefono</td><td><?= htmlspecialchars($reserva['cliente_telefono'], ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr><td>Personas</td><td><?= (int) $reserva['num_personas'] ?></td></tr>
    <tr><td>Total</td><td>$<?= number_format((float) $reserva['precio_total'], 2) ?> <?= $moneda ?></td></tr>
    <tr><td>Pagado</td><td>$<?= number_format((float) $reserva['monto_pagado'], 2) ?> <?= $moneda ?></td></tr>
    <tr><td>Saldo</td><td>$<?= number_format($saldo, 2) ?> <?= $moneda ?></td></tr>
    <?php if ($reserva['expira_en'] && $reserva['estado'] === 'pendiente'): ?>
      <tr><td>Expira</td><td><?= \App\Helpers\Fecha::cortaHora($reserva['expira_en']) ?></td></tr>
    <?php endif; ?>
  </table>

  <?php if ($pagos): ?>
    <h3 class="admin-subtitulo">Pagos registrados</h3>
    <table class="admin-tabla admin-tabla--compacta">
      <thead><tr><th>Fecha</th><th>Concepto</th><th>Monto</th><th>Referencia</th></tr></thead>
      <tbody>
        <?php foreach ($pagos as $pago): ?>
          <tr>
            <td><?= \App\Helpers\Fecha::cortaHora($pago['creado_en']) ?></td>
            <td><?= htmlspecialchars($conceptos[$pago['concepto']] ?? $pago['concepto'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>$<?= number_format((float) $pago['monto'], 2) ?> <?= $moneda ?></td>
            <td><?= htmlspecialchars((string) $pago['referencia_pago'], ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <div class="admin-acciones admin-mt">
    <?php if ($reserva['estado'] === 'confirmada'): ?>
      <a class="btn btn-secundario" href="/admin/reservas/<?= (int) $reserva['id'] ?>/comprobante">Descargar comprobante</a>
    <?php endif; ?>
    <?php if ($reserva['estado'] === 'pendiente' && \Core\Auth::hasPermission('reservas.confirmar')): ?>
      <form method="post" action="/admin/reservas/<?= (int) $reserva['id'] ?>/confirmar">
        <?= \App\Helpers\Csrf::field() ?>
        <button type="submit" class="btn btn-primario">Confirmar reserva</button>
      </form>
    <?php endif; ?>
    <?php if (in_array($reserva['estado'], ['pendiente', 'confirmada'], true) && \Core\Auth::hasPermission('reservas.cancelar')): ?>
      <form method="post" action="/admin/reservas/<?= (int) $reserva['id'] ?>/cancelar" data-confirm="¿Cancelar esta reserva y liberar el cupo?">
        <?= \App\Helpers\Csrf::field() ?>
        <button type="submit" class="btn btn-secundario">Cancelar reserva</button>
      </form>
    <?php endif; ?>
  </div>
</div>
