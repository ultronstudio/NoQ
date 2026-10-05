const CACHE_NAME = 'noq-pwa-v1';
const OFFLINE_URL = '/offline.html';
const STATIC_URLS = [
    OFFLINE_URL,
    '/assets/branding/icon-192.png',
    '/assets/branding/icon-512.png',
    '/assets/branding/icon-maskable-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(STATIC_URLS))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys
                .filter((key) => key.startsWith('noq-pwa-') && key !== CACHE_NAME)
                .map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    // Always fetch current pages. Never cache sessions, queue state, API or Livewire responses.
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(async () => {
            const cache = await caches.open(CACHE_NAME);
            return await cache.match(OFFLINE_URL) ?? Response.error();
        }));
    } else if (STATIC_URLS.includes(url.pathname) && url.search === '') {
        event.respondWith(caches.open(CACHE_NAME).then(async (cache) =>
            await cache.match(request) ?? fetch(request)));
    }
});
