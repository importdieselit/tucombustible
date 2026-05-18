@extends('layouts.app')

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
<div class="container-fluid py-3">
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-center">
                <div class="col-md-3">
                    <label class="small fw-bold">Buscar Unidad</label>
                    <input type="text" id="filter-placa" class="form-control form-control-sm" placeholder="Ej: AB123CD">
                </div>
                 <div class="col-md-3">
                    <label class="small fw-bold">Estatus</label>
                    <select id="filter-status" class="form-select form-select-sm">
                        <option value="">Todos los estatus</option>
                        <option value="2">En Ruta</option>
                        <option value="1">Disponibles</option>
                    </select>
                </div>
                {{--
                <div class="col-md-3">
                    <label class="small fw-bold">Tipo de Vehículo</label>
                    <select id="filter-tipo" class="form-select form-select-sm">
                        <option value="">Todos los tipos</option>
                        <option value="1">Camion</option>
                        <option value="2">Chuto</option>
                        <option value="3">Cisterna</option>
                        <option value="6">Liviano</option>
                    </select>
                </div> --}}
                <div class="col-md-3 text-end pt-3">
                    <button class="btn btn-sm btn-outline-secondary" onclick="resetFilters()">
                        <i class="fas fa-undo me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="map"></div>
</div>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-fullscreen@1.0.2/dist/Leaflet.fullscreen.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        let map, markers = [];
        // Cambiamos a 'let' para permitir actualizaciones de GPS
        let unidades = @json($unidades); 
        
        console.log("Unidades iniciales:", unidades);

        // COORDENADAS DE LA SEDE
        const sedeCoords = L.latLng(10.488249123497356, -66.8234169941792);
        const RADIO_SEDE_METROS = 180;

        function initMap() {
            map = L.map('map', { attributionControl: false, fullscreenControl: true })
                   .setView([10.488249123497356, -66.8234169941792], 6); 

            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png').addTo(map);
            
            L.circle(sedeCoords, {
                color: '#002d72',
                fillColor: '#002d72',
                fillOpacity: 0.1,
                radius: RADIO_SEDE_METROS
            }).addTo(map).bindPopup("Sede Principal");

            renderMarkers(unidades);
            
            // Ciclo de actualización cada 60seg
            setInterval(actualizarData, 150000);
        }

        async function actualizarData() {
            console.log('Intentando actualizar...');
                try {
                    const response = await fetch("{{ route('api.vehiculos.ubicacion') }}", {
                        method: 'GET',
                        credentials: 'include', // <--- IMPORTANTE: Incluye cookies de sesión
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    });

                    if (response.status === 401) {
                        console.warn('Sesión expirada. Redirigiendo...');
                        window.location.reload(); // O manejar el re-login
                        return;
                    }

                    const nuevaData = await response.json();
                    
                    if (nuevaData.length > 0) {
                        unidades = nuevaData; 
                        updateMapMarkers(nuevaData);
                        console.log('Posiciones actualizadas.');
                    }
                } catch (error) {
                    console.error('Error al actualizar:', error);
                }
            }

        function renderMarkers(dataFiltro) {
            markers.forEach(m => map.removeLayer(m));
            markers = [];
            const group = L.featureGroup();

            dataFiltro.forEach(u => {
                const unitLatLng = L.latLng(u.latitud, u.longitud);
                const distanciaASede = unitLatLng.distanceTo(sedeCoords);
                const fechaActual = new Date();
                const fechaAyer = new Date(fechaActual.getTime() - (24 * 60 * 60 * 1000));
                // Formatear para que coincida con el formato del controlador (YYYY-MM-DDTHH:mm)
                const formatDT = (date) => date.toISOString().slice(0, 16);
                const urlHistorial = `/vehiculos/historial/${u.id}?desde=${formatDT(fechaAyer)}&hasta=${formatDT(fechaActual)}`;
                
                // Mostrar siempre si estatus es 2 (En Ruta) o está lejos de la sede
                const mostrarSiempre = (u.estatus == 2 || distanciaASede > RADIO_SEDE_METROS);

                let colorStatus = '#002d72'; 
                if(u.estatus == 2) colorStatus = '#f2A435'; // Naranja/Dorado (Ruta)
                if(u.estatus == 3) colorStatus = '#e74a3b'; // Rojo (Incidencia)

                const icon = L.divIcon({
                    html: `
                        <div class="map-marker-container" style="border-color: ${colorStatus}">
                            <div class="marker-pulse" style="border-color: ${colorStatus}"></div>
                            <i class="fa-solid fa-truck small" style="color: ${colorStatus}"></i>
                        </div>`,
                    iconSize: [35, 35],
                    className: 'custom-marker'
                });

                const m = L.marker(unitLatLng, { icon: icon })
                    .bindTooltip(`
                        <div class="tooltip-content">
                            <b>${u.placa}</b><br>
                            <small>${u.is_marca?.marca || ''} ${u.is_modelo?.modelo || ''}</small><br>
                             <small class="text-white opacity-75">${tiempoRelativo(u.updated_at)}</small>
                        </div>`, {
                        direction: 'top',
                        offset: [0, -15],
                        className: 'custom-tooltip',
                        permanent: mostrarSiempre,
                        sticky: false 
                    });

                m.addTo(map);
                markers.push(m);
                group.addLayer(m);
            });

            if (markers.length > 0) {
                map.fitBounds(group.getBounds(), { padding: [50, 50] });
            }
        }

        function updateMapMarkers(data) {
            const currentIds = data.map(u => u.id);

            data.forEach(u => {
                const newLatLng = [u.latitud, u.longitud];
                const colorStatus = obtenerColorPorEstatus(u.estatus);
                const contentTooltip = generarHtmlTooltip(u);

                if (markers[u.id]) {
                    // --- EL VEHÍCULO YA EXISTE: ACTUALIZAR ---
                    
                    // 1. Mover posición (El CSS hará el resto)
                    markers[u.id].setLatLng(newLatLng);

                    // 2. Actualizar Icono (Por si cambió el estatus/color)
                    const newIcon = generarIcono(colorStatus);
                    markers[u.id].setIcon(newIcon);

                    // 3. Actualizar Contenido del Tooltip y visibilidad
                    const mostrarSiempre = (u.estatus == 2 || L.latLng(newLatLng).distanceTo(sedeCoords) > RADIO_SEDE_METROS);
                    markers[u.id].getTooltip().setContent(contentTooltip);
                    
                    // Forzar visibilidad del tooltip según estatus
                    if (mostrarSiempre) {
                        markers[u.id].openTooltip();
                    } else {
                        markers[u.id].closeTooltip();
                    }

                } else {
                    // --- VEHÍCULO NUEVO: CREAR ---
                    const mostrarSiempre = (u.estatus == 2 || L.latLng(newLatLng).distanceTo(sedeCoords) > RADIO_SEDE_METROS);
                    
                    const m = L.marker(newLatLng, { 
                        icon: generarIcono(colorStatus) 
                    }).bindTooltip(contentTooltip, {
                        direction: 'top',
                        offset: [0, -15],
                        className: 'custom-tooltip',
                        permanent: mostrarSiempre
                    });

                    m.addTo(map);
                    markers[u.id] = m; // Guardar en el registro global
                }
            });

            // Opcional: Eliminar marcadores de unidades que ya no vienen en la data
            Object.keys(markers).forEach(id => {
                if (!currentIds.includes(parseInt(id))) {
                    map.removeLayer(markers[id]);
                    delete markers[id];
                }
            });
        }

        // Funciones Helper para mantener el código limpio y reutilizable
        function obtenerColorPorEstatus(estatus) {
            if(estatus == 2) return '#f2A435'; // Ruta
            if(estatus == 3) return '#e74a3b'; // Incidencia
            return '#002d72'; // Sede/Disponible
        }

        function generarIcono(color) {
            return L.divIcon({
                html: `
                    <div class="map-marker-container" style="border-color: ${color}">
                        <div class="marker-pulse" style="border-color: ${color}"></div>
                        <i class="fa-solid fa-truck small" style="color: ${color}"></i>
                    </div>`,
                iconSize: [35, 35],
                className: 'custom-marker'
            });
        }

        function generarHtmlTooltip(u) {
            return `
                <div class="tooltip-content">
                    <b>${u.placa}</b><br>
                    <small>${u.is_marca?.marca || ''} ${u.is_modelo?.modelo || ''}</small><br>
                    <small class="text-white opacity-75">Actualizado: ${tiempoRelativo(u.updated_at)}</small>
                </div>`;
        }

        function applyFilters() {
            const fPlaca = document.getElementById('filter-placa').value.toLowerCase();
            const fEstatus = document.getElementById('filter-status').value;

            const filtrados = unidades.filter(u => {
                const placaU = (u.placa || '').toLowerCase();
                const estatusU = String(u.estatus || ''); // Normalizar a String

                const coincidePlaca = placaU.includes(fPlaca);
                const coincideEstatus = (fEstatus === "") || (estatusU === fEstatus);

                return coincidePlaca && coincideEstatus;
            });

            renderMarkers(filtrados);
        }

        function resetFilters() {
            document.getElementById('filter-placa').value = "";
            document.getElementById('filter-status').value = "";
            renderMarkers(unidades);
        }

        function tiempoRelativo(fecha) {
            const now = new Date();
            const past = new Date(fecha);
            const diffMin = Math.floor((now - past) / 1000 / 60);

            if (diffMin < 1) return 'Hace segundos';
            if (diffMin < 60) return `Hace ${diffMin} min`;
            const horas = Math.floor(diffMin / 60);
            if (horas < 24) return `Hace ${horas} h`;
            return `Hace ${Math.floor(horas / 24)} días`;
        }

        // Listeners activados
        document.getElementById('filter-placa').addEventListener('input', applyFilters);
        document.getElementById('filter-status').addEventListener('change', applyFilters);

        initMap();
    });
</script>
@endsection