// Service worker de Dream Go: cache-first para el shell estatico, network-first
// para paginas dinamicas, con una pagina offline.html como respaldo final.
const CACHE_VERSION = 'dreamgo-v1';
const SHELL_CACHE = CACHE_VERSION + '-shell';
const PAGES_CACHE = CACHE_VERSION + '-pages';

const SHELL_ASSETS = [
  '/assets/css/site.css',
  '/assets/js/site.js',
  '/assets/img/logo.avif',
  '/assets/fonts/fraunces-500.woff2',
  '/assets/fonts/fraunces-700.woff2',
  '/assets/fonts/worksans-400.woff2',
  '/assets/fonts/worksans-500.woff2',
  '/assets/fonts/worksans-600.woff2',
  '/manifest.json',
  '/offline.html',
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
  return /\/assets\//.test(url.pathname) || url.pathname === '/manifest.json';
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

  event.respondWith(
    fetch(request)
      .then(function (response) {
        const clone = response.clone();
        caches.open(PAGES_CACHE).then(function (cache) { cache.put(request, clone); });
        return response;
      })
      .catch(function () {
        return caches.match(request).then(function (cached) {
          return cached || caches.match('/offline.html');
        });
      })
  );
});
