@extends('layouts.app')

@section('title', 'Hoja de Vida del Vehículo')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        #map {
            height: 300px;
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
</style>
@endpush


@section('content')
<div class="container-fluid">
<div class="col-12 col-md-auto ms-auto pt-2">
    <div class="d-grid d-md-flex gap-2 flex-wrap" style="grid-template-columns: repeat(2, 1fr);">
        
        <button class="btn btn-corporate shadow-sm  w-md-auto" data-bs-toggle="modal" data-bs-target="#modalKm">
            <i class="fa-solid fa-gauge-high me-1"></i> KM
        </button>

        <button class="btn btn-warning shadow-sm w-md-auto" data-bs-toggle="modal" data-bs-target="#modalPlanificar">
            <i class="fa-solid fa-calendar-check me-1"></i> <span class="d-inline d-md-none d-lg-inline">Planificar</span>
        </button>

        @if($esChuto)
            <button class="btn btn-outline-dark shadow-sm w-md-auto" data-bs-toggle="modal" data-bs-target="#modalAcoplar">
                <i class="fa-solid fa-link me-1"></i> Acoplar
            </button>
        @endif

        <a class="btn btn-danger shadow-sm g-col-2 w-md-auto d-flex align-items-center justify-content-center" 
           href="{{ route('ot.create', $item->id) }}" >
            <i class="fa-solid fa-triangle-exclamation me-1"></i> 
            <span class="text-nowrap">Crear Orden</span>
        </a>

        <a href="{{ route('vehiculos.edit', $item->id) }}" class="btn btn-dark shadow-sm w-md-auto">
            <i class="fa-solid fa-pen-to-square me-1 d-md-none"></i> Editar
        </a>
    </div>
</div>
    <div class="card mb-4 border-0 shadow-sm bg-white">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-auto text-center">
                    <div class="rounded-circle p-3 pb-0  d-block">
                        <i class="fa-solid fa-truck-moving fa-3x text-corporate"></i>
                    </div>
                    <small class="text-muted" style="margin-top: -20px">{{ $tipo }}</small>
                </div>
                <div class="col-md">
                    <h3 class="mb-0">{{ $item->placa }} </h3>
                    <p class="text-muted mb-0">{{ $item->marca()->marca }} {{ $item->modelo()->modelo }} | {{ $item->anno }}</p>
                    <span class="badge bg-{{ $estatus->css }}">
                        {{ $estatus->auto  }}
                    </span>
                </div>
                @if($esChuto || $esCisterna)
                    <div class="col-md-auto border-start d-none d-md-block">
                       @if($esChuto)
                            <div class="mt-1">
                                @if($item->acoplado_id)
                                   <small class="text-muted d-block">Cisterna Acoplada</small>
                                    <span class=" text-dark d-inline">
                                        <i class="fa fa-link"></i> {{ $item->cisternaAcoplada->placa }}
                                    </span>
                                    <button type="button" class="btn btn-link text-danger p-0 ms-1 d-inline" style="font-size: 0.6rem;"
                                            onclick="event.stopPropagation(); desacoplar({{ $item->id }})"
                                            title="Desacoplar">
                                        <i class="fa fa-times-circle" style="font-size: 0.6rem;"></i>
                                    </button>
                                    @else
                                    <span class="text-muted">(Sin Cisterna)</span>
                                @endif
                            </div>
                        @else
                            @if($item->chutoAsignado)

                                    <i class="fa fa-truck"></i>: {{ $item->chutoAsignado->placa }}

                            @else
                                (Disponible)
                            @endif
                        @endif
                    </div>
                @endif
                <div class="col-md-auto border-start d-none d-md-block">
                    <small class="text-muted d-block">Kilometraje Actual</small>
                    <h5 class="mb-0">{{ number_format($item->kilometraje) }} km</h5>
                </div>
                <div class="col-md-auto border-start d-none d-md-block">
                    <small class="text-muted d-block">Ubicación Actual</small>
                    <h5 class="mb-0 text-primary">{{ $ubicacion['nombre'] ?? 'En Patio' }}</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white p-0">
            <ul class="nav nav-tabs card-header-tabs m-0" id="vehiculoTab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link text-corporate-emphasis active" id="resumen-tab" data-bs-toggle="tab" href="#resumen" role="tab"><i class="fa-solid fa-file-invoice me-1"></i> <span class="d-none d-sm-inline">Hoja de Vida</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-corporate-emphasis" id="docs-tab" data-bs-toggle="tab" href="#docs" role="tab"><i class="fa-solid fa-folder-open me-1"></i><span class="d-none d-sm-inline"> Documentación</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-corporate-emphasis" id="mantenimiento-tab" data-bs-toggle="tab" href="#mantenimiento" role="tab"><i class="fa-solid fa-screwdriver-wrench me-1"></i><span class="d-none d-sm-inline"> Mantenimientos</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-corporate-emphasis" id="viajes-tab" data-bs-toggle="tab" href="#viajes" role="tab"><i class="fa-solid fa-route me-1"></i><span class="d-none d-sm-inline"> Viajes</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-corporate-emphasis" id="fotos-tab" data-bs-toggle="tab" href="#fotos" role="tab"><i class="fa-solid fa-image me-1"></i><span class="d-none d-sm-inline"> Galería</span></a>
                </li>
            </ul>
        </div>

        <div class="card-body bg-white">
            <div class="tab-content" id="vehiculoTabContent">

                <div class="tab-pane fade show active" id="resumen" role="tabpanel">
                    <div class="row">
                        <h6 class="mb-3">Historial de Kilometraje y Consumo Mensual <span class="text-danger">(MODO DEMO)</span></h6>
                    {{-- Contenedor del gráfico --}}
                    <div class="col-md-8 d-none" id="monthly-chart" style="height: 300px; margin-bottom: 20px;"></div>
                    <div class="card border-0 shadow-sm mt-3">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0 text-uppercase small fw-bold">
                                <i class="fa-solid fa-location-dot text-danger me-1"></i> Ubicación en Tiempo Real
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <div id="map"></div>
                        </div>
                    </div>
                        <div class="col-md-4 border-start ps-4 bg-white rounded shadow-sm p-2">
                            <h6 class="text-uppercase fw-bold border-bottom pb-2">Datos Técnicos</h6>
                            <table class="table table-white table-sm table-borderless bg-white">
                                <tr><td class="text-muted">Serial Motor:</td><td>{{ $item->serial_motor }}</td></tr>
                                <tr><td class="text-muted">Serial Chasis:</td><td>{{ $item->serial_carroceria }}</td></tr>
                                <tr><td class="text-muted">Carga Máxima:</td><td>{{ number_format($item->carga_max) }} kg</td></tr>
                                <tr><td class="text-muted">Tipo de Combustible:</td><td>{{ $item->tipo_combustible }}</td></tr>
                                <tr><td class="text-muted">Capacidad Tanque<td>{{ number_format($item->consumo, 2) }} Ltrs</td></tr>
                                <tr><td class="text-muted">Kilometraje Recorrido:</td><td>{{ number_format($item->km_mantt) }} km</td></tr>
                                <tr><td class="text-muted">Proximo Mantenimiento:</td><td class="font-weight-bold text-{{ 5000-$item->km_mantt < 50 ? 'danger' : (5000-$item->km_mantt< 200 ? 'warning' : 'success') }} "><strong>{{ number_format(5000-$item->km_mantt) }} km</strong></td></tr>
                                <tr><td class="text-muted">Horas de Trabajo:</td><td>{{ number_format($item->hrs_mantt) }} hrs</td></tr>
                                <tr><td class="text-muted">Próximo Mantenimiento:</td><td class="font-weight-bold text-{{ 200-$item->hrs_mantt <= 15 ? 'danger' : (200-$item->hrs_mantt<= 36 ? 'warning' : 'success') }} "><strong>{{ number_format(200-$item->hrs_mantt) }} hrs</strong></td></tr>
                                <tr><td class="text-muted">Afecta Disponibilidad:</td><td>{{ $item->es_flota ? 'Sí' : 'No' }}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="docs" role="tabpanel">
                   <div class="card-header bg-dark py-3 d-flex justify-content-between align-items-center">
                    <h6 class="text-white mb-0 fw-black text-uppercase small">
                        <i class="fas fa-file-signature me-2 text-orange"></i> Visor de Documentación Digital
                    </h6>
                </div>
                    <div class="card-body p-0">
                        <div class="row g-0">
                            {{-- Listado de Pestañas Lateral --}}
                            <div class="col-md-3 border-end bg-light">
                                <div class="nav flex-column nav-pills p-2" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                    @foreach($docsV as $index => $doc)
                                        @php
                                            // Buscamos si existe el archivo (probamos con pdf y jpg)
                                            $pathPdf = "storage/vehiculos/{$item->id}/documentos/{$doc->abreviatura}_{$item->id}.pdf";
                                            $pathJpg = "storage/vehiculos/{$item->id}/documentos/{$doc->abreviatura}_{$item->id}.jpg";
                                            $pathPng = "storage/vehiculos/{$item->id}/documentos/{$doc->abreviatura}_{$item->id}.png";
                                            
                                            $finalPath = null;
                                            if(file_exists(public_path($pathPdf))) $finalPath = asset($pathPdf);
                                            elseif(file_exists(public_path($pathJpg))) $finalPath = asset($pathJpg);
                                            elseif(file_exists(public_path($pathPng))) $finalPath = asset($pathPng);
                                        @endphp

                                        <button class="nav-link {{ $index === 0 ? 'active' : '' }} d-flex justify-content-between align-items-center text-uppercase fw-bold mb-1 py-2 px-3 small shadow-sm" 
                                                id="tab-{{ $doc->abreviatura }}" 
                                                data-bs-toggle="pill" 
                                                data-bs-target="#content-{{ $doc->abreviatura }}" 
                                                type="button" role="tab" style="font-size: 11px;">
                                            <span>{{ $doc->nombre }}</span>
                                            @if($finalPath)
                                                <i class="fas fa-check-circle text-success"></i>
                                            @else
                                                <i class="fas fa-times-circle text-muted"></i>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Visor del Documento --}}
                            <div class="col-md-9 bg-secondary bg-opacity-10" style="min-height: 500px;">
                                <div class="tab-content p-3 h-100" id="v-pills-tabContent">
                                    @foreach($docsV as $index => $doc)
                                        @php
                                            $pathPdf = "storage/vehiculos/{$item->id}/documentos/{$doc->abreviatura}_{$item->id}.pdf";
                                            $pathImgJ = "storage/vehiculos/{$item->id}/documentos/{$doc->abreviatura}_{$item->id}.jpg";
                                            $pathImgP = "storage/vehiculos/{$item->id}/documentos/{$doc->abreviatura}_{$item->id}.png";
                                        @endphp

                                        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }} h-100" 
                                            id="content-{{ $doc->abreviatura }}" role="tabpanel">
                                            
                                            @if(file_exists(public_path($pathPdf)))
                                                <iframe src="{{ asset($pathPdf) }}#toolbar=0" width="100%" height="600px" class="rounded shadow-sm border-0"></iframe>
                                            @elseif(file_exists(public_path($pathImgJ)) || file_exists(public_path($pathImgP)))
                                                @php $img = file_exists(public_path($pathImgJ)) ? $pathImgJ : $pathImgP; @endphp
                                                <div class="text-center bg-white p-2 rounded shadow-sm">
                                                    <img src="{{ asset($img) }}" class="img-fluid rounded">
                                                </div>
                                            @else
                                                <div class="d-flex flex-column align-items-center justify-content-center h-100 py-5 text-muted">
                                                    <i class="fas fa-file-upload fa-3x mb-3 opacity-20"></i>
                                                    <h6 class="fw-black text-uppercase small">Documento no cargado</h6>
                                                    <p class="small mb-0">No se encontró el archivo: {{ $doc->abreviatura }}_{{ $item->id }}</p>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="mantenimiento" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nro Orden</th>
                                    <th>Fecha</th>
                                    <th>Descripción</th>
                                    <th>Responsable</th>
                                    <th>Estatus</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($mantenimientos as $m)
                                    <tr>
                                        <td><a href="{{ route('ordenes.show', $m->id) }}" class="text-decoration-none">#{{ $m->nro_orden }}</a></td>
                                        <td>{{ $m->fecha_in }}</td>
                                        <td class="descripcion-celda" style="cursor: pointer;" onclick="toggleDescripcion(this)">
                                            <div class="fw-bold small text-orange">{{ $m->tipo }}</div>
                                            {{-- Contenedor con clase para truncar --}}
                                            <div class="contenido-descripcion text-muted small mt-1 collapsed-text">
                                                {!! $m->descripcion !!}
                                            </div>
                                            <small class="text-primary x-small btn-leer-mas">
                                                <i class="fas fa-chevron-down me-1"></i>Ver más
                                            </small>
                                        </td>
                                        <td>{{ $m->responsable }}</td>
                                        <td><span class="badge bg-{{ $m->estatus() ? $m->estatus()->css : 'secondary' }}">{{ $m->estatus() ? $m->estatus()->orden : 'Sin Estatus' }}</span></td>
                                        <td class="text-center">
                                            <a href="{{ route('ordenes.show', $m->id) }}" 
                                            class="btn btn-sm btn-outline-dark shadow-sm" 
                                            title="Ver detalle de la Orden">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                <tr class="text-center"><td colspan="4">No hay mantenimientos registrados</td></tr>

                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="viajes" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Origen</th>
                                    <th>Destino</th>
                                    <th>Conductor</th>
                                    <th>Cliente</th>
                                    <th>Carga Litros</th>
                                    <th>Kilometraje</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($viajes as $viaje)

                                    <tr>
                                        <td>{{ $viaje["fecha"] }}</td>
                                        <td> Sede Principal</td>
                                        <td>{{ $viaje["destino"] }}</td>
                                        <td>{{ $viaje["chofer"]}}</td>
                                        <td>{{ $viaje["cliente"] }}</td>
                                        <td>{{ $viaje["litros"] }} L</td>
                                        <td>-- km</td>
                                    </tr>
                                @empty
                                    <tr class="text-center"><td colspan="7">No hay viajes registrados</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="fotos" role="tabpanel">
                    <div class="row g-3">
                        <div class="col-12 text-center p-5">
                            <i class="fa-solid fa-images fa-4x text-light-emphasis mb-3"></i>
                            <p class="text-muted">Módulo de Galería: <strong>En Construcción</strong></p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalKm" data-bs-backdrop="false" tabindex="-1">
    <div class="modal-dialog">
        <form action="#" method="POST" class="modal-content">
            @csrf @method('PATCH')
            <div class="modal-header"><h5>Actualizar Kilometraje</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label>Kilometraje Actual (Placa: {{ $item->placa }})</label>
                <input type="number" name="km_actual" class="form-control form-control-lg" value="{{ $item->km_actual }}" required>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary w-100">Guardar Cambios</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalPlanificar" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-orange">
            <form id="formPlanificarMantenimiento" class="modal-content border-orange">
                @csrf
                <input type="hidden" name="vehiculo_id" value="{{ $item->id }}">
                
                <div class="modal-header bg-corporate text-white">
                    <h5 class="modal-title"><i class="fas fa-tools me-2"></i>Planificar Rutina Preventiva</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-light border mb-3">
                        <small class="text-muted d-block text-uppercase fw-bold small">Contadores Actuales:</small>
                        <div class="d-flex justify-content-between mt-1">
                            <span><strong>KM:</strong> {{ number_format($item->km_contador) }} / 50.000</span>
                            <span><strong>HRS:</strong> {{ number_format($item->hrs_contador) }} / 2.000</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Fecha Programada</label>
                        <input type="date" name="fecha_programada" id="fecha_programada" class="form-control" min="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Rutina a Ejecutar</label>
                        {{-- El name debe ser tipo_mantenimiento para el validate del controlador --}}
                        <label class="form-label fw-bold">Rutina Recomendada</label>
                        <select name="tipo_mantenimiento" id="selectRutina" class="form-select border-primary fw-bold" required>
                            <option value="1" data-short="M1">M1 Basica     (5000/200)</option>
                            <option value="2" data-short="M2">M2 Intermedia (10000/400)</option>
                            <option value="3" data-short="M3">M3 Mayor      (20000/800)</option>
                            <option value="4" data-short="M4">M4 General / Overhaul</option>
                        </select>
                        <div id="rutinaSugeridaMsg" class="form-text text-primary fw-bold mt-2"></div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label small">Notas Adicionales</label>
                        <textarea name="titulo" class="form-control" rows="2" placeholder="Información adicional para la descripción..."></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" id="btnEnviarPlan" class="btn btn-warning fw-bold">
                        <i class="fas fa-save me-1"></i> Generar Planificación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalAcoplar" data-bs-backdrop="false" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content text-center p-4">
            <i class="fa-solid fa-link fa-3x text-info mb-3"></i>
            <div class="modal-header">
                <h6 class="modal-title">Acoplar a <span id="placaChutoModal"></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAcoplar" method="POST" action="{{ route('vehiculos.acoplar') }}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="chuto_id" id="chuto_id_input" value="{{ $item->id }}">
                    <label class="form-label small">Seleccione Unidad</label>
                    <select name="acoplado_id" class="form-select form-select-sm" required>
                        <option value="">-- Seleccionar --</option>
                        @foreach($acoples as $cisterna)
                            <option value="{{ $cisterna->id }}">{{ $cisterna->flota }} - {{ $cisterna->placa }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Confirmar Acople</button>
                </div>
            </
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>
    </div>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {

        // Coordenadas desde el modelo $item
        const lat = {{ $item->latitud ?? 0 }};
        const lng = {{ $item->longitud ?? 0 }};
        const placa = "{{ $item->placa }}";

        // Verificar si hay coordenadas válidas (si no, poner una por defecto o no mostrar)
        if (lat !== 0 && lng !== 0) {
  if (lat !== 0 && lng !== 0) {
    const map = L.map('map', { attributionControl: false }).setView([lat, lng], 16);
    
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png').addTo(map);

    const truckIcon = L.divIcon({
        html: `<div class="map-marker-container"><div class="marker-pulse"></div><i class="fa-solid fa-truck-moving text-corporate"></i></div>`,
        iconSize: [40, 40],
        className: 'custom-truck-icon'
    });

    // Creamos el marcador
    const marker = L.marker([lat, lng], { icon: truckIcon }).addTo(map);

    // 1. Colocamos un contenido temporal mientras carga la dirección
    marker.bindTooltip(`
        <div class="tooltip-content">
            <b>${placa}</b>
            <span id="geo-loader" class="location-text"><i class="fas fa-spinner fa-spin"></i> Traduciendo ubicación...</span>
        </div>`, {
        permanent: true,       // Para que siempre esté visible
        direction: 'top',      // Que aparezca arriba
        offset: [0, -20],      // SUBE la burbuja 20px para que NO tape el camión
        className: 'custom-tooltip'
    }).openTooltip();

    // 2. Llamada asíncrona para actualizar el texto
    obtenerDireccion(lat, lng).then(direccion => {
        const content = `
            <div class="tooltip-content">
                <b>${placa}</b>
                <span class="location-text"><i class="fas fa-map-marker-alt"></i> ${direccion}</span>
            </div>`;
        marker.setTooltipContent(content);
    });
    }
}



// Lógica de recomendación de rutina
    const kmAcumulado = {{ $item->km_contador ?? 0 }};
    const hrsAcumuladas = {{ $item->hrs_contador ?? 0 }};
    const select = document.getElementById('selectRutina');
    const msg = document.getElementById('rutinaSugeridaMsg');

    function calcularSugerencia() {
        let sugerencia = "M1";
        
        // Lógica de ciclos según tu requerimiento:
        // M4 a los 50k km o 2000 hrs
        if (kmAcumulado >= 45000 || hrsAcumuladas >= 1800) {
            sugerencia = "M4";
        } 
        // M3 a los 20k, 40k km o 800, 1600 hrs
        else if ((kmAcumulado >= 15000 && kmAcumulado < 25000) || (kmAcumulado >= 35000 && kmAcumulado < 45000) || 
                 (hrsAcumuladas >= 700 && hrsAcumuladas < 900) || (hrsAcumuladas >= 1500 && hrsAcumuladas < 1700)) {
            sugerencia = "M3";
        }
        // M2 a los 10k, 30k km o 400, 1200 hrs
        else if ((kmAcumulado >= 5000 && kmAcumulado < 15000) || (kmAcumulado >= 25000 && kmAcumulado < 35000) ||
                 (hrsAcumuladas >= 300 && hrsAcumuladas < 500) || (hrsAcumuladas >= 1100 && hrsAcumuladas < 1300)) {
            sugerencia = "M2";
        }

        select.value = sugerencia;
        msg.innerHTML = `<i class="fas fa-info-circle"></i> Sugerencia basada en uso: <strong>${sugerencia}</strong>`;
    }

    // Ejecutar al abrir el modal
    document.getElementById('modalPlanificar').addEventListener('show.bs.modal', calcularSugerencia);



        // Datos de PHP pasados a JavaScript
        const historialMensual = @json($historialMensual);

        // Prepara los datos para Highcharts
        const categories = historialMensual.map(item => item.mes).reverse();
        const kmSeries = historialMensual.map(item => item.km).reverse();
        const consumoSeries = historialMensual.map(item => item.consumo).reverse();

        // Configuración y renderización del gráfico
        Highcharts.chart('monthly-chart', {
            chart: {
                type: 'line'
            },
            title: {
                text: 'Kilometraje vs Consumo Mensual'
            },
            xAxis: {
                categories: categories,
                title: {
                    text: 'Mes'
                }
            },
            yAxis: [{
                // Eje Y para Kilometraje
                title: {
                    text: 'Kilometraje (km)',
                    style: {
                        color: Highcharts.getOptions().colors[0]
                    }
                },
                labels: {
                    format: '{value} km',
                    style: {
                        color: Highcharts.getOptions().colors[0]
                    }
                }
            }, {
                // Eje Y secundario para Consumo
                title: {
                    text: 'Consumo (L)',
                    style: {
                        color: Highcharts.getOptions().colors[1]
                    }
                },
                labels: {
                    format: '{value} L',
                    style: {
                        color: Highcharts.getOptions().colors[1]
                    }
                },
                opposite: true // Ubica el eje en el lado opuesto
            }],
            tooltip: {
                shared: true
            },
            series: [{
                name: 'KM Recorridos',
                data: kmSeries,
                color: Highcharts.getOptions().colors[0],
                tooltip: {
                    valueSuffix: ' km'
                }
            }, {
                name: 'Consumo',
                data: consumoSeries,
                yAxis: 1, // Asigna esta serie al segundo eje Y
                color: Highcharts.getOptions().colors[1],
                tooltip: {
                    valueSuffix: ' L'
                }
            }],
            credits: false
        });


        // 2. Envío de datos al Controlador (Store)
        $('#formPlanificarMantenimiento').on('submit', function(e) {
            e.preventDefault();
            
            const btn = $('#btnEnviarPlan');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

            $.ajax({
                url: "{{ route('mantenimiento.planificacion.store') }}", // Asegúrate que esta ruta apunte a tu función store
                method: "POST",
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Planificado!',
                            text: response.message,
                            confirmButtonColor: '#e67e22'
                        }).then(() => {
                            location.reload(); // Recargamos para ver la nueva OT en el historial
                        });
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false).text('Generar Planificación');
                    const errorMsg = xhr.responseJSON ? xhr.responseJSON.message : 'Error desconocido';
                    Swal.fire('Error', errorMsg, 'error');
                }
            });
        });

    });

            function desacoplar(id) {
                Swal.fire({
                    title: '¿Desacoplar unidad?',
                    text: "El chuto y la cisterna figurarán como independientes.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sí, desacoplar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `/vehiculos/desacoplar/${id}`;
                    }
                });
            }

            function toggleDescripcion(elemento) {
                const contenedor = $(elemento).find('.contenido-descripcion');
                const btn = $(elemento).find('.btn-leer-mas');

                if (contenedor.hasClass('collapsed-text')) {
                    // Expandir
                    contenedor.removeClass('collapsed-text').addClass('expanded-text');
                    btn.html('<i class="fas fa-chevron-up me-1"></i>Ver menos');
                } else {
                    // Contraer
                    contenedor.removeClass('expanded-text').addClass('collapsed-text');
                    btn.html('<i class="fas fa-chevron-down me-1"></i>Ver más');
                }
            }

            async function obtenerDireccion(lat, lng) {
    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`);
        const data = await response.json();
        
        // Estructuramos la dirección: Calle, Población/Municipio, Estado.
        const calle = data.address.road || data.address.pedestrian || 'Vía innominada';
        const poblacion = data.address.city || data.address.town || data.address.village || 'N/D';
        const estado = data.address.state || '';
        
        return `${calle}, ${poblacion}, ${estado}`;
    } catch (error) {
        return "Dirección no disponible";
    }
}


</script>
@endsection
