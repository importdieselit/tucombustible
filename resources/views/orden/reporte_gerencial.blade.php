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
            <button id="sendTelegramButton" class="btn btn-outline-info shadow-sm fw-bold">
                <i class="fa fa-telegram me-2"></i> Enviar Telegram
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
            <form action="{{ route('ordenes.reporte_gerencial') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="small fw-bold text-muted mb-1">FECHA INICIO</label>
                    <input type="date" name="fecha_inicio" class="form-control form-control-sm bg-light" value="{{ $reporte['periodo']['inicio'] }}">
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted mb-1">FECHA FIN</label>
                    <input type="date" name="fecha_fin" class="form-control form-control-sm bg-light" value="{{ $reporte['periodo']['fin'] }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold me-2"><i class="fas fa-filter me-1"></i> Aplicar Filtro</button>
                    <a href="{{ route('ordenes.reporte_gerencial') }}" class="btn btn-light btn-sm px-3 text-muted"><i class="fas fa-undo me-1"></i> Limpiar</a>
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
                <span class="badge bg-dark px-3 py-2 fs-6 rounded-pill shadow-sm">
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
                                    <span class="badge bg-danger mt-1">{{ $reporte['operativo']['unidad_top']['cantidad'] }} Visitas al taller</span>
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
                                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCompras">
                                        Compras Directas Realizadas (Total: ${{ number_format($reporte['financiero']['compras'], 2) }})
                                    </button>
                                </h2>
                                <div id="collapseCompras" class="accordion-collapse collapse" data-bs-parent="#accordionDesglose">
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
                                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAlmacen">
                                        Consumo de Inventario de Almacén (Total: ${{ number_format($reporte['financiero']['suministros'], 2) }})
                                    </button>
                                </h2>
                                <div id="collapseAlmacen" class="accordion-collapse collapse" data-bs-parent="#accordionDesglose">
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
                                    <button class="accordion-button collapsed fw-bold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExternos">
                                        Trabajos Externos / Tercerizados (Total: ${{ number_format($reporte['financiero']['externos'], 2) }})
                                    </button>
                                </h2>
                                <div id="collapseExternos" class="accordion-collapse collapse" data-bs-parent="#accordionDesglose">
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
                                                            <td>{{ $item->proveedor ?? 'No especificado' }}</td>
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
        
        </div>

        <div class="text-end text-muted small mt-4 pb-4">
            <em>Reporte generado automáticamente por el Sistema de Control de Mantenimiento el {{ now()->format('d/m/Y H:i') }}</em>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
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
            name: 'Generadas (Entradas)',
            data: timelineData.abiertas,
            color: '#d32f2f', // Rojo corporativo
            lineColor: '#d32f2f'
        }, {
            name: 'Cerradas (Salidas)',
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
    const printableArea = document.querySelector('.printableArea');
    const statusMsg = document.getElementById('statusMessage');
    
    function showStatus(msg, type) {
        statusMsg.textContent = msg;
        statusMsg.className = `alert alert-${type} mb-4 text-center fw-bold`;
        setTimeout(() => statusMsg.classList.add('d-none'), 5000);
    }

    // Exportar Imagen a PC
    document.getElementById('exportButton').addEventListener('click', async function() {
        this.disabled = true;
        showStatus('Generando reporte corporativo...', 'info');
        try {
            const canvas = await html2canvas(printableArea, { scale: 2, useCORS: true, backgroundColor: '#ffffff' });
            const link = document.createElement('a');
            link.download = `Reporte_Gerencial_${new Date().getTime()}.png`;
            link.href = canvas.toDataURL("image/png");
            link.click();
            showStatus('Reporte descargado exitosamente.', 'success');
        } catch(e) {
            showStatus('Error al generar la descarga.', 'danger');
        }
        this.disabled = false;
    });

    // Copiar al Portapapeles
    document.getElementById('captureButton').addEventListener('click', async function() {
        this.disabled = true;
        showStatus('Copiando al portapapeles...', 'info');
        try {
            const canvas = await html2canvas(printableArea, { scale: 2, useCORS: true, backgroundColor: '#ffffff' });
            canvas.toBlob(async blob => {
                const item = new ClipboardItem({ "image/png": blob });
                await navigator.clipboard.write([item]);
                showStatus('Imagen copiada. Puedes pegarla con Ctrl+V.', 'success');
            });
        } catch(e) {
            showStatus('No se pudo copiar. Verifica los permisos del navegador.', 'danger');
        }
        this.disabled = false;
    });
});
</script>
@endpush
@endsection