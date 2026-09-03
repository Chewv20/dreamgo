<?php
/** @var string $whatsapp */
/** @var string $email */
/** @var array|null $intro */
/** @var array $errores */
/** @var array $valores */
$errores ??= [];
$valores ??= [];
$introVisible = $intro && (int) $intro['visible'] === 1;
?>
<section class="seccion contenedor bloque-medio">
  <h1><?= htmlspecialchars($introVisible && !empty($intro['titulo']) ? $intro['titulo'] : 'Contacto', ENT_QUOTES, 'UTF-8') ?></h1>
  <?php if ($introVisible && !empty($intro['subtitulo'])): ?>
    <p><?= htmlspecialchars($intro['subtitulo'], ENT_QUOTES, 'UTF-8') ?></p>
  <?php else: ?>
    <p>Escríbenos y un asesor te contactará. Si prefieres, también puedes usar cualquiera de los canales de abajo.</p>
  <?php endif; ?>

  <form method="post" action="/contacto" data-atribucion>
    <?= \App\Helpers\Csrf::field() ?>

    <div class="campo">
      <label for="nombre">Nombre completo</label>
      <input type="text" id="nombre" name="nombre" autocomplete="name" required value="<?= htmlspecialchars($valores['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <?php if (!empty($errores['nombre'])): ?><small class="campo__error"><?= htmlspecialchars($errores['nombre'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
    </div>

    <div class="campo">
      <label for="email">Correo electrónico</label>
      <input type="email" id="email" name="email" autocomplete="email" required value="<?= htmlspecialchars($valores['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <?php if (!empty($errores['email'])): ?><small class="campo__error"><?= htmlspecialchars($errores['email'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
    </div>

    <div class="campo">
      <label for="telefono">Teléfono / WhatsApp</label>
      <input type="tel" id="telefono" name="telefono" autocomplete="tel" required value="<?= htmlspecialchars($valores['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <?php if (!empty($errores['telefono'])): ?><small class="campo__error"><?= htmlspecialchars($errores['telefono'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
    </div>

    <div class="campo">
      <label for="mensaje">Mensaje</label>
      <textarea id="mensaje" name="mensaje" required><?= htmlspecialchars($valores['mensaje'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
      <?php if (!empty($errores['mensaje'])): ?><small class="campo__error"><?= htmlspecialchars($errores['mensaje'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primario btn--bloque">Enviar mensaje</button>
  </form>

  <div class="grid-tarjetas mt-25">
    <a href="<?= htmlspecialchars($whatsapp, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="tarjeta tarjeta-contacto animar-entrada">
      <h3>WhatsApp</h3>
      <p class="m-0">Respuesta rápida para dudas y cotizaciones.</p>
    </a>
    <a href="mailto:<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" class="tarjeta tarjeta-contacto animar-entrada">
      <h3>Correo</h3>
      <p class="m-0 wrap-anywhere"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></p>
    </a>
    <a href="/cotizador" class="tarjeta tarjeta-contacto animar-entrada">
      <h3>Cotizador</h3>
      <p class="m-0">Cuéntanos los detalles de tu viaje ideal.</p>
    </a>
  </div>
</section>
