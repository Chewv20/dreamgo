<?php /** @var array $valores */ ?>
<form method="post" action="/admin/configuracion">
  <?= \App\Helpers\Csrf::field() ?>

  <div class="admin-panel" style="max-width:760px;">
    <h2 style="margin-top:0;">Contacto</h2>
    <p style="opacity:0.75;margin-top:-0.5rem;">Estos datos se muestran en el pie de pagina del sitio.</p>

    <div class="campo">
      <label for="direccion">Direccion (opcional)</label>
      <input type="text" id="direccion" name="direccion" value="<?= htmlspecialchars($valores['direccion'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Av. Siempre Viva 123, Ciudad de Mexico">
    </div>

    <div class="admin-form-grid admin-form-grid--2">
      <div class="campo">
        <label for="telefono_contacto">Telefono de contacto (opcional)</label>
        <input type="text" id="telefono_contacto" name="telefono_contacto" value="<?= htmlspecialchars($valores['telefono_contacto'], ENT_QUOTES, 'UTF-8') ?>" placeholder="+52 55 1234 5678">
      </div>
      <div class="campo">
        <label for="email_contacto">Correo de contacto publico (opcional)</label>
        <input type="email" id="email_contacto" name="email_contacto" value="<?= htmlspecialchars($valores['email_contacto'], ENT_QUOTES, 'UTF-8') ?>" placeholder="contacto@dreamgooperadoraturistica.com">
      </div>
    </div>

    <div class="campo">
      <label for="whatsapp_numero">Numero de WhatsApp (formato internacional, sin +)</label>
      <input type="text" id="whatsapp_numero" name="whatsapp_numero" value="<?= htmlspecialchars($valores['whatsapp_numero'], ENT_QUOTES, 'UTF-8') ?>" placeholder="5215500000000">
    </div>
  </div>

  <div class="admin-panel" style="max-width:760px;">
    <h2 style="margin-top:0;">Redes sociales</h2>
    <p style="opacity:0.75;margin-top:-0.5rem;">Deja en blanco las que no apliquen: su icono no se mostrara en el sitio.</p>

    <div class="admin-form-grid admin-form-grid--2">
      <div class="campo">
        <label for="facebook_url">Facebook (URL completa)</label>
        <input type="url" id="facebook_url" name="facebook_url" value="<?= htmlspecialchars($valores['facebook_url'], ENT_QUOTES, 'UTF-8') ?>" placeholder="https://facebook.com/dreamgo">
      </div>
      <div class="campo">
        <label for="instagram_url">Instagram (URL completa)</label>
        <input type="url" id="instagram_url" name="instagram_url" value="<?= htmlspecialchars($valores['instagram_url'], ENT_QUOTES, 'UTF-8') ?>" placeholder="https://instagram.com/dreamgo">
      </div>
      <div class="campo">
        <label for="tiktok_url">TikTok (URL completa)</label>
        <input type="url" id="tiktok_url" name="tiktok_url" value="<?= htmlspecialchars($valores['tiktok_url'], ENT_QUOTES, 'UTF-8') ?>" placeholder="https://tiktok.com/@dreamgo">
      </div>
      <div class="campo">
        <label for="youtube_url">YouTube (URL completa)</label>
        <input type="url" id="youtube_url" name="youtube_url" value="<?= htmlspecialchars($valores['youtube_url'], ENT_QUOTES, 'UTF-8') ?>" placeholder="https://youtube.com/@dreamgo">
      </div>
    </div>
  </div>

  <div class="admin-panel" style="max-width:760px;">
    <h2 style="margin-top:0;">Reservas y recordatorios</h2>

    <div class="admin-form-grid admin-form-grid--2">
      <div class="campo">
        <label for="horas_expiracion_reserva">Horas para liberar una reserva pendiente sin confirmar</label>
        <input type="number" min="1" id="horas_expiracion_reserva" name="horas_expiracion_reserva" value="<?= htmlspecialchars($valores['horas_expiracion_reserva'], ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="campo">
        <label for="dias_recordatorio_viaje">Dias de anticipacion para el recordatorio de viaje</label>
        <input type="number" min="1" id="dias_recordatorio_viaje" name="dias_recordatorio_viaje" value="<?= htmlspecialchars($valores['dias_recordatorio_viaje'], ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="campo">
        <label for="dias_recordatorio_saldo">Dias antes de la salida para el recordatorio de saldo pendiente</label>
        <input type="number" min="1" id="dias_recordatorio_saldo" name="dias_recordatorio_saldo" value="<?= htmlspecialchars($valores['dias_recordatorio_saldo'], ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="campo">
        <label for="porcentaje_anticipo_reserva">Porcentaje de anticipo al reservar en linea (1-100)</label>
        <input type="number" min="1" max="100" id="porcentaje_anticipo_reserva" name="porcentaje_anticipo_reserva" value="<?= htmlspecialchars($valores['porcentaje_anticipo_reserva'], ENT_QUOTES, 'UTF-8') ?>">
      </div>
    </div>

    <div class="campo">
      <label for="email_equipo_reportes">Correo interno del equipo (recibe notificaciones de cotizaciones y reportes)</label>
      <input type="email" id="email_equipo_reportes" name="email_equipo_reportes" value="<?= htmlspecialchars($valores['email_equipo_reportes'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
  </div>

  <div class="admin-panel" style="max-width:760px;">
    <h2 style="margin-top:0;">SEO</h2>

    <div class="campo">
      <label for="meta_title_default">Titulo SEO por defecto</label>
      <input type="text" id="meta_title_default" name="meta_title_default" value="<?= htmlspecialchars($valores['meta_title_default'], ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="campo">
      <label for="meta_description_default">Descripcion SEO por defecto</label>
      <textarea id="meta_description_default" name="meta_description_default"><?= htmlspecialchars($valores['meta_description_default'], ENT_QUOTES, 'UTF-8') ?></textarea>
    </div>
  </div>

  <button type="submit" class="btn btn-primario">Guardar configuracion</button>
</form>
