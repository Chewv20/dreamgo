<?php
/** @var array|null $paquete */
/** @var array $errores */
/** @var array $valores */
$errores ??= [];
$valores ??= [];
?>
<section class="seccion contenedor" style="max-width:640px;">
  <h1>Cotiza tu viaje</h1>
  <p>Cuentanos que estas buscando y nuestro equipo te enviara una propuesta personalizada.</p>
  <?php if ($paquete): ?>
    <p style="background:var(--color-fondo-alterno);padding:0.75rem 1rem;border-radius:var(--radio);">
      Estas cotizando: <strong><?= htmlspecialchars($paquete['titulo'], ENT_QUOTES, 'UTF-8') ?></strong>
    </p>
  <?php endif; ?>

  <form method="post" action="/cotizador">
    <?= \App\Helpers\Csrf::field() ?>
    <input type="hidden" name="paquete_slug" value="<?= htmlspecialchars($paquete['slug'] ?? ($valores['paquete_slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

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
      <input type="number" id="num_personas" name="num_personas" min="1" value="<?= htmlspecialchars($valores['num_personas'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="campo">
      <label for="fecha_tentativa">Fecha tentativa</label>
      <input type="date" id="fecha_tentativa" name="fecha_tentativa" value="<?= htmlspecialchars($valores['fecha_tentativa'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="campo">
      <label for="mensaje">Cuentanos mas sobre tu viaje (opcional)</label>
      <textarea id="mensaje" name="mensaje"><?= htmlspecialchars($valores['mensaje'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
    </div>

    <button type="submit" class="btn btn-primario" style="width:100%;">Enviar solicitud</button>
  </form>
</section>
