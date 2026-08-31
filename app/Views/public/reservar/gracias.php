<?php
/** @var string $codigo */
/** @var string|null $mensaje */
/** @var bool $esAprobado */
$esAprobado ??= false;
?>
<section class="seccion contenedor bloque-medio centrado">
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
<?php if ($esAprobado && \App\Helpers\Analytics::habilitado()): ?>
<script nonce="<?= htmlspecialchars(CSP_NONCE, ENT_QUOTES, 'UTF-8') ?>">
  if (typeof gtag === 'function') { gtag('event', 'purchase', { transaction_id: <?= json_encode($codigo, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?> }); }
  if (typeof fbq === 'function') { fbq('track', 'Purchase'); }
</script>
<?php endif; ?>
