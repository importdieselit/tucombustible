const appBase = self.location.pathname.substring(0, self.location.pathname.lastIndexOf('/') + 1);

const CACHE_NAME = 'tucombustible-v1';

// 1. Lista de recursos esenciales (Assets y Rutas)
// Agrega aquí las URLs de las páginas que más usas
const urlsToCache = [
    appBase,
    appBase + 'dashboard',
    appBase + 'viajes',
    appBase + 'viajes/create',
    appBase + 'combustible/compra/crear',
    appBase + 'combustible/flete/crear',
    appBase + 'combustible/create',
    appBase + 'mantenimiento',
    appBase + 'css/app.css',
    appBase + 'js/app.js',
    appBase + 'img/logomini.png',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css'
];

// Instalación: Descarga TODO el sitio base para uso offline
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            console.log('Cache abierto: Descargando sitio para uso offline');
            return cache.addAll(urlsToCache);
        })
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

// Esto hace que la navegación sea instantánea porque lee del disco, no de internet
self.addEventListener('fetch', event => {
    // Solo manejamos peticiones GET (Navegación y recursos)
    if (event.request.method !== 'GET') return;

    event.respondWith(
        caches.match(event.request).then(response => {
            // Si está en caché, lo devuelve inmediatamente
            if (response) {
                return response;
            }

            // Si no está, intenta ir a la red y lo guarda en caché para la próxima vez
            return fetch(event.request).then(networkResponse => {
                if (!networkResponse || networkResponse.status !== 200) {
                    return networkResponse;
                }
                
                const responseToCache = networkResponse.clone();
                caches.open(CACHE_NAME).then(cache => {
                    cache.put(event.request, responseToCache);
                });
                
                return networkResponse;
            }).catch(() => {
                // Si falla la red y no hay caché (ej. página nueva), 
                // podrías devolver una página personalizada de "Offline"
                return caches.match('/');
            });
        })
    );
});