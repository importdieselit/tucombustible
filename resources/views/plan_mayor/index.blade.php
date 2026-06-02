@extends('layouts.app')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
<style>
    .plan-mayor-container {
        font-family: 'Inter', sans-serif;
        color: #1e293b;
        background-color: #f8fafc;
    }
    
    .text-money {
        font-family: 'JetBrains Mono', monospace;
        font-weight: 700;
        color: #0f766e;
    }

    .card-kpi-executive {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
    }

    /* --- CONTENEDOR DE SCROLL INTERNO CON ALTURA ESTÁTICA --- */
    .matrix-scroll-wrapper {
        max-height: 580px; /* Altura máxima controlada para pantallas ejecutivas */
        overflow-y: auto;
        overflow-x: auto;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        position: relative;
    }

    /* --- CONGELACIÓN DE ENCABEZADOS Y COLUMNA IZQUIERDA (STICKY) --- */
    .table-executive-matrix {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    
   .table-executive-matrix thead tr:first-child th {
    position: sticky;
    top: 0;
    background-color: #1e293b !important;
    color: #ffffff;
    z-index: 5;
    border-bottom: 1px solid #cbd5e1;
}

.matrix-cell-clickable {
    cursor: pointer;
    transition: all 0.15s ease-in-out;
    position: relative;
    user-select: none; /* Evita que se seleccione el texto de la celda accidentalmente */
}

/* Efecto al pasar el cursor: indica que toda la celda es un botón */
.matrix-cell-clickable:hover {
    background-color: #f1f5f9 !important; 
}

/* Fondo corporativo sutil para las celdas con trabajos activos */
.matrix-cell-clickable.cell-active-bg {
    background-color: #fff7ed !important; /* Un tono naranja traslúcido muy elegante */
}

/* Evitar clics dobles mientras se procesa la petición en segundo plano */
.matrix-cell-clickable.is-processing {
    pointer-events: none;
    opacity: 0.5;
}

/* Regla para las columnas maestras fijas de doble altura (Unidad, Cant, Total) */
.table-executive-matrix thead tr th[rowspan="2"] {
    z-index: 10; /* Mayor prioridad para tapar el scroll horizontal y vertical simultáneo */
    background-color: #1e293b !important;
}

/* Regla corregida para la segunda fila (Sub-baremos) */
.table-executive-matrix thead tr:nth-child(2) th {
    position: sticky;
    /* Usamos la variable dinámica calculada por el navegador. Si falla, cae en 32px por seguridad */
    top: var(--first-row-height, 0px); 
    background-color: #1e293b !important;
    color: #ffffff;
    z-index: 5;
    border-bottom: 2px solid #cbd5e1;
}

    /* Congelación de la primera columna (Nombre de la Unidad Cisterna) */
    .table-executive-matrix tbody td:first-child,
    .table-executive-matrix thead th:first-child {
        position: sticky;
        left: 0;
        z-index: 6;
        background-color: #f8fafc;
        border-right: 2px solid #cbd5e1;
    }
    .table-executive-matrix thead th:first-child {
        z-index: 10; /* Mayor peso para la esquina superior izquierda */
        background-color: #1e293b !important;
    }

    /* --- LECTURA COMPLETA Y CLARA DE TÍTULOS --- */
    .table-executive-matrix thead th.title-header-complete {
        white-space: normal;       /* Permite quiebre de líneas natural sin cortar texto */
        min-width: 160px;          /* Ancho óptimo para leer términos técnicos largos */
        max-width: 220px;
        font-size: 11px;
        line-height: 1.3;
        padding: 12px 8px;
        font-weight: 600;
        letter-spacing: 0.3px;
    }

    .table-executive-matrix tbody td {
        padding: 10px;
        font-size: 13px;
        border-bottom: 1px solid #e2e8f0;
        border-right: 1px solid #e2e8f0;
        vertical-align: middle;
        background-color: #ffffff;
    }

    .table-executive-matrix tbody tr:hover td {
        background-color: #f1f5f9 !important;
    }

    .nav-tabs-modern .nav-link-custom {
        color: #64748b;
        font-weight: 600;
        font-size: 14px;
        padding: 12px 20px;
        border: none;
        background: transparent;
        cursor: pointer;
        position: relative;
    }
    .nav-tabs-modern .nav-link-custom.active {
        color: #ff6600;
    }
    .nav-tabs-modern .nav-link-custom.active::after {
        content: ''; position: absolute; bottom: -2px; left: 0; width: 100%; height: 2px; background-color: #ff6600;
    }

    .badge-executive { padding: 5px 10px; font-size: 11px; font-weight: 700; border-radius: 6px; text-transform: uppercase; }
    .badge-ovh { background-color: #fef2f2; color: #991b1b; border: 1px solid #fee2e2; }
    .badge-rep { background-color: #fffbeb; color: #92400e; border: 1px solid #fef3c7; }

    /* --- BOTONES DE EXPORTACIÓN EJECUTIVA --- */
.btn-export-custom {
    font-weight: 600;
    font-size: 12px;
    padding: 6px 16px;
    border-radius: 20px;
    transition: all 0.2s ease;
}

/* --- HOJA DE ESTILOS DE IMPRESIÓN (AISLAMIENTO ONE-PAGE PDF) --- */
@media print {
    /* Ocultar absolutamente toda la interfaz web, menús, barras y pestañas */
    body *, 
    .layouts-app-sidebar, 
    .navbar, 
    .plan-mayor-container > div:not(#panel-resumen),
    #planMayorTabs,
    .tab-pane-custom:not(#panel-resumen),
    .border-bottom {
        display: none !important;
        visibility: hidden !important;
    }
    
    /* Hacer visible únicamente el área limpia del reporte ejecutivo */
    #panel-resumen,
    #area-reporte-final, 
    #area-reporte-final * {
        visibility: visible !important;
        display: block !important;
    }
    
    #area-reporte-final {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        border: none !important;
        padding: 0 !important;
        background: #ffffff !important;
    }

    /* Optimización de tabla en PDF para evitar saltos de página huérfanos */
    .table-executive-matrix {
        page-break-inside: avoid;
        width: 100% !important;
    }
    
    .table-executive-matrix thead tr th {
        background-color: #1e293b !important;
        color: #000000 !important; /* Algunos navegadores fuerzan texto oscuro al imprimir */
    }
}
</style>
@endpush

@section('content')
<div class="container-fluid py-4 plan-mayor-container">
    
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-lg d-flex align-items-center mb-4" id="main-alert" role="alert">
            <i class="fas fa-check-circle mr-2 text-success" style="font-size: 18px;"></i>
            <div class="font-weight-medium text-dark">{{ session('success') }}</div>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="font-weight-bold tracking-tight m-0 text-dark">PLAN MAYOR DE MANTENIMIENTO</h2>
            <p class="text-muted m-0 small">Estructura analítica de costos y asignación operativa de la flota</p>
        </div>
        <div class="card-kpi-executive p-3 d-flex align-items-center bg-white">
            <div class="mr-3 bg-light rounded p-2 text-center" style="width: 40px; height: 40px;">
                <i class="fas fa-wallet text-muted"></i>
            </div>
            <div>
                <small class="text-muted text-uppercase d-block font-weight-bold" style="font-size: 10px;">Presupuesto Consolidado</small>
                <span class="text-money h4 m-0" id="kpi-global">$ {{ number_format($totalInversionGlobal, 2, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <div class="nav-tabs-modern d-flex mb-4 border-bottom" id="planMayorTabs" role="tablist">
        <button class="nav-link-custom active" data-target="#panel-control">
            <i class="fas fa-layer-group mr-2"></i> 1. Matriz de Control
        </button>
        <button class="nav-link-custom" data-target="#panel-baremos">
            <i class="fas fa-calculator mr-2"></i> 2. Baremos de Mercado
        </button>
        <button class="nav-link-custom" data-target="#panel-resumen">
            <i class="fas fa-print mr-2"></i> 3. Reporte Ejecutivo
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-xl p-4 bg-white">
        
        <div class="tab-pane-custom d-block" id="panel-control">
            <div class="matrix-scroll-wrapper">
                <table class="table-executive-matrix">
                    <thead class="text-center">
                        <tr>
                            <th rowspan="2" class="align-middle text-left" style="min-width: 220px; padding-left: 15px;">Unidad Cisterna</th>
                            @foreach($itemsAgrupados as $categoria => $itemsCat)
                                <th colspan="{{ count($itemsCat) }}" class="text-uppercase" style="font-size: 10px; background-color: #2c3e50 !important; letter-spacing: 1px;">{{ $categoria }}</th>
                            @endforeach
                            <th rowspan="2" class="align-middle" style="width: 70px;">Cant</th>
                            <th rowspan="2" class="align-middle text-right" style="min-width: 150px; padding-right: 15px;">Total Inversión</th>
                        </tr>
                        <tr>
                            @foreach($itemsAgrupados as $categoria => $itemsCat)
                                @foreach($itemsCat as $it)
                                    <th class="title-header-complete border-top">{{ $it->nombre }}</th>
                                @endforeach
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($unidades as $u)
                            @php 
                                $idsActivos = $u->planMayorItems->pluck('id')->toArray(); 
                                $totalUnidad = $u->planMayorItems->sum('costo_promedio');
                            @endphp
                            <tr data-unidad-id="{{ $u->id }}">
                                <td class="font-weight-bold text-dark" style="padding-left: 15px;">
                                    <i class="fas fa-truck-moving text-muted mr-2" style="font-size:11px;"></i>{{ $u->flota }} - {{ $u->placa }}
                                </td>
                               @foreach($itemsAgrupados as $categoria => $itemsCat)
                                    @foreach($itemsCat as $it)
                                        @php 
                                            $hasItem = in_array($it->id, $idsActivos); 
                                        @endphp
                                        <td class="text-center matrix-cell-clickable col-chk-box {{ $hasItem ? 'cell-active-bg' : '' }}" 
                                            data-unidad="{{ $u->id }}" 
                                            data-item="{{ $it->id }}">
                                            
                                            <i class="fas fa-check check-icon {{ $hasItem ? '' : 'd-none' }}" 
                                            style="color: #ff6600; font-size: 14px; vertical-align: middle;"></i>
                                        </td>
                                    @endforeach
                                @endforeach
                                <td class="text-center font-weight-bold text-secondary cell-cant">{{ $u->planMayorItems->count() }}</td>
                                <td class="text-right text-money cell-total-unidad" style="padding-right: 15px;">
                                    $ {{ number_format($totalUnidad, 2, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane-custom d-none" id="panel-baremos">
            <div class="row">
                <div class="col-lg-8 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="font-weight-bold text-muted m-0">Catálogo Operativo de Costos Estándar</h6>
                        <button class="btn btn-sm btn-success rounded-pill px-3 font-weight-bold shadow-sm" id="btn-crear-nuevo-item">
                            <i class="fas fa-plus mr-1"></i> Registrar Nuevo Ítem
                        </button>
                    </div>
                    
                    <div class="table-responsive border rounded bg-white" style="max-height: 500px; overflow-y: auto;">
                        <table class="table-executive-matrix m-0">
                            <thead>
                                <tr class="bg-light">
                                    <th class="text-dark py-2" style="position: sticky; top:0; background:#f8fafc; z-index:2;">Línea de Mantenimiento</th>
                                    <th class="text-dark py-2" style="position: sticky; top:0; background:#f8fafc; z-index:2;">Clasificación</th>
                                    <th class="text-right text-dark py-2" style="position: sticky; top:0; background:#f8fafc; z-index:2;">Costo (USD)</th>
                                    <th class="text-center text-dark py-2" style="position: sticky; top:0; background:#f8fafc; z-index:2; width: 180px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $it)
                                    <tr>
                                        <td class="font-weight-bold text-dark">{{ $it->nombre }}</td>
                                        <td>
                                            <span class="badge-executive {{ $it->categoria == 'OVERHAUL' ? 'badge-ovh' : 'badge-rep' }}">
                                                {{ $it->categoria }}
                                            </span>
                                        </td>
                                        <td class="text-right text-money">$ {{ number_format($it->costo_promedio, 2, ',', '.') }}</td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center align-items-center">
                                                <button class="btn btn-xs btn-outline-dark rounded-pill px-2 py-1 mr-2 btn-editar-baremo" 
                                                        data-id="{{ $it->id }}" 
                                                        data-nombre="{{ $it->nombre }}" 
                                                        data-categoria="{{ $it->categoria }}"
                                                        data-costo="{{ $it->costo_promedio }}" style="font-size: 11px;">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                
                                                <form action="{{ route('plan_mayor.baremo.destroy', $it->id) }}" method="POST" onsubmit="return confirm('¿Está seguro de eliminar este ítem? Se desvinculará de toda la matriz.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-xs btn-outline-danger rounded-pill px-2 py-1" style="font-size: 11px;">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card p-3 border shadow-sm bg-light" id="card-editor-baremo" style="border-left: 4px solid #ff6600 !important;">
                        <h6 class="font-weight-bold text-dark m-0" id="editor-title"><i class="fas fa-plus-circle text-orange mr-1"></i> Registrar Parámetro</h6>
                        <small class="text-muted d-block mb-3" id="editor-subtitle">Agregar concepto al Plan Mayor global</small>
                        
                        <form action="{{ route('plan_mayor.baremo.store') }}" method="POST" id="form-baremo">
                            @csrf
                            <input type="hidden" name="item_id" id="edit-item-id">
                            
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold text-secondary mb-1">Clasificación Estructural</label>
                                <select name="categoria" id="edit-item-categoria" class="form-control font-weight-medium" required>
                                    <option value="OVERHAUL">OVERHAUL (Mantenimiento Mayor)</option>
                                    <option value="REPARACION GENERAL">REPARACIÓN GENERAL</option>
                                </select>
                            </div>

                            <div class="form-group mb-2">
                                <label class="small font-weight-bold text-secondary mb-1">Nombre Comercial del Trabajo</label>
                                <input type="text" class="form-control text-uppercase font-weight-medium" name="nombre" id="edit-item-nombre" placeholder="Ej: TURBOCOMPRESOR O SISTEMA API" required>
                            </div>

                            <div class="form-group mb-3">
                                <label class="small font-weight-bold text-secondary mb-1">Costo Base Referencial</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white text-muted font-weight-bold">$</span>
                                    </div>
                                    <input type="number" step="0.01" class="form-control font-weight-bold" name="costo_promedio" id="edit-item-costo" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-dark btn-block w-100 font-weight-bold rounded-pill" id="btn-submit-baremo">
                                <i class="fas fa-save mr-1"></i> Guardar en Catálogo
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane-custom d-none" id="panel-resumen">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                <span class="text-muted small"><i class="fas fa-info-circle mr-1"></i> Vista limpia consolidada para exportación directa a canales corporativos.</span>
                <div class="d-flex align-items-center">
                    <button type="button" class="btn btn-outline-danger btn-export-custom mr-2 d-none" id="btn-exportar-pdf">
                        <i class="fas fa-file-pdf mr-1"></i> Imprimir / PDF
                    </button>
                    <button type="button" class="btn btn-outline-success btn-export-custom" id="btn-exportar-excel">
                        <i class="fas fa-file-excel mr-1"></i> Descargar Excel
                    </button>
                </div>
            </div>
            
            <div id="area-reporte-final" class="p-4 border rounded bg-white">
                <div class="text-center mb-4">
                    <h3 class="m-0 font-weight-bold text-dark">tuCombustible</h3>
                    <h6 class="text-uppercase text-muted mt-1" style="font-size:12px;">Consolidado Financiero - Plan Mayor</h6>
                </div>

                <table class="table-executive-matrix table-striped border w-100">
                    <thead>
                        <tr class="bg-dark text-white">
                            <th class="text-left py-2 px-3">Equipo Operativo</th>
                            <th class="text-left py-2">Desglose de Trabajos</th>
                            <th class="text-center py-2" style="width: 100px;">Cant</th>
                            <th class="text-right py-2 px-3" style="width: 180px;">Presupuesto Asignado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($unidades as $u)
                            <tr data-rep-unidad="{{ $u->id }}">
                                <td class="font-weight-bold text-dark px-3">{{ $u->flota }} - {{ $u->placa }}</td>
                                <td>
                                    @foreach($u->planMayorItems as $it)
                                        <span class="badge-executive {{ $it->categoria == 'OVERHAUL' ? 'badge-ovh' : 'badge-rep' }} mr-1 mb-1 d-inline-block">
                                            {{ $it->nombre }}
                                        </span>
                                    @endforeach
                                </td>
                                <td class="text-center font-weight-bold text-muted rep-cant">{{ $u->planMayorItems->count() }}</td>
                                <td class="text-right text-money rep-total px-3">$ {{ number_format($u->planMayorItems->sum('costo_promedio'), 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    const calcularAlturaHeader = () => {
        const firstRow = document.querySelector('.table-executive-matrix thead tr:first-child');
        const wrapper = document.querySelector('.matrix-scroll-wrapper');
        
        if (firstRow && wrapper) {
            // offsetHeight extrae el tamaño exacto incluyendo bordes y paddings activos
            const alturaReal = firstRow.offsetHeight;
            // Inyectamos el valor dinámicamente al contenedor de la tabla
            wrapper.style.setProperty('--first-row-height', `${alturaReal}px`);
        }
    };

    // Ejecutar al cargar la vista
    calcularAlturaHeader();

    // Escuchar si el usuario cambia el tamaño de la ventana o aplica zoom en la pantalla
    window.addEventListener('resize', calcularAlturaHeader);
    
    // =========================================================================
    // 1. GESTIÓN Y PERSISTENCIA DE PESTAÑAS (LOCALSTORAGE)
    // =========================================================================
    const tabLinks = document.querySelectorAll('#planMayorTabs .nav-link-custom');
    const tabPanes = document.querySelectorAll('.tab-pane-custom');

    function switchTab(targetId) {
        tabLinks.forEach(link => {
            link.classList.toggle('active', link.getAttribute('data-target') === targetId);
        });
        tabPanes.forEach(pane => {
            if('#' + pane.id === targetId) {
                pane.classList.replace('d-none', 'd-block');
            } else {
                pane.classList.replace('d-block', 'd-none');
            }
        });
        localStorage.setItem('tucombustible_active_plan_tab', targetId);
    }

    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            switchTab(this.getAttribute('data-target'));
        });
    });

    const savedTab = localStorage.getItem('tucombustible_active_plan_tab');
    switchTab(savedTab && document.querySelector(`[data-target="${savedTab}"]`) ? savedTab : '#panel-control');

    // Auto-ocultar alertas
    const alert = document.getElementById('main-alert');
    if (alert) {
        setTimeout(() => {
            alert.style.transition = "opacity 0.5s ease";
            alert.style.opacity = "0";
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    }

   // =========================================================================
    // 2. INTERACCIONES EN CALIENTE POR CELDA (FETCH AJAX OPTIMIZADO)
    // =========================================================================
    const matrixCells = document.querySelectorAll('.matrix-cell-clickable');

    matrixCells.forEach(cell => {
        cell.addEventListener('click', function() {
            // Protección anti-rebote si hay un proceso Fetch activo en esta celda
            if (this.classList.contains('is-processing')) return;

            const unidadId = this.getAttribute('data-unidad');
            const itemId = this.getAttribute('data-item');
            const icon = this.querySelector('.check-icon');

            // Estado visual de carga temporal
            this.classList.add('is-processing');

            fetch("{{ route('plan_mayor.toggle') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ unidad_id: unidadId, item_id: itemId })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    // Evaluamos la respuesta estricta del backend para alternar estados
                    if (data.status === 'attached') {
                        icon.classList.remove('d-none');
                        this.classList.add('cell-active-bg');
                    } else {
                        icon.classList.add('d-none');
                        this.classList.remove('cell-active-bg');
                    }
                    
                    // Formateador numérico de alta precisión sin redondeos
                    const formatter = new Intl.NumberFormat('es-VE', { style: 'currency', currency: 'USD' });
                    
                    // Actualizar contadores horizontales en la Matriz
                    const fila = document.querySelector(`tr[data-unidad-id="${unidadId}"]`);
                    fila.querySelector('.cell-cant').textContent = data.cant_trabajos;
                    fila.querySelector('.cell-total-unidad').textContent = formatter.format(data.costo_unidad);
                    
                    // Sincronizar en segundo plano el Reporte Final (Vista 3)
                    const filaReporte = document.querySelector(`tr[data-rep-unidad="${unidadId}"]`);
                    if(filaReporte) {
                        filaReporte.querySelector('.rep-cant').textContent = data.cant_trabajos;
                        filaReporte.querySelector('.rep-total').textContent = formatter.format(data.costo_unidad);
                    }
                    
                    // Refrescar el KPI Financiero Global de la cabecera
                    document.getElementById('kpi-global').textContent = formatter.format(data.total_global);
                }
            })
            .catch(err => {
                console.error('Error en procesamiento de matriz:', err);
            })
            .finally(() => {
                // Liberar celda para nuevas operaciones
                this.classList.remove('is-processing');
            });
        });
    });

    // =========================================================================
    // 3. CONTROL DINÁMICO DEL FORMULARIO DE BAREMOS (POLIMORFISMO)
    // =========================================================================
    const formBaremo = document.getElementById('form-baremo');
    const editorTitle = document.getElementById('editor-title');
    const editorSubtitle = document.getElementById('editor-subtitle');
    const inputId = document.getElementById('edit-item-id');
    const inputCategoria = document.getElementById('edit-item-categoria');
    const inputNombre = document.getElementById('edit-item-nombre');
    const inputCosto = document.getElementById('edit-item-costo');
    const btnSubmit = document.getElementById('btn-submit-baremo');

    // Cambiar a Modo: REGISTRAR NUEVO ÍTEM
    document.getElementById('btn-crear-nuevo-item').addEventListener('click', function() {
        formBaremo.action = "{{ route('plan_mayor.baremo.store') }}";
        editorTitle.innerHTML = '<i class="fas fa-plus-circle text-success mr-1"></i> Registrar Parámetro';
        editorSubtitle.textContent = 'Agregar concepto al Plan Mayor global';
        
        inputId.value = '';
        inputNombre.value = '';
        inputCosto.value = '';
        btnSubmit.className = "btn btn-success btn-block w-100 font-weight-bold rounded-pill";
        inputNombre.focus();
    });

    // Cambiar a Modo: MODIFICAR ÍTEM EXISTENTE
    const botonesEditar = document.querySelectorAll('.btn-editar-baremo');
    botonesEditar.forEach(btn => {
        btn.addEventListener('click', function() {
            formBaremo.action = "{{ route('plan_mayor.baremo.update') }}"; // Ajustamos dinámicamente la ruta de actualización
            formBaremo.action = formBaremo.action.replace('store', 'update'); 
            
            editorTitle.innerHTML = '<i class="fas fa-edit text-orange mr-1"></i> Modificar Parámetro';
            editorSubtitle.textContent = 'Actualizando especificaciones de catálogo';
            
            inputId.value = this.getAttribute('data-id');
            inputCategoria.value = this.getAttribute('data-categoria');
            inputNombre.value = this.getAttribute('data-nombre');
            inputCosto.value = this.getAttribute('data-costo');
            
            btnSubmit.className = "btn btn-dark btn-block w-100 font-weight-bold rounded-pill";
            inputCosto.focus();
            inputCosto.select();
        });
    });

    document.getElementById('btn-exportar-pdf').addEventListener('click', function() {
        window.print();
    });

    // Acción 2: Parseador de Tabla a formato CSV estructurado (Sin redondeos)
    document.getElementById('btn-exportar-excel').addEventListener('click', function() {
        const table = document.querySelector('#area-reporte-final table');
        let csv = [];
        
        // Recorrer filas de la tabla analítica
        for (let i = 0; i < table.rows.length; i++) {
            let row = [], cols = table.rows[i].cells;
            
            for (let j = 0; j < cols.length; j++) {
                // Capturar el texto y limpiar espacios en blanco innecesarios
                let text = cols[j].innerText.trim();
                
                // Si estamos en la columna de Desglose de Trabajos, normalizar separaciones de los badges
                if (j === 1 && i > 0) {
                    text = text.replace(/\n/g, ' | '); // Cambia saltos de línea por un separador elegante
                }
                
                // Sanitizar comillas para evitar rupturas de celdas en Excel
                row.push('"' + text.replace(/"/g, '""') + '"');
            }
            // Unimos con punto y coma para la lectura nativa de Excel en configuraciones regionales hispanas
            csv.push(row.join(";")); 
        }
        
        // Inyección del BOM UTF-8 (\uFEFF) para forzar a Excel a leer correctamente tildes, eñes y símbolos de dólar
        const csvContent = "data:text/csv;charset=utf-8,\uFEFF" + csv.join("\n");
        const encodedUri = encodeURI(csvContent);
        
        // Crear disparador de descarga temporal
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        
        // Generar nombre dinámico basado en la fecha del día
        const fechaHoy = new Date().toISOString().slice(0, 10);
        link.setAttribute("download", `Plan_Mayor_tuCombustible_${fechaHoy}.csv`);
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link); // Limpieza de DOM
    });
});
</script>
@endpush