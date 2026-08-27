<?php
/** @var array $reserva */
/** @var array $pagos */
$pagos ??= [];
$moneda = htmlspecialchars((string) $reserva['paquete_moneda'], ENT_QUOTES, 'UTF-8');
$saldo = max(0, (float) $reserva['precio_total'] - (float) $reserva['monto_pagado']);
$conceptos = ['anticipo' => 'Anticipo', 'saldo' => 'Saldo', 'otro' => 'Otro'];
?>
<div class="admin-panel" style="max-width:560px;">
  <p><span class="admin-badge admin-badge--<?= ['pendiente' => 'ambar', 'confirmada' => 'verde', 'cancelada' => 'gris', 'expirada' => 'rojo'][$reserva['estado']] ?>"><?= ucfirst($reserva['estado']) ?></span></p>
  <table class="admin-tabla" style="min-width:0;">
    <tr><td style="font-weight:bold;">Paquete</td><td><?= htmlspecialchars($reserva['paquete_titulo'], ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr><td style="font-weight:bold;">Fecha de salida</td><td><?= date('d M Y', strtotime($reserva['fecha_salida'])) ?></td></tr>
    <tr><td style="font-weight:bold;">Cliente</td><td><?= htmlspecialchars($reserva['cliente_nombre'], ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr><td style="font-weight:bold;">Correo</td><td><?= htmlspecialchars($reserva['cliente_email'], ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr><td style="font-weight:bold;">Telefono</td><td><?= htmlspecialchars($reserva['cliente_telefono'], ENT_QUOTES, 'UTF-8') ?></td></tr>
    <tr><td style="font-weight:bold;">Personas</td><td><?= (int) $reserva['num_personas'] ?></td></tr>
    <tr><td style="font-weight:bold;">Total</td><td>$<?= number_format((float) $reserva['precio_total'], 2) ?> <?= $moneda ?></td></tr>
    <tr><td style="font-weight:bold;">Pagado</td><td>$<?= number_format((float) $reserva['monto_pagado'], 2) ?> <?= $moneda ?></td></tr>
    <tr><td style="font-weight:bold;">Saldo</td><td>$<?= number_format($saldo, 2) ?> <?= $moneda ?></td></tr>
    <?php if ($reserva['expira_en'] && $reserva['estado'] === 'pendiente'): ?>
      <tr><td style="font-weight:bold;">Expira</td><td><?= date('d M Y H:i', strtotime($reserva['expira_en'])) ?></td></tr>
    <?php endif; ?>
  </table>

  <?php if ($pagos): ?>
    <h3 style="margin:1.5rem 0 .5rem;">Pagos registrados</h3>
    <table class="admin-tabla" style="min-width:0;">
      <thead><tr><th>Fecha</th><th>Concepto</th><th>Monto</th><th>Referencia</th></tr></thead>
      <tbody>
        <?php foreach ($pagos as $pago): ?>
          <tr>
            <td><?= date('d M Y H:i', strtotime($pago['creado_en'])) ?></td>
            <td><?= htmlspecialchars($conceptos[$pago['concepto']] ?? $pago['concepto'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>$<?= number_format((float) $pago['monto'], 2) ?> <?= $moneda ?></td>
            <td><?= htmlspecialchars((string) $pago['referencia_pago'], ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <div class="admin-acciones" style="margin-top:1.5rem;">
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
