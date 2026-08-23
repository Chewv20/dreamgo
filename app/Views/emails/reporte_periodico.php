<?php
/** @var string $periodo */
/** @var int $nuevasCotizaciones */
/** @var int $nuevasReservas */
/** @var int $reservasConfirmadas */
?>
<h2 style="margin-top:0;color:#2f3e46;">Reporte <?= htmlspecialchars($periodo, ENT_QUOTES, 'UTF-8') ?></h2>
<p>Resumen de actividad reciente en el sitio:</p>
<table role="presentation" width="100%" cellpadding="6" style="border-collapse:collapse;">
  <tr><td style="font-weight:bold;width:220px;">Cotizaciones nuevas</td><td><?= (int) $nuevasCotizaciones ?></td></tr>
  <tr><td style="font-weight:bold;">Reservas nuevas (pendientes)</td><td><?= (int) $nuevasReservas ?></td></tr>
  <tr><td style="font-weight:bold;">Reservas confirmadas</td><td><?= (int) $reservasConfirmadas ?></td></tr>
</table>
<p>Ingresa al panel administrativo para ver el detalle completo.</p>
