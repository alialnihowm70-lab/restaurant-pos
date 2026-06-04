const CACHE_NAME = 'almadina-pos-cache-v1';
const ASSETS = [
    '/pos',
    '/kds',
    '/admin',
    'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap',
    'https://cdn.tailwindcss.com',
    'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js'
];

self.addEventListener('install', (e) => {
    e.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS);
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', (e) => {
    // Never cache API sync calls, payments webhooks/status, print triggers, or non-GET requests
    if (
        e.request.url.includes('/api/') || 
        e.request.url.includes('/print') || 
        e.request.url.includes('/status') ||
        e.request.method !== 'GET'
    ) {
        return;
    }

    e.respondWith(
        fetch(e.request)
            .then((res) => {
                // Cache a copy of the retrieved page for offline use
                const resClone = res.clone();
                caches.open(CACHE_NAME).then((cache) => {
                    cache.put(e.request, resClone);
                });
                return res;
            })
            .catch(() => {
                // Serve cached copy when network is unreachable
                return caches.match(e.request);
            })
    );
});
