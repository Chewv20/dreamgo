<?php
/**
 * Relleno de la imagen de una tarjeta cuando el destino/paquete aún no tiene foto: mantiene
 * la misma estructura y alto que las tarjetas con imagen, para que la rejilla no quede
 * dispareja (unas tarjetas con foto y otras sólo con texto estiradas al alto de la fila).
 *
 * @var 'paquete'|'destino' $variante
 */
?>
<span class="tarjeta__media tarjeta__media--<?= $variante ?> tarjeta__media--vacia" aria-hidden="true">
  <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/></svg>
</span>
