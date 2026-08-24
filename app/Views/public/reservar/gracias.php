<?php
/** @var string $codigo */
/** @var string|null $mensaje */
?>
<section class="seccion contenedor" style="max-width:640px;text-align:center;">
  <h1>Gracias por tu reserva</h1>
  <p>Codigo de reserva: <strong><?= htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') ?></strong></p>

  <?php if ($mensaje): ?>
    <p><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
  <?php else: ?>
    <p>Registramos tu solicitud de reserva. Te avisaremos por correo en cuanto tengamos novedades.</p>
  <?php endif; ?>

  <p>Puedes consultar el estado real de tu reserva en cualquier momento en
    <a href="/mi-reserva">Mi reserva</a> con tu codigo y correo.</p>

  <a href="/" class="btn btn-primario">Volver al inicio</a>
</section>
