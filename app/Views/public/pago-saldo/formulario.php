<?php
/** @var array $reserva */
/** @var float $saldo */
/** @var bool $pagable */
/** @var float $abonoMinimo */
/** @var bool $permiteAbono */
/** @var string $montoAbono */
/** @var string|null $error */

$abonoMinimo ??= 0.0;
$permiteAbono ??= false;
$montoAbono ??= '';
$moneda = htmlspecialchars((string) ($reserva['paquete_moneda'] ?? ''), ENT_QUOTES, 'UTF-8');
$fmt = static fn (float $v): string => number_format($v, 2, '.', ',');
$total = (float) $reserva['precio_total'];
$pagado = (float) $reserva['monto_pagado'];
$modoAbono = $montoAbono !== '';
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
    <p class="mt-15 txt-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <?php if ($pagable): ?>
    <form method="post" action="/reserva/<?= rawurlencode((string) $reserva['codigo_reserva']) ?>/pagar-saldo" class="mt-15" data-pago-saldo>
      <?= \App\Helpers\Csrf::field() ?>
      <input type="hidden" name="t" value="<?= htmlspecialchars((string) $reserva['token_publico'], ENT_QUOTES, 'UTF-8') ?>">

      <?php if ($permiteAbono): ?>
        <fieldset class="opciones-pago">
          <legend>¿Cuánto quieres pagar?</legend>
          <label class="opcion-pago">
            <input type="radio" name="modo" value="total" <?= $modoAbono ? '' : 'checked' ?> data-pago-modo>
            <span>
              <strong>Liquidar el saldo</strong>
              <small>$<?= $fmt($saldo) ?> <?= $moneda ?></small>
            </span>
          </label>
          <label class="opcion-pago">
            <input type="radio" name="modo" value="abono" <?= $modoAbono ? 'checked' : '' ?> data-pago-modo>
            <span>
              <strong>Abonar otra cantidad</strong>
              <small>Desde $<?= $fmt($abonoMinimo) ?> <?= $moneda ?>. Puedes seguir abonando con este mismo link.</small>
            </span>
          </label>
          <div class="campo opcion-pago__monto" data-pago-monto-campo>
            <label for="monto">Monto del abono (<?= $moneda ?>)</label>
            <input type="number" id="monto" name="monto" inputmode="decimal"
                   min="<?= htmlspecialchars(number_format($abonoMinimo, 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>"
                   max="<?= htmlspecialchars(number_format($saldo, 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>"
                   step="0.01" value="<?= htmlspecialchars($montoAbono, ENT_QUOTES, 'UTF-8') ?>" data-pago-monto>
          </div>
        </fieldset>
      <?php else: ?>
        <input type="hidden" name="modo" value="total">
      <?php endif; ?>

      <button type="submit" class="btn btn-primario w-100" data-pago-boton data-saldo="<?= htmlspecialchars(number_format($saldo, 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>" data-moneda="<?= $moneda ?>">
        Pagar $<?= $fmt($saldo) ?> <?= $moneda ?> en línea
      </button>
    </form>
    <p class="nota-form">Te llevaremos a Mercado Pago para completar el pago de forma segura.</p>
  <?php elseif ($reserva['estado'] !== 'confirmada'): ?>
    <p class="mt-15">Esta reserva todavía no está confirmada, así que aún no hay un saldo por pagar. Te avisaremos por correo en cuanto se confirme.</p>
  <?php else: ?>
    <p class="mt-15">Esta reserva no tiene saldo pendiente. ¡Todo listo!</p>
  <?php endif; ?>

  <p class="mt-15">¿Dudas? Escríbenos desde <a href="/contacto">nuestra página de contacto</a>.</p>
</section>
