<?php /** @var array $reserva */ ?>
<h2 style="margin-top:0;color:#2f3e46;">Tu viaje se acerca</h2>
<p>Hola <?= htmlspecialchars($reserva['cliente_nombre'], ENT_QUOTES, 'UTF-8') ?>, este es un recordatorio de que tu viaje <strong><?= htmlspecialchars($reserva['paquete_titulo'], ENT_QUOTES, 'UTF-8') ?></strong> está cerca.</p>
<table role="presentation" width="100%" cellpadding="6" style="border-collapse:collapse;">
  <tr><td style="font-weight:bold;width:160px;">Código de reserva</td><td><?= htmlspecialchars($reserva['codigo_reserva'], ENT_QUOTES, 'UTF-8') ?></td></tr>
  <tr><td style="font-weight:bold;">Fecha de salida</td><td><?= date('d/m/Y', strtotime($reserva['fecha_salida'])) ?></td></tr>
  <tr><td style="font-weight:bold;">Personas</td><td><?= (int) $reserva['num_personas'] ?></td></tr>
</table>
<p>Prepara tu equipaje y documentos. ¡Nos vemos muy pronto!</p>
