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
    appBase + 'ordenes',
    appBase + 'ordenes/create',
    appBase + 'css/app.css',
    appBase + 'js/app.js',
    appBase + 'img/logomini.png',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css'
];

// Instalación Tolerante: Descarga uno por uno
self.addEventListener('install', event => {
    //console.log('SW: Iniciando instalación...');
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            // En lugar de addAll, mapeamos cada URL a una promesa individual
            return Promise.all(
                urlsToCache.map(url => {
                    return fetch(url)
                        .then(response => {
                            if (response.ok) {
                                return cache.put(url, response);
                            }
                            throw new TypeError('Error al cargar recurso: ' + url);
                        })
                        .catch(err => console.warn('SW: No se pudo cachear:', url, err));
                })
            );
        })
    );
});

// Activación: Limpia cachés antiguos
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

// Estrategia de Fetch

self.addEventListener('fetch', event => {
    // 1. FILTRO DE SEGURIDAD: Solo procesar peticiones HTTP o HTTPS
    // Esto ignora las extensiones de Chrome (chrome-extension://) y evita el error
    if (!event.request.url.startsWith('http')) return;

    // 2. Solo manejamos peticiones GET para el caché de navegación
    if (event.request.method !== 'GET') return;

    event.respondWith(
        caches.match(event.request).then(response => {
            // Si está en caché, lo devuelve inmediatamente
            if (response) return response;

            // Si no está, intenta ir a la red
            return fetch(event.request).then(networkResponse => {
                // Solo cacheamos respuestas válidas del servidor
                if (!networkResponse || networkResponse.status !== 200 || networkResponse.type !== 'basic') {
                    return networkResponse;
                }
                
                const responseToCache = networkResponse.clone();
                caches.open(CACHE_NAME).then(cache => {
                    cache.put(event.request, responseToCache);
                });
                
                return networkResponse;
            }).catch(() => {
                // Si falla la red y es una navegación, enviamos a la base (offline)
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
        tag: 'orden-notificacion', // <--- Agrupa notificaciones
        renotify: true,
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