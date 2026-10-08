<?php $enPanel = isset($adminNombre); ?>
<div class="<?= $enPanel ? 'admin-panel' : 'contenedor seccion centrado' ?>">
  <?php if ($enPanel): ?>
    <p>La sección que buscas no existe o fue movida.</p>
    <a href="/admin" class="btn btn-primario">Volver al panel</a>
  <?php else: ?>
    <h1>404 &mdash; Página no encontrada</h1>
    <p>La página que buscas no existe o fue movida.</p>
    <a href="/" class="btn btn-primario">Volver al inicio</a>
  <?php endif; ?>
</div>
