<?php
/** @var array $categoria */
/** @var array $paquetes */
/** @var array $articulos */
/** @var array $imagenesDestino */
/** @var array<int, list<array{ruta_thumb: string}>> $galerias */
$articulos ??= [];
$imagenesDestino ??= [];
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

  <?php if (!empty($imagenesDestino)): ?>
    <?php $imgPrincipal = $imagenesDestino[0]; ?>
    <figure class="galeria" data-galeria>
      <button type="button" class="galeria__abrir" data-galeria-abrir aria-label="Ampliar imagen">
        <img
          class="galeria__principal"
          data-galeria-principal
          src="<?= htmlspecialchars($imgPrincipal['ruta_original'], ENT_QUOTES, 'UTF-8') ?>"
          alt="<?= htmlspecialchars($imgPrincipal['alt_text'] ?? $categoria['nombre'], ENT_QUOTES, 'UTF-8') ?>"
          loading="lazy"
        >
      </button>
      <?php if (count($imagenesDestino) > 1): ?>
        <div class="galeria__tiras">
          <?php foreach ($imagenesDestino as $i => $img): ?>
            <button
              type="button"
              class="galeria__tira<?= $i === 0 ? ' is-activa' : '' ?>"
              data-galeria-tira
              data-full="<?= htmlspecialchars($img['ruta_original'], ENT_QUOTES, 'UTF-8') ?>"
              data-alt="<?= htmlspecialchars($img['alt_text'] ?? $categoria['nombre'], ENT_QUOTES, 'UTF-8') ?>"
              aria-label="Ver imagen <?= $i + 1 ?>"
            >
              <img src="<?= htmlspecialchars($img['ruta_thumb'], ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy">
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </figure>

    <div class="lightbox" data-lightbox hidden role="dialog" aria-modal="true" aria-label="Imagen ampliada">
      <button type="button" class="lightbox__cerrar" data-lightbox-cerrar aria-label="Cerrar">&times;</button>
      <?php if (count($imagenesDestino) > 1): ?>
        <button type="button" class="lightbox__nav lightbox__nav--prev" data-lightbox-prev aria-label="Imagen anterior">&#8249;</button>
        <button type="button" class="lightbox__nav lightbox__nav--next" data-lightbox-next aria-label="Imagen siguiente">&#8250;</button>
      <?php endif; ?>
      <img class="lightbox__img" data-lightbox-img src="" alt="">
    </div>
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
