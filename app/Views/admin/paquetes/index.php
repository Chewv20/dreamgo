<?php /** @var array $paquetes */ ?>
<div class="admin-acciones" style="margin-bottom:1.25rem;">
  <a href="/admin/paquetes/crear" class="btn btn-primario">Nuevo paquete</a>
</div>

<div class="admin-panel">
  <div class="admin-tabla-wrap">
    <table class="admin-tabla">
      <thead><tr><th>Titulo</th><th>Categoria</th><th>Precio</th><th>Estado</th><th>Destacado</th><th>Acciones</th></tr></thead>
      <tbody>
        <?php foreach ($paquetes as $paquete): ?>
          <tr>
            <td><?= htmlspecialchars($paquete['titulo'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($paquete['categoria_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>$<?= number_format((float) $paquete['precio_desde'], 0, '.', ',') ?> <?= htmlspecialchars($paquete['moneda'], ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <?php $badge = ['publicado' => 'verde', 'borrador' => 'ambar', 'archivado' => 'gris'][$paquete['estado']]; ?>
              <span class="admin-badge admin-badge--<?= $badge ?>"><?= ucfirst($paquete['estado']) ?></span>
            </td>
            <td><?= (int) $paquete['destacado'] === 1 ? 'Si' : '-' ?></td>
            <td class="admin-acciones">
              <a href="/admin/paquetes/<?= (int) $paquete['id'] ?>/editar" class="btn btn-secundario">Editar</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php $rutaBase = '/admin/paquetes'; require __DIR__ . '/../../partials/paginacion.php'; ?>
</div>
