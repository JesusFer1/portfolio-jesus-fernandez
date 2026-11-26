// Service Worker PWA - Moda Circular & Útiles Escolares Técnica 5
const CACHE_NAME = 'modacircular-v2';
const STATIC_ASSETS = [
  './',
  './index.html',
  './foro.html',
  './manifest.json',
  './css/custom.css',
  './imagenes/logo.jfif',
  './imagenes/icon-192.png',
  './imagenes/icon-512.png'
];

// 1. INSTALACIÓN: Cachear recursos base
self.addEventListener('install', (event) => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(STATIC_ASSETS).catch((err) => {
        console.warn('Aviso caché PWA:', err);
      });
    })
  );
});

// 2. ACTIVACIÓN: Limpiar versiones viejas
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
      );
    })
  );
  self.clients.claim();
});

// 3. FETCH: Network-Only para PHP/API (tiempo real) y Network-First para estáticos
self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;

  const url = new URL(event.request.url);

  // Todo lo que sea API PHP va en vivo sin caché para garantizar integridad de stock y QR
  if (url.pathname.includes('/api/') || url.pathname.endsWith('.php')) {
    return;
  }

  // Recursos estáticos con soporte offline
  event.respondWith(
    fetch(event.request)
      .then((networkResponse) => {
        if (networkResponse && networkResponse.status === 200) {
          const responseToCache = networkResponse.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(event.request, responseToCache);
          });
        }
        return networkResponse;
      })
      .catch(() => {
        return caches.match(event.request).then((cachedResponse) => {
          if (cachedResponse) return cachedResponse;
          if (event.request.headers.get('accept')?.includes('text/html')) {
            return caches.match('./index.html');
          }
        });
      })
  );
});
