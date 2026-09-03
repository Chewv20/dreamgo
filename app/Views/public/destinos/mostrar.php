<?php
/** @var array $categoria */
/** @var array $paquetes */
/** @var array $articulos */
$articulos ??= [];
?>
<section class="seccion contenedor">
  <?php
  $migas = [
      ['texto' => 'Inicio', 'url' => '/'],
      ['texto' => 'Destinos', 'url' => '/destinos'],
      ['texto' => $categoria['nombre']],
  ];
  require __DIR__ . '/../../partials/_breadcrumbs.php';
  ?>
  <p class="etiqueta-categoria">
    <?= $categoria['tipo'] === 'internacional' ? 'Internacional' : 'Nacional' ?>
  </p>
  <h1><?= htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8') ?></h1>
  <?php if (!empty($categoria['imagen_portada'])): ?>
    <img class="destino-portada" src="<?= htmlspecialchars($categoria['imagen_portada'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8') ?>" width="1200" height="675">
  <?php endif; ?>
  <p class="bloque-medio"><?= htmlspecialchars($categoria['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>

  <?php if (empty($paquetes)): ?>
    <?php
    $titulo = 'Próximamente';
    $texto = 'Aún no hay paquetes publicados en este destino. Vuelve pronto o pide una cotización a medida.';
    $cta = ['url' => '/cotizador', 'texto' => 'Solicitar cotización'];
    require __DIR__ . '/../../partials/_estado_vacio.php';
    ?>
  <?php else: ?>
    <div class="grid-tarjetas mt-2">
      <?php foreach ($paquetes as $paquete): ?>
        <?php require __DIR__ . '/../paquetes/_tarjeta.php'; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php if (!empty($articulos)): ?>
<section class="seccion contenedor">
  <div class="seccion__encabezado"><h2>Artículos sobre <?= htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8') ?></h2></div>
  <div class="grid-tarjetas grid-tarjetas--3">
    <?php foreach ($articulos as $a): ?>
      <article class="tarjeta animar-entrada">
        <a href="/blog/<?= htmlspecialchars($a['slug'], ENT_QUOTES, 'UTF-8') ?>">
          <img class="tarjeta__img" src="<?= htmlspecialchars($a['imagen'] ?? '/assets/img/logo.avif', ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($a['titulo'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" width="480" height="320">
        </a>
        <div class="tarjeta__cuerpo">
          <h3><a class="enlace-plano" href="/blog/<?= htmlspecialchars($a['slug'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($a['titulo'], ENT_QUOTES, 'UTF-8') ?></a></h3>
          <p><?= htmlspecialchars($a['resumen'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
