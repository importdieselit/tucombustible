const TEST_JS_VERSION = "1.0.4"; //

document.addEventListener('DOMContentLoaded', function () {
    let map;
    let markers = {}; 
    let countdownInterval;
    const REFRESH_SECONDS = 5; 
    let timeLeft = REFRESH_SECONDS;
    
    const sedeCoords = L.latLng(10.488249123497356, -66.8234169941792);
    const RADIO_SEDE_METROS = 180;

    // --- ESCALADO AUTOMÁTICO PERFECTO (Como Imagen) ---
    function ajustarResolucionTV() {
        const scaler = document.getElementById('noc-scaler');
        if (!scaler) return;

        // Base 16:9 estricta
        const baseWidth = 1920;
        const baseHeight = 1080;

        const winWidth = window.innerWidth;
        const winHeight = window.innerHeight;

        const scaleX = winWidth / baseWidth;
        const scaleY = winHeight / baseHeight;
        const scale = Math.min(scaleX, scaleY);

        scaler.style.transform = `scale(${scale})`;

        // Centrado dinámico para micro-ajustes
        const marginX = (winWidth - (baseWidth * scale)) / 2;
        const marginY = (winHeight - (baseHeight * scale)) / 2;
        scaler.style.marginLeft = `${marginX}px`;
        scaler.style.marginTop = `${marginY}px`;
        
        const resDisplay = document.getElementById('div-res');
        if (resDisplay) resDisplay.textContent = `${winWidth}x${winHeight}`;
    }

    window.addEventListener('resize', ajustarResolucionTV);
    ajustarResolucionTV();

    // --- CONTINGENCIA OFFLINE ---
    window.addEventListener('offline', () => {
        document.getElementById('offline-banner').style.display = 'block';
        document.getElementById('reporte-container').style.opacity = '0.4';
    });

    window.addEventListener('online', () => {
        document.getElementById('offline-banner').style.display = 'none';
        document.getElementById('reporte-container').style.opacity = '1';
        fetchSalaData();
    });

    // --- INICIALIZACIÓN DEL MAPA ---
    function initMap() {
        map = L.map('map', { 
            attributionControl: false, fullscreenControl: false,
            zoomControl: false, scrollWheelZoom: false, dragging: false
        }).setView([10.488249123497356, -66.8234169941792], 8); 

        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png').addTo(map);
        L.circle(sedeCoords, { color: '#002d72', fillOpacity: 0.1, radius: RADIO_SEDE_METROS }).addTo(map);

        fetchSalaData(); 
        startWatchdog(); 
    }

    function startWatchdog() {
        countdownInterval = setInterval(() => {
            timeLeft--;
            const timerDisplay = document.getElementById('countdown-timer');
            if (timerDisplay) timerDisplay.textContent = timeLeft + 's';
            if (timeLeft <= 0) { fetchSalaData(); }
        }, 1000);
    }

    // --- ADQUISICIÓN DE DATOS (AJAX) ---
    async function fetchSalaData() {
        const container = document.getElementById('reporte-container');
        
        // NOC_CONFIG viene del HTML inyectado por Laravel
        const tvToken = window.NOC_CONFIG.tvToken;
        const streamUrl = window.NOC_CONFIG.streamUrl;

        try {
            const response = await fetch(streamUrl, {
                method: 'GET',
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest', 
                    'Accept': 'application/json',
                    'X-TV-Token': tvToken 
                },
                credentials: 'same-origin'
            });

            if (response.ok) {
                const data = await response.json();

                if (data.hasOwnProperty('css_version') && data.hasOwnProperty('js_version')) {
    
                    const serverCss = String(data.css_version);
                    const serverJs  = String(data.js_version);
                    const localCss  = String(window.NOC_CONFIG.cssVersion);
                    const localJs   = String(window.NOC_CONFIG.jsVersion);

                    // Solo recargamos si no son 'undefined', no son 'null', y son diferentes
                    if (serverCss !== 'undefined' && serverJs !== 'undefined') {
                        if (serverCss !== localCss || serverJs !== localJs) {
                            
                            // Imprimimos en consola exactamente por qué está recargando
                            console.warn(`🔄 RECARGA FORZADA DETECTADA:`);
                            console.warn(`CSS -> Local: ${localCss} | Servidor: ${serverCss}`);
                            console.warn(`JS  -> Local: ${localJs} | Servidor: ${serverJs}`);
                            
                            // Pausamos 2 segundos antes de recargar para que te dé tiempo de leer la consola si lo necesitas
                            setTimeout(() => {
                                window.location.reload(true);
                            }, 2000);
                            
                            return; // Detenemos la ejecución actual
                        }
                    }
                } else {
                    // Si cae aquí, significa que el Controlador PHP no está enviando las variables en el JSON
                    console.error("El servidor no envió css_version o js_version en el JSON de respuesta.");
                }

                container.innerHTML = data.html_dashboard;
                
                setTimeout(() => {
                     const lastSync = document.getElementById('last-sync-time');
                     if (lastSync) lastSync.textContent = new Date().toLocaleTimeString();
                 }, 5000);

                updateMapMarkers(data.unidades);
                timeLeft = REFRESH_SECONDS; 
            }
        } catch (error) {
            console.error('Error de comunicación NOC:', error);
        }
    }

    // --- ACTUALIZACIÓN DEL MAPA ---
    function updateMapMarkers(unidades) {
        const currentIds = unidades.map(u => u.id);
        const group = L.featureGroup();

        unidades.forEach(u => {
            const newLatLng = [u.latitud, u.longitud];
            const colorStatus = obtenerColorPorEstatus(u.estatus);
            const contentTooltip = generarHtmlTooltip(u);
            const mostrarSiempre = (u.estatus > 2 || L.latLng(newLatLng).distanceTo(sedeCoords) > RADIO_SEDE_METROS);

            if (markers[u.id]) {
                markers[u.id].setLatLng(newLatLng);
                markers[u.id].setIcon(generarIcono(colorStatus));
                markers[u.id].getTooltip().setContent(contentTooltip);
                if (mostrarSiempre) { markers[u.id].openTooltip(); } else { markers[u.id].closeTooltip(); }
            } else {
                const m = L.marker(newLatLng, { icon: generarIcono(colorStatus) })
                    .bindTooltip(contentTooltip, { direction: 'top', offset: [0, -15], className: 'custom-tooltip', permanent: mostrarSiempre });
                m.addTo(map);
                markers[u.id] = m;
            }
            group.addLayer(markers[u.id]);
        });

        Object.keys(markers).forEach(id => {
            if (!currentIds.includes(parseInt(id))) {
                map.removeLayer(markers[id]);
                delete markers[id];
            }
        });

        if (group.getLayers().length > 0) {
            map.fitBounds(group.getBounds(), { padding: [80, 80], maxZoom: 14, animate: true, duration: 2.0 });
        }
    }

    function obtenerColorPorEstatus(estatus) {
        if(estatus == 2) return '#ff6600'; 
        if(estatus == 3 || estatus == 4 || estatus == 5) return '#e74a3b'; 
        return '#002d72'; 
    }

    function generarIcono(color) {
        return L.divIcon({
            html: `<div class="map-marker-container" style="border-color: ${color}">
                    <div class="marker-pulse" style="border-color: ${color}"></div>
                    <i class="fa-solid fa-truck-moving" style="color: ${color}; font-size: 16px;"></i>
                </div>`,
            iconSize: [40, 40], className: 'custom-marker'
        });
    }

    function generarHtmlTooltip(u) {
        let tiempo = 'Justo ahora';
        if(u.updated_at) {
            const diffMin = Math.floor((new Date() - new Date(u.updated_at)) / 60000);
            tiempo = diffMin < 1 ? 'Hace segundos' : `Hace ${diffMin} min`;
        }
        return `<div class="tooltip-content">
                    <b>${u.placa || 'S/P'}</b><br>
                    <small>${u.is_marca?.marca || ''}</small><br>
                    <small style="opacity:0.85;"><i class="far fa-clock"></i> ${tiempo}</small>
                </div>`;
    }

    initMap();

    // --- WATCHDOG ACTUALIZADOR APK ---
    async function verificarActualizacionNOC() {
        const versionActualAPK = window.CURRENT_APK_VERSION || 0;
        console.log("Versión actual de la APK en esta TV:", versionActualAPK);

        if (versionActualAPK === 0) {
            console.log("[Watchdog] Ejecutándose en modo web/desarrollo.");
            return;
        }

        try {
            const response = await fetch('/api/apk/latest');
            const servidor = await response.json();

            if (servidor.latest_version > versionActualAPK) {
                console.log(`Nueva versión disponible: ${servidor.version_name}.`);
                if (window.flutter_inappwebview) {
                    window.flutter_inappwebview.callHandler('AndroidInterface', servidor.apk_url);
                }
            }
        } catch (error) {
            console.error("Error al procesar la actualización:", error);
        }
    }

    setTimeout(verificarActualizacionNOC, 5000);
    setInterval(verificarActualizacionNOC, 1000 * 60 * 60);
});

document.addEventListener('DOMContentLoaded', () => {
    // Evitamos duplicados si el script se llega a ejecutar dos veces
    if (document.getElementById('debug-js-version')) return;

    const debugDiv = document.createElement('div');
    debugDiv.id = 'debug-js-version';
    debugDiv.style.position = 'absolute';
    debugDiv.style.top = '15px';
    debugDiv.style.left = '15px';
    debugDiv.style.background = '#000000';
    debugDiv.style.color = '#00ff00'; // Verde terminal
    debugDiv.style.zIndex = '999999';
    debugDiv.style.padding = '8px 15px';
    debugDiv.style.border = '2px solid #00ff00';
    debugDiv.style.borderRadius = '8px';
    debugDiv.style.fontWeight = 'bold';
    debugDiv.style.fontFamily = 'monospace';
    
    debugDiv.innerHTML = '⚙️ JS VERSION: ' + TEST_JS_VERSION;
    document.body.appendChild(debugDiv);
    
    console.log("El script JS cargado es la versión: " + TEST_JS_VERSION);
});