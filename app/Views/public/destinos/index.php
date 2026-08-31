<?php /** @var array $categorias */ /** @var array|null $intro */ ?>
<section class="seccion contenedor">
  <?php $introVisible = $intro && (int) $intro['visible'] === 1; ?>
  <h1><?= htmlspecialchars($introVisible && !empty($intro['titulo']) ? $intro['titulo'] : 'Destinos', ENT_QUOTES, 'UTF-8') ?></h1>
  <?php if ($introVisible && !empty($intro['subtitulo'])): ?>
    <p><?= htmlspecialchars($intro['subtitulo'], ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>
  <div class="grid-tarjetas">
    <?php foreach ($categorias as $categoria): ?>
      <a href="/destinos/<?= htmlspecialchars($categoria['slug'], ENT_QUOTES, 'UTF-8') ?>" class="tarjeta tarjeta-contacto animar-entrada">
        <?php if (!empty($categoria['imagen_portada'])): ?>
          <img class="destino-portada" src="<?= htmlspecialchars($categoria['imagen_portada'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy" width="480" height="270">
        <?php endif; ?>
        <p class="etiqueta-categoria">
          <?= $categoria['tipo'] === 'internacional' ? 'Internacional' : 'Nacional' ?>
        </p>
        <h3 class="mb-0"><?= htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8') ?></h3>
        <p class="m-0 texto-suave"><?= htmlspecialchars($categoria['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
      </a>
    <?php endforeach; ?>
  </div>
</section>
