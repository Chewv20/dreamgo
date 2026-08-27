<?php
/** @var array $paquete */
/** @var array<int, array{promedio: float, total: int}> $resumenes */
$resumenPaquete = isset($resumenes) ? ($resumenes[$paquete['id']] ?? null) : null;
?>
<article class="tarjeta animar-entrada">
  <a href="/paquetes/<?= htmlspecialchars($paquete['slug'], ENT_QUOTES, 'UTF-8') ?>">
    <img
      src="<?= htmlspecialchars($paquete['imagen_portada'] ?? '/assets/img/logo.avif', ENT_QUOTES, 'UTF-8') ?>"
      alt="<?= htmlspecialchars($paquete['titulo'], ENT_QUOTES, 'UTF-8') ?>"
      loading="lazy"
      width="480"
      height="320"
      style="width:100%;height:auto;aspect-ratio:3/2;object-fit:cover;"
    >
  </a>
  <div style="padding:1.25rem;">
    <p style="color:var(--color-primario-oscuro);font-weight:600;font-size:0.85rem;text-transform:uppercase;letter-spacing:0.04em;margin-bottom:0.3rem;">
      <?= htmlspecialchars($paquete['categoria_nombre'], ENT_QUOTES, 'UTF-8') ?>
    </p>
    <h3><a href="/paquetes/<?= htmlspecialchars($paquete['slug'], ENT_QUOTES, 'UTF-8') ?>" style="color:inherit;"><?= htmlspecialchars($paquete['titulo'], ENT_QUOTES, 'UTF-8') ?></a></h3>
    <?php if ($resumenPaquete !== null && $resumenPaquete['total'] > 0): ?>
      <p style="margin:0.15rem 0 0.4rem;font-size:0.9rem;color:var(--color-texto-oscuro);">
        <span style="color:#a85f4d;letter-spacing:0.08em;" aria-hidden="true"><?= str_repeat('★', (int) round($resumenPaquete['promedio'])) . str_repeat('☆', 5 - (int) round($resumenPaquete['promedio'])) ?></span>
        <span><?= number_format($resumenPaquete['promedio'], 1) ?> &middot; <?= (int) $resumenPaquete['total'] ?> <?= $resumenPaquete['total'] === 1 ? 'reseña' : 'reseñas' ?></span>
      </p>
    <?php endif; ?>
    <p><?= htmlspecialchars($paquete['resumen'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:1rem;">
      <span style="font-family:var(--fuente-titulos);font-size:1.25rem;color:var(--color-texto-oscuro);">
        Desde $<?= number_format((float) $paquete['precio_desde'], 0, '.', ',') ?> <?= htmlspecialchars($paquete['moneda'], ENT_QUOTES, 'UTF-8') ?>
      </span>
      <span style="font-size:0.9rem;color:var(--color-texto-oscuro);opacity:0.7;">
        <?= (int) $paquete['duracion_dias'] ?>d / <?= (int) $paquete['duracion_noches'] ?>n
      </span>
    </div>
    <label class="tarjeta__comparar">
      <input type="checkbox" data-comparar-slug="<?= htmlspecialchars($paquete['slug'], ENT_QUOTES, 'UTF-8') ?>" data-comparar-titulo="<?= htmlspecialchars($paquete['titulo'], ENT_QUOTES, 'UTF-8') ?>">
      Comparar
    </label>
  </div>
</article>
