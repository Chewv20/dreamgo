<?php
/** @var array $paquete */
/** @var array $salida */
/** @var float $precioUnitario */
/** @var int $porcentajeAnticipo */
/** @var array $errores */
/** @var array $valores */
$errores ??= [];
$valores ??= [];
$numPersonasVista = (int) ($valores['num_personas'] ?? 1);
$numPersonasVista = $numPersonasVista > 0 ? $numPersonasVista : 1;
$totalEstimado = $precioUnitario * $numPersonasVista;
$anticipoEstimado = round($totalEstimado * $porcentajeAnticipo / 100, 2);
?>
<section class="seccion contenedor" style="max-width:640px;">
  <h1>Reservar</h1>
  <p style="background:var(--color-fondo-alterno);padding:0.75rem 1rem;border-radius:var(--radio);">
    <strong><?= htmlspecialchars($paquete['titulo'], ENT_QUOTES, 'UTF-8') ?></strong><br>
    Salida: <?= htmlspecialchars(date('d/m/Y', strtotime($salida['fecha_salida'])), ENT_QUOTES, 'UTF-8') ?>
    &middot; <?= (int) $salida['cupo_disponible'] ?> cupos disponibles
    &middot; $<?= number_format($precioUnitario, 2, '.', ',') ?> <?= htmlspecialchars($paquete['moneda'], ENT_QUOTES, 'UTF-8') ?> por persona
  </p>

  <p>Se cobra un anticipo del <strong><?= $porcentajeAnticipo ?>%</strong> del total para confirmar tu lugar (estimado con
    <?= $numPersonasVista ?> persona(s): $<?= number_format($anticipoEstimado, 2, '.', ',') ?> <?= htmlspecialchars($paquete['moneda'], ENT_QUOTES, 'UTF-8') ?>). El resto se liquida
    directamente con nuestro equipo antes del viaje.</p>

  <?php if (!empty($errores['general'])): ?>
    <p style="color:var(--color-error);"><?= htmlspecialchars($errores['general'], ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <form method="post" action="/reservar" data-atribucion>
    <?= \App\Helpers\Csrf::field() ?>
    <input type="hidden" name="salida_id" value="<?= (int) $salida['id'] ?>">

    <div class="campo">
      <label for="nombre">Nombre completo</label>
      <input type="text" id="nombre" name="nombre" required value="<?= htmlspecialchars($valores['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <?php if (!empty($errores['nombre'])): ?><small style="color:var(--color-error);"><?= htmlspecialchars($errores['nombre'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
    </div>

    <div class="campo">
      <label for="email">Correo electronico</label>
      <input type="email" id="email" name="email" required value="<?= htmlspecialchars($valores['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <?php if (!empty($errores['email'])): ?><small style="color:var(--color-error);"><?= htmlspecialchars($errores['email'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
    </div>

    <div class="campo">
      <label for="telefono">Telefono / WhatsApp</label>
      <input type="tel" id="telefono" name="telefono" required value="<?= htmlspecialchars($valores['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <?php if (!empty($errores['telefono'])): ?><small style="color:var(--color-error);"><?= htmlspecialchars($errores['telefono'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
    </div>

    <div class="campo">
      <label for="num_personas">Numero de personas</label>
      <input type="number" id="num_personas" name="num_personas" min="1" max="<?= (int) $salida['cupo_disponible'] ?>" required value="<?= htmlspecialchars((string) $numPersonasVista, ENT_QUOTES, 'UTF-8') ?>">
      <?php if (!empty($errores['num_personas'])): ?><small style="color:var(--color-error);"><?= htmlspecialchars($errores['num_personas'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
    </div>

    <div class="campo">
      <label for="codigo_descuento">Codigo de descuento (opcional)</label>
      <input type="text" id="codigo_descuento" name="codigo_descuento" value="<?= htmlspecialchars($valores['codigo_descuento'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <button type="submit" class="btn btn-primario" style="width:100%;">Continuar al pago del anticipo</button>
  </form>
</section>
