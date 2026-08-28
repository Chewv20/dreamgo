<?php
/** @var array $categoria */
/** @var array $paquetes */
/** @var array $articulos */
$articulos ??= [];
?>
<section class="seccion contenedor">
  <p style="font-size:0.85rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-primario-oscuro);font-weight:600;">
    <?= $categoria['tipo'] === 'internacional' ? 'Internacional' : 'Nacional' ?>
  </p>
  <h1><?= htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8') ?></h1>
  <p style="max-width:640px;"><?= htmlspecialchars($categoria['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>

  <?php if (empty($paquetes)): ?>
    <p>Aun no hay paquetes publicados en este destino. Vuelve pronto.</p>
  <?php else: ?>
    <div class="grid-tarjetas" style="margin-top:2rem;">
      <?php foreach ($paquetes as $paquete): ?>
        <?php require __DIR__ . '/../paquetes/_tarjeta.php'; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php if (!empty($articulos)): ?>
<section class="seccion contenedor">
  <div class="seccion__encabezado"><h2>Articulos sobre <?= htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8') ?></h2></div>
  <div class="grid-tarjetas grid-tarjetas--3">
    <?php foreach ($articulos as $a): ?>
      <article class="tarjeta animar-entrada">
        <a href="/blog/<?= htmlspecialchars($a['slug'], ENT_QUOTES, 'UTF-8') ?>">
          <img src="<?= htmlspecialchars($a['imagen'] ?? '/assets/img/logo.avif', ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($a['titulo'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" width="480" height="320" style="width:100%;height:auto;aspect-ratio:3/2;object-fit:cover;">
        </a>
        <div style="padding:1.25rem;">
          <h3><a href="/blog/<?= htmlspecialchars($a['slug'], ENT_QUOTES, 'UTF-8') ?>" style="color:inherit;"><?= htmlspecialchars($a['titulo'], ENT_QUOTES, 'UTF-8') ?></a></h3>
          <p><?= htmlspecialchars($a['resumen'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
