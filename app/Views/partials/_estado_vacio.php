<?php
/**
 * Estado vacio con diseño (icono + mensaje + CTA opcional).
 * @var string $titulo
 * @var string $texto
 * @var array{url: string, texto: string}|null $cta
 */
$cta ??= null;
?>
<div class="estado-vacio">
  <svg class="estado-vacio__icono" viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <circle cx="11" cy="11" r="7"></circle>
    <path d="m21 21-4.3-4.3"></path>
  </svg>
  <h2><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h2>
  <p><?= htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') ?></p>
  <?php if ($cta !== null): ?>
    <a href="<?= htmlspecialchars($cta['url'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primario"><?= htmlspecialchars($cta['texto'], ENT_QUOTES, 'UTF-8') ?></a>
  <?php endif; ?>
</div>
