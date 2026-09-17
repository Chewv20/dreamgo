<?php
/** @var string $whatsapp */
/** @var string $email */
/** @var array|null $intro */
/** @var array $errores */
/** @var array $valores */
$errores ??= [];
$valores ??= [];
$introVisible = $intro && (int) $intro['visible'] === 1;

$iconosContacto = [
    'whatsapp' => '<svg viewBox="0 0 24 24" width="26" height="26" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.5 15.2L2 22l4.9-1.5A10 10 0 1 0 12 2Zm5.2 14.2c-.2.6-1.2 1.2-1.7 1.3-.4.1-1 .1-1.6-.1-.4-.1-.9-.3-1.5-.6-2.7-1.2-4.5-3.9-4.6-4.1-.1-.2-1.1-1.5-1.1-2.8 0-1.3.7-2 1-2.2.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.4.2.5.7 1.7.8 1.8.1.2.1.4 0 .6-.1.2-.2.4-.4.6-.2.2-.4.4-.2.8.2.4.9 1.5 2 2.4 1.4 1.2 2.4 1.5 2.8 1.7.4.2.6.1.8-.1.2-.2.9-1 1.1-1.4.2-.4.4-.3.7-.2.3.1 1.8.9 2.1 1 .3.2.5.2.6.3.1.2.1.7-.1 1.3Z"/></svg>',
    'correo' => '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><polyline points="4 7 12 13 20 7"/></svg>',
    'cotizador' => '<svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="13" y2="17"/></svg>',
];
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
    <a href="<?= htmlspecialchars($whatsapp, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="tarjeta tarjeta-contacto animar-entrada" aria-label="WhatsApp">
      <span class="tarjeta-contacto__icono" aria-hidden="true"><?= $iconosContacto['whatsapp'] ?></span>
      <p class="m-0">Respuesta rápida para dudas y cotizaciones.</p>
    </a>
    <a href="mailto:<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" class="tarjeta tarjeta-contacto animar-entrada" aria-label="Correo electrónico">
      <span class="tarjeta-contacto__icono" aria-hidden="true"><?= $iconosContacto['correo'] ?></span>
      <p class="m-0 wrap-anywhere"><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></p>
    </a>
    <a href="/cotizador" class="tarjeta tarjeta-contacto animar-entrada" aria-label="Cotizador">
      <span class="tarjeta-contacto__icono" aria-hidden="true"><?= $iconosContacto['cotizador'] ?></span>
      <p class="m-0">Cuéntanos los detalles de tu viaje ideal.</p>
    </a>
  </div>
</section>
