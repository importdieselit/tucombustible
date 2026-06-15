@extends('layouts.app') 

@push('styles')
<style>
    /* --- VARIABLES Y COLORES CORPORATIVOS --- */
    :root {
        --color-corporate-blue: #0f2d59;
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
        background-color: var(--color-corporate-blue) !important;
        border-color: var(--color-corporate-blue) !important;
        box-shadow: 0 0 10px rgba(15, 45, 89, 0.5);
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
            @if(isset($almacen) && isset($ubicacionPrincipal))
                <div class="row g-4">
                    
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
                        <p class="text-muted text-uppercase fw-bold small mb-2 letter-spacing-1">Elevación de Estructura</p>
                        <div class="mini-croquis-container shadow-sm bg-white">
                            @php $gridActivo = $ubicacionPrincipal->estructuraGrid ?? null; @endphp
                            @if($gridActivo)
                                <div class="grid-alzado" style="grid-template-rows: repeat({{ $gridActivo->cantidad_niveles }}, 1fr);">
                                    @for($nivel = $gridActivo->cantidad_niveles; $nivel >= 1; $nivel--)
                                        <div class="nivel-alzado" style="grid-template-columns: repeat({{ $gridActivo->cantidad_secciones }}, 1fr);">
                                            @for($pos = 1; $pos <= $gridActivo->cantidad_secciones; $pos++)
                                                @php $isBinActive = ($ubicacionPrincipal->nivel == $nivel && $ubicacionPrincipal->posicion == $pos); @endphp
                                                <div class="casilla-alzado {{ $isBinActive ? 'active-location' : '' }}"></div>
                                            @endfor
                                        </div>
                                    @endfor
                                </div>
                            @else
                                <span class="text-muted align-self-center">Sin estructura base registrada</span>
                            @endif
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