// Service worker de Dream Go: cache-first para el shell estatico, network-first
// para paginas dinamicas, con una pagina offline.html como respaldo final.
// CACHE_VERSION: sube este numero cada vez que cambie SHELL_ASSETS o la logica
// de este archivo, para que los navegadores con una version vieja instalada
// descarten su cache automaticamente.
const CACHE_VERSION = 'dreamgo-v7';
const SHELL_CACHE = CACHE_VERSION + '-shell';
const PAGES_CACHE = CACHE_VERSION + '-pages';

// Ruta base donde vive este service worker (ej. "/dreamgo/public/" en local,
// "/" en produccion). Se calcula en tiempo de ejecucion para que el mismo
// archivo funcione sin importar la subcarpeta desde la que se sirva el sitio.
const BASE = self.location.pathname.replace(/sw\.js$/, '');

const SHELL_ASSETS = [
  BASE + 'assets/css/site.css',
  BASE + 'assets/js/site.js',
  BASE + 'assets/img/logo.avif',
  BASE + 'assets/fonts/fraunces-500.woff2',
  BASE + 'assets/fonts/fraunces-700.woff2',
  BASE + 'assets/fonts/worksans-400.woff2',
  BASE + 'assets/fonts/worksans-500.woff2',
  BASE + 'assets/fonts/worksans-600.woff2',
  BASE + 'manifest.json',
  BASE + 'offline.html',
];

self.addEventListener('install', function (event) {
  event.waitUntil(
    caches.open(SHELL_CACHE).then(function (cache) {
      return cache.addAll(SHELL_ASSETS);
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', function (event) {
  event.waitUntil(
    caches.keys().then(function (keys) {
      return Promise.all(
        keys
          .filter(function (key) { return key.indexOf(CACHE_VERSION) !== 0; })
          .map(function (key) { return caches.delete(key); })
      );
    })
  );
  self.clients.claim();
});

function esAssetEstatico(url) {
  return /\/assets\//.test(url.pathname) || url.pathname === BASE + 'manifest.json';
}

// Auditoria 2026-08-25, hallazgo CFG-01: el scope del service worker es todo el origen
// (document root = public/), asi que sin esta exclusion el panel admin (datos de clientes,
// reservas) quedaba cacheado en el navegador igual que cualquier pagina publica, recuperable
// desde Cache Storage aunque la sesion ya haya cerrado.
function esRutaAdmin(url) {
  return url.pathname.indexOf(BASE + 'admin') === 0;
}

self.addEventListener('fetch', function (event) {
  const request = event.request;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  if (esAssetEstatico(url)) {
    event.respondWith(
      caches.match(request).then(function (cached) {
        return cached || fetch(request).then(function (response) {
          const clone = response.clone();
          caches.open(SHELL_CACHE).then(function (cache) { cache.put(request, clone); });
          return response;
        });
      })
    );
    return;
  }

  if (esRutaAdmin(url)) {
    // Network-only, sin cache.put: nunca debe quedar una copia de una pagina del panel en
    // Cache Storage. Si la red falla, se deja fallar la peticion tal cual en vez de mostrar
    // una version cacheada (no deberia haber ninguna) u offline.html (que confundiria mas
    // que ayudar dentro del panel).
    event.respondWith(fetch(request));
    return;
  }

  event.respondWith(
    fetch(request)
      .then(function (response) {
        const clone = response.clone();
        caches.open(PAGES_CACHE).then(function (cache) { cache.put(request, clone); });
        return response;
      })
      .catch(function () {
        return caches.match(request).then(function (cached) {
          return cached || caches.match(BASE + 'offline.html');
        });
      })
  );
});
