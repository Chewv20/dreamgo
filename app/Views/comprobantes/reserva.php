<?php
/** @var array $reserva */
/** @var array $agencia */

$e = static fn (?string $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$fecha = static fn (?string $v): string => $v ? date('d/m/Y', strtotime($v)) : '-';
$moneda = $e($reserva['paquete_moneda'] ?? '');
$dinero = static fn ($v): string => number_format((float) $v, 2, '.', ',');

$total = (float) $reserva['precio_total'];
$pagado = (float) $reserva['monto_pagado'];
$saldo = max(0, $total - $pagado);

$etiquetasEstado = [
    'pendiente' => 'Pendiente de confirmacion',
    'confirmada' => 'Confirmada',
    'cancelada' => 'Cancelada',
    'expirada' => 'Expirada',
];
$coloresEstado = [
    'pendiente' => '#a85f4d',
    'confirmada' => '#4f7a5c',
    'cancelada' => '#6b6b6b',
    'expirada' => '#b3453a',
];
$estado = (string) $reserva['estado'];

$etiquetasMetodo = ['mercadopago' => 'Mercado Pago'];
$metodo = (string) ($reserva['metodo_pago'] ?? '');

/**
 * Fila de dos columnas. dompdf alinea de forma fiable con table-layout fijo y anchos
 * explicitos; se evita apilar varias <table> con anchos en % (dompdf las desalinea).
 */
$fila = static function (string $etiqueta, string $valor) use ($e): string {
    return '<tr>'
        . '<td style="width:180px;font-weight:bold;color:#5a6a72;padding:6px 8px;border-bottom:1px solid #ece3de;">' . $e($etiqueta) . '</td>'
        . '<td style="padding:6px 8px;border-bottom:1px solid #ece3de;">' . $e($valor) . '</td>'
        . '</tr>';
};
?>
<!doctype html>
<html lang="es-MX">
<head>
<meta charset="utf-8">
<style>
  @page { margin: 0; }
  body { font-family: "DejaVu Sans", sans-serif; color: #2f3e46; font-size: 12px; margin: 0; }
  .encabezado { background: #a85f4d; color: #ffffff; padding: 18px 24px; }
  .marca { font-size: 17px; font-weight: bold; }
  .doc { font-size: 12px; color: #f6e4de; }
  .cuerpo { padding: 24px; }
  h1 { font-size: 16px; margin: 0 0 4px; }
  .codigo { font-size: 22px; font-weight: bold; letter-spacing: 1px; margin: 2px 0 10px; }
  .pill { display: inline-block; padding: 3px 10px; border-radius: 10px; color: #ffffff; font-size: 11px; }
  .seccion-titulo { font-size: 12px; font-weight: bold; text-transform: uppercase; color: #a85f4d; margin: 20px 0 4px; }
  table.bloque { width: 100%; table-layout: fixed; border-collapse: collapse; margin: 6px 0; }
  table.totales td { padding: 7px 8px; }
  table.totales td.val { text-align: right; }
  table.totales tr.saldo td { background: #faf3ef; font-weight: bold; font-size: 13px; border-top: 2px solid #a85f4d; }
  .pie { margin-top: 26px; padding-top: 12px; border-top: 1px solid #ece3de; font-size: 10.5px; color: #6b6b6b; line-height: 1.5; }
</style>
</head>
<body>
  <div class="encabezado">
    <div class="marca">Dream Go Operadora Turistica</div>
    <div class="doc">Comprobante de reserva</div>
  </div>

  <div class="cuerpo">
    <h1><?= $e($reserva['paquete_titulo']) ?></h1>
    <div class="codigo"><?= $e($reserva['codigo_reserva']) ?></div>
    <span class="pill" style="background: <?= $coloresEstado[$estado] ?? '#6b6b6b' ?>;"><?= $e($etiquetasEstado[$estado] ?? ucfirst($estado)) ?></span>

    <div class="seccion-titulo">Datos del viaje</div>
    <table class="bloque">
      <?= $fila('Fecha de salida', $fecha($reserva['fecha_salida'])) ?>
      <?= $fila('Fecha de regreso', $fecha($reserva['fecha_regreso'] ?? null)) ?>
      <?= $fila('Personas', (string) (int) $reserva['num_personas']) ?>
      <?= $fila('Reserva creada', $fecha($reserva['creado_en'])) ?>
      <?php if (!empty($reserva['confirmada_en'])): ?>
        <?= $fila('Confirmada', $fecha($reserva['confirmada_en'])) ?>
      <?php endif; ?>
    </table>

    <div class="seccion-titulo">Titular de la reserva</div>
    <table class="bloque">
      <?= $fila('Nombre', (string) $reserva['cliente_nombre']) ?>
      <?= $fila('Correo', (string) $reserva['cliente_email']) ?>
      <?= $fila('Telefono', (string) ($reserva['cliente_telefono'] ?? '')) ?>
    </table>

    <div class="seccion-titulo">Pago</div>
    <table class="bloque totales">
      <tr><td style="font-weight:bold;color:#5a6a72;">Precio total</td><td class="val"><?= $dinero($total) ?> <?= $moneda ?></td></tr>
      <tr><td style="font-weight:bold;color:#5a6a72;">Anticipo pagado</td><td class="val"><?= $dinero($pagado) ?> <?= $moneda ?></td></tr>
      <tr class="saldo"><td>Saldo pendiente</td><td class="val"><?= $dinero($saldo) ?> <?= $moneda ?></td></tr>
      <?php if ($metodo !== ''): ?>
        <tr><td style="font-weight:bold;color:#5a6a72;">Metodo de pago</td><td class="val"><?= $e($etiquetasMetodo[$metodo] ?? ucfirst($metodo)) ?></td></tr>
      <?php endif; ?>
      <?php if (!empty($reserva['referencia_pago'])): ?>
        <tr><td style="font-weight:bold;color:#5a6a72;">Referencia</td><td class="val"><?= $e($reserva['referencia_pago']) ?></td></tr>
      <?php endif; ?>
    </table>

    <div class="pie">
      <?php if ($agencia['direccion'] !== ''): ?><?= $e($agencia['direccion']) ?><br><?php endif; ?>
      <?php if ($agencia['telefono'] !== ''): ?>Tel: <?= $e($agencia['telefono']) ?> &nbsp; <?php endif; ?>
      <?php if ($agencia['whatsapp'] !== ''): ?>WhatsApp: +<?= $e($agencia['whatsapp']) ?> &nbsp; <?php endif; ?>
      <?php if ($agencia['email'] !== ''): ?><?= $e($agencia['email']) ?><?php endif; ?>
      <br><br>
      Este comprobante confirma el registro de la reserva con el saldo indicado arriba. El saldo
      pendiente debe liquidarse segun las condiciones acordadas con la agencia. Documento generado
      automaticamente el <?= date('d/m/Y H:i') ?>.
    </div>
  </div>
</body>
</html>
