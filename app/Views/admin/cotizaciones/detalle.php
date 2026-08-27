<?php
/** @var array $cotizacion */
/** @var array $notas */
/** @var array $asesores */
/** @var list<string> $estados */
$c = $cotizacion;
$e = static fn (?string $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$csrf = \App\Helpers\Csrf::field();
$id = (int) $c['id'];
?>
<p><a href="/admin/cotizaciones">&laquo; Volver al listado</a></p>

<div class="admin-panel" style="max-width:720px;">
  <table class="admin-tabla" style="min-width:0;">
    <tr><td style="font-weight:bold;">Recibida</td><td><?= date('d M Y H:i', strtotime($c['creado_en'])) ?></td></tr>
    <tr><td style="font-weight:bold;">Nombre</td><td><?= $e($c['nombre']) ?></td></tr>
    <tr><td style="font-weight:bold;">Correo</td><td><a href="mailto:<?= $e($c['email']) ?>"><?= $e($c['email']) ?></a></td></tr>
    <tr><td style="font-weight:bold;">Telefono</td><td><?= $e($c['telefono']) ?></td></tr>
    <tr><td style="font-weight:bold;">Paquete</td><td><?= $e($c['paquete_titulo'] ?? 'General / sin paquete') ?></td></tr>
    <tr><td style="font-weight:bold;">Personas</td><td><?= $c['num_personas'] !== null ? (int) $c['num_personas'] : '&mdash;' ?></td></tr>
    <tr><td style="font-weight:bold;">Fecha tentativa</td><td><?= $c['fecha_tentativa'] ? $e(date('d/m/Y', strtotime($c['fecha_tentativa']))) : '&mdash;' ?></td></tr>
    <?php if (!empty($c['utm_source']) || !empty($c['referrer'])): ?>
      <tr><td style="font-weight:bold;">Origen</td><td>
        <?php if (!empty($c['utm_source'])): ?>
          <?= $e(trim($c['utm_source'] . ' / ' . ($c['utm_medium'] ?? '') . ' / ' . ($c['utm_campaign'] ?? ''), ' /')) ?>
        <?php endif; ?>
        <?php if (!empty($c['referrer'])): ?><br><small><?= $e($c['referrer']) ?></small><?php endif; ?>
      </td></tr>
    <?php endif; ?>
  </table>

  <?php if (!empty($c['mensaje'])): ?>
    <h3 style="margin:1.25rem 0 0.35rem;">Mensaje del cliente</h3>
    <p style="white-space:pre-wrap;background:var(--color-fondo-alterno,#faf3ef);padding:0.75rem 1rem;border-radius:8px;"><?= $e($c['mensaje']) ?></p>
  <?php endif; ?>
</div>

<div class="admin-panel" style="max-width:720px;">
  <h2 style="margin-top:0;">Gestion</h2>
  <div class="admin-form-grid admin-form-grid--2">
    <form method="post" action="/admin/cotizaciones/<?= $id ?>/estado">
      <?= $csrf ?>
      <input type="hidden" name="volver" value="detalle">
      <label for="estado">Estado</label>
      <div style="display:flex;gap:0.4rem;">
        <select id="estado" name="estado">
          <?php foreach ($estados as $estado): ?>
            <option value="<?= $estado ?>" <?= $c['estado'] === $estado ? 'selected' : '' ?>><?= ucfirst($estado) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secundario">Guardar</button>
      </div>
    </form>

    <form method="post" action="/admin/cotizaciones/<?= $id ?>/asignar">
      <?= $csrf ?>
      <label for="asignado_a">Asesor asignado</label>
      <div style="display:flex;gap:0.4rem;">
        <select id="asignado_a" name="asignado_a">
          <option value="">Sin asignar</option>
          <?php foreach ($asesores as $asesor): ?>
            <option value="<?= (int) $asesor['id'] ?>" <?= (string) $c['asignado_a'] === (string) $asesor['id'] ? 'selected' : '' ?>><?= $e($asesor['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secundario">Guardar</button>
      </div>
    </form>

    <form method="post" action="/admin/cotizaciones/<?= $id ?>/seguimiento">
      <?= $csrf ?>
      <label for="seguimiento_en">Proximo contacto</label>
      <div style="display:flex;gap:0.4rem;">
        <input type="date" id="seguimiento_en" name="seguimiento_en" value="<?= $e($c['seguimiento_en'] ?? '') ?>">
        <button type="submit" class="btn btn-secundario">Guardar</button>
      </div>
    </form>
  </div>
</div>

<div class="admin-panel" style="max-width:720px;">
  <h2 style="margin-top:0;">Notas de seguimiento</h2>

  <form method="post" action="/admin/cotizaciones/<?= $id ?>/nota" style="margin-bottom:1.25rem;">
    <?= $csrf ?>
    <div class="campo">
      <label for="nota">Nueva nota</label>
      <textarea id="nota" name="nota" rows="3" required maxlength="2000" placeholder="Ej. Llame al cliente, pidio propuesta con vuelos incluidos."></textarea>
    </div>
    <button type="submit" class="btn btn-primario">Agregar nota</button>
  </form>

  <?php if (empty($notas)): ?>
    <p style="opacity:0.7;">Todavia no hay notas.</p>
  <?php else: ?>
    <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.75rem;">
      <?php foreach ($notas as $nota): ?>
        <li style="border-left:3px solid var(--color-primario,#d99e8e);padding:0.4rem 0 0.4rem 0.75rem;">
          <div style="white-space:pre-wrap;"><?= $e($nota['nota']) ?></div>
          <small style="opacity:0.7;">
            <?= $e($nota['usuario_nombre'] ?? ('#' . ($nota['usuario_id'] ?? '?'))) ?> &middot;
            <?= date('d M Y H:i', strtotime($nota['creado_en'])) ?>
          </small>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
