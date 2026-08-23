<?php /** @var array $destacados */ /** @var array $categorias */ ?>
<section class="hero">
  <div class="contenedor">
    <div class="hero__contenido animar-entrada">
      <h1>Vive el viaje que sueñas, con la tranquilidad de un equipo experto.</h1>
      <p>Excursiones y paquetes a los mejores destinos de Mexico y el mundo, con acompañamiento real de principio a fin.</p>
      <a href="/cotizador" class="btn btn-primario">Cotiza tu viaje</a>
    </div>
  </div>
</section>

<?php if (!empty($categorias)): ?>
<section class="seccion contenedor">
  <h2>Explora por destino</h2>
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
<?php endif; ?>

<?php if (!empty($destacados)): ?>
<section class="seccion contenedor" style="background:var(--color-fondo-alterno);border-radius:var(--radio);">
  <h2>Paquetes destacados</h2>
  <div class="grid-tarjetas">
    <?php foreach ($destacados as $paquete): ?>
      <?php require __DIR__ . '/../paquetes/_tarjeta.php'; ?>
    <?php endforeach; ?>
  </div>
  <div style="text-align:center;margin-top:2rem;">
    <a href="/paquetes" class="btn btn-secundario">Ver todos los paquetes</a>
  </div>
</section>
<?php endif; ?>

<section class="seccion contenedor" style="text-align:center;">
  <h2>Confianza de principio a fin</h2>
  <p style="max-width:640px;margin-inline:auto;">Acompañamos cada viaje con asesoria personalizada, itinerarios claros y un equipo disponible antes, durante y despues de tu excursion.</p>
  <a href="/cotizador" class="btn btn-primario">Solicita tu cotizacion</a>
</section>
