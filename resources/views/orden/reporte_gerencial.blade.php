@extends('layouts.app')

@push('styles')
<style>
    :root {
        --corp-navy: #1B365D;
        --corp-blue: #0077C8;
        --corp-green: #218f4c;
        --corp-red: #d32f2f;
        --corp-gray: #f8f9fa;
        --corp-text: #333333;
    }
    body { background-color: #f4f7f6; color: var(--corp-text); }
    
    /* Tarjetas Ejecutivas */
    .exec-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.04);
        transition: transform 0.2s ease-in-out;
        background: #fff;
    }
    .exec-card:hover { transform: translateY(-3px); }
    
    .card-header-corp {
        background: #fff;
        border-bottom: 2px solid var(--corp-gray);
        border-radius: 12px 12px 0 0 !important;
        padding: 1rem 1.25rem;
    }
    .card-title-corp {
        font-size: 0.85rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--corp-navy);
        margin: 0;
    }

    /* Progress Bars Corporativos */
    .progress-corp { height: 8px; border-radius: 4px; background-color: #e9ecef; }
    
    /* Utilidades de texto */
    .text-navy { color: var(--corp-navy) !important; }
    .text-blue { color: var(--corp-blue) !important; }
    .currency-lg { font-size: 1.8rem; font-weight: 800; letter-spacing: -0.5px; }
    
    @media print {
        @page { size: A4 landscape; margin: 10mm; }
        
        body { background: white !important; -webkit-print-color-adjust: exact; }
        .page-break-before { page-break-before: always; }
        .no-print { display: none !important; }
        .accordion-collapse { display: block !important; height: auto !important; }
        .table-responsive { max-height: none !important; overflow: visible !important; }
        .exec-card { box-shadow: none !important; border: 1px solid #ddd !important; break-inside: avoid; }
        .printableArea { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
    }   
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <!-- CABECERA DE CONTROLES (NO IMPRIMIBLE) -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <h3 class="fw-bold text-navy mb-0">Reporte Gerencial de Mantenimiento</h3>
            <p class="text-muted small mb-0">Dashboard Ejecutivo para Junta Directiva</p>
        </div>
        <div class="d-flex gap-2">
            <button id="sendWhatsappButton" class="btn btn-outline-success shadow-sm fw-bold">
                <i class="fab fa-whatsapp me-2"></i> Enviar WhatsApp
            </button>
            <button id="captureButton" class="btn btn-outline-secondary shadow-sm fw-bold">
                <i class="fa fa-camera me-2"></i> Capturar
            </button>
            <button id="exportButton" class="btn btn-primary shadow-sm fw-bold px-4" style="background-color: var(--corp-navy); border: none;">
                <i class="fa fa-print me-2"></i> Exportar
            </button>
        </div>
    </div>

    <!-- FORMULARIO DE FILTROS -->
    <div class="exec-card mb-4 no-print">
        <div class="card-body p-3">
           <!-- Formulario de Filtros del Reporte -->
            <form method="GET" action="{{ route('ordenes.reporte_gerencial') }}" class="row g-3 align-items-end mb-4 print-none">
                
                <!-- NUEVO: Selector Rápido de Periodo -->
                <div class="col-md-2">
                    <label for="tipo_periodo" class="form-label fw-bold small text-muted">Periodo</label>
                    <select name="tipo_periodo" id="tipo_periodo" class="form-select" onchange="toggleFechasPersonales(this.value)">
                        <option value="este_mes" {{ $tipoPeriodo == 'este_mes' ? 'selected' : '' }}>Este Mes</option>
                        <option value="mes_pasado" {{ $tipoPeriodo == 'mes_pasado' ? 'selected' : '' }}>Mes Pasado</option>
                        <option value="esta_quincena" {{ $tipoPeriodo == 'esta_quincena' ? 'selected' : '' }}>Esta Quincena</option>
                        <option value="esta_semana" {{ $tipoPeriodo == 'esta_semana' ? 'selected' : '' }}>Esta Semana</option>
                        <option value="personalizado" {{ $tipoPeriodo == 'personalizado' ? 'selected' : '' }}>Personalizado...</option>
                    </select>
                </div>

                <!-- Fechas Personalizadas (Ocultas por defecto si no es personalizado) -->
                <div class="col-md-3 d-flex gap-2" id="bloque_fechas_personales" style="{{ $tipoPeriodo == 'personalizado' ? '' : 'display: none !important;' }}">
                    <div class="w-50">
                        <label class="form-label fw-bold small text-muted">Desde</label>
                        <input type="date" name="fecha_inicio" class="form-control" value="{{ request('fecha_inicio') }}">
                    </div>
                    <div class="w-50">
                        <label class="form-label fw-bold small text-muted">Hasta</label>
                        <input type="date" name="fecha_fin" class="form-control" value="{{ request('fecha_fin') }}">
                    </div>
                </div>

                <!-- Filtros de Tipo (Orden y Vehículo) -->
                <div class="col-md-2">
                    <label for="tipo_orden" class="form-label fw-bold small text-muted">Tipo de Orden</label>
                    <select name="tipo_orden" class="form-select">
                        <option value="">-- Todos --</option>
                        @foreach($tiposOrden as $tipoO)
                            <option value="{{ $tipoO }}" {{ request('tipo_orden') == $tipoO ? 'selected' : '' }}>{{ ucfirst($tipoO) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="tipo_vehiculo_id" class="form-label fw-bold small text-muted">Tipo Vehículo</label>
                    <select name="tipo_vehiculo_id" class="form-select">
                        <option value="">-- Todos --</option>
                        @foreach($tiposVehiculo as $tipoV)
                            <option value="{{ $tipoV->id }}" {{ request('tipo_vehiculo_id') == $tipoV->id ? 'selected' : '' }}>{{ $tipoV->tipo }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- NUEVO: Selector de Agrupación (Unidad, etc.) -->
                <div class="col-md-2">
                    <label for="agrupar_por" class="form-label fw-bold small text-muted">Agrupar Tabla Por</label>
                    <select name="agrupar_por" class="form-select border-primary">
                        <option value="">Global (Sin Agrupar)</option>
                        <option value="unidad" {{ request('agrupar_por') == 'unidad' ? 'selected' : '' }}>Por Unidad</option>
                        <option value="tipo_orden" {{ request('agrupar_por') == 'tipo_orden' ? 'selected' : '' }}>Por Tipo de Orden</option>
                        <option value="tipo_vehiculo" {{ request('agrupar_por') == 'tipo_vehiculo' ? 'selected' : '' }}>Por Tipo de Vehículo</option>
                    </select>
                </div>

                <div class="col-md-1 text-end">
                    <button type="submit" class="btn btn-primary w-100 fw-bold" title="Aplicar Filtros">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="statusMessage" class="alert d-none mb-4 text-center fw-bold"></div>

    <!-- ÁREA DEL REPORTE (IMPRIMIBLE) -->
    <div id="reporte-container" class="printableArea mx-auto" style="max-width: 1400px;">
        
        <!-- ENCABEZADO DEL DOCUMENTO -->
        <div class="d-flex justify-content-between align-items-end border-bottom border-2 border-dark pb-3 mb-4">
            <div>
                <h4 class="fw-bold text-uppercase text-navy mb-1"><i class="fas fa-chart-line me-2"></i> Resultados Operativos y Financieros</h4>
                <span class="text-muted small fw-bold">DEPARTAMENTO DE MANTENIMIENTO DE FLOTA</span>
            </div>
            <div class="text-end">
                <span class="text-dark px-3 py-2 fs-6 shadow-sm">
                    Período: {{ \Carbon\Carbon::parse($reporte['periodo']['inicio'])->format('d/m/Y') }} AL {{ \Carbon\Carbon::parse($reporte['periodo']['fin'])->format('d/m/Y') }}
                </span>
            </div>
        </div>

        <!-- BLOQUE 1: RESUMEN FINANCIERO Y KPIS GLOBALES -->
        <div class="row g-4 mb-4">
            <!-- Gran Total Costos -->
            <div class="col-md-4">
                <div class="exec-card h-100 bg-navy text-white p-4" style="background-color: var(--corp-navy);">
                    <h6 class="text-uppercase text-white-50 fw-bold mb-1">Costo Total de Mantenimiento</h6>
                    <div class="currency-lg text-white mb-3">${{ number_format($reporte['financiero']['total'], 2) }}</div>
                    <div class="row text-center mt-auto border-top border-secondary pt-3">
                        <div class="col-4 border-end border-secondary">
                            <span class="d-block small text-white-50">Almacén</span>
                            <span class="fw-bold">${{ number_format($reporte['financiero']['suministros'], 0) }}</span>
                        </div>
                        <div class="col-4 border-end border-secondary">
                            <span class="d-block small text-white-50">Compras</span>
                            <span class="fw-bold">${{ number_format($reporte['financiero']['compras'], 0) }}</span>
                        </div>
                        <div class="col-4">
                            <span class="d-block small text-white-50">Externos</span>
                            <span class="fw-bold">${{ number_format($reporte['financiero']['externos'], 0) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- KPIs Operativos -->
            <div class="col-md-8">
                <div class="row g-3 h-100">
                    <div class="col-md-4">
                        <div class="exec-card h-100 p-3 border-start border-4 border-primary">
                            <span class="text-muted small fw-bold text-uppercase d-block mb-1">Órdenes Generadas</span>
                            <h2 class="fw-bold text-primary mb-0">{{ $reporte['kpis']['abiertas_hoy'] }}</h2>
                            <small class="text-muted">En el período consultado</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="exec-card h-100 p-3 border-start border-4 border-success">
                            <span class="text-muted small fw-bold text-uppercase d-block mb-1">Órdenes Cerradas</span>
                            <h2 class="fw-bold text-success mb-0">{{ $reporte['kpis']['cerradas_mes'] }}</h2>
                            <small class="text-muted">Unidades operativas</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="exec-card h-100 p-3 border-start border-4 border-warning bg-warning bg-opacity-10">
                            <span class="text-dark small fw-bold text-uppercase d-block mb-1">Activas en Taller</span>
                            <h2 class="fw-bold text-dark mb-0">{{ $reporte['kpis']['activas_totales'] }}</h2>
                            <small class="text-danger fw-bold"><i class="fas fa-exclamation-circle"></i> Flota inmovilizada actual</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BLOQUE 2: ANÁLISIS DE TENDENCIAS (GRÁFICOS) -->
        <div class="row g-4 mb-4">
            <div class="col-md-8">
                <div class="exec-card h-100">
                    <div class="card-header-corp d-flex justify-content-between align-items-center">
                        <h6 class="card-title-corp"><i class="fas fa-chart-area me-2"></i> Flujo de Trabajo (Entradas vs Salidas)</h6>
                    </div>
                    <div class="card-body p-3">
                        <div id="chart-timeline" style="width:100%; height:300px;"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="exec-card h-100">
                    <div class="card-header-corp">
                        <h6 class="card-title-corp"><i class="fas fa-tools me-2"></i> Distribución de Carga</h6>
                    </div>
                    <div class="card-body p-3">
                        <div id="chart-tipo" style="width:100%; height:300px;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BLOQUE 3: MÉTRICAS CRÍTICAS Y GESTIÓN HUMANA -->
        <div class="row g-4 mb-4">
            <!-- Top Categorías / Fallas -->
            <div class="col-md-4">
                <div class="exec-card h-100">
                    <div class="card-header-corp">
                        <h6 class="card-title-corp"><i class="fas fa-cogs me-2"></i> Incidencias Frecuentes (Top 5)</h6>
                    </div>
                    <div class="card-body p-4">
                        @php $maxCat = count($reporte['operativo']['por_categoria']) > 0 ? $reporte['operativo']['por_categoria']->first() : 1; @endphp
                        @forelse($reporte['operativo']['por_categoria'] as $categoria => $cantidad)
                            @php $pct = ($cantidad / $maxCat) * 100; @endphp
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="fw-bold text-dark">{{ $categoria ?: 'No Clasificada' }}</span>
                                    <span class="text-muted">{{ $cantidad }} OTs</span>
                                </div>
                                <div class="progress progress-corp">
                                    <div class="progress-bar bg-danger" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted small text-center my-4">No hay datos de categorías en este período.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Top Mecánicos -->
            <div class="col-md-4">
                <div class="exec-card h-100">
                    <div class="card-header-corp">
                        <h6 class="card-title-corp"><i class="fas fa-users-cog me-2"></i> Productividad por Mecánico</h6>
                    </div>
                    <div class="card-body p-4">
                        @php $maxMec = count($reporte['operativo']['por_mecanico']) > 0 ? $reporte['operativo']['por_mecanico']->first() : 1; @endphp
                        @forelse($reporte['operativo']['por_mecanico'] as $mecanico => $trabajos)
                            @php $pctM = ($trabajos / $maxMec) * 100; @endphp
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="fw-bold text-dark">{{ $mecanico }}</span>
                                    <span class="text-muted">{{ $trabajos }} Trabajos</span>
                                </div>
                                <div class="progress progress-corp">
                                    <div class="progress-bar bg-blue" style="background-color: var(--corp-blue); width: {{ $pctM }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-muted small text-center my-4">No hay trabajos asignados registrados.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Alerta Crítica & Logística -->
            <div class="col-md-4">
                <div class="d-flex flex-column gap-3 h-100">
                    <!-- Unidad Crítica -->
                    <div class="exec-card flex-fill border-start border-4 border-danger">
                        <div class="card-body p-3 d-flex align-items-center">
                            <div class="rounded-circle bg-danger bg-opacity-10 p-3 me-3 text-danger">
                                <i class="fas fa-truck-loading fa-2x"></i>
                            </div>
                            <div>
                                <small class="text-danger fw-bold d-block text-uppercase mb-1">UNIDAD CRÍTICA (MÁS FALLAS)</small>
                                @if($reporte['operativo']['unidad_top'])
                                    <h5 class="mb-0 fw-bold text-dark">{{ $reporte['operativo']['unidad_top']['vehiculo'] }} 
                                        <span class="small text-muted">({{ $reporte['operativo']['unidad_top']['placa'] }})</span>
                                    </h5>
                                    <span class="text-danger mt-1">{{ $reporte['operativo']['unidad_top']['cantidad'] }} Visitas al taller</span>
                                @else
                                    <span class="text-muted small">Flota operando sin reincidencias severas.</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Logística de Almacén -->
                    <div class="exec-card flex-fill">
                        <div class="card-header-corp py-2">
                            <h6 class="card-title-corp text-muted"><i class="fas fa-boxes me-2"></i> Gestión de Almacén</h6>
                        </div>
                        <div class="card-body p-3 row text-center align-items-center">
                            <div class="col-4 border-end">
                                <h4 class="fw-bold text-dark mb-0">{{ $reporte['almacen']['entradas'] }}</h4>
                                <small class="text-muted d-block" style="font-size: 0.7rem;">INGRESOS</small>
                            </div>
                            <div class="col-4 border-end">
                                <h4 class="fw-bold text-dark mb-0">{{ $reporte['almacen']['salidas'] }}</h4>
                                <small class="text-muted d-block" style="font-size: 0.7rem;">SALIDAS</small>
                            </div>
                            <div class="col-4">
                                <h4 class="fw-bold text-primary mb-0">{{ $reporte['almacen']['solicitados'] }}</h4>
                                <small class="text-muted d-block" style="font-size: 0.7rem;">PIEZAS INSTALADAS</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

@if($reporte['agrupar_por'] && $reporte['agrupacion']->isNotEmpty())
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark text-white fw-bold d-flex justify-content-between align-items-center">
            <span>
                <i class="fas fa-chart-pie me-2"></i> 
                Análisis Financiero por: <span class="text-uppercase text-warning">{{ str_replace('_', ' ', $reporte['agrupar_por']) }}</span>
            </span>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover table-striped table-bordered mb-0 text-center align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-start">Categoría / Grupo</th>
                        <th title="Cantidad de órdenes procesadas">Órdenes</th>
                        <th title="Cantidad de trabajos internos realizados">Trabajos Int.</th>
                        <th>Consumo Almacén</th>
                        <th>Compras Directas</th>
                        <th>Trabajos Externos</th>
                        <th class="bg-light fw-bold text-dark">Costo Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $granTotalOrdenes = 0;
                        $granTotalSuministros = 0;
                        $granTotalCompras = 0;
                        $granTotalExternos = 0;
                        $granTotal = 0;
                    @endphp

                    @foreach($reporte['agrupacion'] as $grupo)
                        @php
                            $granTotalOrdenes += $grupo['cantidad_ordenes'];
                            $granTotalSuministros += $grupo['costo_suministros'];
                            $granTotalCompras += $grupo['costo_compras'];
                            $granTotalExternos += $grupo['costo_externos'];
                            $granTotal += $grupo['costo_total'];
                        @endphp
                        <tr>
                            <td class="text-start fw-bold text-primary">{{ $grupo['nombre'] }}</td>
                            <td>
                                <span class="badge bg-secondary rounded-pill px-3">{{ $grupo['cantidad_ordenes'] }}</span>
                            </td>
                            <td class="text-muted small">{{ $grupo['trabajos_internos'] }}</td>
                            <td class="text-success">${{ number_format($grupo['costo_suministros'], 2) }}</td>
                            <td class="text-info">${{ number_format($grupo['costo_compras'], 2) }}</td>
                            <td class="text-warning">${{ number_format($grupo['costo_externos'], 2) }}</td>
                            <td class="bg-light fw-bold text-danger">${{ number_format($grupo['costo_total'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-dark">
                    <tr>
                        <td class="text-end fw-bold">TOTALES:</td>
                        <td class="fw-bold">{{ $granTotalOrdenes }}</td>
                        <td>-</td>
                        <td class="text-success">${{ number_format($granTotalSuministros, 2) }}</td>
                        <td class="text-info">${{ number_format($granTotalCompras, 2) }}</td>
                        <td class="text-warning">${{ number_format($granTotalExternos, 2) }}</td>
                        <td class="fw-bold text-white fs-6">${{ number_format($granTotal, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endif

            <!-- BLOQUE 4: ANEXOS FINANCIEROS (Nivel de Auditoría) -->
        <div class="row g-4 mb-4 page-break-before mt-5">
            <div class="col-12">
                <div class="exec-card">
                    <div class="card-header-corp">
                        <h6 class="card-title-corp text-muted"><i class="fas fa-list-alt me-2"></i> Anexo de Auditoría: Desglose Detallado de Egresos</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="accordion accordion-flush" id="accordionDesglose">
                            
                            <!-- Desglose de Compras Directas -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-bold text-dark" type="button" >
                                        Compras Directas Realizadas (Total: ${{ number_format($reporte['financiero']['compras'], 2) }})
                                    </button>
                                </h2>
                                <div id="collapseCompras" class="accordion-collapse">
                                    <div class="accordion-body p-0">
                                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                            <table class="table table-sm table-striped table-hover small mb-0">
                                                <thead class="table-light sticky-top">
                                                    <tr>
                                                        <th>ID Orden</th>
                                                        <th>Descripción del Artículo</th>
                                                        <th class="text-center">Cant.</th>
                                                        <th class="text-end">Costo Unit.</th>
                                                        <th class="text-end">Subtotal</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($reporte['desglose']['compras'] as $item)
                                                        <tr>
                                                            <!-- Ajusta los nombres de las propiedades ($item->...) según tu base de datos -->
                                                            <td>{{ $item->compraBelong->id_orden ?? 'N/A' }}</td>
                                                            <td>{{ $item->descripcion ?? 'Artículo sin descripción' }}</td>
                                                            <td class="text-center">{{ $item->cantidad_aprobada }}</td>
                                                            <td class="text-end">${{ number_format($item->costo_unitario_aprobado, 2) }}</td>
                                                            <td class="text-end fw-bold">${{ number_format($item->cantidad_aprobada * $item->costo_unitario_aprobado, 2) }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr><td colspan="5" class="text-center text-muted">No hay compras directas en este período.</td></tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Desglose de Suministros de Almacén -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-bold text-dark" type="button">
                                        Consumo de Inventario de Almacén (Total: ${{ number_format($reporte['financiero']['suministros'], 2) }})
                                    </button>
                                </h2>
                                <div id="collapseAlmacen" class="accordion-collapse ">
                                    <div class="accordion-body p-0">
                                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                            <table class="table table-sm table-striped table-hover small mb-0">
                                                <thead class="table-light sticky-top">
                                                    <tr>
                                                        <th>ID Orden</th>
                                                        <th>Repuesto / Material</th>
                                                        <th class="text-center">Cant. Usada</th>
                                                        <th class="text-end">Costo Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($reporte['desglose']['almacen'] as $item)
                                                        <tr>
                                                            <td>{{ $item->id_orden ?? 'N/A' }}</td>
                                                            <td>{{ $item->repuesto ?? $item->descripcion ?? 'N/A' }}</td>
                                                            <td class="text-center">{{ $item->cantidad }}</td>
                                                            <td class="text-end fw-bold">${{ number_format($item->costo_total, 2) }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr><td colspan="4" class="text-center text-muted">No hubo consumo de almacén en este período.</td></tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Desglose de Trabajos Externos -->
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed fw-bold text-dark" type="button">
                                        Trabajos Externos / Tercerizados (Total: ${{ number_format($reporte['financiero']['externos'], 2) }})
                                    </button>
                                </h2>
                                <div id="collapseExternos" class="accordion-collapse" >
                                    <div class="accordion-body p-0">
                                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                            <table class="table table-sm table-striped table-hover small mb-0">
                                                <thead class="table-light sticky-top">
                                                    <tr>
                                                        <th>ID Orden</th>
                                                        <th>Proveedor / Taller</th>
                                                        <th>Trabajo Realizado</th>
                                                        <th class="text-end">Costo Facturado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($reporte['desglose']['externos'] as $item)
                                                        <tr>
                                                            <td>{{ $item->id_orden ?? 'N/A' }}</td>
                                                            <td>{{ $item->proveedor->nombre  ?? 'No especificado' }}</td>
                                                            <td>{{ $item->descripcion ?? 'N/A' }}</td>
                                                            <td class="text-end fw-bold">${{ number_format($item->costo, 2) }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr><td colspan="4" class="text-center text-muted">No se registraron trabajos externos en este período.</td></tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        

        <div class="text-end text-muted small mt-4 pb-4">
            <em>Reporte generado automáticamente por el Sistema de Control de Mantenimiento el {{ now()->format('d/m/Y H:i') }}</em>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {

    function toggleFechasPersonales(valor) {
        const bloqueFechas = document.getElementById('bloque_fechas_personales');
        if (valor === 'personalizado') {
            bloqueFechas.style.setProperty('display', 'flex', 'important');
        } else {
            bloqueFechas.style.setProperty('display', 'none', 'important');
            // Opcional: Limpiar las cajas de fecha si se ocultan
            document.querySelector('input[name="fecha_inicio"]').value = '';
            document.querySelector('input[name="fecha_fin"]').value = '';
        }
    }
    
    // --- GRÁFICO 1: TENDENCIA (TIMELINE) ---
    const timelineData = @json($reporte['timeline']);
    Highcharts.chart('chart-timeline', {
        chart: { type: 'areaspline', backgroundColor: 'transparent', style: { fontFamily: 'inherit' } },
        title: { text: null },
        xAxis: { categories: timelineData.labels, gridLineWidth: 0, crosshair: true },
        yAxis: { title: { text: 'N° de Órdenes' }, gridLineDashStyle: 'Dash' },
        plotOptions: {
            areaspline: { fillOpacity: 0.1, marker: { radius: 4 } }
        },
        series: [{
            name: 'Abiertas)',
            data: timelineData.abiertas,
            color: '#d32f2f', // Rojo corporativo
            lineColor: '#d32f2f'
        }, {
            name: 'Cerradas',
            data: timelineData.cerradas,
            color: '#218f4c', // Verde corporativo
            lineColor: '#218f4c'
        }],
        credits: { enabled: false },
        legend: { align: 'center', verticalAlign: 'top', y: -10 }
    });

    // --- GRÁFICO 2: DISTRIBUCIÓN POR TIPO (DONUT) ---
    const dataTipos = @json($reporte['operativo']['por_tipo']);
    const arrTipos = Object.keys(dataTipos).map(key => {
        let color = '#6c757d';
        let name = key.toUpperCase();
        if(name.includes('PREVENTIVO')) color = '#0077C8'; // Azul
        else if(name.includes('CORRECTIVO')) color = '#d32f2f'; // Rojo
        return { name: name, y: dataTipos[key], color: color };
    });

    Highcharts.chart('chart-tipo', {
        chart: { type: 'pie', backgroundColor: 'transparent', style: { fontFamily: 'inherit' } },
        title: { text: null },
        plotOptions: {
            pie: {
                innerSize: '60%',
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: {
                    enabled: true,
                    format: '<b>{point.name}</b><br>{point.y} ({point.percentage:.1f}%)',
                    distance: -30,
                    style: { fontWeight: 'bold', color: 'white', textOutline: 'none' },
                    filter: { property: 'percentage', operator: '>', value: 4 }
                },
                showInLegend: true
            }
        },
        series: [{ name: 'Órdenes', data: arrTipos }],
        credits: { enabled: false }
    });

    // --- FUNCIONES DE EXPORTACIÓN (Html2Canvas) ---
    const printableArea = document.getElementById('reporte-container');
    const statusMsg = document.getElementById('statusMessage');
    
    function showStatus(msg, type) {
        statusMsg.textContent = msg;
        statusMsg.className = `alert alert-${type} mb-4 text-center fw-bold`;
        statusMsg.classList.remove('d-none');
        setTimeout(() => statusMsg.classList.add('d-none'), 4000);
    }

    // EXPORTAR: Utiliza el motor nativo (Ideal para PDF y paginación)
    document.getElementById('exportButton').addEventListener('click', function() {
        window.print();
    });

    // CAPTURAR: Lógica inteligente para expandir acordeones antes de la foto
    document.getElementById('captureButton').addEventListener('click', async function() {
        const btn = this;
        btn.disabled = true;
        showStatus('Preparando captura, por favor espera...', 'info');

        // 1. Forzar apertura visual de los acordeones para que html2canvas los vea
        const accordions = document.querySelectorAll('.accordion-collapse');
        accordions.forEach(acc => {
            acc.style.display = 'block'; 
            acc.style.height = 'auto';
        });

        // Pequeña pausa para permitir que el DOM aplique los cambios visuales
        await new Promise(resolve => setTimeout(resolve, 300));

        try {
            const canvas = await html2canvas(printableArea, { 
                scale: 2, 
                useCORS: true, 
                backgroundColor: '#ffffff',
                // Aseguramos que capture toda la altura real expandida
                windowHeight: printableArea.scrollHeight 
            });
            
            canvas.toBlob(async blob => {
                const item = new ClipboardItem({ "image/png": blob });
                await navigator.clipboard.write([item]);
                showStatus('Imagen copiada con éxito. Lista para pegar (Ctrl+V).', 'success');
            });
        } catch(e) {
            console.error(e);
            showStatus('Error al generar la captura. Verifica los permisos del navegador.', 'danger');
        } finally {
            // 2. Restaurar los acordeones a su estado original manejado por Bootstrap
            accordions.forEach(acc => {
                acc.style.display = '';
                acc.style.height = '';
            });
            btn.disabled = false;
        }
    });

    document.getElementById('sendWhatsappButton').addEventListener('click', async function() {
        const btn = this;
        btn.disabled = true;
        showStatus('Generando imagen y enviando a WhatsApp...', 'info');

        // 1. Expandir acordeones para capturar el reporte completo
        const accordions = document.querySelectorAll('.accordion-collapse');
        accordions.forEach(acc => {
            acc.style.display = 'block';
            acc.style.height = 'auto';
        });

        await new Promise(resolve => setTimeout(resolve, 300));

        try {
            // 2. Renderizar Canvas y obtener Base64
            const canvas = await html2canvas(printableArea, { 
                scale: 2, 
                useCORS: true, 
                backgroundColor: '#ffffff',
                windowHeight: printableArea.scrollHeight 
            });

            const base64Image = canvas.toDataURL('image/png');

            // 3. Petición AJAX / Fetch al backend Laravel
            const response = await fetch("{{ route('ordenes.enviar_whatsapp') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    imagen: base64Image,
                    periodo: "{{ $reporte['periodo']['inicio'] }} al {{ $reporte['periodo']['fin'] }}"
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                showStatus('Reporte enviado a WhatsApp exitosamente.', 'success');
            } else {
                showStatus(data.message || 'Error al procesar el envío por WhatsApp.', 'danger');
            }

        } catch (error) {
            console.error('Error WhatsApp:', error);
            showStatus('Ocurrió un error en la comunicación con el servidor.', 'danger');
        } finally {
            // 4. Restaurar acordeones y habilitar botón
            accordions.forEach(acc => {
                acc.style.display = '';
                acc.style.height = '';
            });
            btn.disabled = false;
        }
    });
});
</script>
@endpush
@endsection