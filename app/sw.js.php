<?php
header("Content-Type: application/javascript");
header("Service-Worker-Allowed: /abss/");
?>
const CACHE_NAME = 'abss-portal-v4';
const ASSETS_TO_CACHE = [
  '/abss/index.php',
  '/abss/app_home.php',
  '/abss/css/style.css',
  '/abss/app/manifest.json',
  '/abss/app/icons/icon-192x192.png',
  '/abss/app/icons/icon-512x512.png'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(ASSETS_TO_CACHE);
      })
  );
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.filter(name => name !== CACHE_NAME)
          .map(name => caches.delete(name))
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', event => {
  // We don't want to aggressively cache everything for a dynamic portal.
  // We will do a Network-First strategy with Cache Fallback for offline mode.
  if (event.request.method !== 'GET') {
      return;
  }
  
  event.respondWith(
    fetch(event.request)
      .then(response => {
        // Optionally cache the new response here if needed, but for now we just return it
        return response;
      })
      .catch(() => {
        // If network fails (offline), return from cache
        return caches.match(event.request);
      })
  );
});
