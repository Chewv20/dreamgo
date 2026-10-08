<?php use Core\Auth; ?>
<!doctype html>
<html lang="es-MX">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($meta['title'] ?? 'Panel administrativo | Dream Go', ENT_QUOTES, 'UTF-8') ?></title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="/assets/icons/icon-96.png" type="image/png">
<link rel="stylesheet" href="<?= htmlspecialchars(\App\Helpers\Asset::url('/assets/css/site.css'), ENT_QUOTES, 'UTF-8') ?>">
<link rel="stylesheet" href="<?= htmlspecialchars(\App\Helpers\Asset::url('/assets/css/admin.css'), ENT_QUOTES, 'UTF-8') ?>">
<script src="<?= htmlspecialchars(\App\Helpers\Asset::url('/assets/js/admin.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>
</head>
<body class="admin-body">
<a href="#contenido-admin" class="sr-only-focusable">Saltar al contenido</a>
<div class="admin-layout">
  <button type="button" class="admin-sidebar-toggle" data-admin-sidebar-toggle aria-expanded="false" aria-controls="admin-sidebar">&#9776; Menú</button>

  <aside class="admin-sidebar" id="admin-sidebar" data-admin-sidebar>
    <div class="admin-sidebar__logo">
      <img src="/assets/img/logo.avif" alt="Dream Go">
    </div>
    <?php
    $rutaActual = $this->request->uri();
    // [etiqueta del grupo|null, [[ruta, texto, permiso|null, rutas extra que la marcan activa]]]
    $gruposNav = [
        [null, [['/admin', 'Panel', null, []]]],
        ['Ventas', [
            ['/admin/reservas', 'Reservas', 'reservas.ver', []],
            ['/admin/cotizaciones', 'Cotizaciones', 'cotizaciones.ver', []],
        ]],
        ['Catálogo', [
            ['/admin/paquetes', 'Paquetes', 'paquetes.ver', []],
            ['/admin/destinos', 'Destinos', 'destinos.gestionar', []],
        ]],
        ['Marketing', [
            ['/admin/ofertas', 'Ofertas', 'ofertas.gestionar', []],
            ['/admin/modales', 'Modales', 'modales.gestionar', []],
            ['/admin/suscriptores', 'Suscriptores', 'suscriptores.ver', []],
            ['/admin/resenas', 'Reseñas', 'resenas.ver', []],
            ['/admin/articulos', 'Blog', 'articulos.ver', []],
        ]],
        ['Sitio', [
            ['/admin/contenido', 'Contenido y colores', 'contenido.gestionar', ['/admin/colores']],
            ['/admin/configuracion', 'Configuración', 'configuracion.gestionar', []],
        ]],
        ['Sistema', [
            ['/admin/usuarios', 'Usuarios', 'usuarios.gestionar', []],
            ['/admin/roles', 'Roles y permisos', 'roles.gestionar', []],
            ['/admin/bitacora', 'Bitácora', 'bitacora.ver', []],
        ]],
    ];
    $esActiva = static function (string $ruta, array $extra) use ($rutaActual): bool {
        if ($ruta === '/admin') {
            return $rutaActual === '/admin';
        }
        foreach (array_merge([$ruta], $extra) as $r) {
            if ($rutaActual === $r || str_starts_with($rutaActual, $r . '/')) {
                return true;
            }
        }
        return false;
    };
    ?>
    <nav class="admin-nav" aria-label="Secciones del panel">
      <?php foreach ($gruposNav as [$etiqueta, $enlaces]): ?>
        <?php $visibles = array_filter($enlaces, static fn (array $e): bool => $e[2] === null || Auth::hasPermission($e[2])); ?>
        <?php if ($visibles === []) { continue; } ?>
        <?php if ($etiqueta !== null): ?><p class="admin-nav__grupo"><?= $etiqueta ?></p><?php endif; ?>
        <?php foreach ($visibles as [$ruta, $texto, , $extra]): ?>
          <?php $activa = $esActiva($ruta, $extra); ?>
          <a href="<?= $ruta ?>"<?= $activa ? ' class="is-active" aria-current="page"' : '' ?>><?= $texto ?></a>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </nav>
    <div class="admin-sidebar__pie">
      <a href="/" target="_blank" rel="noopener" class="admin-sidebar__enlace">Ver sitio <span aria-hidden="true">↗</span></a>
      <form method="post" action="/admin/logout">
        <?= \App\Helpers\Csrf::field() ?>
        <button type="submit" class="admin-sidebar__enlace">Cerrar sesión</button>
      </form>
    </div>
  </aside>

  <div class="admin-main">
    <header class="admin-topbar">
      <h1><?= htmlspecialchars($meta['heading'] ?? ($meta['title'] ?? 'Panel'), ENT_QUOTES, 'UTF-8') ?></h1>
      <span class="admin-topbar__usuario"><?= htmlspecialchars($adminNombre ?? '', ENT_QUOTES, 'UTF-8') ?></span>
    </header>

    <?php foreach (\App\Helpers\Flash::pull() as $flash): ?>
      <div class="admin-flash admin-flash--<?= htmlspecialchars($flash['tipo'], ENT_QUOTES, 'UTF-8') ?>" role="<?= $flash['tipo'] === 'error' ? 'alert' : 'status' ?>">
        <?= htmlspecialchars($flash['mensaje'], ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endforeach; ?>

    <main class="admin-content" id="contenido-admin">
      <?= $content ?>
    </main>
  </div>
</div>

</body>
</html>
