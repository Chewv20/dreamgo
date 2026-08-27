<?php

declare(strict_types=1);

/** @var Core\Router $router */

use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\ConfiguracionController;
use App\Controllers\Admin\ContenidoController;
use App\Controllers\Admin\CotizacionAdminController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\OfertaAdminController;
use App\Controllers\Admin\PaqueteAdminController;
use App\Controllers\Admin\ResenaAdminController;
use App\Controllers\Admin\ReservaAdminController;
use App\Controllers\Admin\RolAdminController;
use App\Controllers\Admin\SalidaAdminController;
use App\Controllers\Admin\SuscriptorAdminController;
use App\Controllers\Admin\UsuarioAdminController;
use App\Controllers\Public\ComprobanteReservaController;
use App\Controllers\Public\ContactoController;
use App\Controllers\Public\CotizadorController;
use App\Controllers\Public\DestinoController;
use App\Controllers\Public\HomeController;
use App\Controllers\Public\MercadoPagoWebhookController;
use App\Controllers\Public\NosotrosController;
use App\Controllers\Public\PagoSaldoController;
use App\Controllers\Public\PaqueteController;
use App\Controllers\Public\ResenaPublicaController;
use App\Controllers\Public\ReservaConsultaController;
use App\Controllers\Public\ReservaPublicaController;
use App\Controllers\Public\SuscripcionController;

// =========================================================
// SITIO PUBLICO
// =========================================================
$router->get('/', [HomeController::class, 'index']);

$router->get('/destinos', [DestinoController::class, 'index']);
$router->get('/destinos/{slug}', [DestinoController::class, 'mostrar']);

$router->get('/paquetes', [PaqueteController::class, 'catalogo']);
$router->get('/paquetes/{slug}', [PaqueteController::class, 'ficha']);
$router->get('/comparar', [PaqueteController::class, 'comparar']);

$router->get('/cotizador', [CotizadorController::class, 'mostrar']);
$router->post('/cotizador', [CotizadorController::class, 'enviar']);

$router->get('/nosotros', [NosotrosController::class, 'index']);
$router->get('/contacto', [ContactoController::class, 'index']);

$router->get('/mi-reserva', [ReservaConsultaController::class, 'mostrar']);
$router->post('/mi-reserva', [ReservaConsultaController::class, 'buscar']);
$router->get('/reserva/{codigo}/comprobante', [ComprobanteReservaController::class, 'descargar']);
$router->get('/reserva/{codigo}/pagar-saldo', [PagoSaldoController::class, 'mostrar']);
$router->post('/reserva/{codigo}/pagar-saldo', [PagoSaldoController::class, 'iniciar']);

$router->get('/resena/{codigo}', [ResenaPublicaController::class, 'formulario']);
$router->post('/resena/{codigo}', [ResenaPublicaController::class, 'guardar']);

$router->post('/suscribir', [SuscripcionController::class, 'suscribir']);
$router->get('/suscribir/confirmar/{token}', [SuscripcionController::class, 'confirmar']);
$router->get('/suscribir/baja/{token}', [SuscripcionController::class, 'baja']);

$router->get('/paquetes/{slug}/reservar/{salidaId}', [ReservaPublicaController::class, 'formulario']);
$router->post('/reservar', [ReservaPublicaController::class, 'crear']);
$router->get('/reservar/{codigo}/gracias', [ReservaPublicaController::class, 'gracias']);

$router->post('/webhooks/mercadopago', [MercadoPagoWebhookController::class, 'notificar']);

// =========================================================
// PANEL ADMINISTRATIVO
// =========================================================
$router->get('/admin/login', [AuthController::class, 'mostrarLogin']);
$router->post('/admin/login', [AuthController::class, 'login']);
$router->post('/admin/logout', [AuthController::class, 'logout'], ['auth' => true]);

$router->get('/admin/cambiar-password', [AuthController::class, 'cambiarPasswordForm'], ['auth' => true]);
$router->post('/admin/cambiar-password', [AuthController::class, 'cambiarPassword'], ['auth' => true]);

$router->get('/admin', [DashboardController::class, 'index'], ['auth' => true]);

$router->get('/admin/paquetes', [PaqueteAdminController::class, 'index'], ['auth' => true, 'permiso' => 'paquetes.ver']);
$router->get('/admin/paquetes/crear', [PaqueteAdminController::class, 'crearForm'], ['auth' => true, 'permiso' => 'paquetes.crear']);
$router->post('/admin/paquetes', [PaqueteAdminController::class, 'crear'], ['auth' => true, 'permiso' => 'paquetes.crear']);
$router->get('/admin/paquetes/{id}/editar', [PaqueteAdminController::class, 'editarForm'], ['auth' => true, 'permiso' => 'paquetes.editar']);
$router->post('/admin/paquetes/{id}/editar', [PaqueteAdminController::class, 'editar'], ['auth' => true, 'permiso' => 'paquetes.editar']);
$router->post('/admin/paquetes/{id}/archivar', [PaqueteAdminController::class, 'archivar'], ['auth' => true, 'permiso' => 'paquetes.eliminar']);

$router->get('/admin/paquetes/{paqueteId}/salidas', [SalidaAdminController::class, 'index'], ['auth' => true, 'permiso' => 'salidas.gestionar']);
$router->get('/admin/paquetes/{paqueteId}/salidas/crear', [SalidaAdminController::class, 'crearForm'], ['auth' => true, 'permiso' => 'salidas.gestionar']);
$router->post('/admin/paquetes/{paqueteId}/salidas', [SalidaAdminController::class, 'crear'], ['auth' => true, 'permiso' => 'salidas.gestionar']);
$router->get('/admin/paquetes/{paqueteId}/salidas/{id}/editar', [SalidaAdminController::class, 'editarForm'], ['auth' => true, 'permiso' => 'salidas.gestionar']);
$router->post('/admin/paquetes/{paqueteId}/salidas/{id}/editar', [SalidaAdminController::class, 'editar'], ['auth' => true, 'permiso' => 'salidas.gestionar']);

$router->get('/admin/reservas', [ReservaAdminController::class, 'index'], ['auth' => true, 'permiso' => 'reservas.ver']);
$router->get('/admin/reservas/calendario', [ReservaAdminController::class, 'calendario'], ['auth' => true, 'permiso' => 'reservas.ver']);
$router->get('/admin/reservas/calendario/datos', [ReservaAdminController::class, 'calendarioDatos'], ['auth' => true, 'permiso' => 'reservas.ver']);
$router->get('/admin/reservas/crear', [ReservaAdminController::class, 'crearForm'], ['auth' => true, 'permiso' => 'reservas.crear']);
$router->post('/admin/reservas', [ReservaAdminController::class, 'crear'], ['auth' => true, 'permiso' => 'reservas.crear']);
$router->get('/admin/reservas/exportar', [ReservaAdminController::class, 'exportarCsv'], ['auth' => true, 'permiso' => 'reservas.ver']);
$router->get('/admin/reservas/{id}', [ReservaAdminController::class, 'detalle'], ['auth' => true, 'permiso' => 'reservas.ver']);
$router->get('/admin/reservas/{id}/comprobante', [ReservaAdminController::class, 'comprobante'], ['auth' => true, 'permiso' => 'reservas.ver']);
$router->post('/admin/reservas/{id}/confirmar', [ReservaAdminController::class, 'confirmar'], ['auth' => true, 'permiso' => 'reservas.confirmar']);
$router->post('/admin/reservas/{id}/cancelar', [ReservaAdminController::class, 'cancelar'], ['auth' => true, 'permiso' => 'reservas.cancelar']);

$router->get('/admin/cotizaciones', [CotizacionAdminController::class, 'index'], ['auth' => true, 'permiso' => 'cotizaciones.ver']);
$router->get('/admin/cotizaciones/exportar', [CotizacionAdminController::class, 'exportarCsv'], ['auth' => true, 'permiso' => 'cotizaciones.ver']);
$router->post('/admin/cotizaciones/{id}/estado', [CotizacionAdminController::class, 'cambiarEstado'], ['auth' => true, 'permiso' => 'cotizaciones.gestionar']);

$router->get('/admin/resenas', [ResenaAdminController::class, 'index'], ['auth' => true, 'permiso' => 'resenas.ver']);
$router->post('/admin/resenas/{id}/estado', [ResenaAdminController::class, 'cambiarEstado'], ['auth' => true, 'permiso' => 'resenas.gestionar']);

$router->get('/admin/ofertas', [OfertaAdminController::class, 'index'], ['auth' => true, 'permiso' => 'ofertas.gestionar']);
$router->get('/admin/ofertas/crear', [OfertaAdminController::class, 'crearForm'], ['auth' => true, 'permiso' => 'ofertas.gestionar']);
$router->post('/admin/ofertas', [OfertaAdminController::class, 'crear'], ['auth' => true, 'permiso' => 'ofertas.gestionar']);
$router->get('/admin/ofertas/{id}/editar', [OfertaAdminController::class, 'editarForm'], ['auth' => true, 'permiso' => 'ofertas.gestionar']);
$router->post('/admin/ofertas/{id}/editar', [OfertaAdminController::class, 'editar'], ['auth' => true, 'permiso' => 'ofertas.gestionar']);
$router->post('/admin/ofertas/{id}/desactivar', [OfertaAdminController::class, 'desactivar'], ['auth' => true, 'permiso' => 'ofertas.gestionar']);
$router->post('/admin/ofertas/{id}/enviar-suscriptores', [OfertaAdminController::class, 'enviarSuscriptores'], ['auth' => true, 'permiso' => 'ofertas.gestionar']);

$router->get('/admin/suscriptores', [SuscriptorAdminController::class, 'index'], ['auth' => true, 'permiso' => 'suscriptores.ver']);
$router->get('/admin/suscriptores/exportar', [SuscriptorAdminController::class, 'exportarCsv'], ['auth' => true, 'permiso' => 'suscriptores.ver']);

$router->get('/admin/usuarios', [UsuarioAdminController::class, 'index'], ['auth' => true, 'permiso' => 'usuarios.gestionar']);
$router->get('/admin/usuarios/crear', [UsuarioAdminController::class, 'crearForm'], ['auth' => true, 'permiso' => 'usuarios.gestionar']);
$router->post('/admin/usuarios', [UsuarioAdminController::class, 'crear'], ['auth' => true, 'permiso' => 'usuarios.gestionar']);
$router->get('/admin/usuarios/{id}/editar', [UsuarioAdminController::class, 'editarForm'], ['auth' => true, 'permiso' => 'usuarios.gestionar']);
$router->post('/admin/usuarios/{id}/editar', [UsuarioAdminController::class, 'editar'], ['auth' => true, 'permiso' => 'usuarios.gestionar']);

$router->get('/admin/roles', [RolAdminController::class, 'index'], ['auth' => true, 'permiso' => 'roles.gestionar']);
$router->post('/admin/roles', [RolAdminController::class, 'crear'], ['auth' => true, 'permiso' => 'roles.gestionar']);
$router->post('/admin/roles/{id}/eliminar', [RolAdminController::class, 'eliminar'], ['auth' => true, 'permiso' => 'roles.gestionar']);
$router->post('/admin/roles/matriz', [RolAdminController::class, 'guardarMatriz'], ['auth' => true, 'permiso' => 'roles.gestionar']);

$router->get('/admin/configuracion', [ConfiguracionController::class, 'index'], ['auth' => true, 'permiso' => 'configuracion.gestionar']);
$router->post('/admin/configuracion', [ConfiguracionController::class, 'guardar'], ['auth' => true, 'permiso' => 'configuracion.gestionar']);

$router->get('/admin/contenido', [ContenidoController::class, 'index'], ['auth' => true, 'permiso' => 'contenido.gestionar']);
$router->get('/admin/contenido/{pagina}', [ContenidoController::class, 'pagina'], ['auth' => true, 'permiso' => 'contenido.gestionar']);
$router->post('/admin/contenido/{id}/visible', [ContenidoController::class, 'alternarVisible'], ['auth' => true, 'permiso' => 'contenido.gestionar']);
$router->post('/admin/contenido/{id}/mover', [ContenidoController::class, 'mover'], ['auth' => true, 'permiso' => 'contenido.gestionar']);
$router->get('/admin/contenido/{id}/editar', [ContenidoController::class, 'editarForm'], ['auth' => true, 'permiso' => 'contenido.gestionar']);
$router->post('/admin/contenido/{id}/editar', [ContenidoController::class, 'editar'], ['auth' => true, 'permiso' => 'contenido.gestionar']);

$router->get('/admin/colores', [ContenidoController::class, 'colores'], ['auth' => true, 'permiso' => 'contenido.gestionar']);
$router->post('/admin/colores', [ContenidoController::class, 'guardarColores'], ['auth' => true, 'permiso' => 'contenido.gestionar']);
