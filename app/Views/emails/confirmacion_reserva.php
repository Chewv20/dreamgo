<?php
/** @var array $reserva */
/** @var string $urlComprobante */
$urlComprobante ??= '';
?>
<h2 style="margin-top:0;color:#2f3e46;">¡Tu reserva está confirmada!</h2>
<p>Hola <?= htmlspecialchars($reserva['cliente_nombre'], ENT_QUOTES, 'UTF-8') ?>, tu lugar para <strong><?= htmlspecialchars($reserva['paquete_titulo'], ENT_QUOTES, 'UTF-8') ?></strong> ha sido confirmado.</p>
<table role="presentation" width="100%" cellpadding="6" style="border-collapse:collapse;">
  <tr><td style="font-weight:bold;width:160px;">Código de reserva</td><td><?= htmlspecialchars($reserva['codigo_reserva'], ENT_QUOTES, 'UTF-8') ?></td></tr>
  <tr><td style="font-weight:bold;">Fecha de salida</td><td><?= date('d/m/Y', strtotime($reserva['fecha_salida'])) ?></td></tr>
  <tr><td style="font-weight:bold;">Personas</td><td><?= (int) $reserva['num_personas'] ?></td></tr>
  <tr><td style="font-weight:bold;">Total</td><td>$<?= number_format((float) $reserva['precio_total'], 2) ?> <?= htmlspecialchars($reserva['paquete_moneda'], ENT_QUOTES, 'UTF-8') ?></td></tr>
</table>
<p>Adjuntamos tu comprobante de reserva en PDF<?php if ($urlComprobante !== ''): ?>. También puedes
<a href="<?= htmlspecialchars($urlComprobante, ENT_QUOTES, 'UTF-8') ?>" style="color:#a85f4d;">descargarlo desde aquí</a><?php endif; ?>.</p>
<p>Nos vemos pronto. Cualquier duda, contáctanos y con gusto te ayudamos.</p>
