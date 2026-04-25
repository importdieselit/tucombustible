@extends('layouts.app')

@section('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-fullscreen@1.0.2/dist/leaflet.fullscreen.css" />
    <style>
        #map-global { height: 70vh; width: 100%; border-radius: 12px; }
        
        /* Colores por Estatus */
        .status-ruta { --marker-color: #28a745; }    /* Verde */
        .status-detenido { --marker-color: #6c757d; } /* Gris */
        .status-incidencia { --marker-color: #dc3545; } /* Rojo */

        .map-marker-container {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            background: white;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            border: 2px solid var(--marker-color);
        }
        .marker-pulse {
            position: absolute;
            width: 100%; height: 100%;
            border: 2px solid var(--marker-color);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.6; }
            100% { transform: scale(2.2); opacity: 0; }
        }
        .text-corporate { color: var(--marker-color); }
    </style>
@endsection
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
                        <option value="en ruta">En Ruta</option>
                        <option value="detenido">Detenido</option>
                        <option value="incidencia">Incidencia</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold">Tipo de Vehículo</label>
                    <select id="filter-tipo" class="form-select form-select-sm">
                        <option value="">Todos los tipos</option>
                        <option value="Cisterna">Cisterna</option>
                        <option value="Logística">Logística</option>
                    </select>
                </div>
                <div class="col-md-3 text-end pt-3">
                    <button class="btn btn-sm btn-outline-secondary" onclick="resetFilters()">
                        <i class="fas fa-undo me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="map-global"></div>
</div>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-fullscreen@1.0.2/dist/Leaflet.fullscreen.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let map, markers = [];
            const unidades = @json($unidades);
            console.log("Unidades cargadas:", unidades);

            function initMap() {
                // Inicializar mapa
                map = L.map('map-global', { attributionControl: false, fullscreenControl: true })
                       .setView([10.4806, -66.9036], 12); // Centro por defecto (Caracas)

                L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png').addTo(map);
                
                // Renderizar marcadores iniciales
                renderMarkers(unidades);
            }

            function renderMarkers(dataFiltro) {
                // Limpiar marcadores previos
                markers.forEach(m => map.removeLayer(m));
                markers = [];

                const group = L.featureGroup();

                dataFiltro.forEach(u => {
                    // Validación por si el estatus es null en base de datos

                    const icon = L.divIcon({
                        html: `
                            <div class="map-marker-container">
                                <div class="marker-pulse"></div>
                                <i class="fa-solid fa-truck text-corporate small"></i>
                            </div>`,
                        iconSize: [35, 35],
                        className: 'custom-marker'
                    });

                    const m = L.marker([u.latitud, u.longitud], { icon: icon })
                        .bindTooltip(`<b>${u.placa}</b><br><small>${u.modelo || 'Sin modelo'}</small>`, {
                            direction: 'top',
                            offset: [0, -15],
                            className: 'custom-tooltip'
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
                const tipo = document.getElementById('filter-tipo').value.toLowerCase();

                const filtrados = unidades.filter(u => {
                    const tipoU = (u.tipo || '').toLowerCase();
                    const placaU = (u.placa || '').toLowerCase();

                    return (placaU.includes(placa)) &&
                           (tipo === "" || tipoU === tipo);
                });

                renderMarkers(filtrados);
            }

            function resetFilters() {
                document.getElementById('filter-placa').value = "";
                document.getElementById('filter-status').value = "";
                document.getElementById('filter-tipo').value = "";
                renderMarkers(unidades);
            }

            // Listeners de los filtros
            document.getElementById('filter-placa').addEventListener('input', applyFilters);
            document.getElementById('filter-tipo').addEventListener('change', applyFilters);

            // EJECUTAR EL MAPA: Llamamos a la función dentro del mismo scope donde fue creada
            initMap();
        });
    </script>
@endsection