<?php
/** @var array $reserva */
/** @var array $pagos */
/** @var array $notas */
/** @var array<string, string> $metodosPago */
/** @var string $linkPagoSaldo */
use Core\Auth;

$pagos ??= [];
$notas ??= [];
$metodosPago ??= [];
$linkPagoSaldo ??= '';
$e = static fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$moneda = $e($reserva['paquete_moneda']);
$total = (float) $reserva['precio_total'];
$pagado = (float) $reserva['monto_pagado'];
$saldo = max(0, $total - $pagado);
$conceptos = ['anticipo' => 'Anticipo', 'saldo' => 'Saldo', 'otro' => 'Otro'];
$metodos = ['mercadopago' => 'Mercado Pago'] + $metodosPago;
$badge = ['pendiente' => 'ambar', 'confirmada' => 'verde', 'cancelada' => 'gris', 'expirada' => 'rojo'][$reserva['estado']] ?? 'gris';
$activa = in_array($reserva['estado'], ['pendiente', 'confirmada'], true);

// WhatsApp: los teléfonos se capturan casi siempre a 10 dígitos (México); wa.me necesita lada.
$telefono = preg_replace('/\D+/', '', (string) $reserva['cliente_telefono']);
if (strlen($telefono) === 10) {
    $telefono = '52' . $telefono;
}
$nombreCorto = strtok((string) $reserva['cliente_nombre'], ' ');
$mensajeWa = 'Hola ' . $nombreCorto . ', te escribimos de Dream Go sobre tu reserva ' . $reserva['codigo_reserva'] . '.';
if ($linkPagoSaldo !== '') {
    $mensajeWa .= ' Tu saldo pendiente es de $' . number_format($saldo, 2) . ' ' . $reserva['paquete_moneda']
        . '. Puedes abonar o liquidarlo en línea aquí: ' . $linkPagoSaldo;
}
$waLink = $telefono !== '' ? 'https://wa.me/' . $telefono . '?text=' . rawurlencode($mensajeWa) : '';
?>
<div class="admin-detalle">
  <div class="admin-detalle__principal">
    <div class="admin-panel">
      <div class="admin-detalle__cabecera">
        <span class="admin-badge admin-badge--<?= $badge ?>"><?= $e(ucfirst($reserva['estado'])) ?></span>
        <?php if ($reserva['expira_en'] && $reserva['estado'] === 'pendiente'): ?>
          <span class="op-70">Expira <?= \App\Helpers\Fecha::cortaHora($reserva['expira_en']) ?></span>
        <?php endif; ?>
      </div>

      <div class="admin-resumen-pago">
        <div><span>Total</span><strong>$<?= number_format($total, 2) ?></strong></div>
        <div><span>Pagado</span><strong>$<?= number_format($pagado, 2) ?></strong></div>
        <div class="<?= $saldo > 0 && $activa ? 'is-pendiente' : '' ?>"><span>Saldo</span><strong>$<?= number_format($saldo, 2) ?></strong></div>
      </div>

      <table class="admin-tabla admin-tabla--detalle">
        <tr><td>Paquete</td><td><?= $e($reserva['paquete_titulo']) ?></td></tr>
        <tr><td>Fecha de salida</td><td><?= \App\Helpers\Fecha::corta($reserva['fecha_salida']) ?></td></tr>
        <tr><td>Personas</td><td><?= (int) $reserva['num_personas'] ?></td></tr>
        <tr><td>Moneda</td><td><?= $moneda ?></td></tr>
      </table>

      <div class="admin-acciones admin-mt">
        <?php if ($reserva['estado'] === 'confirmada'): ?>
          <a class="btn btn-secundario" href="/admin/reservas/<?= (int) $reserva['id'] ?>/comprobante">Descargar comprobante</a>
        <?php endif; ?>
        <?php if ($reserva['estado'] === 'pendiente' && Auth::hasPermission('reservas.confirmar')): ?>
          <form method="post" action="/admin/reservas/<?= (int) $reserva['id'] ?>/confirmar">
            <?= \App\Helpers\Csrf::field() ?>
            <button type="submit" class="btn btn-primario">Confirmar reserva</button>
          </form>
        <?php endif; ?>
        <?php if ($activa && Auth::hasPermission('reservas.cancelar')): ?>
          <form method="post" action="/admin/reservas/<?= (int) $reserva['id'] ?>/cancelar" data-confirm="¿Cancelar la reserva <?= $e($reserva['codigo_reserva']) ?> y liberar <?= (int) $reserva['num_personas'] ?> lugar(es)? No se puede deshacer.">
            <?= \App\Helpers\Csrf::field() ?>
            <button type="submit" class="btn btn-peligro">Cancelar reserva</button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <div class="admin-panel">
      <h2 class="mt-0">Pagos</h2>
      <?php if ($pagos === []): ?>
        <p class="op-70">Todavía no hay pagos registrados.</p>
      <?php else: ?>
        <div class="admin-tabla-wrap">
          <table class="admin-tabla admin-tabla--compacta">
            <thead><tr><th>Fecha</th><th>Concepto</th><th>Método</th><th>Monto</th><th>Detalle</th></tr></thead>
            <tbody>
              <?php foreach ($pagos as $pago): ?>
                <?php
                // Manual: "manual:{metodo}:{referencia capturada | auto-xxxx}"; se muestra sólo la capturada.
                $refVisible = (string) $pago['referencia_pago'];
                if (str_starts_with($refVisible, 'manual:')) {
                    $refVisible = explode(':', $refVisible, 3)[2] ?? '';
                    $refVisible = str_starts_with($refVisible, 'auto-') ? '' : $refVisible;
                }
                ?>
                <tr>
                  <td><?= \App\Helpers\Fecha::cortaHora($pago['creado_en']) ?></td>
                  <td><?= $e($conceptos[$pago['concepto']] ?? $pago['concepto']) ?></td>
                  <td><?= $e($metodos[$pago['metodo_pago']] ?? $pago['metodo_pago']) ?></td>
                  <td class="num">$<?= number_format((float) $pago['monto'], 2) ?></td>
                  <td>
                    <?php if ($refVisible !== ''): ?><span class="op-70">Ref.</span> <?= $e($refVisible) ?><br><?php endif; ?>
                    <?php if (!empty($pago['nota'])): ?><?= $e($pago['nota']) ?><br><?php endif; ?>
                    <?php if (!empty($pago['registrado_por_nombre'])): ?><small class="op-70">Registró: <?= $e($pago['registrado_por_nombre']) ?></small><?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <?php if ($activa && $saldo > 0 && Auth::hasPermission('reservas.registrar_pago')): ?>
        <details class="admin-desplegable admin-mt">
          <summary>Registrar pago recibido fuera de Mercado Pago</summary>
          <form method="post" action="/admin/reservas/<?= (int) $reserva['id'] ?>/pago" class="admin-form-grid admin-form-grid--2 admin-mt-sm">
            <?= \App\Helpers\Csrf::field() ?>
            <div class="campo">
              <label for="monto">Monto (<?= $moneda ?>)</label>
              <input type="number" id="monto" name="monto" min="0.01" step="0.01" max="<?= number_format($saldo, 2, '.', '') ?>" required placeholder="<?= number_format($saldo, 2, '.', '') ?>">
            </div>
            <div class="campo">
              <label for="metodo">Método</label>
              <select id="metodo" name="metodo" required>
                <?php foreach ($metodosPago as $clave => $etiqueta): ?>
                  <option value="<?= $e($clave) ?>"><?= $e($etiqueta) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="campo">
              <label for="referencia">Referencia / folio (opcional)</label>
              <input type="text" id="referencia" name="referencia" maxlength="60" aria-describedby="ayuda-referencia">
              <small class="op-70" id="ayuda-referencia">Si la capturas, el sistema evita registrar dos veces la misma transferencia.</small>
            </div>
            <div class="campo">
              <label for="nota_pago">Nota (opcional)</label>
              <input type="text" id="nota_pago" name="nota" maxlength="255">
            </div>
            <label class="label-check">
              <input type="checkbox" name="avisar_cliente" value="1" checked>
              Enviar al cliente el comprobante actualizado por correo
            </label>
            <div class="admin-acciones">
              <button type="submit" class="btn btn-primario">Registrar pago</button>
            </div>
          </form>
        </details>
      <?php endif; ?>
    </div>

    <div class="admin-panel" id="notas">
      <h2 class="mt-0">Notas internas</h2>
      <form method="post" action="/admin/reservas/<?= (int) $reserva['id'] ?>/nota" class="admin-nota-form">
        <?= \App\Helpers\Csrf::field() ?>
        <label for="nota" class="sr-only">Nueva nota</label>
        <textarea id="nota" name="nota" rows="2" maxlength="2000" required placeholder="Ej. El cliente pidió habitación doble; pagará el saldo en dos partes."></textarea>
        <button type="submit" class="btn btn-secundario">Agregar nota</button>
      </form>
      <?php if ($notas === []): ?>
        <p class="op-70 admin-mt-sm">Sin notas todavía. Sólo el equipo las ve.</p>
      <?php else: ?>
        <ul class="lista-notas admin-mt-sm">
          <?php foreach ($notas as $nota): ?>
            <li class="nota-item">
              <div class="pre-wrap"><?= $e($nota['nota']) ?></div>
              <small class="op-70"><?= $e($nota['usuario_nombre'] ?? 'Usuario eliminado') ?> · <?= \App\Helpers\Fecha::cortaHora($nota['creado_en']) ?></small>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>

  <aside class="admin-detalle__lateral">
    <div class="admin-panel">
      <h2 class="mt-0">Cliente</h2>
      <p class="m-0"><strong><?= $e($reserva['cliente_nombre']) ?></strong></p>
      <ul class="admin-contacto">
        <li><a href="mailto:<?= $e($reserva['cliente_email']) ?>"><?= $e($reserva['cliente_email']) ?></a></li>
        <li><a href="tel:<?= $e(preg_replace('/[^\d+]/', '', (string) $reserva['cliente_telefono'])) ?>"><?= $e($reserva['cliente_telefono']) ?></a></li>
      </ul>
      <?php if ($waLink !== ''): ?>
        <a href="<?= $e($waLink) ?>" class="btn btn-whatsapp w-100" target="_blank" rel="noopener">
          <?= $linkPagoSaldo !== '' ? 'Enviar link de pago por WhatsApp' : 'Escribir por WhatsApp' ?>
        </a>
      <?php endif; ?>
    </div>

    <?php if ($linkPagoSaldo !== ''): ?>
      <div class="admin-panel">
        <h2 class="mt-0">Link de pago del cliente</h2>
        <p class="admin-nota m-0">Con este link el cliente abona o liquida el saldo con Mercado Pago. Es el mismo que recibe en sus correos.</p>
        <div class="admin-copiar admin-mt-sm">
          <input type="text" readonly value="<?= $e($linkPagoSaldo) ?>" id="link-pago" aria-label="Link de pago del saldo">
          <button type="button" class="btn btn-secundario" data-copiar="#link-pago">Copiar</button>
        </div>
      </div>
    <?php endif; ?>
  </aside>
</div>
