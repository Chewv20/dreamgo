<?php
/** @var bool $buscado */
/** @var array|null $reserva */
/** @var array $errores */
/** @var array $valores */
$errores ??= [];
$valores ??= [];

$etiquetasEstado = [
    'pendiente' => 'Pendiente de confirmacion',
    'confirmada' => 'Confirmada',
    'cancelada' => 'Cancelada',
    'expirada' => 'Expirada',
];
?>
<section class="seccion contenedor" style="max-width:640px;">
  <h1>Mi reserva</h1>
  <p>Ingresa el codigo de tu reserva y el correo con el que la hiciste para ver su estado.</p>

  <form method="post" action="/mi-reserva">
    <?= \App\Helpers\Csrf::field() ?>

    <div class="campo">
      <label for="codigo">Codigo de reserva</label>
      <input type="text" id="codigo" name="codigo" required placeholder="DG-2026-000123" value="<?= htmlspecialchars($valores['codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <?php if (!empty($errores['codigo'])): ?><small style="color:var(--color-error);"><?= htmlspecialchars($errores['codigo'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
    </div>

    <div class="campo">
      <label for="email">Correo con el que reservaste</label>
      <input type="email" id="email" name="email" required value="<?= htmlspecialchars($valores['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <?php if (!empty($errores['email'])): ?><small style="color:var(--color-error);"><?= htmlspecialchars($errores['email'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primario" style="width:100%;">Consultar</button>
  </form>

  <?php if ($buscado): ?>
    <?php if ($reserva): ?>
      <div class="tarjeta" style="margin-top:2rem;padding:1.25rem;border-radius:var(--radio);background:var(--color-fondo-alterno);">
        <h2 style="margin-top:0;"><?= htmlspecialchars($reserva['paquete_titulo'], ENT_QUOTES, 'UTF-8') ?></h2>
        <p><strong>Codigo:</strong> <?= htmlspecialchars($reserva['codigo_reserva'], ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Estado:</strong> <?= htmlspecialchars($etiquetasEstado[$reserva['estado']] ?? ucfirst($reserva['estado']), ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Fecha de salida:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($reserva['fecha_salida'])), ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Personas:</strong> <?= (int) $reserva['num_personas'] ?></p>
        <p><strong>Total:</strong> $<?= number_format((float) $reserva['precio_total'], 2, '.', ',') ?></p>
        <p><strong>Pagado hasta ahora:</strong> $<?= number_format((float) $reserva['monto_pagado'], 2, '.', ',') ?></p>
        <?php if ($reserva['estado'] === 'pendiente' && !empty($reserva['expira_en'])): ?>
          <p>Tienes hasta el <strong><?= htmlspecialchars(date('d/m/Y H:i', strtotime($reserva['expira_en'])), ENT_QUOTES, 'UTF-8') ?></strong> para confirmar tu pago antes de que se libere el cupo.</p>
        <?php endif; ?>
        <p>Si tienes dudas sobre tu reserva, contactanos por <a href="/contacto">nuestra pagina de contacto</a>.</p>
      </div>
    <?php else: ?>
      <p style="margin-top:2rem;color:var(--color-error);">No encontramos ninguna reserva con ese codigo y correo. Verifica los datos e intenta de nuevo.</p>
    <?php endif; ?>
  <?php endif; ?>
</section>
