@extends('layouts.app') {{-- Ajusta según tu layout principal --}}

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="https://unpkg.com/leaflet-fullscreen@1.0.2/dist/leaflet.fullscreen.css" rel="stylesheet" />


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
            
            <div id="map" style="height: 600px; width: 100%; border-radius: 0 0 0.375rem 0.375rem;"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
   <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-fullscreen@1.0.2/dist/Leaflet.fullscreen.min.js"></script>
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
        const btnBuscar = document.getElementById('btn-buscar');
        
        // Feedback visual
        if(loader) loader.classList.remove('d-none');
        if(btnBuscar) btnBuscar.disabled = true;

        // Limpiar lo anterior antes de la nueva consulta para que el usuario vea el cambio
        limpiarRutaPrevia();

        try {
            // 4. Construcción de la URL (asegúrate de que coincida con tu ruta en web.php)
            const url = `/api/vehiculos/${vehiculoId}/puntos?desde=${desde}&hasta=${hasta}`;
            
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) throw new Error('Error en la red o sesión expirada');
            
            const puntos = await response.json();

            if (puntos.length === 0) {
                alert('No se encontraron registros GPS para este vehículo en este horario.');
                return;
            }

            // Mapeo de coordenadas
            const latLngs = puntos.map(p => [parseFloat(p.latitud), parseFloat(p.longitud)]);

            // 5. Dibujar Polilínea
            rutaActual = L.polyline(latLngs, {
                color: '#f2A435', 
                weight: 5,
                opacity: 0.8,
                dashArray: '10, 10', 
                lineJoin: 'round'
            }).addTo(map);

            // Marcador INICIO
            const iconInicio = crearIcono('#28a745', 'fa-play');
            const mInicio = L.marker(latLngs[0], { icon: iconInicio })
                .bindTooltip(`<b>Inicio:</b> ${formatearFecha(puntos[0].created_at)}`)
                .addTo(map);
            marcadoresRuta.push(mInicio);

            // Marcador FIN
            if (latLngs.length > 1) {
                const puntoFinal = puntos[puntos.length - 1];
                const iconFin = crearIcono('#dc3545', 'fa-flag-checkered');
                const mFin = L.marker(latLngs[latLngs.length - 1], { icon: iconFin })
                    .bindTooltip(`<b>Fin:</b> ${formatearFecha(puntoFinal.created_at)}`)
                    .addTo(map);
                marcadoresRuta.push(mFin);
            }

            // Ajustar vista a la ruta encontrada
            map.fitBounds(rutaActual.getBounds(), { padding: [50, 50] });

        } catch (error) {
            console.error('Error:', error);
            alert('No se pudo cargar el historial. Intente de nuevo.');
        } finally {
            if(loader) loader.classList.add('d-none');
            if(btnBuscar) btnBuscar.disabled = false;
        }
    }

    function limpiarRutaPrevia() {
        if (rutaActual) {
            map.removeLayer(rutaActual);
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