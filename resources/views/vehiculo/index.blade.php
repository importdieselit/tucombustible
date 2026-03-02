@extends('layouts.app')
@section('title', 'Dashboard de Vehículos')

@section('content')
<div class="row mb-4 align-items-center">
    <div class="col">
        <h1 class="h3 mb-0 text-gray-800">Dashboard de Vehículos</h1>
        <p class="text-muted small">Estado operativo de la flota en tiempo real.</p>
    </div>
    <div class="col-auto">
            <a class="btn btn-sm btn-primary shadow-sm" href="{{ route('vehiculos.create') }}" >
                <i class="fa fa-plus fa-sm"></i> Nuevo Vehiculo
            </a>
            <a class="btn btn-sm btn-secondary shadow-sm" href="{{ route('vehiculos.list') }}" >
                <i class="fa fa-list fa-sm"></i> Listado Completo
            </a>
            <a class="btn btn-sm btn-info shadow-sm" href="{{ route('vehiculos.reporte.disponibilidad') }}" >
                <i class="fa fa-file-excel fa-sm"></i> Reporte Disponibilidad
            </a>
            <a class="btn btn-sm btn-warning shadow-sm" href="{{ route('mantenimiento.planificacion.index') }}" >
                <i class="fa fa-wrench fa-sm"></i> Planificar Mantenimiento
            </a>
    </div>
</div>

<div class="row g-3 mb-4">
    @php
        $stats = [
            [
                'label' => 'Eficiencia Flota',
                'val' => $eficienciaActual.'%',
                'sub' => "$unidades_disponibles de $total_flota operativas",
                'icon' => 'fa-chart-line',
                'color' => 'success',
                'details' => [
                    'Camiones y Chutos' => $m_dis+$ch_dis.' / '.$m_tot ,
                    'Cisternas' => $c_dis.' / '.$t_tot
                ], // KPI General
                'link' => '#'
            ],
            [
                'label' => 'Disponibles',
                'val' => $unidades_disponibles,
                'sub' => 'Listos para ruta',
                'icon' => 'fa-check-circle',
                'color' => 'success',
                'details' => [
                    'Camiones' => $m_dis ?? 0,
                    'Chutos' => $ch_dis ?? 0,
                    'Cisternas' => $c_dis ?? 0
                ],
                'link' => route('vehiculos.list', ['filter' => 'disponibles'])
            ],
            [
                'label' => 'No Disponibles',
                'val' => $total_flota - $unidades_disponibles,
                'sub' => 'Fuera de operación',
                'icon' => 'fa-ban',
                'color' => 'danger',
                'details' => [
                    'En Ruta' => $unidades_en_servicio,
                    'Con Falla' => $unidades_con_falla ?? 0,
                    'En Taller' => $unidades_en_mantenimiento
                ],
                'link' => route('vehiculos.list', ['filter' => 'no_disponibles'])
            ],
            [
                'label' => 'Alertas Doc.',
                'val' => $unidades_con_alerta,
                'sub' => 'Vencimientos próximos',
                'icon' => 'fa-triangle-exclamation',
                'color' => 'warning',
                'details' => [],
                'link' => route('vehiculos.list', ['filter' => 'documentos_alerta'])
            ]
        ];
    @endphp

   <div class="row g-3 mb-4">
    @foreach($stats as $s)
    <div class="col-xl-3 col-md-6">
        <a href="{{ $s['link'] }}" class="text-decoration-none">
            <div class="card  border-{{ $s['color'] }} shadow-sm h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-12 text-xs font-weight-bold text-{{ $s['color'] }} text-uppercase mb-1" >
                                {{ $s['label'] }} <i class="fa {{ $s['icon'] }} text-gray-200"></i>
                            </div>
                        <div class="col">
                            
                            <div class="h4 mb-0 font-weight-bold text-gray-800">{{ $s['val'] }}</div>
                        </div>
                         @if(!empty($s['details']))
                            @php $cont=count($s['details'])>2?6:12; @endphp
                            <div class="col-8 row p-0">
                                @foreach($s['details'] as $name => $count)
                                    
                                    <div class="col-{{ $cont }} rounded" style="min-width: 30%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <span class="text-dark fw-bold">{{ $count }}</span>
                                            <span class="text-muted" style="font-size: 0.7rem;">{{ $name }}</span>
                                        </div>
                                @endforeach
                            </div>
                        @endif
                       
                    </div>
                    
                    <div class="">
                        <div class="text-muted small">{{ $s['sub'] }}</div>
                        
                       
                    </div>
                </div>
            </div>
        </a>
    </div>
    @endforeach

</div>

<div class="row g-4 mb-4">
    <!-- Acciones rápidas -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">Distribución por Estatus</h5>
            </div>
            <div style="position: relative; margin: auto; height: 500px; width: 350px;">
                <canvas id="vehiculosEstatusChart"></canvas>
            </div>
        </div>
    </div>
    <!-- Gráfica de vehículos por estatus -->

     
        <!-- Gráfico 1: Histórico de Eficiencia de Flota (NUEVO GRÁFICO DE LÍNEA) -->
        <div class="col-xl-8 col-lg-8 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Histórico de Eficiencia de Flota (15 Días)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="eficienciaHistoricoChart" style="height: 200px; "></canvas>
                    </div>
                </div>
            </div>
    <!--  Claficiación por tipo -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title text-uppercase">Distribución de Flota por Tipo</h5>
                </div>
                <div class="card-body">
                    <div id="grafico-grupo" style="width:100%; height:300px;"></div>
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
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">Próximos Mantenimientos</h5>
            </div>
            <div class="card-body">
                 @if($mantenimientos->count() === 0)
                <p class="text-muted">No hay mantenimientos programados.</p>
            @else
                <ul class="list-group list-group-flush">
                    @foreach($mantenimientos as $item)
                        @php
                            $hecho = $item->fecha->isPast() ? 0 : 1;
                        @endphp

                        <li class="list-group-item d-flex flex-column">

                            <div class="d-flex align-items-center mb-1">
                                <i class="fa fa-wrench text-warning me-2"></i>

                                <strong>{{ $item->vehiculo->placa ?? 'Sin placa' }}</strong>
                            </div>

                            <small class="text-muted">
                                <i class="fa fa-calendar"></i>
                                {{ $item->fecha->format('d/m/Y') }}
                            </small>

                            @if($item->km)
                                <small class="text-muted">
                                    <i class="fa fa-tachometer"></i>
                                    Programado a: {{ number_format($item->km) }} km
                                </small>
                            @endif

                            <small>
                                {{ $item->descripcion }}
                            </small>
                            @if($hecho==0)
                                <span class="badge bg-danger mt-1">Atrasado</span>
                            @else
                                @if($item->estatus === '1')
                                    <span class="badge bg-warning text-dark mt-1">Pendiente</span>
                                @elseif($item->estatus === '2')
                                    <span class="badge bg-primary mt-1">Programado</span>
                                @elseif($item->estatus === '3')
                                    <span class="badge bg-success mt-1">Realizado</span>
                                @endif
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
            </div>
        </div>
    </div>
    <!-- Índice de reportes de falla mensuales -->
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">Índice de Reportes de Falla</h5>
            </div>
            <div class="card-body">
                <canvas id="fallasChart" height="80"></canvas>
                <small class="text-muted">Cantidad de reportes de falla por mes.</small>
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
    
    <!-- Camiones en ruta o servicio -->
    <div class="col-md-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">Camiones en Ruta/Servicio  (Demo)</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Placa</th>
                            <th>Modelo</th>
                            <th>Marca</th>
                            <th>Ruta</th>
                            <th>Kilometraje</th>
                            <th>Carga Total (Lts)</th>
                            <th>Cliente Destino</th>
                        </tr>
                    </thead>
                    <tbody>
                        
                       @forelse($viajesActivos as $v)
                        <tr>
                            <td>{{ $v['placa'] }}</td>
                            <td>{{ $v['modelo'] }}</td>
                            <td>{{ $v['marca'] }}</td>
                            <td>{{ $v['ruta'] }}</td>
                            <td>{{ $v['km'] }} km</td>
                            <td>{{ $v['carga_total'] }}</td>
                            <td>
                                {{ $v['cliente_destino'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">
                                No hay vehículos en ruta actualmente.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
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
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // =======================================================  
    // GRÁFICO 1: Histórico de Eficiencia (Línea)
    // =======================================================
    var ctxEficiencia = document.getElementById('eficienciaHistoricoChart').getContext('2d');
    
    // Datos inyectados desde PHP
    const chartLabels = @json($chartLabels);
    const chartDataCierre = @json($chartDataCierre);
    // 3. Registrar el plugin antes de inicializar el gráfico
    Chart.register(ChartDataLabels);

    new Chart(ctxEficiencia, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [
                {
                    label: 'Cierre del Día',
                    data: chartDataCierre,
                    borderColor: '#10b981', // Verde
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                    pointBackgroundColor: '#10b981',
                }
            ]
        },
        options: {
            maintainAspectRatio: false,
            responsive: true,
            scales: {
                x: {
                    //offset: true,
                    title: {
                        display: true,
                        text: 'Fecha'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Eficiencia (%)'
                    },
                    beginAtZero: false,
                    // Asegurar que el eje Y termine en 100%
                    suggestedMax: 100, 
                    ticks: {
                        callback: function(value, index, ticks) {
                            return value + '%';
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('es-ES', { style: 'percent', minimumFractionDigits: 1, maximumFractionDigits: 1 }).format(context.parsed.y / 100);
                            }
                            return label;
                        }
                    }
                },
                datalabels: {
                    align: 'end', // Posiciona la etiqueta al final del punto (arriba)
                    anchor: 'end', // Similar a align, asegura que se mueva hacia afuera
                    offset: 8, // Separación del punto
                    color: '#333333', // Color del texto de la etiqueta
                    font: {
                        weight: 'bold',
                        size: 12,
                    },
                     formatter: function(value, context) {
                        const numericValue = parseFloat(value);
                        if (isNaN(numericValue)) {
                            return 'N/A'; 
                        }
                        return numericValue.toFixed(1) + '%';
                    }
                }
            }
        }
    });



    var ctx = document.getElementById('vehiculosEstatusChart').getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Disponible', 'En servicio', 'En Mantenimiento', 'Fuera Servicio'],
            datasets: [{
                data: [
                    {{ $unidades_disponibles }}, 
                    {{ $unidades_en_servicio }}, 
                    {{ $unidades_en_mantenimiento }}, 
                    {{ $unidades_con_orden_abierta - $unidades_en_mantenimiento }}
                ],
                backgroundColor: ['#28a745', '#ffc107', '#007bff', '#dc3545'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // Permite que respete el tamaño del DIV padre
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        padding: 10,
                        color: '#333', // Color de fuente oscuro para legibilidad
                        font: {
                            size: 16, // Tamaño de letra más grande
                            family: "'Helvetica Neue', 'Helvetica', 'Arial', sans-serif",
                            weight: 'bold'
                        }
                    }
                },
                tooltip: {
                    bodyFont: { size: 18 },
                    titleFont: { size: 20 }
                }
            },
            // Animación para que no sea tan brusco el cambio de tamaño
            animation: {
                duration: 1000
            }
        }
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

     const falla_labels = @json($fallas_labels);
    const falla_values = @json($fallas_values);
    var ctx3 = document.getElementById('fallasChart').getContext('2d');
    new Chart(ctx3, {
        type: 'bar',
        data: {
            labels: falla_labels,
            datasets: [{
                label: 'Reportes de Falla',
                data: falla_values,
                backgroundColor: '#dc3545',
            }]
        },
        options: {
            plugins: {
                legend: { display: false }
            }
        }
    });

    Highcharts.chart('grafico-grupo', {
        chart: { type: 'column' },
        title: { text: null },
        xAxis: {
            categories: @json($chartCategorias),
            crosshair: true
        },
        yAxis: {
            min: 0,
            title: { text: 'Cantidad de Unidades' },
            stackLabels: {
                enabled: true,
                style: { fontWeight: 'bold', color: '#555' }
            }
        },
        legend: {
            align: 'right',
            verticalAlign: 'top',
            y: 25,
            floating: false,
            backgroundColor: 'white',
            shadow: false
        },
        tooltip: {
            headerFormat: '<b>{point.x}</b><br/>',
            pointFormat: '{series.name}: {point.y}<br/>Total: {point.stackTotal}'
        },
        plotOptions: {
            column: {
                stacking: 'normal',
                dataLabels: { enabled: true }
            },
            series: {
                cursor: 'pointer',
                point: {
                    events: {
                        click: function () {
                            // Redirección dinámica usando la URL enviada desde el controlador
                            window.location.href = this.series.options.url;
                        }
                    }
                }
            }
        },
        series: @json($chartSeries)
    });


    // Simulación de datos de los 10 camiones con mayor consumo
    const topVehiculos = [
        { placa: 'XYZ-789', km: 120000, consumo: 32 },
        { placa: 'JKL-321', km: 98000, consumo: 31 },
        { placa: 'LMN-654', km: 110500, consumo: 30 },
        { placa: 'QRS-987', km: 105000, consumo: 29 },
        { placa: 'TUV-654', km: 99000, consumo: 29 },
        { placa: 'DEF-456', km: 112000, consumo: 28 },
        { placa: 'GHI-321', km: 101000, consumo: 28 },
        { placa: 'MNO-852', km: 95000, consumo: 27 },
        { placa: 'PQR-741', km: 97000, consumo: 27 },
        { placa: 'STU-963', km: 93000, consumo: 26 }
    ];

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