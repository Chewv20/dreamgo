<?php /** @var array $categoria */ /** @var array $paquetes */ ?>
<section class="seccion contenedor">
  <p style="font-size:0.85rem;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-primario-oscuro);font-weight:600;">
    <?= $categoria['tipo'] === 'internacional' ? 'Internacional' : 'Nacional' ?>
  </p>
  <h1><?= htmlspecialchars($categoria['nombre'], ENT_QUOTES, 'UTF-8') ?></h1>
  <p style="max-width:640px;"><?= htmlspecialchars($categoria['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>

  <?php if (empty($paquetes)): ?>
    <p>Aun no hay paquetes publicados en este destino. Vuelve pronto.</p>
  <?php else: ?>
    <div class="grid-tarjetas" style="margin-top:2rem;">
      <?php foreach ($paquetes as $paquete): ?>
        <?php require __DIR__ . '/../paquetes/_tarjeta.php'; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
