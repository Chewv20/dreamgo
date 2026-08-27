<?php
/** @var array $reserva */
/** @var string $urlPagarSaldo */
$urlPagarSaldo ??= '';
$moneda = htmlspecialchars((string) ($reserva['paquete_moneda'] ?? ''), ENT_QUOTES, 'UTF-8');
$total = (float) $reserva['precio_total'];
$pagado = (float) $reserva['monto_pagado'];
$saldo = max(0, $total - $pagado);
?>
<h2 style="margin-top:0;color:#2f3e46;">Tienes un saldo pendiente</h2>
<p>Hola <?= htmlspecialchars($reserva['cliente_nombre'], ENT_QUOTES, 'UTF-8') ?>, tu viaje
<strong><?= htmlspecialchars($reserva['paquete_titulo'], ENT_QUOTES, 'UTF-8') ?></strong> se acerca y aun
queda un saldo por liquidar.</p>
<table role="presentation" width="100%" cellpadding="6" style="border-collapse:collapse;">
  <tr><td style="font-weight:bold;width:160px;">Codigo de reserva</td><td><?= htmlspecialchars($reserva['codigo_reserva'], ENT_QUOTES, 'UTF-8') ?></td></tr>
  <tr><td style="font-weight:bold;">Fecha de salida</td><td><?= date('d/m/Y', strtotime($reserva['fecha_salida'])) ?></td></tr>
  <tr><td style="font-weight:bold;">Precio total</td><td>$<?= number_format($total, 2) ?> <?= $moneda ?></td></tr>
  <tr><td style="font-weight:bold;">Pagado</td><td>$<?= number_format($pagado, 2) ?> <?= $moneda ?></td></tr>
  <tr><td style="font-weight:bold;">Saldo pendiente</td><td><strong>$<?= number_format($saldo, 2) ?> <?= $moneda ?></strong></td></tr>
</table>
<?php if ($urlPagarSaldo !== ''): ?>
  <p style="margin:22px 0;">
    <a href="<?= htmlspecialchars($urlPagarSaldo, ENT_QUOTES, 'UTF-8') ?>"
       style="background:#a85f4d;color:#ffffff;padding:12px 22px;border-radius:6px;text-decoration:none;display:inline-block;">
      Pagar saldo en linea
    </a>
  </p>
<?php endif; ?>
<p>Si ya realizaste el pago por otro medio, ignora este mensaje. Cualquier duda, contactanos y con gusto te ayudamos.</p>
