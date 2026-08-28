<?php
/** @var array $articulo */
/** @var array $relacionados */
$a = $articulo;
$e = static fn (?string $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$fechaPub = $a['publicado_en'] ?? $a['creado_en'];
?>
<section class="hero" style="min-height:32vh;">
  <div class="contenedor">
    <?php if (!empty($a['categoria_nombre'])): ?>
      <p style="text-transform:uppercase;letter-spacing:0.04em;font-weight:600;opacity:0.9;">
        <a href="/destinos/<?= $e($a['categoria_slug']) ?>" style="color:inherit;"><?= $e($a['categoria_nombre']) ?></a>
      </p>
    <?php endif; ?>
    <h1><?= $e($a['titulo']) ?></h1>
    <p style="opacity:0.9;"><?= $e(date('d \d\e F, Y', strtotime((string) $fechaPub))) ?></p>
  </div>
</section>

<article class="seccion contenedor" style="max-width:760px;">
  <?php if (!empty($a['imagen'])): ?>
    <img src="<?= $e($a['imagen']) ?>" alt="<?= $e($a['titulo']) ?>" style="width:100%;height:auto;border-radius:var(--radio);margin-bottom:2rem;" loading="lazy">
  <?php endif; ?>

  <?php if (!empty($a['resumen'])): ?>
    <p style="font-size:1.15rem;opacity:0.85;"><?= $e($a['resumen']) ?></p>
  <?php endif; ?>

  <div class="contenido-articulo">
    <?= $a['contenido'] ?? '' ?>
  </div>

  <?php if (!empty($a['categoria_nombre'])): ?>
    <p style="margin-top:2.5rem;">
      <a href="/destinos/<?= $e($a['categoria_slug']) ?>" class="btn btn-primario">Ver paquetes de <?= $e($a['categoria_nombre']) ?></a>
    </p>
  <?php endif; ?>

  <p style="margin-top:1.5rem;"><a href="/blog">&laquo; Volver al blog</a></p>
</article>

<?php if (!empty($relacionados)): ?>
<section class="seccion contenedor">
  <div class="seccion__encabezado"><h2>Mas articulos</h2></div>
  <div class="grid-tarjetas grid-tarjetas--3">
    <?php foreach ($relacionados as $r): ?>
      <article class="tarjeta animar-entrada">
        <a href="/blog/<?= $e($r['slug']) ?>">
          <img src="<?= $e($r['imagen'] ?? '/assets/img/logo.avif') ?>" alt="<?= $e($r['titulo']) ?>" loading="lazy" width="480" height="320" style="width:100%;height:auto;aspect-ratio:3/2;object-fit:cover;">
        </a>
        <div style="padding:1.25rem;">
          <h3><a href="/blog/<?= $e($r['slug']) ?>" style="color:inherit;"><?= $e($r['titulo']) ?></a></h3>
          <p><?= $e($r['resumen'] ?? '') ?></p>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
