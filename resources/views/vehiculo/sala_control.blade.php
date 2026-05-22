@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="https://unpkg.com/leaflet-fullscreen@1.0.2/dist/leaflet.fullscreen.css" rel="stylesheet" />

    <style>
        /* -------------------------- */
        /* ESTÁNDAR NOC SMART TV      */
        /* -------------------------- */
        body { 
            background-color: #000000;
            overflow: hidden; /* ESTRICTAMENTE PROHIBIDO EL SCROLL EN TV */
            cursor: none !important;
            margin: 0;
            padding: 0;
        }
        
        .tv-grid-container {
            display: grid;
            grid-template-columns: 55% 45%;
            height: 100vh;
            width: 100vw;
            background: #f4f6f9;
        }

        #map-panel { 
            height: 100vh; 
            position: relative; 
            border-right: 4px solid #002d72;
            box-shadow: 10px 0 30px rgba(0,0,0,0.15);
            z-index: 10;
        }
        #map { height: 100%; width: 100%; z-index: 1; background: #e5e7eb; }

        .map-marker-container {
            position: relative; display: flex; justify-content: center; align-items: center;
            background: white; border-radius: 50%; width: 40px; height: 40px;
            border: 2px solid #002d72; box-shadow: 0 0 10px rgba(0,0,0,0.2);
        }
        
        .marker-pulse {
            position: absolute; width: 100%; height: 100%;
            border: 2px solid #002d72; border-radius: 50%;
            animation: radarPulse 2s infinite; opacity: 0;
        }

        @keyframes radarPulse {
            0% { transform: scale(1); opacity: 0.6; }
            100% { transform: scale(2.3); opacity: 0; }
        }

        .leaflet-marker-icon {
            transition: transform 2.0s cubic-bezier(0.25, 1, 0.5, 1) !important;
        }

        .custom-tooltip {
            background: rgba(0, 45, 114, 0.95) !important; color: white !important;
            border: none !important; border-radius: 8px !important;
            padding: 5px 12px !important; box-shadow: 0 4px 15px rgba(0,0,0,0.3) !important;
            font-size: 13px !important;
        }
        .tooltip-content b { color: #ffc107; text-transform: uppercase; font-size: 15px; }

        #dashboard-panel {
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            padding: 10px;
            background-color: #f4f6f9;
        }
        
        #reporte-container {
            transform: scale(0.70); 
            transform-origin: center center;
            width: 115%;
            margin-left: -7.5%;
            flex: 1;
            flex-direction: column;
            transition: opacity 0.4s ease-in-out;
        }

        #reporte-container table tbody tr, 
        #reporte-container .card {
            animation: slideInRow 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes slideInRow {
            0% { opacity: 0; transform: translateY(12px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .bg-chutos { background-color: #ff6600 !important; color: white;}
        .bg-camiones { background-color: #ffc107 !important; color: #212529;}
        .bg-cisternas { background-color: #198754 !important; color: white;}
        .bg-camionetas { background-color: #2c3e50 !important; color: white;}
        
        .offline-alert {
            display: none; position: absolute; top: 0; left: 0; width: 100vw;
            background: #dc3545; color: white; text-align: center; z-index: 9999;
            font-weight: bold; padding: 10px; font-size: 20px; text-transform: uppercase;
        }

        .table td, .table th {
            padding: 0.5rem 0.75rem !important; 
            white-space: nowrap;
        }
    </style>
@endpush

@section('content')

<div id="offline-banner" class="offline-alert">
    ⚠️ ALERTA DE SISTEMA: PÉRDIDA DE SEÑAL DE RED - RECONECTANDO...
</div>

<div class="tv-grid-container">
    <div id="map-panel">
        <div id="map"></div>
    </div>

    <div id="dashboard-panel">
        <div id="reporte-container">
            <div class="text-center mt-5 text-muted">
                <i class="fas fa-circle-notch fa-spin fa-4x mb-3 text-primary"></i>
                <h2 style="font-weight: 900; color:#002d72;">INICIALIZANDO CONSOLA NOC...</h2>
                <p>Estableciendo conexión encriptada con la central...</p>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-fullscreen@1.0.2/dist/Leaflet.fullscreen.min.js"></script>
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://js.pusher.com/8.0.1/pusher.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        
        let map;
        let markers = {}; 
        let countdownInterval;
        const REFRESH_SECONDS = 300; 
        let timeLeft = REFRESH_SECONDS;
        let lastFlotaState = null;
        
        const sedeCoords = L.latLng(10.488249123497356, -66.8234169941792);
        const RADIO_SEDE_METROS = 180;

        // --- 1. CONTINGENCIA OFFLINE ---
        window.addEventListener('offline', () => {
            document.getElementById('offline-banner').style.display = 'block';
            document.getElementById('reporte-container').style.opacity = '0.4';
        });

        window.addEventListener('online', () => {
            document.getElementById('offline-banner').style.display = 'none';
            document.getElementById('reporte-container').style.opacity = '1';
            fetchSalaData();
        });

        // --- 2. INICIALIZACIÓN DEL MAPA ---
        function initMap() {
            map = L.map('map', { 
                attributionControl: false, 
                fullscreenControl: false,
                zoomControl: false, 
                scrollWheelZoom: false,
                dragging: false
            }).setView([10.488249123497356, -66.8234169941792], 8); 

            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png').addTo(map);
            L.circle(sedeCoords, { color: '#002d72', fillOpacity: 0.1, radius: RADIO_SEDE_METROS }).addTo(map);

            fetchSalaData(); 
            startWatchdog(); 
            initWebSockets(); 
        }

        // --- 3. SOCKETS Y WATCHDOG ---
        function initWebSockets() {
            const pusher = new Pusher('{{ env("PUSHER_APP_KEY") }}', {
                cluster: '{{ env("PUSHER_APP_CLUSTER", "mt1") }}',
                wsHost: window.location.hostname,
                wsPort: 8080,
                forceTLS: false,
                disableStats: true
            });

            const channel = pusher.subscribe('sala-control');
            channel.bind('tv.refresh', function(payload) {
                document.getElementById('reporte-container').style.opacity = '0.7';
                fetchSalaData().then(() => {
                    document.getElementById('reporte-container').style.opacity = '1';
                });
            });
        }

        function startWatchdog() {
            countdownInterval = setInterval(() => {
                timeLeft--;
                const timerDisplay = document.getElementById('countdown-timer');
                if (timerDisplay) timerDisplay.textContent = timeLeft + 's';
                if (timeLeft <= 0) { fetchSalaData(); }
            }, 1000);
        }

        // --- 4. ADQUISICIÓN DE DATOS (AJAX) ---
        async function fetchSalaData() {
            const container = document.getElementById('reporte-container');
            const tvToken = "{{ $token ?? '' }}";

            try {
                const response = await fetch("{{ route('api.sala.control.stream') }}", {
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
                    container.innerHTML = data.html_dashboard;
                    
                    setTimeout(() => {
                        const lastSync = document.getElementById('last-sync-time');
                        if (lastSync) lastSync.textContent = new Date().toLocaleTimeString();
                    }, 10);

                    renderCharts(data.charts);
                    updateMapMarkers(data.unidades);
                    timeLeft = REFRESH_SECONDS; 
                }
            } catch (error) {
                console.error('Error de comunicación NOC:', error);
            }
        }

        // --- 5. ACTUALIZACIÓN DEL MAPA ---
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

        // =========================================================
        // GRÁFICOS HIGHCHARTS CON PARÁMETROS DIRECTOS DE API
        // =========================================================
        function renderCharts(chartData) {
            if (!chartData) return;


            // Gráfico 2: Segmentos Detallados de Flota (3 Columnas por Tipo)
            if (document.getElementById('chart-segmentos')) {
                Highcharts.chart('chart-segmentos', {
                    chart: { type: 'column', backgroundColor: 'transparent', height: 260 },
                    title: { text: null },
                    xAxis: { 
                        categories: ['Chutos', 'Camiones', 'Cisternas', 'Livianos'], 
                        labels: { style: { fontSize: '13px', fontWeight: 'bold' } } 
                    },
                    yAxis: { min: 0, title: { text: null } },
                    plotOptions: { 
                        column: { 
                            borderRadius: 4, 
                            colorByPoint: false, 
                            pointWidth: 14, // 👈 AQUÍ: Define el grosor en píxeles de cada barra (bájalo a 12 o sube a 16 según veas la TV)
                            dataLabels: { 
                                enabled: true, 
                                inside: false, // Fuerza a que el número quede arriba de la barra si es muy delgada
                                y: -5, // Ajusta la posición vertical del número para que no se monte
                                style: { fontSize: '10px', fontWeight: 'bold' } 
                            } 
                        } 
                    },
                    legend: { enabled: true, itemStyle: { fontSize: '11px' } },
                    colors: ['#198754', '#ffc107', '#dc3545'], /* Estándar de color NOC unificado */
                    series: [
                        { name: 'Operativos', data: chartData.segmentos.operativos },
                        { name: 'En Ruta', data: chartData.segmentos.enRuta },
                        { name: 'En Falla', data: chartData.segmentos.fallas }
                    ],
                    credits: { enabled: false }
                });
            }
        }

        initMap();
    });
</script>
@endsection