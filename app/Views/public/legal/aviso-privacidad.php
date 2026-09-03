<?php
/** @var array $contacto */
/*
 * PLANTILLA GENERICA de Aviso de Privacidad (LFPDPPP - Mexico). Antes de difundirla:
 *  1) Reemplazar todos los campos entre [CORCHETES] con los datos reales de la empresa.
 *  2) Que un abogado la revise contra la operacion real (finalidades, transferencias, plazos).
 * Los datos de contacto se toman de /admin/configuracion cuando existen; si no, quedan como
 * marcador entre corchetes.
 */
$e = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$direccion = $contacto['direccion'] !== '' ? $e($contacto['direccion']) : '[DOMICILIO FISCAL]';
$email = $contacto['email'] !== '' ? $e($contacto['email']) : '[CORREO DE CONTACTO]';
$telefono = $contacto['telefono'] !== '' ? $e($contacto['telefono']) : '[TELÉFONO DE CONTACTO]';
?>
<section class="seccion contenedor ancho-820">
  <h1>Aviso de Privacidad</h1>
  <p><em>Última actualización: [FECHA DE ÚLTIMA ACTUALIZACIÓN].</em></p>

  <p>
    <strong>[RAZÓN SOCIAL / NOMBRE DEL TITULAR]</strong> (en adelante, &laquo;Dream Go&raquo; o
    &laquo;el Responsable&raquo;), con domicilio en <?= $direccion ?>, es responsable del
    tratamiento y protección de tus datos personales conforme a la Ley Federal de Protección de
    Datos Personales en Posesión de los Particulares (LFPDPPP), su Reglamento y los Lineamientos
    del Aviso de Privacidad.
  </p>

  <h2>1. Datos personales que recabamos</h2>
  <p>Para las finalidades descritas más abajo podemos recabar:</p>
  <ul>
    <li><strong>Identificación y contacto:</strong> nombre, correo electrónico, teléfono.</li>
    <li><strong>Datos sobre el viaje:</strong> fechas tentativas, número de personas, destino o
      paquete de interés, preferencias y comentarios que nos compartas.</li>
    <li><strong>Datos de facturación:</strong> únicamente si solicitas factura ([RFC, razón
      social y domicilio fiscal, uso de CFDI]).</li>
    <li><strong>Datos de pago:</strong> el cobro del anticipo y del saldo se procesa
      directamente en la plataforma de <strong>Mercado Pago</strong>. Dream Go <u>no almacena</u>
      los datos de tu tarjeta; solo conservamos la referencia y el monto del pago.</li>
    <li><strong>Datos de navegación:</strong> ver la sección &laquo;Cookies y tecnologías de
      rastreo&raquo;.</li>
  </ul>
  <p>No recabamos datos personales sensibles.</p>

  <h2>2. Finalidades del tratamiento</h2>
  <p><strong>Finalidades primarias</strong> (necesarias para la relación con el Responsable):</p>
  <ul>
    <li>Atender y dar seguimiento a tus solicitudes de cotización e información.</li>
    <li>Gestionar tus reservas, pagos, confirmaciones y comprobantes.</li>
    <li>Enviarte recordatorios de tu viaje y del saldo pendiente.</li>
    <li>Brindarte atención y soporte antes, durante y después del viaje.</li>
    <li>Cumplir obligaciones legales, fiscales y contractuales.</li>
  </ul>
  <p><strong>Finalidades secundarias</strong> (no necesarias, puedes negarte):</p>
  <ul>
    <li>Enviarte promociones, ofertas y boletines informativos (newsletter).</li>
    <li>Realizar encuestas de satisfacción y solicitarte reseñas.</li>
    <li>Elaborar estadísticas de uso del sitio y medir el desempeño de nuestras campañas
      publicitarias.</li>
  </ul>
  <p>
    Si no deseas que tus datos se usen para las finalidades secundarias, envía tu solicitud a
    <?= $email ?>. La negativa no será motivo para negarte los servicios que contrates.
  </p>

  <h2>3. Transferencias de datos</h2>
  <p>Tus datos pueden transferirse, sin requerir tu consentimiento en los supuestos del
    artículo 37 de la LFPDPPP, a:</p>
  <ul>
    <li><strong>Prestadores de servicios turísticos</strong> (aerolíneas, hoteles, operadores
      locales, aseguradoras) estrictamente necesarios para prestar el servicio que contrataste.</li>
    <li><strong>Mercado Pago</strong> (Mercado Libre, S. de R.L. de C.V.) para procesar los pagos.</li>
    <li><strong>Autoridades</strong> competentes cuando exista requerimiento fundado y motivado.</li>
  </ul>
  <p>Previo tu consentimiento otorgado a través del banner de cookies, también compartimos
    identificadores de navegación con <strong>Google LLC</strong> y <strong>Meta Platforms,
    Inc.</strong> (ubicadas fuera de México) para fines de analítica y publicidad.</p>

  <h2>4. Cookies y tecnologías de rastreo</h2>
  <p>El sitio utiliza:</p>
  <ul>
    <li><strong>Cookies y almacenamiento propios y necesarios:</strong> mantienen tu sesión,
      el token de seguridad de los formularios y tus preferencias (por ejemplo la selección
      del comparador de paquetes y tu decisión sobre este aviso de cookies). No requieren
      consentimiento.</li>
    <li><strong>Analítica y publicidad de terceros (opcionales):</strong> solo se activan si
      pulsas &laquo;Aceptar&raquo; en el banner. Google Analytics 4 (cookies <code>_ga</code>,
      <code>_ga_*</code>) para estadísticas de uso, y Meta Pixel (cookie <code>_fbp</code>)
      para medir la efectividad de nuestras campañas. Puedes rechazarlas desde el mismo banner;
      para cambiar tu elección más adelante, borra los datos de este sitio en tu navegador y
      vuelve a cargar la página.</li>
  </ul>

  <h2>5. Derechos ARCO y revocación del consentimiento</h2>
  <p>Tienes derecho a <strong>Acceder</strong> a tus datos personales, <strong>Rectificar</strong>los
    cuando sean inexactos, <strong>Cancelar</strong>los cuando consideres que no se requieren
    para las finalidades señaladas, y <strong>Oponer</strong>te a su tratamiento. También puedes
    revocar el consentimiento que nos hayas otorgado y limitar el uso o divulgación de tus datos.</p>
  <p>Para ejercer cualquiera de estos derechos, envía tu solicitud a <strong><?= $email ?></strong>
    (o al domicilio señalado arriba, a la atención del &laquo;Departamento de Datos
    Personales&raquo;) e incluye: (i) tu nombre y un medio para comunicarte la respuesta;
    (ii) copia de una identificación oficial que acredite tu identidad; (iii) la descripción
    clara y precisa de los datos y el derecho que deseas ejercer. Responderemos en un plazo
    máximo de <strong>20 días hábiles</strong>.</p>

  <h2>6. Conservación de los datos</h2>
  <p>Conservamos tus datos durante el tiempo necesario para cumplir las finalidades descritas
    y los plazos legales y fiscales aplicables ([por ejemplo, 5 años para efectos fiscales]),
    tras lo cual se suprimen o anonimizan.</p>

  <h2>7. Cambios al Aviso de Privacidad</h2>
  <p>Este aviso puede modificarse en cualquier momento para atender cambios legislativos,
    internos o en nuestras prácticas. Las modificaciones se publicarán en esta misma página,
    indicando la fecha de la última actualización.</p>

  <h2>8. Aceptación</h2>
  <p>Al proporcionarnos tus datos personales por cualquier medio (formularios del sitio,
    correo, teléfono o WhatsApp) y utilizar este sitio, manifiestas que has leído y aceptas el
    presente Aviso de Privacidad.</p>

  <h2>9. Contacto</h2>
  <p>Dudas sobre este aviso o sobre el tratamiento de tus datos: <?= $email ?> &middot;
    Tel. <?= $telefono ?> &middot; <?= $direccion ?>.</p>
</section>
