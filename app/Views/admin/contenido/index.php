<?php /** @var array $paginas */ ?>
<div class="admin-acciones" style="margin-bottom:1.25rem;">
  <a href="/admin/colores" class="btn btn-secundario">Colores del sitio</a>
</div>

<div class="admin-panel">
  <p style="opacity:0.75;margin-top:0;">
    Elige una pagina para editar sus textos, orden y colores. Los cambios se ven de inmediato en el sitio.
  </p>

  <div class="grid-tarjetas grid-tarjetas--3">
    <?php foreach ($paginas as $slug => $nombre): ?>
      <a href="/admin/contenido/<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>" class="tarjeta" style="display:block;padding:1.5rem;">
        <h3 style="margin-bottom:0;"><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></h3>
      </a>
    <?php endforeach; ?>
  </div>
</div>
