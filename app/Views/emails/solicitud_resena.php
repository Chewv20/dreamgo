<?php
/** @var array $reserva */
/** @var string $urlResena */
?>
<h2 style="margin-top:0;color:#2f3e46;">¿Que tal estuvo tu viaje?</h2>
<p>Hola <?= htmlspecialchars($reserva['cliente_nombre'], ENT_QUOTES, 'UTF-8') ?>, esperamos que hayas disfrutado <strong><?= htmlspecialchars($reserva['paquete_titulo'], ENT_QUOTES, 'UTF-8') ?></strong>. Nos encantaria conocer tu opinion.</p>
<p style="text-align:center;margin:2rem 0;">
  <a href="<?= htmlspecialchars($urlResena, ENT_QUOTES, 'UTF-8') ?>" style="background:#a85f4d;color:#ffffff;padding:0.75rem 1.5rem;border-radius:6px;text-decoration:none;font-weight:bold;display:inline-block;">Dejar mi resena</a>
</p>
<p>Tu codigo de reserva es <strong><?= htmlspecialchars($reserva['codigo_reserva'], ENT_QUOTES, 'UTF-8') ?></strong>; lo necesitaras junto con el correo con el que reservaste para dejar tu resena.</p>
