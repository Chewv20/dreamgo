<?php
/**
 * Imagen de una tarjeta (destino o paquete) en los listados publicos. Si hay mas de una
 * imagen en la galeria, se agregan los frames extra para que site.js (initCarruselesHover)
 * los cicle automaticamente al pasar el cursor por encima.
 *
 * @var string $portada URL de la imagen principal (ya con fallback resuelto por el llamador)
 * @var list<array{ruta_thumb: string}> $extra imagenes adicionales de la galeria (sin la portada)
 * @var string $alt
 * @var 'paquete'|'destino' $variante define aspect-ratio/clase visual
 * @var int $width
 * @var int $height
 */
$extra ??= [];
?>
<span class="tarjeta__media tarjeta__media--<?= $variante ?>"<?= $extra !== [] ? ' data-carrusel-hover' : '' ?>>
  <img
    class="tarjeta__media-img is-activa"
    src="<?= htmlspecialchars($portada, ENT_QUOTES, 'UTF-8') ?>"
    alt="<?= htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') ?>"
    loading="lazy"
    width="<?= (int) $width ?>"
    height="<?= (int) $height ?>"
  >
  <?php foreach ($extra as $img): ?>
    <img class="tarjeta__media-img" src="<?= htmlspecialchars($img['ruta_thumb'], ENT_QUOTES, 'UTF-8') ?>" alt="" aria-hidden="true" loading="lazy">
  <?php endforeach; ?>
</span>
