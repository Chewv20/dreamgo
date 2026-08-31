<?php
/** @var array $destinos */
/** @var array<string,string> $tipos */
?>
<div class="admin-acciones mb-md">
  <a href="/admin/destinos/crear" class="btn btn-primario">Nuevo destino</a>
</div>

<div class="admin-panel">
  <div class="admin-tabla-wrap">
    <table class="admin-tabla">
      <thead><tr><th>Orden</th><th>Nombre</th><th>Tipo</th><th>Paquetes</th><th>Estado</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($destinos as $i => $d): ?>
          <tr>
            <td class="admin-acciones">
              <form method="post" action="/admin/destinos/<?= (int) $d['id'] ?>/mover">
                <?= \App\Helpers\Csrf::field() ?>
                <input type="hidden" name="direccion" value="arriba">
                <button type="submit" class="btn btn-secundario btn--icono" <?= $i === 0 ? 'disabled' : '' ?> aria-label="Subir">&uarr;</button>
              </form>
              <form method="post" action="/admin/destinos/<?= (int) $d['id'] ?>/mover">
                <?= \App\Helpers\Csrf::field() ?>
                <input type="hidden" name="direccion" value="abajo">
                <button type="submit" class="btn btn-secundario btn--icono" <?= $i === count($destinos) - 1 ? 'disabled' : '' ?> aria-label="Bajar">&darr;</button>
              </form>
            </td>
            <td><?= htmlspecialchars($d['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($tipos[$d['tipo']] ?? $d['tipo'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= (int) $d['total_paquetes'] ?></td>
            <td>
              <?php $activo = (int) $d['activo'] === 1; ?>
              <span class="admin-badge admin-badge--<?= $activo ? 'verde' : 'gris' ?>"><?= $activo ? 'Visible' : 'Oculto' ?></span>
            </td>
            <td class="admin-acciones">
              <a href="/admin/destinos/<?= (int) $d['id'] ?>/editar" class="btn btn-secundario">Editar</a>
              <form method="post" action="/admin/destinos/<?= (int) $d['id'] ?>/visible">
                <?= \App\Helpers\Csrf::field() ?>
                <button type="submit" class="btn btn-secundario"><?= $activo ? 'Ocultar' : 'Mostrar' ?></button>
              </form>
              <?php if ((int) $d['total_paquetes'] === 0): ?>
                <form method="post" action="/admin/destinos/<?= (int) $d['id'] ?>/eliminar" data-confirm="¿Eliminar este destino? No se puede deshacer.">
                  <?= \App\Helpers\Csrf::field() ?>
                  <button type="submit" class="btn btn-secundario btn--xs">Eliminar</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($destinos)): ?>
          <tr><td colspan="6">Todavia no hay destinos. Crea el primero.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
