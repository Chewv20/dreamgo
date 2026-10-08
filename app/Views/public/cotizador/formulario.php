<?php
/** @var array|null $paquete */
/** @var list<array{slug: string, titulo: string, categoria_nombre: string}> $opcionesPaquete */
/** @var array $errores */
/** @var array $valores */
$errores ??= [];
$valores ??= [];
$opcionesPaquete ??= [];
$slugElegido = (string) ($valores['paquete_slug'] ?? '');
?>
<section class="seccion contenedor bloque-medio">
  <h1>Cotiza tu viaje</h1>
  <p>Cuéntanos qué estás buscando y nuestro equipo te enviará una propuesta personalizada.</p>
  <?php if ($paquete): ?>
    <p class="aviso-caja">
      Estás cotizando: <strong><?= htmlspecialchars($paquete['titulo'], ENT_QUOTES, 'UTF-8') ?></strong>
    </p>
  <?php endif; ?>

  <form method="post" action="/cotizador" data-atribucion>
    <?= \App\Helpers\Csrf::field() ?>
    <?php if ($paquete): ?>
      <input type="hidden" name="paquete_slug" value="<?= htmlspecialchars($paquete['slug'], ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="paquete_desde_ficha" value="1">
    <?php elseif ($opcionesPaquete !== []): ?>
      <div class="campo">
        <label for="paquete_slug">Paquete de interés (opcional)</label>
        <select id="paquete_slug" name="paquete_slug">
          <option value="">Aún no lo sé / otro destino</option>
          <?php $grupoActual = null; ?>
          <?php foreach ($opcionesPaquete as $op): ?>
            <?php if ($op['categoria_nombre'] !== $grupoActual): ?>
              <?php if ($grupoActual !== null): ?></optgroup><?php endif; ?>
              <optgroup label="<?= htmlspecialchars($op['categoria_nombre'], ENT_QUOTES, 'UTF-8') ?>">
              <?php $grupoActual = $op['categoria_nombre']; ?>
            <?php endif; ?>
            <option value="<?= htmlspecialchars($op['slug'], ENT_QUOTES, 'UTF-8') ?>" <?= $slugElegido === $op['slug'] ? 'selected' : '' ?>><?= htmlspecialchars($op['titulo'], ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
          <?php if ($grupoActual !== null): ?></optgroup><?php endif; ?>
        </select>
      </div>
    <?php endif; ?>

    <div class="campo">
      <label for="nombre">Nombre completo</label>
      <input type="text" id="nombre" name="nombre" autocomplete="name" required value="<?= htmlspecialchars($valores['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <?php if (!empty($errores['nombre'])): ?><small class="campo__error"><?= htmlspecialchars($errores['nombre'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
    </div>

    <div class="campo">
      <label for="email">Correo electrónico</label>
      <input type="email" id="email" name="email" autocomplete="email" required value="<?= htmlspecialchars($valores['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <?php if (!empty($errores['email'])): ?><small class="campo__error"><?= htmlspecialchars($errores['email'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
    </div>

    <div class="campo">
      <label for="telefono">Teléfono / WhatsApp</label>
      <input type="tel" id="telefono" name="telefono" autocomplete="tel" required value="<?= htmlspecialchars($valores['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <?php if (!empty($errores['telefono'])): ?><small class="campo__error"><?= htmlspecialchars($errores['telefono'], ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?>
    </div>

    <div class="campo">
      <label for="num_personas">Número de personas</label>
      <input type="number" id="num_personas" name="num_personas" min="1" value="<?= htmlspecialchars($valores['num_personas'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="campo">
      <label for="fecha_tentativa">Fecha tentativa</label>
      <input type="date" id="fecha_tentativa" name="fecha_tentativa" value="<?= htmlspecialchars($valores['fecha_tentativa'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="campo">
      <label for="mensaje">Cuéntanos más sobre tu viaje (opcional)</label>
      <textarea id="mensaje" name="mensaje" placeholder="Destino que te interesa, presupuesto aproximado, tipo de hospedaje, si viajan niños…"><?= htmlspecialchars($valores['mensaje'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
    </div>

    <button type="submit" class="btn btn-primario btn--bloque">Enviar solicitud</button>
  </form>
</section>
