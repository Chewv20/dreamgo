-- Actualización de configuracion_sitio en producción (2026-10-16).
--
-- Solo toca `valor`: las descripciones ya llevan acentos desde la migración 0028 y no se
-- sobrescriben. `actualizado_en` se actualiza solo (ON UPDATE CURRENT_TIMESTAMP).
-- Es idempotente: correrlo dos veces deja el mismo resultado.
--
-- Uso: phpMyAdmin > pestaña SQL, o  mysql --default-character-set=utf8mb4 <bd> < este_archivo

SET NAMES utf8mb4;

START TRANSACTION;

-- Colores
UPDATE configuracion_sitio SET valor = '#d4af37' WHERE clave = 'color_error';
UPDATE configuracion_sitio SET valor = '#d4af37' WHERE clave = 'color_exito';
UPDATE configuracion_sitio SET valor = '#0f2744' WHERE clave = 'color_fondo';
UPDATE configuracion_sitio SET valor = '#f4f4f4' WHERE clave = 'color_fondo_alterno';
UPDATE configuracion_sitio SET valor = '#0f2744' WHERE clave = 'color_primario';
UPDATE configuracion_sitio SET valor = '#0f2744' WHERE clave = 'color_primario_oscuro';
UPDATE configuracion_sitio SET valor = '#d4af37' WHERE clave = 'color_texto_oscuro';

-- Recordatorios y reservas
UPDATE configuracion_sitio SET valor = '15' WHERE clave = 'dias_recordatorio_saldo';
UPDATE configuracion_sitio SET valor = '3'  WHERE clave = 'dias_recordatorio_viaje';
UPDATE configuracion_sitio SET valor = '3'  WHERE clave = 'dias_solicitud_resena';
UPDATE configuracion_sitio SET valor = '72' WHERE clave = 'horas_expiracion_reserva';
UPDATE configuracion_sitio SET valor = '30' WHERE clave = 'porcentaje_anticipo_reserva';

-- Contacto
UPDATE configuracion_sitio SET valor = 'Iztapalapa CDMX' WHERE clave = 'direccion';
UPDATE configuracion_sitio SET valor = 'ventas@dreamgooperadoraturistica.com' WHERE clave = 'email_contacto';
UPDATE configuracion_sitio SET valor = 'admin@dreamgooperadoraturistica.com' WHERE clave = 'email_equipo_reportes';
UPDATE configuracion_sitio SET valor = '5527714287' WHERE clave = 'telefono_contacto';
-- wa.me necesita el código de país: con solo 10 dígitos el enlace no abre el chat.
UPDATE configuracion_sitio SET valor = '525527714287' WHERE clave = 'whatsapp_numero';

-- Redes sociales (vacío = icono oculto)
UPDATE configuracion_sitio SET valor = 'https://www.facebook.com/profile.php?id=61587608214529' WHERE clave = 'facebook_url';
UPDATE configuracion_sitio SET valor = 'https://www.instagram.com/dreamgoviaja/' WHERE clave = 'instagram_url';
UPDATE configuracion_sitio SET valor = '' WHERE clave = 'tiktok_url';
UPDATE configuracion_sitio SET valor = '' WHERE clave = 'youtube_url';

-- SEO
UPDATE configuracion_sitio SET valor = 'Dream Go Operadora Turística // Viajes // Experiencias //Turismo' WHERE clave = 'meta_title_default';
UPDATE configuracion_sitio SET valor = 'Somos una agencia de viajes que amamos planear, organizar viajes y experiencias.\nTrámites fáciles, viajes seguros, para disfrutar al 100%. Documentos listos, maletas también.' WHERE clave = 'meta_description_default';

COMMIT;

-- Verificación
SELECT clave, valor, actualizado_en FROM configuracion_sitio ORDER BY clave;
