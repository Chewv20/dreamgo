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
  <button type="button" class="admin-sidebar-toggle" data-admin-sidebar-toggle aria-expanded="false" aria-controls="admin-sidebar">&#9776; Menu</button>

  <aside class="admin-sidebar" id="admin-sidebar" data-admin-sidebar>
    <div class="admin-sidebar__logo">
      <img src="/assets/img/logo.avif" alt="Dream Go">
    </div>
    <nav class="admin-nav">
      <a href="/admin">Panel</a>
      <?php if (Auth::hasPermission('paquetes.ver')): ?><a href="/admin/paquetes">Paquetes</a><?php endif; ?>
      <?php if (Auth::hasPermission('destinos.gestionar')): ?><a href="/admin/destinos">Destinos</a><?php endif; ?>
      <?php if (Auth::hasPermission('reservas.ver')): ?><a href="/admin/reservas">Reservas</a><?php endif; ?>
      <?php if (Auth::hasPermission('cotizaciones.ver')): ?><a href="/admin/cotizaciones">Cotizaciones</a><?php endif; ?>
      <?php if (Auth::hasPermission('resenas.ver')): ?><a href="/admin/resenas">Resenas</a><?php endif; ?>
      <?php if (Auth::hasPermission('articulos.ver')): ?><a href="/admin/articulos">Blog</a><?php endif; ?>
      <?php if (Auth::hasPermission('suscriptores.ver')): ?><a href="/admin/suscriptores">Suscriptores</a><?php endif; ?>
      <?php if (Auth::hasPermission('ofertas.gestionar')): ?><a href="/admin/ofertas">Ofertas</a><?php endif; ?>
      <?php if (Auth::hasPermission('usuarios.gestionar')): ?><a href="/admin/usuarios">Usuarios</a><?php endif; ?>
      <?php if (Auth::hasPermission('roles.gestionar')): ?><a href="/admin/roles">Roles y permisos</a><?php endif; ?>
      <?php if (Auth::hasPermission('contenido.gestionar')): ?><a href="/admin/contenido">Contenido del sitio</a><a href="/admin/colores">Colores del sitio</a><?php endif; ?>
      <?php if (Auth::hasPermission('configuracion.gestionar')): ?><a href="/admin/configuracion">Configuracion</a><?php endif; ?>
      <?php if (Auth::hasPermission('bitacora.ver')): ?><a href="/admin/bitacora">Bitacora</a><?php endif; ?>
    </nav>
    <form method="post" action="/admin/logout" class="admin-sidebar__logout">
      <?= \App\Helpers\Csrf::field() ?>
      <button type="submit" class="btn btn-secundario w-100">Cerrar sesion</button>
    </form>
  </aside>

  <div class="admin-main">
    <header class="admin-topbar">
      <h1><?= htmlspecialchars($meta['heading'] ?? ($meta['title'] ?? 'Panel'), ENT_QUOTES, 'UTF-8') ?></h1>
      <span class="admin-topbar__usuario"><?= htmlspecialchars($adminNombre ?? '', ENT_QUOTES, 'UTF-8') ?></span>
    </header>

    <?php foreach (\App\Helpers\Flash::pull() as $flash): ?>
      <div class="admin-flash admin-flash--<?= htmlspecialchars($flash['tipo'], ENT_QUOTES, 'UTF-8') ?>">
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
