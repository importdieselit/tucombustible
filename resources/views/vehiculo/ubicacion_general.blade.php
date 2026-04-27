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
                {{-- <div class="col-md-3">
                    <label class="small fw-bold">Estatus</label>
                    <select id="filter-status" class="form-select form-select-sm">
                        <option value="">Todos los estatus</option>
                        <option value="2">En Ruta</option>
                        <option value="1">Disponibles</option>
                    </select>
                </div>
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
            const unidades = @json($unidades);
            console.log(unidades);
            // COORDENADAS DE LA SEDE
            const sedeCoords = L.latLng(10.48834308128781, -66.82329619185627);
            const RADIO_SEDE_METROS = 80;
            function initMap() {
                // Inicializar mapa
                map = L.map('map', { attributionControl: false, fullscreenControl: true })
                       .setView([10.4806, -66.9036], 6); // Centro por defecto (Caracas)
                       

                L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png').addTo(map);
                
                // Dibujar un círculo suave para representar la sede (opcional, ayuda visualmente)
                L.circle(sedeCoords, {
                    color: '#002d72',
                    fillColor: '#002d72',
                    fillOpacity: 0.1,
                    radius: RADIO_SEDE_METROS
                }).addTo(map).bindPopup("Sede Principal");
                // Renderizar marcadores iniciales
                renderMarkers(unidades);
                setInterval(actualizarData, 60000);
            }

            async function actualizarData() {
                console.log('Buscando actualizaciones de GPS...');
                try {
                    const response = await fetch("{{ route('api.vehiculos.ubicacion') }}");
                    const nuevaData = await response.json();
                    
                    if (nuevaData.length > 0) {
                        unidades = nuevaData; // Actualizamos la variable global
                        applyFilters();       // Re-renderizamos respetando los filtros actuales
                        console.log('Mapa actualizado con éxito.');
                    }
                } catch (error) {
                    console.error('Error al actualizar posiciones:', error);
                }
            }

            function renderMarkers(dataFiltro) {
                // Limpiar marcadores previos
                markers.forEach(m => map.removeLayer(m));
                markers = [];

                const group = L.featureGroup();

                dataFiltro.forEach(u => {
                   const unitLatLng = L.latLng(u.latitud, u.longitud);
            
                    // 1. Calcular distancia a la sede
                    const distanciaASede = unitLatLng.distanceTo(sedeCoords);
                    
                    // 2. Lógica de visibilidad permanente:
                    // Es estatus 2 (En Ruta) O está a más de 200 metros de la sede
                    const mostrarSiempre = (u.estatus == 2 || distanciaASede > RADIO_SEDE_METROS);

                    // 3. Color dinámico para el pulso (Verde si está en ruta, Azul si está en sede, Rojo si hay incidencia)
                    let colorStatus = '#002d72'; // Azul corporativo (Default/Sede)
                    if(u.estatus == 2) colorStatus = '#f2A435'; // Verde (Ruta)
                    if(u.estatus == 3) colorStatus = '#e74a3b'; // Rojo (Incidencia/Parado)

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
                                <small>${u.is_marca.marca || ''} ${u.is_modelo.modelo || ''}</small><br>
                                <small class="text-white opacity-75">${tiempoRelativo(u.updated_at)}</small>
                            </div>`, {
                            direction: 'top',
                            offset: [0, -15],
                            className: 'custom-tooltip',
                            permanent: mostrarSiempre, // AQUÍ SE APLICA LA REGLA
                            sticky: false 
                        });

                    m.unitData = u; // Guardamos data para filtrar
                    m.addTo(map);
                    markers.push(m);
                    group.addLayer(m);
                });

                // Auto-ajuste de zoom solo si hay marcadores en pantalla
                if (markers.length > 0) {
                    map.fitBounds(group.getBounds(), { padding: [50, 50] });
                }
            }

            // Lógica de Filtrado
            function applyFilters() {
                const placa = document.getElementById('filter-placa').value.toLowerCase();
                //const tipo = document.getElementById('filter-tipo').value.toLowerCase();
                //const estatus = document.getElementById('filter-status').value.toLowerCase();


                const filtrados = unidades.filter(u => {
                    //const tipoU = (u.tipo || '').toLowerCase();
                    const placaU = (u.placa || '').toLowerCase();
                    //const estatusU = (u.estatus || '').toLowerCase();

                    return (placaU.includes(placa));
                });

                renderMarkers(filtrados);
            }

            function resetFilters() {
                document.getElementById('filter-placa').value = "";
               // document.getElementById('filter-status').value = "";
               // document.getElementById('filter-tipo').value = "";
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

                const dias = Math.floor(horas / 24);
                return `Hace ${dias} días`;
            }

            // Listeners de los filtros
            document.getElementById('filter-placa').addEventListener('input', applyFilters);
           // document.getElementById('filter-tipo').addEventListener('change', applyFilters);
            //document.getElementById('filter-status').addEventListener('change', applyFilters);


            // EJECUTAR EL MAPA: Llamamos a la función dentro del mismo scope donde fue creada
            initMap();

        });


    </script>
@endsection