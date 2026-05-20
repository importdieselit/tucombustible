@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="https://unpkg.com/leaflet-fullscreen@1.0.2/dist/leaflet.fullscreen.css" rel="stylesheet" />

    <style>
        body { background-color: #f4f6f9; overflow-x: hidden; }
        
        .main-control-wrapper {
            height: calc(100vh - 20px);
            margin: 10px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        /* -------------------------- */
        /* MAPA EN TIEMPO REAL (IZQ)  */
        /* -------------------------- */
        #map-panel { height: 100%; position: relative; border-right: 2px solid #e2e8f0; }
        #map { height: 100%; width: 100%; z-index: 1; }

        .map-marker-container {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            background: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            border: 2px solid #002d72;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
        }
        
        .marker-pulse {
            position: absolute;
            width: 100%;
            height: 100%;
            border: 2px solid #002d72;
            border-radius: 50%;
            animation: radarPulse 2s infinite;
            opacity: 0;
        }

        @keyframes radarPulse {
            0% { transform: scale(1); opacity: 0.6; }
            100% { transform: scale(2.3); opacity: 0; }
        }

        /* Transición fluida para el movimiento de los camiones en el mapa */
        .leaflet-marker-icon {
            transition: transform 2.0s cubic-bezier(0.25, 1, 0.5, 1) !important;
        }

        .custom-tooltip {
            background: rgba(0, 45, 114, 0.95) !important;
            color: white !important;
            border: none !important;
            border-radius: 8px !important;
            padding: 5px 12px !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3) !important;
            font-size: 11px !important;
        }
        .tooltip-content b { color: #ffc107; text-transform: uppercase; }

        /* ---------------------------------- */
        /* EFECTO DE DESPLAZAMIENTO Y REORDEN */
        /* ---------------------------------- */
        #dashboard-panel {
            height: 100%;
            overflow-y: auto;
            background-color: #f4f6f9;
            scroll-behavior: smooth;
        }
        
        .dashboard-container-scaled { padding: 15px; }

        /* Animación suave cuando los elementos se renderizan o cambian de zona */
        #reporte-container table tbody tr, 
        #reporte-container .card {
            animation: slideInRow 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes slideInRow {
            0% { opacity: 0; transform: translateY(12px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        /* Suavizado general para evitar saltos bruscos en el contenedor global */
        .fade-smooth {
            transition: opacity 0.3s ease-in-out;
        }

        /* Clases de Flota */
        .bg-chutos { background-color: #ff6600 !important; color: white;}
        .bg-camiones { background-color: #ffc107 !important; color: #212529;}
        .bg-cisternas { background-color: #198754 !important; color: white;}
        .bg-camionetas { background-color: #2c3e50 !important; color: white;}
    </style>
@endpush

@section('content')
<div class="container-fluid p-0">
    <div class="row g-0 main-control-wrapper">
        
        <div class="col-md-5 col-lg-6" id="map-panel">
            <div id="map"></div>
        </div>

        <div class="col-md-7 col-lg-6" id="dashboard-panel">
            <div class="dashboard-container-scaled fade-smooth" id="reporte-container">
                <div class="text-center mt-5 text-muted">
                    <i class="fas fa-circle-notch fa-spin fa-3x mb-3 text-primary"></i>
                    <h5>Inicializando Sala de Control...</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-fullscreen@1.0.2/dist/Leaflet.fullscreen.min.js"></script>
<script src="https://code.highcharts.com/highcharts.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        
        // --- VARIABLES DE CONFIGURACIÓN ---
        let map;
        let markers = {}; 
        let countdownInterval;
        let fetchInterval;
        const REFRESH_SECONDS = 60;
        let timeLeft = REFRESH_SECONDS;
        
        const sedeCoords = L.latLng(10.488249123497356, -66.8234169941792);
        const RADIO_SEDE_METROS = 180;

        // --- 1. INICIALIZACIÓN DEL MAPA ---
        function initMap() {
            map = L.map('map', { attributionControl: false, fullscreenControl: true })
                   .setView([10.488249123497356, -66.8234169941792], 8); 

            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png').addTo(map);
            
            L.circle(sedeCoords, {
                color: '#002d72',
                fillColor: '#002d72',
                fillOpacity: 0.1,
                radius: RADIO_SEDE_METROS
            }).addTo(map);

            // Carga inicial inmediata
            fetchSalaData();
            
            // Iniciar los hilos de temporizadores automáticos
            startTimers();
        }

        // --- 2. CONTROLADORES DE TIEMPO AUTOMÁTICOS ---
        function startTimers() {
            // Hilo 1: Descuento del reloj segundo a segundo (Sincronizado con tu partial)
            countdownInterval = setInterval(() => {
                timeLeft--;
                
                const timerDisplay = document.getElementById('countdown-timer');
                if (timerDisplay) {
                    timerDisplay.textContent = timeLeft + 's';
                }

                if (timeLeft <= 0) {
                    timeLeft = REFRESH_SECONDS; // Reset local inmediato
                    fetchSalaData();
                }
            }, 1000);
        }

        // --- 3. ADQUISICIÓN DE DATOS (AJAX) ---
        async function fetchSalaData() {
            const container = document.getElementById('reporte-container');
            const btnRefresh = document.getElementById('btn-refresh');
            const iconRefresh = document.getElementById('refresh-icon');

            if(btnRefresh) btnRefresh.disabled = true;
            if(iconRefresh) iconRefresh.classList.add('fa-spin');

            try {
                const response = await fetch("{{ route('api.sala.control.stream') }}", {
                    method: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });

                if (response.ok) {
                    const data = await response.json();
                    
                    // Inyección limpia del contenido
                    container.innerHTML = data.html_dashboard;
                    
                    // Re-dibujar gráficos sobre la nueva estructura
                    renderCharts();

                    // Mapeo e interpolación de posiciones
                    updateMapMarkers(data.unidades);
                    
                    // Forzar actualización del reloj de sincronización real
                    const lastSync = document.getElementById('last-sync-time');
                    if(lastSync) lastSync.textContent = new Date().toLocaleTimeString();
                    
                    timeLeft = REFRESH_SECONDS; // Garantizar sincronía post-stream
                }
            } catch (error) {
                console.error('Error de comunicación con el flujo de datos:', error);
            } finally {
                if(btnRefresh) btnRefresh.disabled = false;
                if(iconRefresh) iconRefresh.classList.remove('fa-spin');
            }
        }

        // --- 4. ACTUALIZACIÓN DINÁMICA DEL MAPA Y ZOOM EN CUADRANTE ---
        function updateMapMarkers(unidades) {
            const currentIds = unidades.map(u => u.id);
            const group = L.featureGroup(); // Contenedor para calcular las fronteras geométricas exactas

            unidades.forEach(u => {
                const newLatLng = [u.latitud, u.longitud];
                const colorStatus = obtenerColorPorEstatus(u.estatus);
                const contentTooltip = generarHtmlTooltip(u);
                
                // Criterio operativo de visualización de tooltips permanentes
                const mostrarSiempre = (u.estatus == 2 || L.latLng(newLatLng).distanceTo(sedeCoords) > RADIO_SEDE_METROS);

                if (markers[u.id]) {
                    // SI YA EXISTE: Se desplaza dinámicamente con la transición CSS
                    markers[u.id].setLatLng(newLatLng);
                    markers[u.id].setIcon(generarIcono(colorStatus));
                    markers[u.id].getTooltip().setContent(contentTooltip);
                    
                    if (mostrarSiempre) { markers[u.id].openTooltip(); } 
                    else { markers[u.id].closeTooltip(); }
                } else {
                    // SI ES NUEVO: Se instancia en el mapa
                    const m = L.marker(newLatLng, { 
                        icon: generarIcono(colorStatus) 
                    }).bindTooltip(contentTooltip, {
                        direction: 'top',
                        offset: [0, -15],
                        className: 'custom-tooltip',
                        permanent: mostrarSiempre
                    });

                    m.addTo(map);
                    markers[u.id] = m;
                }
                group.addLayer(markers[u.id]);
            });

            // Limpieza de unidades que salieron de línea
            Object.keys(markers).forEach(id => {
                if (!currentIds.includes(parseInt(id))) {
                    map.removeLayer(markers[id]);
                    delete markers[id];
                }
            });

            // AJUSTE AUTOMÁTICO DE ZOOM (Abarca la posición de todos los vehículos)
            if (group.getLayers().length > 0) {
                // El padding evita que los marcadores queden pegados a los bordes de la pantalla de la TV
                map.fitBounds(group.getBounds(), { 
                    padding: [60, 60], 
                    maxZoom: 13,
                    animate: true,
                    duration: 1.5
                });
            }
        }

        // --- 5. FUNCIONES AUXILIARES ---
        function obtenerColorPorEstatus(estatus) {
            if(estatus == 2) return '#ff6600'; // En Ruta (Naranja Chuto)
            if(estatus == 3 || estatus == 4 || estatus == 5) return '#e74a3b'; // Falla / Incidencia
            return '#002d72'; // Disponible / Operativo (Azul Corporativo)
        }

        function generarIcono(color) {
            return L.divIcon({
                html: `
                    <div class="map-marker-container" style="border-color: ${color}">
                        <div class="marker-pulse" style="border-color: ${color}"></div>
                        <i class="fa-solid fa-truck-moving" style="color: ${color}; font-size: 14px;"></i>
                    </div>`,
                iconSize: [40, 40],
                className: 'custom-marker'
            });
        }

        function generarHtmlTooltip(u) {
            let tiempo = 'Justo ahora';
            if(u.updated_at) {
                const diffMin = Math.floor((new Date() - new Date(u.updated_at)) / 60000);
                tiempo = diffMin < 1 ? 'Hace segundos' : `Hace ${diffMin} min`;
            }
            return `
                <div class="tooltip-content">
                    <b>${u.placa || 'S/P'}</b><br>
                    <small>${u.is_marca?.marca || ''} ${u.is_modelo?.modelo || ''}</small><br>
                    <small style="opacity:0.85;"><i class="far fa-clock"></i> ${tiempo}</small>
                </div>`;
        }

        // --- 6. RENDERIZADO DE HIGHCHARTS SOBRE EL PARTIAL ---
        function renderCharts() {
            const meta = document.getElementById('chart-data-meta');
            if (!meta) return;

            const data = {
                operativos: parseInt(meta.dataset.operativos),
                enRuta: parseInt(meta.dataset.enruta),
                fallas: parseInt(meta.dataset.fallas),
                chutos: parseInt(meta.dataset.chutos),
                camiones: parseInt(meta.dataset.camiones),
                tanques: parseInt(meta.dataset.tanques),
                livianos: parseInt(meta.dataset.livianos)
            };

            if (document.getElementById('chart-disponibilidad')) {
                Highcharts.chart('chart-disponibilidad', {
                    chart: { type: 'pie', backgroundColor: 'transparent', height: 220 },
                    title: { text: null },
                    plotOptions: { pie: { innerSize: '65%', dataLabels: { enabled: false }, showInLegend: true } },
                    legend: { itemStyle: { fontSize: '11px' } },
                    series: [{
                        name: 'Unidades',
                        data: [
                            { name: 'Disponibles', y: data.operativos, color: '#002d72' },
                            { name: 'En Ruta', y: data.enRuta, color: '#ff6600' },
                            { name: 'En Falla', y: data.fallas, color: '#e74a3b' }
                        ]
                    }],
                    credits: { enabled: false }
                });
            }

            if (document.getElementById('chart-segmentos')) {
                Highcharts.chart('chart-segmentos', {
                    chart: { type: 'column', backgroundColor: 'transparent', height: 220 },
                    title: { text: null },
                    xAxis: { categories: ['Chutos', 'Camiones', 'Cisternas', 'Livianos'], labels: { style: { fontSize: '10px' } } },
                    yAxis: { min: 0, title: { text: null } },
                    plotOptions: { column: { borderRadius: 4, colorByPoint: true, dataLabels: { enabled: true, style: { fontSize: '10px' } } } },
                    colors: ['#ff6600', '#ffc107', '#198754', '#2c3e50'],
                    series: [{ name: 'Unidades', showInLegend: false, data: [data.chutos, data.camiones, data.tanques, data.livianos] }],
                    credits: { enabled: false }
                });
            }
        }

        // Vincular la acción del botón manual del partial al flujo controlado global
        window.manualRefresh = function() {
            fetchSalaData();
        };

        initMap();
    });
</script>
@endsection