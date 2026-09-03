<?php /** @var array $paquete */ /** @var array $categorias */ /** @var array $imagenes */ ?>
<div class="admin-panel">
  <form method="post" action="/admin/paquetes/<?= (int) $paquete['id'] ?>/editar" enctype="multipart/form-data">
    <?php require __DIR__ . '/_form.php'; ?>
  </form>
</div>

<?php if (!empty($paquete['imagen_portada'])): ?>
<div class="admin-panel">
  <h2 class="mt-0">Portada actual</h2>
  <img src="<?= htmlspecialchars($paquete['imagen_portada'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="img-preview">
</div>
<?php endif; ?>

<div class="admin-panel">
  <div class="admin-acciones">
    <a href="/admin/paquetes/<?= (int) $paquete['id'] ?>/salidas" class="btn btn-secundario">Gestionar fechas y cupos</a>
    <?php if (\Core\Auth::hasPermission('paquetes.eliminar')): ?>
      <form method="post" action="/admin/paquetes/<?= (int) $paquete['id'] ?>/archivar" data-confirm="¿Archivar este paquete? Dejará de mostrarse en el sitio público.">
        <?= \App\Helpers\Csrf::field() ?>
        <button type="submit" class="btn btn-secundario">Archivar paquete</button>
      </form>
    <?php endif; ?>
  </div>
</div>
