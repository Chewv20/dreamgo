<?php $enPanel = isset($adminNombre); ?>
<div class="<?= $enPanel ? 'admin-panel' : 'contenedor seccion centrado' ?>">
  <?php if (!$enPanel): ?><h1>403 &mdash; Acceso no autorizado</h1><?php endif; ?>
  <p><?= htmlspecialchars(($message ?? '') ?: 'No tienes permiso para ver esta página.', ENT_QUOTES, 'UTF-8') ?></p>
  <?php if ($enPanel): ?>
    <a href="/admin" class="btn btn-primario">Volver al panel</a>
  <?php else: ?>
    <a href="/" class="btn btn-primario">Volver al inicio</a>
  <?php endif; ?>
</div>
