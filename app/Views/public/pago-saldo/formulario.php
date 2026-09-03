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
<section class="seccion contenedor bloque-medio">
  <h1>Pagar saldo</h1>
  <p><strong><?= htmlspecialchars($reserva['paquete_titulo'], ENT_QUOTES, 'UTF-8') ?></strong></p>

  <div class="panel-resultado">
    <p><strong>Código:</strong> <?= htmlspecialchars($reserva['codigo_reserva'], ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Fecha de salida:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($reserva['fecha_salida'])), ENT_QUOTES, 'UTF-8') ?></p>
    <p><strong>Precio total:</strong> $<?= $fmt($total) ?> <?= $moneda ?></p>
    <p><strong>Pagado:</strong> $<?= $fmt($pagado) ?> <?= $moneda ?></p>
    <p class="fs-lg"><strong>Saldo pendiente:</strong> $<?= $fmt($saldo) ?> <?= $moneda ?></p>
  </div>

  <?php if ($error): ?>
    <p class="mt-15 txt-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php if ($pagable): ?>
    <form method="post" action="/reserva/<?= rawurlencode((string) $reserva['codigo_reserva']) ?>/pagar-saldo" class="mt-15">
      <?= \App\Helpers\Csrf::field() ?>
      <input type="hidden" name="t" value="<?= htmlspecialchars((string) $reserva['token_publico'], ENT_QUOTES, 'UTF-8') ?>">
      <button type="submit" class="btn btn-primario w-100">Pagar $<?= $fmt($saldo) ?> <?= $moneda ?> en línea</button>
    </form>
    <p class="nota-form">Te llevaremos a Mercado Pago para completar el pago de forma segura.</p>
  <?php elseif ($reserva['estado'] !== 'confirmada'): ?>
    <p class="mt-15">Esta reserva todavía no está confirmada, así que aún no hay un saldo por pagar. Te avisaremos por correo en cuanto se confirme.</p>
  <?php else: ?>
    <p class="mt-15">Esta reserva no tiene saldo pendiente. ¡Todo listo!</p>
  <?php endif; ?>

  <p class="mt-15">¿Dudas? Escríbenos desde <a href="/contacto">nuestra página de contacto</a>.</p>
</section>
