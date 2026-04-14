@extends('layouts.app')
@section('title', 'Dashboard de Vehículos')

@section('content')
<div class="row mb-4">
    <div class="col-12 bg-white p-3 shadow-sm rounded">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            
            <div class="mb-3 mb-md-0 text-center text-md-start">
                <h4 class="fw-bold mb-0 text-uppercase" style="font-size: 1.1rem;">Control de Flota | Dashboard</h4>
                <small class="text-muted d-block">Estado operativo en tiempo real</small>
            </div>

            <div class="row g-2 g-md-1 row-cols-2 row-cols-md-auto justify-content-md-end">
                <div class="col">
                    <a href="{{ route('vehiculos.list') }}" class="btn btn-sm btn-outline-dark w-100 h-100 d-flex align-items-center justify-content-center">
                        <i class="fas fa-list me-1 me-md-2"></i> <span class="d-none d-sm-inline">Listado</span>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('vehiculos.reporte.disponibilidad') }}" class="btn btn-sm btn-dark w-100 h-100 d-flex align-items-center justify-content-center text-nowrap">
                        <i class="fas fa-file-excel me-1 me-md-2"></i> <span class="d-sm-inline">Reporte</span>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('vehiculos.documentacion') }}" class="btn btn-sm btn-outline-dark w-100 h-100 d-flex align-items-center justify-content-center">
                        <i class="fas fa-file-alt me-1 me-md-2"></i> <span class="d-none d-sm-inline">Documentación</span>
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('mantenimiento.planificacion.index') }}" class="btn btn-sm btn-dark w-100 h-100 d-flex align-items-center justify-content-center">
                        <i class="fas fa-wrench me-1 me-md-2"></i> Planificar
                    </a>
                </div>
                <div class="col">
                    <a href="{{ route('vehiculos.create') }}" class="btn btn-sm text-white w-100 h-100 d-flex align-items-center justify-content-center" style="background-color: #f2A435;">
                        <i class="fas fa-plus me-1 me-md-2"></i> Nuevo
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row g-3 mb-4">
    @php
        $stats = [
            [
                'label' => 'Eficiencia Flota',
                'val' => ($eficienciaActual ?? 0).'%',
                'sub' => ($unidades_disponibles ?? 0)." de ".($total_flota ?? 0)." operativas",
                'icon' => 'fa-chart-line',
                'color' => 'success', // Mantiene el verde para éxito
                'details' => [
                    'Chutos/Camiones' => ['count' => ($m_dis ?? 0) + ($ch_dis ?? 0), 'filter' => 'chutos_camiones'],
                    'Cisternas' => ['count' => ($c_dis ?? 0), 'filter' => 'cisternas']
                ],
                'link' => '#'
            ],
            [
                'label' => 'Disponibles',
                'val' => $unidades_disponibles ?? 0,
                'sub' => 'Listos para ruta',
                'icon' => 'fa-check-circle',
                'color' => 'orange', // Aplicamos el color corporativo naranja
                'details' => [
                    'Camiones' => ['count' => $m_dis ?? 0, 'filter' => 'camiones_disponibles'],
                    'Chutos' => ['count' => $ch_dis ?? 0, 'filter' => 'chutos_disponibles'],
                    'Cisternas' => ['count' => $c_dis ?? 0, 'filter' => 'cisternas_disponibles']
                ],
                'link' => route('vehiculos.list', ['filter' => 'disponibles'])
            ],
            [
                'label' => 'No Disponibles',
                'val' => $unidades_no_disponibles ?? 0,
                'sub' => 'Fuera de operación',
                'icon' => 'fa-ban',
                'color' => 'corporate-emphasis', // Aplicamos el gris oscuro corporativo
                'details' => [
                    'En Ruta' => ['count' => $unidades_en_servicio ?? 0, 'filter' => 'en_servicio'],
                    'Con Falla' => ['count' => $unidades_con_orden_abierta ?? 0, 'filter' => 'con_orden_abierta'],
                    'En Taller' => ['count' => $unidades_en_mantenimiento ?? 0, 'filter' => 'en_mantenimiento']
                ],
                'link' => route('vehiculos.list', ['filter' => 'no_disponibles'])
            ],
            [
                'label' => 'Alertas Doc.',
                'val' => $unidades_con_alerta ?? 0,
                'sub' => 'Vencimientos próximos',
                'icon' => 'fa-triangle-exclamation',
                'color' => 'danger',
                'details' => [],
                'link' => route('vehiculos.list', ['filter' => 'documentos_alerta'])
            ]
        ];
    @endphp

   @foreach($stats as $s)
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card card-kpi border-b-{{ $s['color'] }} shadow-sm h-100">
                <div class="card-body">
                    {{-- Enlace Global en la Cabecera y Valor Principal --}}
                    
                        <div class="row align-items-center">
                            <div class="col-12 text-xs font-weight-bold text-uppercase mb-1" style="color: {{ $s['color'] == 'orange' ? '#f2A435' : ($s['color'] == 'corporate-emphasis' ? '#4C474F' : '') }};">
                                {{ $s['label'] }} 
                                <i class="fa {{ $s['icon'] }} text-{{ $s['color'] }} float-end text-gray-300"></i>
                            </div>
                             
                            <div class="col-4">
                                <a href="{{ $s['link'] }}" class="text-decoration-none ">
                                    <div class="h3 mb-0 font-weight-bold text-gray-800">{{ $s['val'] }}</div>
                                </a>
                            </div>
                            
                            @if(!empty($s['details']))
                                @php $cont = count($s['details']) > 2 ? 6 : 12; @endphp
                                <div class="col-8 row p-0 m-0">
                                    @foreach($s['details'] as $name => $data)
                                        <div class="col-{{ $cont }} p-1">
                                            {{-- Enlace independiente por detalle --}}
                                            <a href="{{ route('vehiculos.list', ['filter' => $data['filter']]) }}" 
                                            class="d-block p-1 text-decoration-none hover-shadow-sm transition-all" 
                                            title="Ver {{ $name }}">
                                                <span class="text-dark fw-bold small">{{ $data['count'] }}</span>
                                                <span class="text-muted text-truncate" style="font-size: 0.8rem;">{{ $name }}</span>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    
                    
                    <div class="mt-2 pt-2 border-top">
                        <div class="text-muted small" style="font-size: 0.75rem;">{{ $s['sub'] }}</div>
                    </div>
                </div>
            </div>
        </div>
@endforeach
</div>

<div class="row g-4 mb-4">
    <!-- Acciones rápidas -->
    <div class="col-lg-4">
    <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-white py-3 border-0">
            <h6 class="m-0 fw-bold text-dark text-uppercase small">Distribución por Estatus</h6>
        </div>
        <div class="card-body">
            {{-- Contenedor para Highcharts adaptado al estándar --}}
            <div id="vehiculos-estatus-chart" style="width:100%; height: 320px; margin: 0 auto;"></div>
        </div>
    </div>
</div>
    <!-- Gráfica de vehículos por estatus -->

     
        <!-- Gráfico 1: Histórico de Eficiencia de Flota (NUEVO GRÁFICO DE LÍNEA) -->
        <div class="col-lg-8 mb-1">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-0 d-flex align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-dark text-uppercase small">Histórico de Eficiencia (Últimos 15 Días)</h6>
                </div>
                <div class="card-body">
                    {{-- Div específico para Highcharts --}}
                    <div id="eficienciaHistoricoChart" style="width:100%; height: 320px;"></div>
                </div>
            </div>
        </div>
    <!--  Claficiación por tipo -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="m-0 fw-bold text-dark text-uppercase small">Distribución de Flota por Tipo</h6>
                </div>
                <div class="card-body">
                    <div id="grafico-grupo" style="width:100%; height:320px;"></div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="m-0 fw-bold text-dark text-uppercase small">Índice de Reportes de Falla</h6>
                </div>
                <div class="card-body">
                    {{-- Contenedor para Highcharts --}}
                    <div id="fallasChartContainer" style="width:100%; height:300px;"></div>
                    <div class="mt-2">
                        <small class="text-muted"><i class="fa fa-info-circle me-1"></i> Cantidad de reportes de falla registrados por mes.</small>
                    </div>
                </div>
            </div>
        </div>


     <!-- Relación Kilometraje vs Consumo -->
    <div class="col-lg-6" style="display: none">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">Relación Kilometraje / Consumo</h5>
            </div>
            <div class="card-body">
                <canvas id="kmConsumoChart" height="120"></canvas>
                <small class="text-muted">Detecta incongruencias entre el kilometraje recorrido y el consumo de combustible.</small>
            </div>
        </div>
    </div>
    <!-- Relación Kilometraje vs Consumo (Top 10 camiones con mayor consumo) -->
    <div class="col-lg-6" style="display:none">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">Top 10 Camiones con Mayor Consumo  (Demo)</h5>
            </div>
            <div class="card-body">
                <canvas id="topConsumoChart" height="180"></canvas>
                <small class="text-muted">Valida el consumo versus el kilometraje. <span class="fw-bold text-danger">Barras rojas</span> indican consumo excesivo.</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4" >
    <!-- Nivel de combustible estimado -->
    <div class="col-md-4" style="display: none">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">Nivel Combustible Estimado  (Demo)</h5>
            </div>
            <div class="card-body">
                <h2 class="fw-bold text-info">58%</h2>
                <div class="progress mb-2" style="height: 18px;">
                    <div class="progress-bar bg-info" role="progressbar" style="width: 58%;" aria-valuenow="58" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted">Basado en rutas y consumo promedio.</small>
            </div>
        </div>
    </div>
    <!-- Próximos mantenimientos -->
    <div class="col-md-4 mb-4">
    <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="m-0 fw-bold text-dark text-uppercase small">Próximos Mantenimientos</h6>
            <span class="badge bg-corporate px-3">{{ $mantenimientos->count() }} Pendientes</span>
        </div>
        <div class="card-body">
            @if($mantenimientos->count() === 0)
                <div class="text-center py-4">
                    <i class="fa-solid fa-calendar-check fa-3x text-light mb-2"></i>
                    <p class="text-muted italic">No hay mantenimientos programados.</p>
                </div>
            @else
                <ul class="list-group list-group-flush timeline-list">
                    @foreach($mantenimientos as $item)
                        @php
                            $atrasado = $item->fecha->isPast();
                            
                            // Definir clase de borde según estatus
                            $borderClass = 'border-timeline-primary';
                            if($atrasado) $borderClass = 'border-timeline-danger';
                            elseif($item->estatus == '1') $borderClass = 'border-timeline-warning';
                            elseif($item->estatus == '2') $borderClass = 'border-timeline-success';
                        @endphp

                        <li class="list-group-item shadow-sm {{ $borderClass }}">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="bg-light rounded-circle p-2 me-3 text-center" style="width: 40px;">
                                        <i class="fa-solid fa-wrench {{ $atrasado ? 'text-danger' : 'text-corporate' }}"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-bold text-dark">{{ $item->vehiculo->placa ?? 'Sin placa' }}</h6>
                                        <small class="text-secondary">{{ Str::limit($item->descripcion, 40) }}</small>
                                    </div>
                                </div>

                                <div class="text-end">
                                    <div class="mb-1">
                                        @if($atrasado)
                                            <span class="badge bg-danger">Atrasado</span>
                                        @else
                                            @switch($item->estatus)
                                                @case('1') <span class="badge bg-warning text-dark">Pendiente</span> @break
                                                @case('2') <span class="badge bg-info text-white">Iniciado</span> @break
                                            @endswitch
                                        @endif
                                    </div>
                                    <small class="text-muted fw-bold d-block">
                                        <i class="fa-solid fa-calendar-day me-1"></i>
                                        {{ $item->fecha->format('d M, Y') }}
                                    </small>
                                </div>
                            </div>

                            @if($item->km)
                                <div class="mt-2 pt-2 border-top d-flex justify-content-start">
                                    <small class="text-muted small">
                                        <i class="fa-solid fa-gauge-high me-1"></i>
                                        Estimado: <strong>{{ number_format($item->km) }} km</strong>
                                    </small>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
    
    <!-- Camiones en ruta o servicio -->
<div class="col-md-8 mb-4">
    <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-white py-3 border-0 d-flex align-items-center justify-content-between">
            <h6 class="m-0 fw-bold text-dark text-uppercase small">Camiones en Ruta / Servicio</h6>
            <span class="badge bg-success rounded-pill px-3">En Vivo</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.875rem;">
                    <thead class="bg-light text-dark">
                        <tr>
                            <th class="ps-4 py-3 border-0 text-uppercase small" style="width: 120px;">Placa</th>
                            <th class="py-3 border-0 text-uppercase small d-none d-sm-table-cell">Vehículo</th>
                            <th class="py-3 border-0 text-uppercase small text-center">Ruta</th>
                            <th class="py-3 border-0 text-uppercase small text-center " width="10%">Carga (Lts)</th>
                            <th class="pe-4 py-3 border-0 text-uppercase small text-end d-none d-sm-table-cell">Cliente Destino</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($viajesActivos as $v)
                        <tr>
                            <td class="ps-4 py-3">
                                <span class="badge bg-corporate px-2 py-2 w-100 fw-bold shadow-sm" style="letter-spacing: 1px;">
                                    {{ $v['placa'] }}
                                </span>
                            </td>
                            <td class="py-3 d-none d-sm-table-cell">
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-dark">{{ $v['modelo'] }}</span>
                                    <small class="text-muted">{{ $v['marca'] }}</small>
                                </div>
                            </td>
                            <td class="py-3 text-center">
                                <span class="text-secondary"><i class="fa-solid fa-route me-1 text-corporate"></i> {{ $v['ruta'] }}</span>
                            </td>
                            <td class="py-3 text-center">
                                <div class="d-inline-flex align-items-center bg-light px-1 py-1 rounded border">
                                    <i class="fa-solid fa-droplet text-primary me-2" style="font-size: 0.7rem;"></i>
                                    <span class="fw-bold text-primary">{{ $v['carga_total'] }}</span>
                                </div>
                            </td>
                            <td class="pe-4 py-3 text-end d-none d-sm-table-cell">
                                <span class="fw-bold text-dark"><i class="fa-solid fa-location-dot text-danger me-1"></i> {{ $v['cliente_destino'] }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-truck-fast fa-3x mb-3 text-light"></i>
                                <p class="italic mb-0">No hay vehículos en ruta actualmente.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
   
</div>

<div class="row g-4 mb-4">
     <div class="col-md-4" style="display: none">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">Gasto Estimado Mensual  (Demo)</h5>
            </div>
            <div class="card-body">
                <h2 class="fw-bold text-danger">$12,500</h2>
                <small class="text-muted">En mantenimientos y reparaciones</small>
            </div>
        </div>
    </div>
    
    
</div>

<!-- Tabla de vehículos recientes -->
{{-- <div class="row g-4">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">Vehículos recientes</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Placa</th>
                            <th>Modelo</th>
                            <th>Marca</th>
                            <th>Estatus</th>
                            <th>Última actividad</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>ABC-123</td>
                            <td>Sprinter</td>
                            <td>Mercedes</td>
                            <td><span class="badge bg-success">Disponible</span></td>
                            <td>2025-08-10</td>
                            <td><a href="#" class="btn btn-sm btn-outline-primary">Ver</a></td>
                        </tr>
                        <tr>
                            <td>XYZ-789</td>
                            <td>Daily</td>
                            <td>Iveco</td>
                            <td><span class="badge bg-warning text-dark">En servicio</span></td>
                            <td>2025-08-09</td>
                            <td><a href="#" class="btn btn-sm btn-outline-primary">Ver</a></td>
                        </tr>
                        <tr>
                            <td>DEF-456</td>
                            <td>Cargo</td>
                            <td>Ford</td>
                            <td><span class="badge bg-primary">En Mantenimiento</span></td>
                            <td>2025-08-08</td>
                            <td><a href="#" class="btn btn-sm btn-outline-primary">Ver</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div> --}}
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {


// Definición de colores estándar Impordiesel
    const corpoColors = ['#1cc88a', '#f2A435', '#4C474F', '#e74a3b'];

    Highcharts.chart('vehiculos-estatus-chart', {
        chart: { 
            type: 'pie', 
            backgroundColor: 'transparent',
            style: { fontFamily: "'Helvetica Neue', 'Arial', sans-serif" }
        },
        title: { text: null },
        tooltip: {
            headerFormat: '<span style="font-size: 12px; font-weight: bold;">{point.key}</span><br/>',
            pointFormat: 'Cantidad: <b>{point.y}</b> (<b>{point.percentage:.1f}%</b>)'
        },
        plotOptions: {
            pie: { 
                innerSize: '70%', // Efecto de anillo moderno
                borderWidth: 0,
                allowPointSelect: true,
                cursor: 'pointer',
                dataLabels: { 
                    enabled: true, 
                    format: '<b>{point.y}</b>', 
                    distance: -25, // Coloca el número dentro del anillo
                    style: { color: '#ffffff', textOutline: 'none', fontSize: '14px' }
                },
                showInLegend: true
            }
        },
        legend: {
            itemStyle: { color: '#333', fontWeight: 'bold', fontSize: '12px' },
            position: 'bottom'
        },
        series: [{
            name: 'Vehículos',
            colorByPoint: true,
            data: [
                { name: 'Disponible', y: {{ $unidades_disponibles }}, color: corpoColors[0] },
                { name: 'En servicio', y: {{ $unidades_en_servicio }}, color: corpoColors[1] },
                { name: 'En Mantenimiento', y: {{ $unidades_en_mantenimiento }}, color: corpoColors[2] },
                { name: 'Fuera de Servicio', y: {{ $unidades_con_orden_abierta - $unidades_en_mantenimiento }}, color: corpoColors[3] }
            ]
        }],
        credits: { enabled: false }
    });

    // =======================================================  
    // GRÁFICO 1: Histórico de Eficiencia (Línea)
    // =======================================================
    // 1. Procesamiento de Data (Garantiza que Highcharts reciba números)
    const rawLabels = @json($chartLabels ?? []);
    const rawData = @json($chartDataCierre ?? []);
    
    // Convertimos a float y limpiamos índices para evitar el error de "no muestra data"
    const cleanData = Object.values(rawData).map(val => parseFloat(val) || 0);

    // 2. Inicialización de Highcharts
    Highcharts.chart('eficienciaHistoricoChart', {
        chart: {
            type: 'areaspline',
            backgroundColor: 'transparent',
            style: { fontFamily: "'Helvetica Neue', 'Arial', sans-serif" },
            spacingTop: 30 // Espacio para las etiquetas superiores
        },
        title: { text: null },
        xAxis: {
            categories: rawLabels,
            // Ajuste estricto al inicio y fin de la data
            min: 0.5,
            max: cleanData.length - 1.5,
            boundaryGap: false, 
            tickmarkPlacement: 'on',
            gridLineWidth: 0,
            lineColor: '#e3e6f0',
            labels: { 
                style: { color: '#858796', fontSize: '10px' },
                y: 15
            }
        },
        yAxis: {
            min: 0,
            max: 100,
            title: { text: null },
            gridLineColor: '#f8f9fc',
            labels: { 
                format: '{value}%',
                style: { color: '#858796', fontSize: '11px' }
            }
        },
        tooltip: {
            shared: true,
            backgroundColor: '#4C474F',
            style: { color: '#ffffff' },
            borderRadius: 8,
            borderWidth: 0,
            headerFormat: '<span style="font-size: 10px; color: #f2A435; font-weight: bold;">{point.key}</span><br/>',
            pointFormat: 'Eficiencia: <b>{point.y:.1f}%</b>'
        },
        plotOptions: {
            areaspline: {
                fillColor: {
                    linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
                    stops: [
                        [0, 'rgba(28, 200, 138, 0.25)'], // Verde Impordiesel suave
                        [1, 'rgba(28, 200, 138, 0)']
                    ]
                },
                marker: {
                    enabled: true,
                    radius: 4,
                    fillColor: '#ffffff',
                    lineWidth: 2,
                    lineColor: '#1cc88a'
                },
                lineWidth: 3,
                color: '#1cc88a',
                dataLabels: {
                    enabled: true,
                    format: '{y:.1f}%',
                    style: { 
                        fontSize: '11px', 
                        color: '#4C474F', 
                        fontWeight: 'bold',
                        textOutline: 'none' 
                    },
                    y: -10 // Posiciona la etiqueta arriba del punto
                }
            }
        },
        series: [{
            name: 'Eficiencia Flota',
            data: cleanData,
            showInLegend: false
        }],
        credits: { enabled: false }
    });


    // Relación Kilometraje / Consumo
    var ctx2 = document.getElementById('kmConsumoChart').getContext('2d');
    new Chart(ctx2, {
        type: 'line',
        data: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago'],
            datasets: [
                {
                    label: 'Kilometraje (km)',
                    data: [12000, 13500, 12800, 14000, 15000, 14500, 15500, 16000],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0,123,255,0.1)',
                    yAxisID: 'y',
                },
                {
                    label: 'Consumo (L/100km)',
                    data: [27, 28, 26, 29, 30, 28, 27, 29],
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40,167,69,0.1)',
                    yAxisID: 'y1',
                }
            ]
        },
        options: {
            scales: {
                y: {
                    type: 'linear',
                    position: 'left',
                    title: { display: true, text: 'Kilometraje' }
                },
                y1: {
                    type: 'linear',
                    position: 'right',
                    title: { display: true, text: 'Consumo (L/100km)' },
                    grid: { drawOnChartArea: false }
                }
            },
            plugins: {
                legend: { display: true, position: 'bottom' }
            }
        }
    });

    // Índice de reportes de falla mensuales

   const labelsFalla = @json($fallas_labels ?? []);
    const valuesFalla = @json($fallas_values ?? []);
    const cleanValuesFalla = Object.values(valuesFalla).map(val => parseFloat(val) || 0);

    Highcharts.chart('fallasChartContainer', {
        chart: {
            type: 'column',
            backgroundColor: 'transparent',
            style: { fontFamily: "'Helvetica Neue', 'Arial', sans-serif" }
        },
        title: { text: null },
        xAxis: {
            categories: labelsFalla,
            gridLineWidth: 0,
            lineColor: '#e3e6f0',
            labels: { style: { color: '#858796', fontSize: '11px' } }
        },
        yAxis: {
            min: 0,
            title: { text: null },
            gridLineColor: '#f8f9fc',
            labels: { 
                style: { color: '#858796', fontSize: '11px' }
            }
        },
        tooltip: {
            backgroundColor: '#4C474F',
            style: { color: '#ffffff' },
            borderRadius: 8,
            borderWidth: 0,
            headerFormat: '<span style="font-size: 10px; color: #f2A435; font-weight: bold;">{point.key}</span><br/>',
            pointFormat: 'Reportes: <b>{point.y}</b>'
        },
        plotOptions: {
            column: {
                borderRadius: 5, // Bordes redondeados para un look moderno
                color: '#e74a3b', // Rojo Alerta Corporativo
                borderWidth: 0,
                dataLabels: {
                    enabled: true,
                    format: '{y}',
                    style: { 
                        fontSize: '11px', 
                        color: '#4C474F', 
                        fontWeight: 'bold',
                        textOutline: 'none'
                    }
                },
                groupPadding: 0.2,
                pointPadding: 0.1
            }
        },
        series: [{
            name: 'Reportes de Falla',
            data: cleanValuesFalla,
            showInLegend: false
        }],
        credits: { enabled: false }
    });

   Highcharts.chart('grafico-grupo', {
        chart: { 
            type: 'column',
            backgroundColor: 'transparent',
            style: { fontFamily: "'Helvetica Neue', 'Arial', sans-serif" }
        },
        title: { text: null },
        colors: corpoColors,
        xAxis: {
            categories: @json($chartCategorias),
            gridLineWidth: 0,
            lineColor: '#e3e6f0',
            labels: { style: { color: '#858796', fontSize: '11px' } }
        },
        yAxis: {
            min: 0,
            title: { text: null }, // Limpieza visual
            gridLineColor: '#f8f9fc',
            stackLabels: {
                enabled: true,
                style: { fontWeight: 'bold', color: '#4C474F', textOutline: 'none' }
            },
            labels: { style: { color: '#858796', fontSize: '11px' } }
        },
        legend: {
            align: 'center',
            verticalAlign: 'bottom',
            itemStyle: { color: '#333', fontWeight: 'bold', fontSize: '12px' }
        },
        tooltip: {
            backgroundColor: '#4C474F',
            style: { color: '#ffffff' },
            borderRadius: 8,
            borderWidth: 0,
            headerFormat: '<span style="font-size: 10px; color: #f2A435; font-weight: bold;">{point.x}</span><br/>',
            pointFormat: '{series.name}: <b>{point.y}</b><br/>Total: <b>{point.stackTotal}</b>',
            shared: false
        },
        plotOptions: {
            column: {
                stacking: 'normal',
                borderRadius: 4, // Esquinas suavizadas
                borderWidth: 0,
                dataLabels: { 
                    enabled: true,
                    style: { textOutline: 'none', color: '#ffffff', fontSize: '10px' }
                }
            },
            series: {
                cursor: 'pointer',
                point: {
                    events: {
                        click: function () {
                            if (this.series.options.url) {
                                window.location.href = this.series.options.url;
                            }
                        }
                    }
                }
            }
        },
        series: @json($chartSeries),
        credits: { enabled: false }
    });


    // Simulación de datos de los 10 camiones con mayor consumo
    const topVehiculos = [];

    // Define el umbral de consumo excesivo
    const consumoExcesivo = 29;

    // Colores: rojo si consumo >= umbral, azul si no
    const barColors = topVehiculos.map(v => v.consumo >= consumoExcesivo ? '#dc3545' : '#007bff');

    // Gráfica de barras: Consumo por vehículo, línea: kilometraje
    var ctxTop = document.getElementById('topConsumoChart').getContext('2d');
    new Chart(ctxTop, {
        type: 'bar',
        data: {
            labels: topVehiculos.map(v => v.placa),
            datasets: [
                {
                    label: 'Consumo (L/100km)',
                    data: topVehiculos.map(v => v.consumo),
                    backgroundColor: barColors,
                    yAxisID: 'y',
                },
                {
                    label: 'Kilometraje',
                    data: topVehiculos.map(v => v.km),
                    type: 'line',
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40,167,69,0.1)',
                    yAxisID: 'y1',
                    tension: 0.3,
                    pointRadius: 4,
                    pointBackgroundColor: '#28a745'
                }
            ]
        },
        options: {
            scales: {
                y: {
                    type: 'linear',
                    position: 'left',
                    title: { display: true, text: 'Consumo (L/100km)' }
                },
                y1: {
                    type: 'linear',
                    position: 'right',
                    title: { display: true, text: 'Kilometraje' },
                    grid: { drawOnChartArea: false }
                }
            },
            plugins: {
                legend: { display: true, position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            if(context.dataset.label === 'Consumo (L/100km)') {
                                return 'Consumo: ' + context.parsed.y + ' L/100km';
                            }
                            if(context.dataset.label === 'Kilometraje') {
                                return 'Kilometraje: ' + context.parsed.y + ' km';
                            }
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush