<?php
/** @var array $reserva */
/** @var string $urlComprobante */
$urlComprobante ??= '';
$moneda = htmlspecialchars((string) ($reserva['paquete_moneda'] ?? ''), ENT_QUOTES, 'UTF-8');
$total = (float) $reserva['precio_total'];
$pagado = (float) $reserva['monto_pagado'];
$saldo = max(0, $total - $pagado);
?>
<h2 style="margin-top:0;color:#2f3e46;">Recibimos tu pago</h2>
<p>Hola <?= htmlspecialchars($reserva['cliente_nombre'], ENT_QUOTES, 'UTF-8') ?>, registramos un pago para tu
reserva <strong><?= htmlspecialchars($reserva['codigo_reserva'], ENT_QUOTES, 'UTF-8') ?></strong>
(<?= htmlspecialchars($reserva['paquete_titulo'], ENT_QUOTES, 'UTF-8') ?>).</p>
<table role="presentation" width="100%" cellpadding="6" style="border-collapse:collapse;">
  <tr><td style="font-weight:bold;width:160px;">Precio total</td><td>$<?= number_format($total, 2) ?> <?= $moneda ?></td></tr>
  <tr><td style="font-weight:bold;">Pagado</td><td>$<?= number_format($pagado, 2) ?> <?= $moneda ?></td></tr>
  <tr><td style="font-weight:bold;">Saldo pendiente</td><td><strong>$<?= number_format($saldo, 2) ?> <?= $moneda ?></strong></td></tr>
</table>
<?php if ($saldo <= 0): ?>
  <p>Con esto tu reserva queda totalmente pagada. ¡Nos vemos pronto!</p>
<?php else: ?>
  <p>Aún queda un saldo por liquidar antes de la salida.</p>
<?php endif; ?>
<p>Adjuntamos tu comprobante actualizado en PDF<?php if ($urlComprobante !== ''): ?>. También puedes
<a href="<?= htmlspecialchars($urlComprobante, ENT_QUOTES, 'UTF-8') ?>" style="color:#a85f4d;">descargarlo desde aquí</a><?php endif; ?>.</p>
