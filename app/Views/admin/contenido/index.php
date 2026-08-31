<?php /** @var array $paginas */ ?>
<div class="admin-acciones mb-md">
  <a href="/admin/colores" class="btn btn-secundario">Colores del sitio</a>
</div>

<div class="admin-panel">
  <p class="op-75 mt-0">
    Elige una pagina para editar sus textos, orden y colores. Los cambios se ven de inmediato en el sitio.
  </p>

  <div class="grid-tarjetas grid-tarjetas--3">
    <?php foreach ($paginas as $slug => $nombre): ?>
      <a href="/admin/contenido/<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>" class="tarjeta tarjeta-panel">
        <h3 class="mb-0"><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></h3>
      </a>
    <?php endforeach; ?>
  </div>
</div>
