<?php
/** @var array $articulos */
/** @var \Core\Paginator $paginador */
$fecha = static fn (?string $v): string => \App\Helpers\Fecha::corta($v);
?>
<section class="hero hero--mini">
  <div class="contenedor">
    <h1>Blog de viajes</h1>
    <p>Guias, consejos e inspiracion para planear tu proxima escapada.</p>
  </div>
</section>

<section class="seccion contenedor">
  <?php $migas = [['texto' => 'Inicio', 'url' => '/'], ['texto' => 'Blog']]; require __DIR__ . '/../../partials/_breadcrumbs.php'; ?>
  <?php if (empty($articulos)): ?>
    <p>Todavia no publicamos articulos. Vuelve pronto.</p>
  <?php else: ?>
    <div class="grid-tarjetas">
      <?php foreach ($articulos as $a): ?>
        <article class="tarjeta animar-entrada">
          <a href="/blog/<?= htmlspecialchars($a['slug'], ENT_QUOTES, 'UTF-8') ?>">
            <img
              class="tarjeta__img"
              src="<?= htmlspecialchars($a['imagen'] ?? '/assets/img/logo.avif', ENT_QUOTES, 'UTF-8') ?>"
              alt="<?= htmlspecialchars($a['titulo'], ENT_QUOTES, 'UTF-8') ?>"
              loading="lazy" width="480" height="320">
          </a>
          <div class="tarjeta__cuerpo">
            <?php if (!empty($a['categoria_nombre'])): ?>
              <p class="etiqueta-categoria">
                <?= htmlspecialchars($a['categoria_nombre'], ENT_QUOTES, 'UTF-8') ?>
              </p>
            <?php endif; ?>
            <h3><a class="enlace-plano" href="/blog/<?= htmlspecialchars($a['slug'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($a['titulo'], ENT_QUOTES, 'UTF-8') ?></a></h3>
            <p><?= htmlspecialchars($a['resumen'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <p class="tarjeta__fecha"><?= $fecha($a['publicado_en'] ?? $a['creado_en']) ?></p>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <?php $rutaBase = '/blog'; require __DIR__ . '/../../partials/paginacion.php'; ?>
  <?php endif; ?>
</section>
