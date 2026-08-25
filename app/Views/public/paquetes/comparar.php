<?php
/** @var array $paquetes */

$aplanarTexto = static function (?string $html, int $largo = 240): string {
    $texto = trim(strip_tags((string) $html));

    return $texto === '' ? '-' : mb_strimwidth($texto, 0, $largo, '...');
};
?>
<section class="seccion contenedor">
  <h1>Comparar paquetes</h1>

  <?php if (count($paquetes) < 2): ?>
    <p>Selecciona al menos 2 paquetes desde el <a href="/paquetes">catalogo</a> para compararlos.</p>
  <?php else: ?>
    <div class="tabla-comparativa-wrap">
      <table class="tabla-comparativa">
        <thead>
          <tr>
            <th></th>
            <?php foreach ($paquetes as $p): ?>
              <th>
                <img
                  src="<?= htmlspecialchars($p['imagen_portada'] ?? '/assets/img/logo.avif', ENT_QUOTES, 'UTF-8') ?>"
                  alt="<?= htmlspecialchars($p['titulo'], ENT_QUOTES, 'UTF-8') ?>"
                  loading="lazy"
                  style="width:100%;aspect-ratio:3/2;object-fit:cover;border-radius:var(--radio);"
                >
                <a href="/paquetes/<?= htmlspecialchars($p['slug'], ENT_QUOTES, 'UTF-8') ?>" style="display:block;margin-top:0.5rem;"><?= htmlspecialchars($p['titulo'], ENT_QUOTES, 'UTF-8') ?></a>
              </th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Categoria</td>
            <?php foreach ($paquetes as $p): ?><td><?= htmlspecialchars($p['categoria_nombre'], ENT_QUOTES, 'UTF-8') ?></td><?php endforeach; ?>
          </tr>
          <tr>
            <td>Precio desde</td>
            <?php foreach ($paquetes as $p): ?><td>$<?= number_format((float) $p['precio_desde'], 0, '.', ',') ?> <?= htmlspecialchars($p['moneda'], ENT_QUOTES, 'UTF-8') ?></td><?php endforeach; ?>
          </tr>
          <tr>
            <td>Duracion</td>
            <?php foreach ($paquetes as $p): ?><td><?= (int) $p['duracion_dias'] ?> dias / <?= (int) $p['duracion_noches'] ?> noches</td><?php endforeach; ?>
          </tr>
          <tr>
            <td>Incluye</td>
            <?php foreach ($paquetes as $p): ?><td><?= htmlspecialchars($aplanarTexto($p['incluye'] ?? null), ENT_QUOTES, 'UTF-8') ?></td><?php endforeach; ?>
          </tr>
          <tr>
            <td>No incluye</td>
            <?php foreach ($paquetes as $p): ?><td><?= htmlspecialchars($aplanarTexto($p['no_incluye'] ?? null), ENT_QUOTES, 'UTF-8') ?></td><?php endforeach; ?>
          </tr>
          <tr>
            <td></td>
            <?php foreach ($paquetes as $p): ?><td><a href="/paquetes/<?= htmlspecialchars($p['slug'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primario">Ver paquete</a></td><?php endforeach; ?>
          </tr>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
