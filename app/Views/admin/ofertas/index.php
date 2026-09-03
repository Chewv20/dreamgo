<?php /** @var array $ofertas */ ?>
<div class="admin-acciones mb-md">
  <a href="/admin/ofertas/crear" class="btn btn-primario">Nuevo código</a>
</div>

<div class="admin-panel">
  <div class="admin-tabla-wrap">
    <table class="admin-tabla">
      <thead><tr><th>Código</th><th>Descuento</th><th>Alcance</th><th>Vigencia</th><th>Usos</th><th>Estado</th><th>Acciones</th></tr></thead>
      <tbody>
        <?php foreach ($ofertas as $o): ?>
          <tr>
            <td><?= htmlspecialchars($o['codigo'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= $o['tipo'] === 'porcentaje' ? ((float) $o['valor'] . '%') : ('$' . number_format((float) $o['valor'], 2)) ?></td>
            <td><?= $o['alcance'] === 'global' ? 'Global' : htmlspecialchars($o['paquete_titulo'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= date('d/m/y', strtotime($o['fecha_inicio'])) ?> - <?= date('d/m/y', strtotime($o['fecha_fin'])) ?></td>
            <td><?= (int) $o['usos_actuales'] ?><?= $o['uso_maximo'] ? ' / ' . (int) $o['uso_maximo'] : '' ?></td>
            <td>
              <?php if ((int) $o['activo'] === 1): ?>
                <span class="admin-badge admin-badge--verde">Activo</span>
              <?php else: ?>
                <span class="admin-badge admin-badge--gris">Inactivo</span>
              <?php endif; ?>
            </td>
            <td class="admin-acciones">
              <a href="/admin/ofertas/<?= (int) $o['id'] ?>/editar" class="btn btn-secundario">Editar</a>
              <?php if ((int) $o['activo'] === 1): ?>
                <form method="post" action="/admin/ofertas/<?= (int) $o['id'] ?>/enviar-suscriptores" data-confirm="¿Enviar esta oferta a todos los suscriptores confirmados?">
                  <?= \App\Helpers\Csrf::field() ?>
                  <button type="submit" class="btn btn-secundario">Enviar a suscriptores</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($ofertas)): ?>
          <tr><td colspan="7">Aún no hay códigos de descuento.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
