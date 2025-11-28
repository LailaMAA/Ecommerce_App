// Service Worker for e-commerce site
const CACHE_NAME = 'ecommerce-v1';
const ASSETS_TO_CACHE = [
  '/workspace/',
  '/workspace/assets/css/style.css',
  '/workspace/assets/js/main.js'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(ASSETS_TO_CACHE);
      })
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    Promise.all([
      self.clients.claim(),
      caches.keys().then(cacheNames => {
        return Promise.all(
          cacheNames
            .filter(cacheName => cacheName !== CACHE_NAME)
            .map(cacheName => caches.delete(cacheName))
        );
      })
    ])
  );
});

self.addEventListener('fetch', (event) => {
  // Network first, fallback to cache
  event.respondWith(
    fetch(event.request)
      .catch(() => {
        return caches.match(event.request);
      })
  );
});
