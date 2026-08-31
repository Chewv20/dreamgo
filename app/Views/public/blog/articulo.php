<?php
/** @var array $articulo */
/** @var array $relacionados */
$a = $articulo;
$e = static fn (?string $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$fechaPub = $a['publicado_en'] ?? $a['creado_en'];
?>
<section class="hero hero--mini">
  <div class="contenedor">
    <?php if (!empty($a['categoria_nombre'])): ?>
      <p class="hero__categoria">
        <a class="enlace-plano" href="/destinos/<?= $e($a['categoria_slug']) ?>"><?= $e($a['categoria_nombre']) ?></a>
      </p>
    <?php endif; ?>
    <h1><?= $e($a['titulo']) ?></h1>
    <p class="texto-suave"><?= $e(\App\Helpers\Fecha::larga((string) $fechaPub)) ?></p>
  </div>
</section>

<article class="seccion contenedor articulo-cuerpo">
  <?php
  $migas = [
      ['texto' => 'Inicio', 'url' => '/'],
      ['texto' => 'Blog', 'url' => '/blog'],
      ['texto' => $a['titulo']],
  ];
  require __DIR__ . '/../../partials/_breadcrumbs.php';
  ?>
  <?php if (!empty($a['imagen'])): ?>
    <img class="articulo-cuerpo__portada" src="<?= $e($a['imagen']) ?>" alt="<?= $e($a['titulo']) ?>" loading="lazy">
  <?php endif; ?>

  <?php if (!empty($a['resumen'])): ?>
    <p class="articulo-cuerpo__resumen"><?= $e($a['resumen']) ?></p>
  <?php endif; ?>

  <div class="contenido-articulo">
    <?= $a['contenido'] ?? '' ?>
  </div>

  <?php if (!empty($a['categoria_nombre'])): ?>
    <p class="articulo-cuerpo__cta">
      <a href="/destinos/<?= $e($a['categoria_slug']) ?>" class="btn btn-primario">Ver paquetes de <?= $e($a['categoria_nombre']) ?></a>
    </p>
  <?php endif; ?>

  <p class="mt-15"><a href="/blog">&laquo; Volver al blog</a></p>
</article>

<?php if (!empty($relacionados)): ?>
<section class="seccion contenedor">
  <div class="seccion__encabezado"><h2>Mas articulos</h2></div>
  <div class="grid-tarjetas grid-tarjetas--3">
    <?php foreach ($relacionados as $r): ?>
      <article class="tarjeta animar-entrada">
        <a href="/blog/<?= $e($r['slug']) ?>">
          <img class="tarjeta__img" src="<?= $e($r['imagen'] ?? '/assets/img/logo.avif') ?>" alt="<?= $e($r['titulo']) ?>" loading="lazy" width="480" height="320">
        </a>
        <div class="tarjeta__cuerpo">
          <h3><a class="enlace-plano" href="/blog/<?= $e($r['slug']) ?>"><?= $e($r['titulo']) ?></a></h3>
          <p><?= $e($r['resumen'] ?? '') ?></p>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
