<?php
/** @var array $articulos */
/** @var \Core\Paginator $paginador */
$fecha = static fn (?string $v): string => $v ? date('d M Y', strtotime($v)) : '';
?>
<section class="hero" style="min-height:30vh;">
  <div class="contenedor">
    <h1>Blog de viajes</h1>
    <p>Guias, consejos e inspiracion para planear tu proxima escapada.</p>
  </div>
</section>

<section class="seccion contenedor">
  <?php if (empty($articulos)): ?>
    <p>Todavia no publicamos articulos. Vuelve pronto.</p>
  <?php else: ?>
    <div class="grid-tarjetas">
      <?php foreach ($articulos as $a): ?>
        <article class="tarjeta animar-entrada">
          <a href="/blog/<?= htmlspecialchars($a['slug'], ENT_QUOTES, 'UTF-8') ?>">
            <img
              src="<?= htmlspecialchars($a['imagen'] ?? '/assets/img/logo.avif', ENT_QUOTES, 'UTF-8') ?>"
              alt="<?= htmlspecialchars($a['titulo'], ENT_QUOTES, 'UTF-8') ?>"
              loading="lazy" width="480" height="320"
              style="width:100%;height:auto;aspect-ratio:3/2;object-fit:cover;">
          </a>
          <div style="padding:1.25rem;">
            <?php if (!empty($a['categoria_nombre'])): ?>
              <p style="color:var(--color-primario-oscuro);font-weight:600;font-size:0.85rem;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.3rem;">
                <?= htmlspecialchars($a['categoria_nombre'], ENT_QUOTES, 'UTF-8') ?>
              </p>
            <?php endif; ?>
            <h3><a href="/blog/<?= htmlspecialchars($a['slug'], ENT_QUOTES, 'UTF-8') ?>" style="color:inherit;"><?= htmlspecialchars($a['titulo'], ENT_QUOTES, 'UTF-8') ?></a></h3>
            <p><?= htmlspecialchars($a['resumen'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <p style="font-size:0.85rem;opacity:0.7;margin-top:0.75rem;"><?= $fecha($a['publicado_en'] ?? $a['creado_en']) ?></p>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <?php $rutaBase = '/blog'; require __DIR__ . '/../../partials/paginacion.php'; ?>
  <?php endif; ?>
</section>
