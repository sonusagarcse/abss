const CACHE_VERSION = 'v1.0.5';
const CACHE_NAME = `app-${CACHE_VERSION}`;
const PRE_CACHE_RESOURCES = [
  '/abss/app/index.php',
  '/abss/app/manifest.json',
  '/abss/app/version.json'
];
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return Promise.all(PRE_CACHE_RESOURCES.map(url => fetch(url, { cache: 'no-store' }).then(res => cache.put(url, res)).catch(() => { })));
    })
  );
  self.skipWaiting();
});
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => Promise.all(
      cacheNames.filter(name => name.startsWith('app-') && name !== CACHE_NAME).map(name => caches.delete(name))
    )).then(() => self.clients.claim())
  );
});
self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);
  if (url.origin !== self.location.origin || url.pathname.includes('/api/')) return;
  event.respondWith(
    caches.match(event.request).then((cached) => {
      const networkFetch = fetch(event.request).then((networkRes) => {
        const clone = networkRes.clone();
        caches.open(CACHE_NAME).then((c) => c.put(event.request, clone));
        return networkRes;
      }).catch(() => { });
      return cached || networkFetch;
    })
  );
});
