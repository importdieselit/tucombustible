@extends('layouts.app')

@section('title', 'Editar Orden de Trabajo #' . $item->nro_orden)

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        .border-orange { border-top: 3px solid #e67e22 !important; }
        .text-orange { color: #e67e22 !important; }
        .bg-corporate { background-color: #2c3e50 !important; color: white; }
        .btn-corporate { background-color: #e67e22; color: white; font-weight: bold; }
        #map { height: 300px; width: 100%; border-radius: 8px; border: 2px solid #ddd; }
        .select2-container--bootstrap-5 .select2-selection { border-radius: 0.375rem; }
    </style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 shadow-sm rounded">
        <div>
            <h3 class="fw-bold mb-0 text-uppercase">
                <i class="fas fa-file-signature text-orange me-2"></i>Editar Orden de Trabajo
            </h3>
            <p class="text-muted mb-0 small">Modificación de registros, diagnósticos y repuestos asignados.</p>
        </div>
        <div class="text-end">
            <span class="badge bg-corporate p-2 fs-6">ORDEN NRO: {{ $item->nro_orden }}</span>
        </div>
    </div>

    <form action="{{ route('ordenes.update', $item->id) }}" method="POST" id="orden-form" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        
        <input type="hidden" name="latitud" id="latitud" value="{{ $item->latitud }}">
        <input type="hidden" name="longitud" id="longitud" value="{{ $item->longitud }}">

        <div class="row g-4">
            {{-- SECCIÓN 1: DATOS DE LA UNIDAD --}}
            <div class="col-md-4">
                <div class="card h-100 shadow-sm border-orange">
                    <div class="card-header bg-white fw-bold"><i class="fas fa-truck me-2 text-orange"></i>Datos de la Unidad</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Vehículo / Equipo</label>
                            <select name="id_vehiculo" class="form-select select2-vehiculos" required>
                                <option value="{{ $item->id_vehiculo }}" selected>
                                    {{ $item->vehiculo()->flota }} - {{ $item->vehiculo()->placa }} ({{ $item->vehiculo()->marca()->marca }})
                                </option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Kilometraje al Ingreso</label>
                            <input type="number" name="kilometraje" class="form-control" value="{{ $item->kilometraje }}" required>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-bold">Prioridad</label>
                            <select name="prioridad" class="form-select">
                                <option value="Baja" {{ $item->prioridad == 'Baja' ? 'selected' : '' }}>Baja</option>
                                <option value="Media" {{ $item->prioridad == 'Media' ? 'selected' : '' }}>Media</option>
                                <option value="Alta" {{ $item->prioridad == 'Alta' ? 'selected' : '' }}>Alta</option>
                                <option value="Crítica" {{ $item->prioridad == 'Crítica' ? 'selected' : '' }}>Crítica</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SECCIÓN 2: UBICACIÓN --}}
            <div class="col-md-8">
                <div class="card h-100 shadow-sm border-orange">
                    <div class="card-header bg-white fw-bold"><i class="fas fa-map-marker-alt me-2 text-orange"></i>Ubicación del Servicio</div>
                    <div class="card-body">
                        <div class="input-group mb-3">
                            <input type="text" id="search-input" class="form-control" placeholder="Buscar dirección o lugar..." value="{{ $item->direccion }}">
                            <button class="btn btn-corporate" type="button" id="search-btn"><i class="fas fa-search"></i></button>
                        </div>
                        <div id="map"></div>
                        <input type="hidden" name="direccion" id="input-direccion" value="{{ $item->direccion }}">
                    </div>
                </div>
            </div>

            {{-- SECCIÓN 3: DETALLES --}}
            <div class="col-md-12">
                <div class="card shadow-sm border-orange">
                    <div class="card-header bg-white fw-bold"><i class="fas fa-tools me-2 text-orange"></i>Detalles Técnicos y Diagnóstico</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label small fw-bold">Tipo de Servicio</label>
                                <input type="text" name="tipo" class="form-control" value="{{ $item->tipo }}" placeholder="Ej: Preventivo, Correctivo...">
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label small fw-bold">Título / Resumen</label>
                                <input type="text" name="descripcion_1" class="form-control" value="{{ $item->descripcion_1 }}" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Observaciones / Informe Detallado</label>
                                <textarea name="descripcion" class="form-control" rows="4">{{ $item->descripcion }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="mt-4 text-end">
            <a href="{{ route('ordenes.index') }}" class="btn btn-outline-secondary px-4 me-2">Cancelar</a>
            <button type="submit" class="btn btn-corporate px-5 shadow-sm">
                <i class="fas fa-save me-2"></i> ACTUALIZAR ORDEN DE TRABAJO
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    document.addEventListener('DOMContentLoaded', function() {
        // --- LÓGICA DEL MAPA (IDENTICA AL CREATE) ---
        const initialLat = {{ $item->latitud ?? 10.6447 }};
        const initialLon = {{ $item->longitud ?? -71.6105 }};
        
        const map = L.map('map').setView([initialLat, initialLon], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        let marker = L.marker([initialLat, initialLon], { draggable: true }).addTo(map);

        function updateCoords(lat, lon) {
            document.getElementById('latitud').value = lat;
            document.getElementById('longitud').value = lon;
            marker.setLatLng([lat, lon]);
            map.panTo([lat, lon]);
        }

        marker.on('dragend', function(e) {
            const pos = e.target.getLatLng();
            updateCoords(pos.lat, pos.lng);
        });

        // --- BÚSQUEDA DE UBICACIÓN ---
        $('#search-btn').click(function() {
            const query = $('#search-input').val();
            if (query.length < 3) return;
            
            $.getJSON(`https://nominatim.openstreetmap.org/search?format=json&q=${query}`, function(data) {
                if (data && data.length > 0) {
                    const res = data[0];
                    updateCoords(res.lat, res.lon);
                    $('#input-direccion').val(res.display_name);
                }
            });
        });

     
    });
</script>
@endpush