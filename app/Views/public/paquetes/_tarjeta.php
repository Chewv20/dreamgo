<?php
/** @var array $paquete */
/** @var array<int, array{promedio: float, total: int}> $resumenes */
/** @var array<int, list<array{ruta_thumb: string}>> $galerias */
$resumenPaquete = isset($resumenes) ? ($resumenes[$paquete['id']] ?? null) : null;
$galeriaPaquete = isset($galerias) ? ($galerias[$paquete['id']] ?? []) : [];
?>
<article class="tarjeta animar-entrada">
  <a href="/paquetes/<?= htmlspecialchars($paquete['slug'], ENT_QUOTES, 'UTF-8') ?>">
    <?php
    $portada = $paquete['imagen_portada'] ?? '/assets/img/logo.avif';
    $extra = array_slice($galeriaPaquete, 1);
    $alt = $paquete['titulo'];
    $variante = 'paquete';
    $width = 480;
    $height = 320;
    require __DIR__ . '/../../partials/_tarjeta_media.php';
    ?>
  </a>
  <div class="tarjeta__cuerpo">
    <p class="etiqueta-categoria">
      <?= htmlspecialchars($paquete['categoria_nombre'], ENT_QUOTES, 'UTF-8') ?>
    </p>
    <h3><a class="enlace-plano" href="/paquetes/<?= htmlspecialchars($paquete['slug'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($paquete['titulo'], ENT_QUOTES, 'UTF-8') ?></a></h3>
    <?php if ($resumenPaquete !== null && $resumenPaquete['total'] > 0): ?>
      <p class="rating">
        <span class="rating__estrellas" aria-hidden="true"><?= \App\Helpers\Rating::estrellas((float) $resumenPaquete['promedio']) ?></span>
        <span><?= number_format($resumenPaquete['promedio'], 1) ?> &middot; <?= (int) $resumenPaquete['total'] ?> <?= $resumenPaquete['total'] === 1 ? 'reseña' : 'reseñas' ?></span>
      </p>
    <?php endif; ?>
    <p><?= htmlspecialchars($paquete['resumen'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
    <div class="tarjeta-paquete__pie">
      <span class="precio">
        Desde $<?= number_format((float) $paquete['precio_desde'], 0, '.', ',') ?> <?= htmlspecialchars($paquete['moneda'], ENT_QUOTES, 'UTF-8') ?>
      </span>
      <span class="tarjeta-paquete__duracion">
        <?= (int) $paquete['duracion_dias'] ?>d / <?= (int) $paquete['duracion_noches'] ?>n
      </span>
    </div>
    <label class="tarjeta__comparar">
      <input type="checkbox" data-comparar-slug="<?= htmlspecialchars($paquete['slug'], ENT_QUOTES, 'UTF-8') ?>" data-comparar-titulo="<?= htmlspecialchars($paquete['titulo'], ENT_QUOTES, 'UTF-8') ?>">
      Comparar
    </label>
  </div>
</article>
