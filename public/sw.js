const CACHE_NAME = 'almadina-pos-cache-v2';
const ASSETS = [
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
    // Only cache GET requests
    if (e.request.method !== 'GET') {
        return;
    }

    const url = new URL(e.request.url);

    // Never cache API sync calls, payments webhooks/status, print triggers, login, pos, admin, kds, or live HTML
    if (
        url.pathname.includes('/api/') || 
        url.pathname.includes('/print') || 
        url.pathname.includes('/status') ||
        url.pathname === '/login' ||
        url.pathname.startsWith('/pos') ||
        url.pathname.startsWith('/admin') ||
        url.pathname.startsWith('/kds') ||
        (e.request.headers.get('accept') && e.request.headers.get('accept').includes('text/html'))
    ) {
        return;
    }

    // Cache static assets and CDN calls
    e.respondWith(
        caches.match(e.request).then((cachedResponse) => {
            if (cachedResponse) {
                return cachedResponse;
            }
            return fetch(e.request).then((res) => {
                // Check if it's a valid static resource before caching
                if (
                    res.status === 200 && 
                    (
                        url.pathname.match(/\.(js|css|png|jpg|jpeg|gif|svg|ico|woff2|woff|ttf|eot)$/) || 
                        url.host.includes('fonts.googleapis.com') ||
                        url.host.includes('fonts.gstatic.com') ||
                        url.host.includes('cdn.tailwindcss.com') ||
                        url.host.includes('cdn.jsdelivr.net')
                    )
                ) {
                    const resClone = res.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(e.request, resClone);
                    });
                }
                return res;
            });
        })
    );
});
