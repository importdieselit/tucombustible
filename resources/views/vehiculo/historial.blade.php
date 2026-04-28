@extends('layouts.app') {{-- Ajusta según tu layout principal --}}

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="https://unpkg.com/leaflet-fullscreen@1.0.2/dist/leaflet.fullscreen.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.css" />


    <style>
        #map {
            height: 800px;
            width: 100%;
            border-radius: 8px;
            box-shadow: inset 0 0 5px rgba(0,0,0,0.2);
            z-index: 1; /* Para que no se superponga a los menús desplegables */
        }
    /* Efecto de Radar para la unidad */
    .map-marker-container {
        position: relative;
        display: flex;
        justify-content: center;
        align-items: center;
        background: white;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        border: 2px solid #002d72; /* Color corporativo */
        box-shadow: 0 0 10px rgba(0,0,0,0.2);
        transition: border-color 0.3s ease, background-color 0.3s ease;
    }
    
    .marker-pulse {
        position: absolute;
        width: 100%;
        height: 100%;
        border: 2px solid #002d72;
        border-radius: 50%;
        animation: pulse 2s infinite;
        opacity: 0;
    }

    @keyframes pulse {
        0% { transform: scale(1); opacity: 0.5; }
        100% { transform: scale(2.5); opacity: 0; }
    }

    /* Eliminar borde azul de Leaflet al hacer click */
    .leaflet-container { outline: 0; }

    /* Pantalla completa manual (si no quieres usar plugins) */
    #map:fullscreen {
        width: 100vw;
        height: 100vh;
    }
 .custom-tooltip {
        background: rgba(0, 45, 114, 0.9); /* Azul corporativo con transparencia */
        color: white !important;
        border: none !important;
        border-radius: 20px !important;
        padding: 5px 12px !important;
        font-family: 'Inter', sans-serif;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3) !important;
        font-size: 11px !important;
        pointer-events: none; /* No interfiere con clics en el mapa */
    }

    /* Eliminar la flecha clásica de Leaflet si prefieres algo más limpio */
    .leaflet-tooltip-top:before {
        border-top-color: rgba(0, 45, 114, 0.9) !important;
    }

    /* Contenedor de texto dentro de la burbuja */
    .tooltip-content b { color: #ffc107; text-transform: uppercase; }
    .location-text { display: block; opacity: 0.9; margin-top: 2px; }

    .leaflet-control-fullscreen-button {
        background-color: #ffffff !important;
        border: 2px solid rgba(0,0,0,0.2) !important;
        border-radius: 4px !important;
        width: 34px !important;
        height: 34px !important;
        background-size: 18px 18px !important; /* Ajustar icono interno */
    }

    .leaflet-control-fullscreen-button:hover {
        background-color: #f4f4f4 !important;
    }

    .leaflet-marker-icon {
        transition: transform 0.5s linear;
    }

    /* Ajuste para que el Tooltip (burbuja) no se pierda en fullscreen */
    .leaflet-fullscreen-on .custom-tooltip {
        font-size: 14px !important; /* Un poco más grande en pantalla completa */
        padding: 8px 15px !important;
    }
</style>
@endpush

@section('content')
<div class="container-fluid px-4 py-3">
    <h4 class="mb-4 text-primary"><i class="fa-solid fa-route"></i> Historial de Recorridos</h4>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form id="form-historial" class="row g-3 align-items-end">
                
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Seleccionar Unidad</label>
                    <select id="filtro-vehiculo" class="form-select" required>
                        <option value="" selected disabled>Elige un vehículo...</option>
                        {{-- Iteramos los vehículos que mandes desde el controlador web --}}
                        @foreach($vehiculos as $v)
                            <option value="{{ $v->id }}">{{ $v->placa }} - {{ $v->isMarca->marca ?? '' }} {{ $v->isModelo->modelo ?? '' }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold">Desde</label>
                    <input type="datetime-local" id="filtro-desde" value="{{ date('Y-m-d H:i') }}"class="form-control" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold">Hasta</label>
                    <input type="datetime-local" id="filtro-hasta" value="{{ date('Y-m-d H:i') }}" class="form-control" required>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100" id="btn-buscar">
                        <i class="fa-solid fa-search"></i> Trazar Ruta
                    </button>
                </div>

            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            {{-- Capa de carga (Loader) --}}
            <div id="map-loader" class="d-none position-absolute w-100 h-100 bg-white bg-opacity-75 d-flex justify-content-center align-items-center" style="z-index: 9999;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
            </div>
            <div id="info-ruta" class="position-absolute top-0 end-0 m-3 p-2 bg-white shadow-sm rounded" style="z-index: 1000;">
            <small class="text-muted d-block">Distancia Calculada:</small>
            <span id="distancia-recorrida" class="fw-bold text-primary">0.00 KM</span>
        </div>
            <div id="map" style="height: 600px; width: 100%; border-radius: 0 0 0.375rem 0.375rem;"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
   <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-fullscreen@1.0.2/dist/Leaflet.fullscreen.min.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@latest/dist/leaflet-routing-machine.js"></script>
<script> // 1. Corregida la etiqueta de apertura
document.addEventListener('DOMContentLoaded', function () {
    let map;
    let rutaActual = null;
    let marcadoresRuta = []; 

    function initMap() {
        // Inicializar el mapa
        map = L.map('map', { attributionControl: false, fullscreenControl: true })
                   .setView([10.4806, -66.9036], 13); 
          L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png').addTo(map);

        // 2. Verificación de ID desde el controlador (Blade)
        const vehiculoIdDesdeUrl = "{{ $id ?? '' }}";

        if (vehiculoIdDesdeUrl !== "" && vehiculoIdDesdeUrl !== "null") {
            const hoy = new Date();
            const fechaHoy = hoy.toISOString().split('T')[0];
            
            // Seteamos valores por defecto en los inputs para que el usuario vea qué se consultó
            const desde = fechaHoy + "T00:00";
            const hasta = fechaHoy + "T23:59";
            
            document.getElementById('filtro-desde').value = desde;
            document.getElementById('filtro-hasta').value = hasta;
            document.getElementById('filtro-vehiculo').value = vehiculoIdDesdeUrl;

            dibujarTrazado(vehiculoIdDesdeUrl, desde, hasta);
        }
    }

    // 3. Listener del formulario
    const form = document.getElementById('form-historial');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const vehiculoId = document.getElementById('filtro-vehiculo').value;
            const desde = document.getElementById('filtro-desde').value;
            const hasta = document.getElementById('filtro-hasta').value;

            if (!vehiculoId) {
                alert("Por favor, seleccione un vehículo.");
                return;
            }

            if (new Date(hasta) < new Date(desde)) {
                alert("La fecha 'Hasta' no puede ser menor que la fecha 'Desde'.");
                return;
            }

            dibujarTrazado(vehiculoId, desde, hasta);
        });
    }

    async function dibujarTrazado(vehiculoId, desde, hasta) {
        const loader = document.getElementById('map-loader');
        if(loader) loader.classList.remove('d-none');

        try {
            const response = await fetch(`/api/vehiculos/${vehiculoId}/puntos?desde=${desde}&hasta=${hasta}`);
            const puntos = await response.json();

            limpiarRutaPrevia();

            if (puntos.length < 2) {
                alert('Se necesitan al menos 2 puntos para trazar una ruta por carretera.');
                return;
            }

            // 1. Convertir puntos a formato Waypoints de Leaflet Routing
            const waypoints = puntos.map(p => L.latLng(p.latitud, p.longitud));

            // 2. Crear el control de ruta
            rutaActual = L.Routing.control({
                waypoints: waypoints,
                router: L.Routing.osrmv1({
                    serviceUrl: `https://router.project-osrm.org/route/v1` // Servidor gratuito de OSRM
                }),
                lineOptions: {
                    styles: [{ color: '#f2A435', opacity: 0.8, weight: 6 }]
                },
                addWaypoints: false, // Evita que el usuario mueva los puntos
                draggableWaypoints: false,
                fitSelectedRoutes: true,
                show: false, // Oculta el panel de instrucciones giro a giro
                createMarker: function(i, wp, n) {
                    // Personalizamos los marcadores de inicio y fin
                    if (i === 0) return L.marker(wp.latLng, { icon: crearIcono('#28a745', 'fa-play') });
                    if (i === n - 1) return L.marker(wp.latLng, { icon: crearIcono('#dc3545', 'fa-flag-checkered') });
                    return null; // No mostrar marcadores en puntos intermedios
                }
            }).addTo(map);

            // 3. Capturar el evento cuando la ruta se calcula para obtener los KM
            rutaActual.on('routesfound', function(e) {
                const routes = e.routes;
                const summary = routes[0].summary;
                // summary.totalDistance está en metros
                const kilometros = (summary.totalDistance / 1000).toFixed(2);
                
                // Ejemplo: Mostrar los KM en un alert o en un div de tu vista
                console.log(`Distancia Real: ${kilometros} KM`);
                document.getElementById('distancia-recorrida').innerText = `${kilometros} KM Totales`;
            });

        } catch (error) {
            console.error('Error:', error);
        } finally {
            if(loader) loader.classList.add('d-none');
        }
    }

    function limpiarRutaPrevia() {
        if (rutaActual) {
            map.removeControl(rutaActual); // El routing se elimina como control
            rutaActual = null;
        }
        marcadoresRuta.forEach(m => map.removeLayer(m));
        marcadoresRuta = [];
    }

    function crearIcono(color, iconoFa) {
        return L.divIcon({
            html: `
                <div style="
                    background-color: white; 
                    border: 2px solid ${color}; 
                    border-radius: 50%; 
                    width: 30px; 
                    height: 30px; 
                    display: flex; 
                    align-items: center; 
                    justify-content: center;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                    <i class="fa-solid ${iconoFa}" style="color: ${color}; font-size: 12px;"></i>
                </div>`,
            iconSize: [30, 30],
            iconAnchor: [15, 15], // Importante para que el marcador esté centrado en la coordenada
            className: 'custom-marker-historial'
        });
    }

    function formatearFecha(fechaStr) {
        if(!fechaStr) return '';
        const date = new Date(fechaStr);
        return date.toLocaleString('es-ES', {
            day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit'
        });
    }

    initMap();
});
</script>
@endpush