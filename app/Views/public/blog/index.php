<?php
/** @var array $articulos */
/** @var \Core\Paginator $paginador */
/** @var array $categorias */
/** @var string|null $categoriaActiva */
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

  <?php if (!empty($categorias)): ?>
    <nav class="blog-categorias" aria-label="Filtrar articulos por categoria">
      <a href="/blog"<?= $categoriaActiva === null ? ' class="is-activa" aria-current="true"' : '' ?>>Todos</a>
      <?php foreach ($categorias as $cat): ?>
        <a href="/blog?categoria=<?= urlencode($cat['slug']) ?>"<?= $categoriaActiva === $cat['slug'] ? ' class="is-activa" aria-current="true"' : '' ?>><?= htmlspecialchars($cat['nombre'], ENT_QUOTES, 'UTF-8') ?></a>
      <?php endforeach; ?>
    </nav>
  <?php endif; ?>

  <?php if (empty($articulos)): ?>
    <?php
    $titulo = 'Sin articulos';
    $texto = $categoriaActiva !== null
        ? 'Todavia no hay articulos publicados en esta categoria.'
        : 'Todavia no publicamos articulos. Vuelve pronto.';
    $cta = $categoriaActiva !== null ? ['url' => '/blog', 'texto' => 'Ver todos los articulos'] : null;
    require __DIR__ . '/../../partials/_estado_vacio.php';
    ?>
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

    <?php
    $rutaBase = '/blog';
    $queryExtra = $categoriaActiva !== null ? ['categoria' => $categoriaActiva] : [];
    require __DIR__ . '/../../partials/paginacion.php';
    ?>
  <?php endif; ?>

  <p class="blog-rss">
    <a href="/blog/feed">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M4 11a9 9 0 0 1 9 9h2A11 11 0 0 0 4 9v2Zm0 4a5 5 0 0 1 5 5h2a7 7 0 0 0-7-7v2Zm1.5 5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z"/></svg>
      Suscribete por RSS
    </a>
  </p>
</section>
