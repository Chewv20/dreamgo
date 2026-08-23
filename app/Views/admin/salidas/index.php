<?php /** @var array $paquete */ /** @var array $salidas */ ?>
<div class="admin-acciones" style="margin-bottom:1.25rem;">
  <a href="/admin/paquetes/<?= (int) $paquete['id'] ?>/salidas/crear" class="btn btn-primario">Nueva fecha</a>
  <a href="/admin/paquetes/<?= (int) $paquete['id'] ?>/editar" class="btn btn-secundario">Volver al paquete</a>
</div>

<div class="admin-panel">
  <div class="admin-tabla-wrap">
    <table class="admin-tabla">
      <thead><tr><th>Salida</th><th>Regreso</th><th>Cupo</th><th>Estado</th><th>Acciones</th></tr></thead>
      <tbody>
        <?php foreach ($salidas as $salida): ?>
          <tr>
            <td><?= date('d M Y', strtotime($salida['fecha_salida'])) ?></td>
            <td><?= $salida['fecha_regreso'] ? date('d M Y', strtotime($salida['fecha_regreso'])) : '-' ?></td>
            <td><?= (int) $salida['cupo_disponible'] ?> / <?= (int) $salida['cupo_maximo'] ?></td>
            <td>
              <?php $badge = ['abierta' => 'verde', 'cerrada' => 'ambar', 'cancelada' => 'rojo'][$salida['estado']]; ?>
              <span class="admin-badge admin-badge--<?= $badge ?>"><?= ucfirst($salida['estado']) ?></span>
            </td>
            <td class="admin-acciones">
              <a href="/admin/paquetes/<?= (int) $paquete['id'] ?>/salidas/<?= (int) $salida['id'] ?>/editar" class="btn btn-secundario">Editar</a>
              <?php if ($salida['estado'] === 'abierta' && (int) $salida['cupo_disponible'] > 0 && \Core\Auth::hasPermission('reservas.ver')): ?>
                <a href="/admin/reservas/crear?salida_id=<?= (int) $salida['id'] ?>" class="btn btn-primario">Nueva reserva</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($salidas)): ?>
          <tr><td colspan="5">Aun no hay fechas de salida para este paquete.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
