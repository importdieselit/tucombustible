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
    appBase + 'inspecciones',
    appBase + 'inspecciones/create',
    appBase + 'inspecciones/index',
    appBase + 'logistica/dashboard',
    appBase + 'logistica/planificacion',
    appBase + 'logistica/crear/diesel',
    appBase + 'logistica/crear/mgo',
    appBase + 'logistica/crear/flete',
    appBase + 'logistica/crear/compra',

    
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
const DYNAMIC_CACHE_NAME = 'dynamic-cache-v1';

self.addEventListener('fetch', event => {
    const requestUrl = new URL(event.request.url);

    // Definimos el patrón Regex para detectar la URL dinámica
    // Esto hace match con: /vehiculos/inspeccion/12/salida (o entrada)
    const inspectionRoutePattern = /vehiculos\/inspeccion\/\d+\/(salida|entrada)/;

    // Si la petición coincide con la ruta dinámica de inspecciones
    if (inspectionRoutePattern.test(requestUrl.pathname)) {
        event.respondWith(
            // Estrategia: Network First (Red primero, cae en Caché si falla)
            fetch(event.request)
                .then(networkResponse => {
                    // Si la red responde bien, guardamos una copia en el caché dinámico
                    return caches.open(DYNAMIC_CACHE_NAME).then(cache => {
                        cache.put(event.request, networkResponse.clone());
                        return networkResponse;
                    });
                })
                .catch(() => {
                    // Si no hay red (offline), buscamos si ya guardamos esta URL previamente
                    return caches.match(event.request);
                })
        );
    } 
    else {
        // Estrategia por defecto para el resto de la app (ej. Cache First para assets estáticos)
        event.respondWith(
            caches.match(event.request).then(cachedResponse => {
                return cachedResponse || fetch(event.request);
            })
        );
    }
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