<?php
/** @var array $paquete */
/** @var array $imagenes */
/** @var array $salidas */
/** @var array $resenas */
/** @var array{promedio: float, total: int} $resumen */
use App\Helpers\Fecha;
use App\Helpers\Rating;
use App\Models\ConfiguracionSitio;
use App\Models\Resena;
use App\Services\WhatsAppLinkService;

$resumen ??= ['promedio' => 0.0, 'total' => 0];

$whatsapp = (new WhatsAppLinkService())->generarLinkCotizacionPaquete(
    ConfiguracionSitio::get('whatsapp_numero', ''),
    $paquete['titulo']
);
?>
<section class="hero hero--compacto">
  <div class="contenedor">
    <p class="hero__categoria">
      <a class="enlace-plano" href="/destinos/<?= htmlspecialchars($paquete['categoria_slug'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($paquete['categoria_nombre'], ENT_QUOTES, 'UTF-8') ?></a>
    </p>
    <h1><?= htmlspecialchars($paquete['titulo'], ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= htmlspecialchars($paquete['resumen'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
    <?php if ($resumen['total'] > 0): ?>
      <p class="hero__rating">
        <a class="enlace-plano" href="#resenas">
          <span class="rating__estrellas" aria-hidden="true"><?= Rating::estrellas((float) $resumen['promedio']) ?></span>
          <strong><?= number_format($resumen['promedio'], 1) ?></strong>
          <span class="rating__meta">(<?= (int) $resumen['total'] ?> <?= $resumen['total'] === 1 ? 'reseña' : 'reseñas' ?>)</span>
        </a>
      </p>
    <?php endif; ?>
  </div>
</section>

<div class="contenedor mt-1">
  <?php
  $migas = [
      ['texto' => 'Inicio', 'url' => '/'],
      ['texto' => $paquete['categoria_nombre'], 'url' => '/destinos/' . $paquete['categoria_slug']],
      ['texto' => $paquete['titulo']],
  ];
  require __DIR__ . '/../../partials/_breadcrumbs.php';
  ?>
</div>

<section class="seccion contenedor paquete-detalle">
  <div>
    <?php if (!empty($imagenes)): ?>
      <?php $imgPrincipal = $imagenes[0]; ?>
      <figure class="galeria" data-galeria>
        <button type="button" class="galeria__abrir" data-galeria-abrir aria-label="Ampliar imagen">
          <img
            class="galeria__principal"
            data-galeria-principal
            src="<?= htmlspecialchars($imgPrincipal['ruta_original'], ENT_QUOTES, 'UTF-8') ?>"
            alt="<?= htmlspecialchars($imgPrincipal['alt_text'] ?? $paquete['titulo'], ENT_QUOTES, 'UTF-8') ?>"
            loading="lazy"
          >
        </button>
        <?php if (count($imagenes) > 1): ?>
          <div class="galeria__tiras">
            <?php foreach ($imagenes as $i => $img): ?>
              <button
                type="button"
                class="galeria__tira<?= $i === 0 ? ' is-activa' : '' ?>"
                data-galeria-tira
                data-full="<?= htmlspecialchars($img['ruta_original'], ENT_QUOTES, 'UTF-8') ?>"
                data-alt="<?= htmlspecialchars($img['alt_text'] ?? $paquete['titulo'], ENT_QUOTES, 'UTF-8') ?>"
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
        <?php if (count($imagenes) > 1): ?>
          <button type="button" class="lightbox__nav lightbox__nav--prev" data-lightbox-prev aria-label="Imagen anterior">&#8249;</button>
          <button type="button" class="lightbox__nav lightbox__nav--next" data-lightbox-next aria-label="Imagen siguiente">&#8250;</button>
        <?php endif; ?>
        <img class="lightbox__img" data-lightbox-img src="" alt="">
      </div>
    <?php endif; ?>

    <h2>Itinerario</h2>
    <div><?= $paquete['itinerario'] ?? '<p>Itinerario disponible pronto.</p>' ?></div>

    <div class="paquete-incluye">
      <div>
        <h3>Incluye</h3>
        <?= $paquete['incluye'] ?? '<p>-</p>' ?>
      </div>
      <div>
        <h3>No incluye</h3>
        <?= $paquete['no_incluye'] ?? '<p>-</p>' ?>
      </div>
    </div>
  </div>

  <aside class="tarjeta paquete-aside">
    <span class="precio precio--grande">
      Desde $<?= number_format((float) $paquete['precio_desde'], 0, '.', ',') ?> <?= htmlspecialchars($paquete['moneda'], ENT_QUOTES, 'UTF-8') ?>
    </span>
    <p class="paquete-aside__sub"><?= (int) $paquete['duracion_dias'] ?> días / <?= (int) $paquete['duracion_noches'] ?> noches</p>

    <label class="tarjeta__comparar tarjeta__comparar--bloque">
      <input type="checkbox" data-comparar-slug="<?= htmlspecialchars($paquete['slug'], ENT_QUOTES, 'UTF-8') ?>" data-comparar-titulo="<?= htmlspecialchars($paquete['titulo'], ENT_QUOTES, 'UTF-8') ?>">
      Agregar a comparar
    </label>

    <h3>Próximas salidas</h3>
    <?php if (empty($salidas)): ?>
      <p>Consulta disponibilidad con nuestro equipo.</p>
    <?php else: ?>
      <ul class="lista-salidas">
        <?php foreach ($salidas as $salida): ?>
          <li>
            <span><?= htmlspecialchars(Fecha::corta($salida['fecha_salida']), ENT_QUOTES, 'UTF-8') ?></span>
            <span class="lista-salidas__cupo"><?= (int) $salida['cupo_disponible'] ?> cupos</span>
            <?php if ($salida['estado'] === 'abierta' && (int) $salida['cupo_disponible'] > 0): ?>
              <a href="/paquetes/<?= urlencode($paquete['slug']) ?>/reservar/<?= (int) $salida['id'] ?>" class="btn btn-primario btn--chico">Reservar</a>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <div class="acciones-apiladas">
      <a href="/cotizador?paquete=<?= urlencode($paquete['slug']) ?>" class="btn btn-primario btn--bloque">Solicitar cotización</a>
      <a href="<?= htmlspecialchars($whatsapp, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-whatsapp btn--bloque" target="_blank" rel="noopener">Cotizar por WhatsApp</a>
    </div>
  </aside>
</section>

<?php if (!empty($resenas)): ?>
<section class="seccion contenedor" id="resenas">
  <div class="seccion__encabezado">
    <h2>Lo que dicen nuestros viajeros</h2>
    <?php if ($resumen['total'] > 0): ?>
      <p class="texto-suave"><?= number_format($resumen['promedio'], 1) ?> / 5 &middot; <?= (int) $resumen['total'] ?> <?= $resumen['total'] === 1 ? 'reseña aprobada' : 'reseñas aprobadas' ?></p>
    <?php endif; ?>
  </div>
  <div class="grid-tarjetas grid-tarjetas--3">
    <?php foreach ($resenas as $r): ?>
      <?php
        $nombrePublico = Resena::nombrePublico((string) $r['cliente_nombre']);
        $inicial = mb_strtoupper(mb_substr($nombrePublico, 0, 1));
      ?>
      <figure class="tarjeta-testimonio animar-entrada">
        <span class="tarjeta-testimonio__avatar" aria-hidden="true"><?= htmlspecialchars($inicial, ENT_QUOTES, 'UTF-8') ?></span>
        <p class="rating-linea"><?= Rating::estrellas((float) $r['calificacion']) ?></p>
        <blockquote>&quot;<?= htmlspecialchars($r['comentario'], ENT_QUOTES, 'UTF-8') ?>&quot;</blockquote>
        <figcaption><?= htmlspecialchars($nombrePublico, ENT_QUOTES, 'UTF-8') ?></figcaption>
      </figure>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($relacionados)): ?>
<section class="seccion contenedor seccion--panel">
  <div class="seccion__encabezado">
    <h2>También te puede interesar</h2>
    <p>Otros paquetes de <?= htmlspecialchars($paquete['categoria_nombre'], ENT_QUOTES, 'UTF-8') ?>.</p>
  </div>
  <div class="grid-tarjetas grid-tarjetas--3">
    <?php
    $paqueteActual = $paquete;
    $resumenes = $resumenesRelacionados;
    foreach ($relacionados as $paquete) { // _tarjeta.php lee $paquete
        require __DIR__ . '/_tarjeta.php';
    }
    $paquete = $paqueteActual;
    ?>
  </div>
</section>
<?php endif; ?>
