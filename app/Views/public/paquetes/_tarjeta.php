<?php
/** @var array $paquete */
/** @var array<int, array{promedio: float, total: int}> $resumenes */
$resumenPaquete = isset($resumenes) ? ($resumenes[$paquete['id']] ?? null) : null;
?>
<article class="tarjeta animar-entrada">
  <a href="/paquetes/<?= htmlspecialchars($paquete['slug'], ENT_QUOTES, 'UTF-8') ?>">
    <img
      class="tarjeta__img"
      src="<?= htmlspecialchars($paquete['imagen_portada'] ?? '/assets/img/logo.avif', ENT_QUOTES, 'UTF-8') ?>"
      alt="<?= htmlspecialchars($paquete['titulo'], ENT_QUOTES, 'UTF-8') ?>"
      loading="lazy"
      width="480"
      height="320"
    >
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
