<?php
/**
 * @var array $imagenes lista de filas de imagenes_paquete/imagenes_destino, ORDER BY orden ASC
 * @var string $galeriaBase p.ej. "/admin/paquetes/12" o "/admin/destinos/7"
 */
?>
<?php if (!empty($imagenes)): ?>
<div class="admin-panel">
  <h2 class="mt-0">Galería de imágenes</h2>
  <div class="galeria-admin">
    <?php foreach ($imagenes as $i => $img): ?>
      <div class="galeria-admin__item">
        <?php if ($i === 0): ?><span class="galeria-admin__badge">Portada</span><?php endif; ?>
        <img src="<?= htmlspecialchars($img['ruta_thumb'], ENT_QUOTES, 'UTF-8') ?>" alt="">
        <div class="galeria-admin__acciones">
          <form method="post" action="<?= $galeriaBase ?>/imagenes/<?= (int) $img['id'] ?>/mover">
            <?= \App\Helpers\Csrf::field() ?>
            <input type="hidden" name="direccion" value="arriba">
            <button type="submit" class="btn btn-secundario btn--xs" <?= $i === 0 ? 'disabled' : '' ?> aria-label="Mover antes">&uarr;</button>
          </form>
          <form method="post" action="<?= $galeriaBase ?>/imagenes/<?= (int) $img['id'] ?>/mover">
            <?= \App\Helpers\Csrf::field() ?>
            <input type="hidden" name="direccion" value="abajo">
            <button type="submit" class="btn btn-secundario btn--xs" <?= $i === count($imagenes) - 1 ? 'disabled' : '' ?> aria-label="Mover después">&darr;</button>
          </form>
          <form method="post" action="<?= $galeriaBase ?>/imagenes/<?= (int) $img['id'] ?>/eliminar" data-confirm="¿Eliminar esta imagen de la galería?">
            <?= \App\Helpers\Csrf::field() ?>
            <button type="submit" class="btn btn-secundario btn--xs" aria-label="Eliminar imagen">&times;</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
