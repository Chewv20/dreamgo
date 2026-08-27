<?php
/** @var array $reserva */
/** @var float $saldo */
/** @var bool $pagable */
/** @var string|null $error */

$moneda = htmlspecialchars((string) ($reserva['paquete_moneda'] ?? ''), ENT_QUOTES, 'UTF-8');
$fmt = static fn (float $v): string => number_format($v, 2, '.', ',');
$total = (float) $reserva['precio_total'];
$pagado = (float) $reserva['monto_pagado'];
?>
<section class="seccion contenedor" style="max-width:640px;">
  <h1>Pagar saldo</h1>
  <p><strong><?= htmlspecialchars($reserva['paquete_titulo'], ENT_QUOTES, 'UTF-8') ?></strong></p>

  <div class="tarjeta" style="margin-top:1.5rem;padding:1.25rem;border-radius:var(--radio);background:var(--color-fondo-alterno);">
    <p><strong>Codigo:</strong> <?= htmlspecialchars($reserva['codigo_reserva'], ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Fecha de salida:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($reserva['fecha_salida'])), ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Precio total:</strong> $<?= $fmt($total) ?> <?= $moneda ?></p>
    <p><strong>Pagado:</strong> $<?= $fmt($pagado) ?> <?= $moneda ?></p>
    <p style="font-size:1.15rem;"><strong>Saldo pendiente:</strong> $<?= $fmt($saldo) ?> <?= $moneda ?></p>
  </div>

  <?php if ($error): ?>
    <p style="margin-top:1.5rem;color:var(--color-error);"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php if ($pagable): ?>
    <form method="post" action="/reserva/<?= rawurlencode((string) $reserva['codigo_reserva']) ?>/pagar-saldo" style="margin-top:1.5rem;">
      <?= \App\Helpers\Csrf::field() ?>
      <input type="hidden" name="t" value="<?= htmlspecialchars((string) $reserva['token_publico'], ENT_QUOTES, 'UTF-8') ?>">
      <button type="submit" class="btn btn-primario" style="width:100%;">Pagar $<?= $fmt($saldo) ?> <?= $moneda ?> en linea</button>
    </form>
    <p style="margin-top:1rem;font-size:.9rem;color:var(--color-texto-suave,#6b6b6b);">Te llevaremos a Mercado Pago para completar el pago de forma segura.</p>
  <?php elseif ($reserva['estado'] !== 'confirmada'): ?>
    <p style="margin-top:1.5rem;">Esta reserva todavia no esta confirmada, asi que aun no hay un saldo por pagar. Te avisaremos por correo en cuanto se confirme.</p>
  <?php else: ?>
    <p style="margin-top:1.5rem;">Esta reserva no tiene saldo pendiente. ¡Todo listo!</p>
  <?php endif; ?>

  <p style="margin-top:1.5rem;">¿Dudas? Escribenos desde <a href="/contacto">nuestra pagina de contacto</a>.</p>
</section>
