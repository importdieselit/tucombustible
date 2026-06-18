@extends('layouts.app') 

@push('styles')
<style>
    /* --- VARIABLES Y COLORES CORPORATIVOS --- */
    :root {
        --color-corporate-blue: #0f2d59;
        --color-corporate-red: #ef4444;
        --color-industrial-orange: #f59e0b;
        --color-industrial-orange-dark: #b45309;
    }

    .bg-dashboard { background-color: #f1f5f9; } 
    .kpi-card { background: #ffffff; border-left: 4px solid var(--color-corporate-blue); border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .table-header-custom { background-color: var(--color-corporate-blue) !important; color: white; }
    .text-bold-custom { font-weight: 700; color: var(--color-corporate-blue); }
    .text-bold-title { font-weight: 800; color: var(--color-corporate-blue); font-size: 1.7rem; }
    .chart-container { background: white; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0; }

    /* --- ESTILOS DE LOS PLANOS 2D (CROQUIS) --- */
    .mini-croquis-container {
        background-color: #f8f9fa;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        height: 250px;
        padding: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    /* 1. Vista de Planta (Almacén completo) */
    .grid-planta {
        display: grid;
        gap: 4px;
        width: 100%;
        height: 100%;
        background: white;
        border: 1px solid #cbd5e1;
        padding: 5px;
    }
    .celda-planta {
        background-color: #f1f5f9;
        border: 1px dashed #e2e8f0;
        border-radius: 2px;
    }
    .celda-planta.has-est {
        background-color: #94a3b8;
        border: 1px solid #64748b;
    }   
    .celda-planta.active-location {
        background-color: var(--color-corporate-red) !important;
        border-color: var(--color-corporate-red) !important;
        box-shadow: 0 0 10px rgba(239,68,68,0.5);
        animation: pulse 2s infinite;
    }

    /* 2. Vista de Alzado (Estante Frontal) */
    .grid-alzado {
        display: grid;
        gap: 6px;
        width: 80%;
        height: 90%;
        background-color: #e2e8f0;
        padding: 8px;
        border-radius: 4px;
        border-bottom: 6px solid #475569;
    }
    .nivel-alzado {
        display: grid;
        gap: 4px;
    }
    .casilla-alzado {
        background-color: white;
        border: 1px solid #94a3b8;
        border-radius: 2px;
    }
    .casilla-alzado.active-location {
        background-color: var(--color-industrial-orange);
        border: 2px solid var(--color-industrial-orange-dark);
        box-shadow: inset 0 0 8px rgba(180, 83, 9, 0.3);
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.08); }
        100% { transform: scale(1); }
    }

    #tablaMatrizEstante th, #tablaMatrizEstante td {
    vertical-align: middle !important;
}

/* Estados estándar */
.slot-libre {
    border-color: #10b981 !important; /* Verde Esmeralda */
}
.slot-ocupado {
    border-color: #ef4444 !important; /* Rojo Cuidado */
}

/* ESTADO DE ALTA PRIORIDAD: Resalta la ubicación del ítem consultado */
.slot-item-actual {
    border-color: #ffc107 !important; /* Amarillo/Dorado Neón de Bootstrap */
    background-color: #2d2613 !important; /* Fondo ámbar oscuro para contraste */
    box-shadow: 0 0 12px rgba(255, 193, 7, 0.7);
    transform: scale(1.02);
    z-index: 5;
}

/* Animación sutil de respiración sobre el borde del producto actual */
.pulse-item-border {
    animation: borderGlow 2.5s infinite;
}

@keyframes borderGlow {
    0% { border-color: #ffc107; box-shadow: 0 0 4px rgba(255, 193, 7, 0.4); }
    50% { border-color: #fd7e14; box-shadow: 0 0 14px rgba(253, 126, 20, 0.8); }
    100% { border-color: #ffc107; box-shadow: 0 0 4px rgba(255, 193, 7, 0.4); }
}
</style>
@endpush

@section('content')
<div class="container-fluid py-4 bg-dashboard">
    
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded shadow-sm">
        <div>
            <h3 class="mb-0 text-dark fw-bold">{{ $item->codigo }} - {{ $item->descripcion }}</h3>
            <span class="text-muted"><i class="bi bi-tag"></i> {{ $item->categoria ?? $item->grupo ?? 'General' }}</span>
        </div>
        <div>
            <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer"></i> Imprimir Ficha</button>
            <button class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i> Editar Ítem</button>
        </div>
    </div>

    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="text-bold-custom border-bottom pb-2 mb-3">Información del Ítem</h5>
                    <p class="mb-1"><strong>ID de Ítem:</strong> {{ $item->codigo ?? 'N/A' }}</p>
                    <p class="mb-1"><strong>Nombre:</strong> {{ $item->descripcion ?? 'N/A' }}</p>
                    <p class="mb-1"><strong>Categoría/Grupo:</strong> {{ $item->grupo ?? $item->categoria ?? 'N/A' }}</p>
                    <p class="mb-1"><strong>Almacén Base:</strong> {{ $item->almacen->nombre ?? 'N/A' }}</p>
                </div>
                <div class="col-md-6">
                    <h5 class="text-bold-custom border-bottom pb-2 mb-3">Existencia y Estado</h5>
                    <p class="mb-1">
                        <strong>Existencia Actual:</strong> {{ number_format($item->stock_actual ?? $item->existencia ?? 0, 0, ',', '.') }} {{ $item->unidad ?? 'Und' }}
                        @if (($item->stock_actual ?? $item->existencia ?? 0) < ($item->existencia_minima ?? 10))
                            <span class="badge bg-danger ms-2">Bajo Stock</span>
                        @else
                            <span class="badge bg-success ms-2">Stock Óptimo</span>
                        @endif
                    </p>
                    <p class="mb-1"><strong>Existencia Mínima:</strong> {{ number_format($item->existencia_minima ?? 0, 0, ',', '.') }}</p>
                    <p class="mb-1"><strong>Fecha de Registro:</strong> {{ !empty($item->created_at) ? \Carbon\Carbon::parse($item->created_at)->format('d/m/Y') : 'N/A' }}</p>
                </div>
            </div>
            
            <hr class="text-muted my-4">

            <h5 class="text-bold-custom mb-3">Opciones de Inventario</h5>
            <div class="d-flex justify-content-start flex-wrap gap-2">
                <a href="{{ route('inventario.entry', $item->id) }}" class="btn btn-success d-flex align-items-center fw-bold shadow-sm">
                    <i class="bi bi-plus-circle me-2"></i> Registrar Entrada
                </a>
                <a href="{{ route('inventario.adjustment', $item->id) }}" class="btn btn-warning text-dark d-flex align-items-center fw-bold shadow-sm">
                    <i class="bi bi-sliders me-2"></i> Registrar Ajuste
                </a>
            </div>
        </div>
    </div>  

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column justify-content-center">
                    <div class="row text-center g-3">
                        <div class="col-12">
                            <h6 class="text-muted text-uppercase mb-1 fw-bold">Stock Actual</h6>
                            <h2 class="fw-bold text-primary mb-0" style="font-size: 2.5rem;">{{ $item->stock_actual ?? $item->existencia ?? 0 }} <span class="fs-6 text-muted">{{ $item->unidad ?? 'Und' }}</span></h2>
                        </div>
                        <div class="col-12"><hr class="my-1 text-muted"></div>
                        <div class="col-6">
                            <h6 class="text-muted mb-1 text-uppercase fw-bold" style="font-size: 0.75rem;">Rotación</h6>
                            <h4 class="mb-0 text-dark">{{ $item->tasa_rotacion ?? '0.0' }}</h4>
                        </div>
                        <div class="col-6 border-start">
                            <h6 class="text-muted mb-1 text-uppercase fw-bold" style="font-size: 0.75rem;">Duración (Días)</h6>
                            <h4 class="mb-0 text-dark">{{ $item->promedio_duracion ?? '0' }} <span class="fs-6 text-muted">d</span></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body pb-0">
                    <h6 class="text-bold-custom mb-3"><i class="bi bi-graph-up text-primary me-2"></i> Historial de Existencias (Últimos 15 días)</h6>
                    <div style="height: 220px; width: 100%;">
                        <canvas id="stockChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4 mt-4">
        <div class="card-header bg-white border-bottom pt-4 pb-3">
            <h6 class="text-bold-custom mb-0 fs-5">
                <i class="bi bi-geo-alt-fill text-danger me-2"></i> Ubicación Física Registrada: 
                <span class="text-dark">{{ $ubicacion_texto ?? 'No Asignada' }}</span>
            </h6>
        </div>
        <div class="card-body bg-light">
            <div class="row w-100 col-12">
            @if(isset($almacen) && isset($ubicacionPrincipal))
                
                    <div class="col-md-4 text-center">
                        <p class="text-muted text-uppercase fw-bold small mb-2 letter-spacing-1">Planta de Almacén</p>
                        <div class="mini-croquis-container shadow-sm bg-white">
                            @php 
                                $cols = $almacen->total_columnas_grid ?? 10;
                                $filas = $almacen->total_filas_grid ?? 10;
                            @endphp
                            <div class="grid-planta" style="grid-template-columns: repeat({{ $cols }}, 1fr);">
                                @for($y = 1; $y <= $filas; $y++)
                                    @for($x = 1; $x <= $cols; $x++)
                                        @php
                                            $est = $estructuras["{$y}-{$x}"] ?? null;
                                            $isActive = $est && $est->id == $ubicacionPrincipal->estructura_grid_id;
                                        @endphp
                                        <div class="celda-planta {{ $est ? 'has-est' : '' }} {{ $isActive ? 'active-location' : '' }}"></div>
                                    @endfor
                                @endfor
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 text-center">
                        <div class="table-responsive bg-dark p-3 rounded shadow-inner mb-4">
                            <table class="table table-bordered text-center align-middle mb-0 text-white border-secondary" id="tablaMatrizEstante">
                                <thead>
                                    <tr>
                                        <th class="bg-secondary text-light small border-secondary" style="width:120px;">Nivel \ Posición</th>
                                        @for($p = 1; $p <= $gridActivo->cantidad_secciones; $p++)
                                            <th class="bg-secondary text-light small border-secondary">Posición {{ $p }}</th>
                                        @endfor
                                    </tr>
                                </thead>
                                <tbody>
                                    @for($n = $gridActivo->cantidad_niveles; $n >= 1; $n--)
                                        <tr>
                                            <td class="bg-dark text-light fw-bold small align-middle border-secondary">Nivel {{ $n }}</td>
                                            
                                            @php 
                                                $saltarColumnas = 0; 
                                            @endphp

                                            @for($p = 1; $p <= $gridActivo->cantidad_secciones; $p++)
                                                {{-- Ignorar celdas absorbidas por un colspan anterior --}}
                                                @if($saltarColumnas > 0)
                                                    @php $saltarColumnas--; @endphp
                                                    @continue
                                                @endif

                                                @php
                                                    $key = "{$n}-{$p}";
                                                    // Obtenemos los slots que pertenecen a esta coordenada
                                                    $slotsCelda = $cacheUbicacionesEstante->get($key);
                                                @endphp

                                                @if($slotsCelda && $slotsCelda->count() > 0)
                                                    @php
                                                        // Evaluamos el colspan configurado en la primera ubicación del bloque
                                                        $colspan = $slotsCelda->first()->colspan ?? 1;
                                                        if ($colspan > 1) {
                                                            $saltarColumnas = $colspan - 1;
                                                        }
                                                    @endphp

                                                    <td colspan="{{ $colspan }}" class="p-1 align-middle border-secondary bg-dark-subtle" style="min-width: 100px;">
                                                        {{-- Contenedor flexible para múltiples subdivisiones (subposiciones) --}}
                                                        <div class="d-flex w-100 h-100 gap-1 justify-content-center">
                                                            @foreach($slotsCelda as $slot)
                                                                @php
                                                                    $ocupado = $slot->ocupado ?? false;
                                                                    
                                                                    // Condición CRÍTICA: ¿Es este slot el lugar específico del ítem actual?
                                                                    $isCurrentItemLocation = ($slot->id == $ubicacionPrincipal->id);
                                                                    
                                                                    // Selección de Clases de Estado y Bordes
                                                                    if ($isCurrentItemLocation) {
                                                                        $claseEstado = 'slot-item-actual pulse-item-border';
                                                                    } else {
                                                                        $claseEstado = $ocupado ? 'slot-ocupado border-danger' : 'slot-libre border-success';
                                                                    }

                                                                    // Cálculo métrico de ocupación
                                                                    $porcentaje = 0;
                                                                    $colorBarra = 'bg-success';
                                                                    if ($ocupado && $slot->inventario) {
                                                                        $capacidad = $slot->capacidad_maxima ?? $slot->total_articulos ?? 1;
                                                                        $actual = $slot->total_articulos ?? 0;
                                                                        $porcentaje = $capacidad > 0 ? ($actual / $capacidad) * 100 : 0;
                                                                        $colorBarra = $porcentaje > 90 ? 'bg-danger' : ($porcentaje > 70 ? 'bg-warning' : 'bg-success');
                                                                    }
                                                                @endphp

                                                                <div class="slot-rack flex-fill p-2 rounded text-center {{ $claseEstado }}" 
                                                                    style="background: #1e293b; border: 1px solid; min-width: 85px; transition: 0.2s;">
                                                                    
                                                                    @if($slot->subposicion)
                                                                        <span class="badge bg-secondary mb-1" style="font-size: 7px; padding: 2px 4px;">SUB: {{ $slot->subposicion }}</span>
                                                                    @endif
                                                                    
                                                                    <div class="fw-bold" style="font-size: 10px; color: #cbd5e1;">N{{ $n }}-P{{ $p }}</div>
                                                                    
                                                                    @if($ocupado && $slot->inventario)
                                                                        <div class="small text-truncate text-white-50 mt-1" style="font-size:9px;" title="{{ $slot->inventario->producto }}">
                                                                            {{ $slot->inventario->sku }}
                                                                        </div>
                                                                        <div class="fw-bold text-light" style="font-size:10px;">
                                                                            {{ $slot->total_articulos }} / {{ $slot->capacidad_maxima ?? '∞' }}
                                                                        </div>
                                                                        
                                                                        <div class="progress mt-1" style="height: 4px; background-color: #334155;">
                                                                            <div class="progress-bar {{ $colorBarra }}" role="progressbar" style="width: {{ min($porcentaje, 100) }}%"></div>
                                                                        </div>
                                                                        <div class="d-flex justify-content-between mt-1" style="font-size: 7px; opacity: 0.8;">
                                                                            <span style="color: #94a3b8;">Llenado</span>
                                                                            <span class="fw-bold text-white">{{ round($porcentaje) }}%</span>
                                                                        </div>
                                                                    @else
                                                                        <div class="mt-1"><small style="font-size:9px;" class="text-muted">Libre</small></div>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                @else
                                                    {{-- Celda vacía o sin configurar en la matriz --}}
                                                    <td class="bg-dark text-muted text-center align-middle border-secondary" style="opacity:0.2; border-style: dashed !important;">-</td>
                                                @endif
                                            @endfor
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col-md-4 text-center">
                        <p class="text-muted text-uppercase fw-bold small mb-2 letter-spacing-1">Referencia Visual 3D</p>
                        <div class="mini-croquis-container p-0 border-0 shadow-sm bg-black" id="canvas-container" style="position: relative; cursor: grab;">
                            <div class="position-absolute bottom-0 start-50 translate-middle-x mb-2 text-white small opacity-75" style="pointer-events: none; z-index: 5;">
                                Arrastrar para rotar
                            </div>
                        </div>
                    </div>
            
            @else
                <div class="text-center py-5">
                    <i class="bi bi-box-seam text-muted fs-1 mb-3"></i>
                    <h5 class="text-muted">Este ítem aún no tiene una ubicación física asignada en el almacén.</h5>
                </div>
            @endif
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-5">
        <div class="card-header bg-white border-bottom pt-3 pb-0">
            <ul class="nav nav-tabs border-0" id="itemTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold text-dark border-0" id="historial-tab" data-bs-toggle="tab" data-bs-target="#historial" type="button" role="tab">Historial (Últ. 30)</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link text-muted border-0" id="sustitutos-tab" data-bs-toggle="tab" data-bs-target="#sustitutos" type="button" role="tab">Equivalentes</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link text-muted border-0" id="vehiculos-tab" data-bs-toggle="tab" data-bs-target="#vehiculos" type="button" role="tab">Vehículos Asoc.</button>
                </li>
            </ul>
        </div>
        <div class="card-body p-0">
            <div class="tab-content" id="itemTabsContent">
                
                <div class="tab-pane fade show active p-3" id="historial" role="tabpanel">
                    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-hover table-sm align-middle mb-0">
                            <thead class="table-light position-sticky top-0 z-1">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tipo</th>
                                    <th>Cant.</th>
                                    <th>Documento</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($movimientos ?? [] as $mov)
                                <tr>
                                    <td class="text-muted small">{{ $mov->fecha }}</td>
                                    <td>
                                        <span class="badge {{ $mov->tipo == 'Entrada' ? 'bg-success' : 'bg-danger' }}">
                                            {{ $mov->tipo }}
                                        </span>
                                    </td>
                                    <td class="fw-bold {{ $mov->cantidad > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $mov->cantidad > 0 ? '+'.$mov->cantidad : $mov->cantidad }}
                                    </td>
                                    <td class="small">{{ $mov->documento }}</td>
                                    <td class="small">{{ $mov->usuario }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">No se registran movimientos recientes.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade p-3" id="sustitutos" role="tabpanel">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Descripción</th>
                                <th>Stock Actual</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($equivalentes ?? [] as $eq)
                            <tr>
                                <td class="fw-bold">{{ $eq->codigo }}</td>
                                <td class="small">{{ $eq->descripcion }}</td>
                                <td class="small">{{ $eq->stock }} Und</td>
                                <td><a href="#" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">No existen códigos equivalentes cargados.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane fade p-3" id="vehiculos" role="tabpanel">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Placa</th>
                                <th>Modelo</th>
                                <th>Departamento</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($vehiculos ?? [] as $veh)
                            <tr>
                                <td class="fw-bold">{{ $veh->placa }}</td>
                                <td class="small">{{ $veh->modelo }}</td>
                                <td class="small">{{ $veh->departamento }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">No hay vehículos vinculados a este repuesto/ítem.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    
    // --- CONTROLADOR DE PESTAÑAS (TABS) ---
    const triggerTabList = document.querySelectorAll('#itemTabs button');
    triggerTabList.forEach(tab => {
        tab.addEventListener('click', () => {
            triggerTabList.forEach(t => {
                t.classList.remove('active', 'fw-bold', 'text-dark');
                t.classList.add('text-muted');
            });
            tab.classList.remove('text-muted');
            tab.classList.add('active', 'fw-bold', 'text-dark');
        });
    });

    // --- GRÁFICA DE HISTORIAL DE STOCK (CHART.JS) ---
    const ctx = document.getElementById('stockChart');
    if(ctx) {
        new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: {!! json_encode($graficaFechas ?? []) !!},
                datasets: [{
                    label: 'Nivel de Stock',
                    data: {!! json_encode($graficaStock ?? []) !!},
                    borderColor: '#0f2d59', 
                    backgroundColor: 'rgba(15, 45, 89, 0.06)',
                    borderWidth: 2,
                    pointRadius: 4,
                    pointBackgroundColor: '#f59e0b', 
                    fill: true,
                    tension: 0.4 
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { borderDash: [2, 4], color: '#e2e8f0' } 
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // --- MOTOR GRÁFICO 3D (THREE.JS) ---
    @if(isset($almacen) && isset($ubicacionPrincipal))
        const container = document.getElementById('canvas-container');
        if (container && container.clientWidth > 0) {
            inicializarRender3D(container);
        } else {
            // Re-intento por si el DOM tardó en computar dimensiones
            setTimeout(() => {
                const retryContainer = document.getElementById('canvas-container');
                if(retryContainer) inicializarRender3D(retryContainer);
            }, 200);
        }

        function inicializarRender3D(targetContainer) {
            const scene = new THREE.Scene();
            scene.background = new THREE.Color(0x1a1a1a);

            // Iluminación
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
            scene.add(ambientLight);
            const dirLight = new THREE.DirectionalLight(0xffffff, 0.5);
            dirLight.position.set(10, 20, 10);
            scene.add(dirLight);

            // Cámara y Renderizador
            const camera = new THREE.PerspectiveCamera(45, targetContainer.clientWidth / targetContainer.clientHeight, 0.1, 1000);
            const renderer = new THREE.WebGLRenderer({ antialias: true });
            renderer.setSize(targetContainer.clientWidth, targetContainer.clientHeight);
            targetContainer.appendChild(renderer.domElement);

            // Controles de Órbita
            const controls = new THREE.OrbitControls(camera, renderer.domElement);
            controls.enableDamping = true;
            controls.enablePan = false;
            controls.maxPolarAngle = Math.PI / 2 - 0.05; 

            // Construcción del Suelo Industrial
            const planeGeo = new THREE.PlaneGeometry(60, 60);
            const planeMat = new THREE.MeshPhongMaterial({ color: 0x222222, side: THREE.DoubleSide });
            const plane = new THREE.Mesh(planeGeo, planeMat);
            plane.rotation.x = Math.PI / 2;
            scene.add(plane);

            // Datos Inyectados de Estructuras
            const estructuras = @json($estructuras->values());
            const cols = {{ $almacen->total_columnas_grid ?? 10 }};
            const filas = {{ $almacen->total_filas_grid ?? 10 }};
            const idTarget = {{ $ubicacionPrincipal->estructura_grid_id }};
            let targetPos = new THREE.Vector3(0, 0, 0);

            // Renderizado Dinámico de Bloques Estructurales
            estructuras.forEach(est => {
                const x = (est.coord_x - cols/2) * 2;
                const z = (est.coord_y - filas/2) * 2;
                const height = (est.cantidad_niveles * 0.8) || 2;
                const isTarget = (est.id === idTarget);
                
                const material = new THREE.MeshPhongMaterial({
                    color: isTarget ? 0xf59e0b : 0x64748b,
                    transparent: !isTarget,
                    opacity: isTarget ? 1.0 : 0.5
                });

                const geometry = new THREE.BoxGeometry(1.6, height, 1);
                const mesh = new THREE.Mesh(geometry, material);
                mesh.position.set(x, height / 2, z);
                scene.add(mesh);

                if(isTarget) {
                    targetPos.copy(mesh.position);
                    
                    // Indicador cónico flotante sobre el estante objetivo
                    const indicatorMat = new THREE.MeshBasicMaterial({ color: 0xffffff });
                    const indicatorGeo = new THREE.ConeGeometry(0.25, 0.5, 4);
                    const indicator = new THREE.Mesh(indicatorGeo, indicatorMat);
                    indicator.position.set(x, height + 0.8, z);
                    indicator.rotation.x = Math.PI; 
                    scene.add(indicator);
                }
            });

            // Posicionamiento focalizado de la cámara
            camera.position.set(targetPos.x + 5, targetPos.y + 5, targetPos.z + 5);
            controls.target.copy(targetPos);
            controls.update();

            function animate() {
                requestAnimationFrame(animate);
                controls.update();
                renderer.render(scene, camera);
            }
            animate();

            // Evento Responsivo del Viewport 3D
            window.addEventListener('resize', () => {
                if(!targetContainer) return;
                camera.aspect = targetContainer.clientWidth / targetContainer.clientHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(targetContainer.clientWidth, targetContainer.clientHeight);
            });
        }
    @endif
    
});
</script>
@endpush
@endsection