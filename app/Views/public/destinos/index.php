<?php /** @var array $categorias */ ?>
<section class="seccion contenedor">
  <h1>Nuestros destinos</h1>
  <p>Elige entre experiencias nacionales e internacionales disenadas por nuestro equipo.</p>
  <div class="grid-tarjetas">
    <?php foreach ($categorias as $categoria): ?>
      <a href="/destinos/<?= htmlspecialchars($categoria['slug'], ENT_QUOTES, 'UTF-8') ?>" class="tarjeta animar-entrada" style="display:block;padding:1.5rem;text-decoration:none;">
        <p style="font-size:0.8rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-primario-oscuro);font-weight:600;">
          <?= $categoria['tipo'] === 'internacional' ? 'Internacional' : 'Nacional' ?>
        </p>
        <h3 style="margin-bottom:0.25rem;"><?= htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8') ?></h3>
        <p style="margin:0;color:var(--color-texto-oscuro);opacity:0.8;"><?= htmlspecialchars($categoria['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
      </a>
    <?php endforeach; ?>
  </div>
</section>
