<?php
/** @var string $pagina */
/** @var string $nombrePagina */
/** @var array $bloques */
/** @var array $etiquetas */
$urlsPublicas = [
    'home' => '/',
    'nosotros' => '/nosotros',
    'contacto' => '/contacto',
    'destinos' => '/destinos',
    'paquetes' => '/paquetes',
];
?>
<div class="admin-acciones mb-md">
  <a href="/admin/contenido" class="btn btn-secundario">&larr; Todas las paginas</a>
  <a href="/admin/colores" class="btn btn-secundario">Colores del sitio</a>
  <a href="<?= htmlspecialchars($urlsPublicas[$pagina] ?? '/', ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="btn btn-secundario">Ver "<?= htmlspecialchars($nombrePagina, ENT_QUOTES, 'UTF-8') ?>"</a>
</div>

<div class="admin-panel">
  <p class="op-75 mt-0">
    Ordena las secciones, ocultalas temporalmente o edita su texto. Los cambios se ven de inmediato en el sitio.
  </p>

  <div class="admin-tabla-wrap">
    <table class="admin-tabla">
      <thead><tr><th>Orden</th><th>Seccion</th><th>Titulo actual</th><th>Estado</th><th>Acciones</th></tr></thead>
      <tbody>
        <?php foreach ($bloques as $i => $bloque): ?>
          <tr>
            <td>
              <div class="fila-btns">
                <form method="post" action="/admin/contenido/<?= (int) $bloque['id'] ?>/mover">
                  <?= \App\Helpers\Csrf::field() ?>
                  <input type="hidden" name="direccion" value="arriba">
                  <button type="submit" class="btn btn-secundario btn--icono" <?= $i === 0 ? 'disabled' : '' ?> aria-label="Subir">&uarr;</button>
                </form>
                <form method="post" action="/admin/contenido/<?= (int) $bloque['id'] ?>/mover">
                  <?= \App\Helpers\Csrf::field() ?>
                  <input type="hidden" name="direccion" value="abajo">
                  <button type="submit" class="btn btn-secundario btn--icono" <?= $i === count($bloques) - 1 ? 'disabled' : '' ?> aria-label="Bajar">&darr;</button>
                </form>
              </div>
            </td>
            <td><?= htmlspecialchars($etiquetas[$bloque['clave']] ?? $bloque['clave'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($bloque['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <?php if ((int) $bloque['visible'] === 1): ?>
                <span class="admin-badge admin-badge--verde">Visible</span>
              <?php else: ?>
                <span class="admin-badge admin-badge--gris">Oculta</span>
              <?php endif; ?>
            </td>
            <td class="admin-acciones">
              <a href="/admin/contenido/<?= (int) $bloque['id'] ?>/editar" class="btn btn-secundario">Editar</a>
              <form method="post" action="/admin/contenido/<?= (int) $bloque['id'] ?>/visible" class="d-inline">
                <?= \App\Helpers\Csrf::field() ?>
                <button type="submit" class="btn btn-secundario">
                  <?= (int) $bloque['visible'] === 1 ? 'Ocultar' : 'Mostrar' ?>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
