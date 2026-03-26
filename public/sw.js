const appBase = self.location.pathname.substring(0, self.location.pathname.lastIndexOf('/') + 1);
const CACHE_NAME = 'tucombustible-v1';

const urlsToCache = [
    appBase,
    appBase + 'dashboard',
    appBase + 'viajes',
    appBase + 'viajes/create',
    appBase + 'combustible/compra/crear',
    appBase + 'combustible/flete/crear',
    appBase + 'combustible/index',
    appBase + 'combustible/pedidos',
    appBase + 'combustible/estadisticas',
    appBase + 'combustible/despacho-industrial/historial',
    appBase + 'combustible/despacho-industrial/create',
    appBase + 'ordenes',
    appBase + 'css/app.css',
    appBase + 'js/app.js',
    appBase + 'img/logomini.png',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css'
];

// Instalación Tolerante: Descarga uno por uno

self.addEventListener('install', event => {
    self.skipWaiting(); // FUERZA al SW a convertirse en el nuevo SW activo de inmediato
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return Promise.all(
                urlsToCache.map(url => {
                    return fetch(url).then(response => {
                        if (response.ok) return cache.put(url, response);
                    }).catch(err => console.warn('Falló cache:', url));
                })
            );
        })
    );
});

self.addEventListener('activate', event => {
    // Toma el control de todas las pestañas abiertas de inmediato
    event.waitUntil(clients.claim()); 
    
    // Limpieza de cachés viejos...
    event.waitUntil(
        caches.keys().then(keys => Promise.all(
            keys.map(key => (key !== CACHE_NAME) ? caches.delete(key) : null)
        ))
    );
});

// Estrategia de Fetch
self.addEventListener('fetch', event => {
    // 1. Solo peticiones HTTP/HTTPS y método GET
    if (!event.request.url.startsWith('http') || event.request.method !== 'GET') return;

    event.respondWith(
        // INTENTAR RED PRIMERO
        fetch(event.request)
            .then(networkResponse => {
                // Si la red responde bien, clonamos y guardamos/actualizamos en caché
            if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
                const responseToCache = networkResponse.clone();
                caches.open(CACHE_NAME).then(cache => {
                    cache.put(event.request, responseToCache);
                });
            }
            // Si es una redirección (302), simplemente devuélvela sin cachear
            return networkResponse;
            })
            .catch(() => {
                // SI LA RED FALLA (Offline real), BUSCAR EN CACHÉ
                return caches.match(event.request).then(response => {
                    if (response) return response;
                    
                    // Si es una página y no está en caché, enviar al dashboard/base
                    if (event.request.mode === 'navigate') {
                        return caches.match(appBase);
                    }
                });
            })
    );
});

self.addEventListener('push', function(event) {
    console.log('[Service Worker] Notificación Push recibida:', event.data.text());
    
    // CORRECCIÓN: Usar Notification.permission (con fallback por si no existe en el scope)
    const permission = self.Notification ? self.Notification.permission : 'default';
    if (permission !== 'granted') {
        console.warn('[Service Worker] Permiso de notificaciones no concedido.');
        return;
    }

    let data = {};
    try {
        data = event.data.json();
    } catch (e) {
        data = { title: 'TuCombustible', body: event.data.text(), url: appBase };
    }

    const options = {
        body: data.body || 'Tienes una nueva actualización.',
        icon: data.icon || '/img/icon-192x192.png',
        badge: '/img/logomini.png', 
        vibrate: [100, 50, 100],
        data: { 
            // Laravel envía la URL a veces dentro de data.data.url o data.url
            url: (data.data && data.data.url) ? data.data.url : (data.url || appBase) 
        }
    };

    event.waitUntil(
        self.registration.showNotification(data.title || 'TuCombustible', options)
    );
});

// Abrir el sistema al tocar la notificación
self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    event.waitUntil(clients.openWindow(event.notification.data.url));
});