const CACHE_PREFIX = 'dera-editorial-static-';
const CACHE_VERSION = '20260925-1';
const STATIC_CACHE = CACHE_PREFIX + CACHE_VERSION;
const OFFLINE_URL = '/offline.html';
const PRECACHE_URLS = [
  OFFLINE_URL,
  '/manifest.webmanifest',
  '/templates/mpx/img/logo/editorial-app-icon.svg',
  '/templates/mpx/img/logo/favicon.png',
  '/templates/mpx/css/editorial-design-tokens.css',
  '/templates/mpx/css/literature-redesign.css',
  '/templates/mpx/css/editorial-components.css',
  '/templates/mpx/js/editorial-ui.js'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE).then((cache) =>
      Promise.all(PRECACHE_URLS.map((url) => cache.add(url).catch(() => null)))
    )
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(
        keys
          .filter((key) => key.startsWith(CACHE_PREFIX) && key !== STATIC_CACHE)
          .map((key) => caches.delete(key))
      ))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  if (request.mode === 'navigate') {
    event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
    return;
  }

  if (!url.pathname.startsWith('/templates/mpx/')) return;

  event.respondWith(
    caches.match(request).then((cached) => {
      if (cached) return cached;
      return fetch(request).then((response) => {
        if (!response || !response.ok || response.type !== 'basic') return response;
        const copy = response.clone();
        caches.open(STATIC_CACHE).then((cache) => cache.put(request, copy));
        return response;
      });
    })
  );
});
