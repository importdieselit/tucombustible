const CACHE_NAME = 'tucombustible-v1';
// Archivos críticos para que la App abra sin internet
const urlsToCache = [
    '/',
    '/css/app.css',
    '/js/app.js',
    '/img/logomini.png',
    '/img/icon-192x192.png'
];

// Instalación: Guarda los archivos en el caché del navegador
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(urlsToCache))
    );
});

// Activación: Limpia cachés antiguos si actualizas la versión
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// Estrategia: Network First (Busca en internet, si falla, usa el caché)
// Ideal para sistemas de gestión donde la data cambia constantemente
self.addEventListener('fetch', event => {
    event.respondWith(
        fetch(event.request).catch(() => {
            return caches.match(event.request);
        })
    );
});