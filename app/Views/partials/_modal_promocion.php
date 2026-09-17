<?php
/**
 * Modal promocional del sitio publico. $modalPromo lo arma el layout con
 * \App\Models\ModalPromocion::vigente(); si no hay ninguno vigente no se imprime nada.
 * La visibilidad real (mostrar una vez por sesion, cerrar con Esc / clic fuera) la maneja
 * initModalPromocion() en /assets/js/site.js.
 *
 * @var array|false|null $modalPromo
 */
if (empty($modalPromo)) {
    return;
}
?>
<div class="modal-promo" data-modal-promo data-modal-id="<?= (int) $modalPromo['id'] ?>"
     role="dialog" aria-modal="true" aria-labelledby="modal-promo-titulo" hidden>
  <div class="modal-promo__caja">
    <button type="button" class="modal-promo__cerrar" data-modal-promo-cerrar aria-label="Cerrar">&times;</button>

    <?php if (!empty($modalPromo['imagen'])): ?>
      <img class="modal-promo__img" src="<?= htmlspecialchars($modalPromo['imagen'], ENT_QUOTES, 'UTF-8') ?>" alt="">
    <?php endif; ?>

    <h2 id="modal-promo-titulo" class="modal-promo__titulo"><?= htmlspecialchars($modalPromo['titulo'], ENT_QUOTES, 'UTF-8') ?></h2>

    <?php if (!empty($modalPromo['cuerpo'])): ?>
      <p class="modal-promo__texto"><?= htmlspecialchars($modalPromo['cuerpo'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <a class="btn btn-primario" href="/paquetes/<?= htmlspecialchars($modalPromo['paquete_slug'], ENT_QUOTES, 'UTF-8') ?>" data-modal-promo-cta>
      <?= htmlspecialchars($modalPromo['texto_boton'], ENT_QUOTES, 'UTF-8') ?>
    </a>
  </div>
</div>
