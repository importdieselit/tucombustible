@extends('layouts.app')
@php
$unidades_con_alerta = App\Models\Vehiculo::getUnidadesConDocumentosVencidos(Auth::user()->cliente_id)->count(); 
$total_vehiculos = App\Models\Vehiculo::misVehiculos()->count(); 
$unidades_con_orden_abierta = App\Models\Vehiculo::VehiculosConOrdenAbierta()->count();
$unidades_en_mantenimiento = App\Models\Vehiculo::countVehiculosEnMantenimiento();
$unidades_disponibles = App\Models\Vehiculo::Disponibles()->count();
$unidades_en_servicio = App\Models\Vehiculo::EnServicio()->count();
$eficienciaActual = $total_vehiculos > 0 
    ? ($unidades_disponibles / $total_vehiculos) * 100 
    : 0; 
$eficienciaActual = round($eficienciaActual, 2); 

$historicoEficiencia = [
    // Simulación de 7 días de histórico
    ['date' => Illuminate\Support\Carbon::today()->subDays(6)->toDateString(), 'start_efficiency' => 70.0, 'end_efficiency' => 72.5],
    ['date' => Illuminate\Support\Carbon::today()->subDays(5)->toDateString(), 'start_efficiency' => 72.5, 'end_efficiency' => 75.0],
    ['date' => Illuminate\Support\Carbon::today()->subDays(4)->toDateString(), 'start_efficiency' => 75.0, 'end_efficiency' => 78.0],
    ['date' => Illuminate\Support\Carbon::today()->subDays(3)->toDateString(), 'start_efficiency' => 78.0, 'end_efficiency' => 80.5],
    ['date' => Illuminate\Support\Carbon::today()->subDays(2)->toDateString(), 'start_efficiency' => 80.5, 'end_efficiency' => 79.2],
    ['date' => Illuminate\Support\Carbon::today()->subDays(1)->toDateString(), 'start_efficiency' => 79.2, 'end_efficiency' => 82.1],
    // Hoy: se usa la eficiencia actual como valor de cierre
    ['date' => Illuminate\Support\Carbon::today()->toDateString(), 'start_efficiency' => 82.1, 'end_efficiency' => $eficienciaActual], 
];
@endphp
@section('title', 'Dashboard de Vehículos')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="mb-2">Dashboard de Vehículos</h1>
        <p class="text-muted">Monitorea el estado de la flota, consumo, mantenimientos y desempeño operativo.</p>
    </div>
</div>

<div class="row g-4 mb-4">

     <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Eficiencia de Flota 
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $eficienciaActual }}%
                            </div>
                            <small class="text-muted">
                                {{ $unidades_disponibles }} / {{ $total_vehiculos }} Unidades Disponibles
                            </small>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-graph-up-arrow fa-2x text-gray-300" style="font-size: 2.5rem; color: #10b981;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <!-- KPIs principales -->
    <div class="col-md-2">
        <div class="card shadow-sm border-0 text-center">
            <a href="{{ route('vehiculos.list', ['filter' => 'disponibles']) }}" target="_blank">
            <div class="card-body">
                <span class="rounded-circle p-3 mb-2 d-inline-block" style="background:#28a74510;">
                    <i class="fa fa-flag text-success" style="font-size:2rem;"></i>
                </span>
                <h2 class="fw-bold text-success">{{ $unidades_disponibles}}</h2>
                <div class="text-muted small">Disponibles</div>
            </div>
            </a>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card shadow-sm border-0 text-center">
            <div class="card-body">
                <span class="rounded-circle p-3 mb-2 d-inline-block" style="background:#ffc10710;">
                    <i class="fa fa-car text-warning" style="font-size:2rem;"></i>
                </span>
                <h2 class="fw-bold text-warning">{{ $unidades_en_servicio }}</h2>
                <div class="text-muted small">En Ruta/Servicio</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card shadow-sm border-0 text-center">
            <a href="{{ route('vehiculos.list', ['filter' => 'mantenimiento']) }}" target="_blank">
            <div class="card-body">
                <span class="rounded-circle p-3 mb-2 d-inline-block" style="background:#007bff10;">
                    <i class="fa fa-exclamation-triangle text-primary" style="font-size:2rem;"></i>
                </span>
                <h2 class="fw-bold text-primary">{{ $unidades_en_mantenimiento}}</h2>
                <div class="text-muted small">Por Mantenimiento</div>
            </div>
            </a>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card shadow-sm border-0 text-center">
            <a href="{{ route('vehiculos.list', ['filter' => 'con_orden_abierta']) }}" target="_blank">
            <div class="card-body">
                <span class="rounded-circle p-3 mb-2 d-inline-block" style="background:#6c757d10;">
                    <i class="fa fa-exclamation-triangle text-secondary" style="font-size:2rem;"></i>
                </span>
                <h2 class="fw-bold text-secondary">{{$unidades_con_orden_abierta}}</h2>
                <div class="text-muted small">Fuera Servicio</div>
            </div>
            </a>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card shadow-sm border-0 text-center">
            <a href="{{ route('vehiculos.list', ['filter' => 'documentos_alerta']) }}" target="_blank">
            <div class="card-body">
                <span class="rounded-circle p-3 mb-2 d-inline-block" style="background:#d12638e0;">
                    <i class="fa fa-times text-danger" style="font-size:2rem;"></i>
                </span>
                <h2 class="fw-bold text-danger">{{ $unidades_con_alerta }}</h2>
                <div class="text-muted small">Documentos Vencidos</div>
            </div>
            </a>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card shadow-sm border-0 text-center">
            <a href="{{ route('vehiculos.list') }}" target="_blank">
            <div class="card-body">
                <span class="rounded-circle p-3 mb-2 d-inline-block bg-dark">
                    <i class="fa fa-car text-white" style="font-size:2rem;"></i>
                </span>
                <h2 class="fw-bold">{{ $total_vehiculos }}</h2>
                <div class="text-muted small">Total Vehículos</div>
            </div>
            </a>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Acciones rápidas -->
    <div class="col-lg-3">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">Acciones rápidas</h5>
            </div>
            <div class="card-body">
                 @if(Auth::user()->canAccess('create', 10))
                <a href="{{ route('vehiculos.create') }}" class="btn btn-primary w-100 mb-2">
                    <i class="fa fa-plus"></i> Registrar Vehículo
                </a>
                @endif
                 @if(Auth::user()->canAccess('read', 10))

                <a href="{{ route('vehiculos.list') }}" class="btn btn-outline-secondary w-100 mb-2">
                    <i class="fa fa-list"></i> Ver Listado
                </a>
                @endif
                 
                <a href="#" class="btn btn-outline-info w-100">
                    <i class="fa fa-file-excel"></i> Exportar Excel
                </a>
            </div>
        </div>
    </div>
    
    <!-- Gráfica de vehículos por estatus -->

     
        <!-- Gráfico 1: Histórico de Eficiencia de Flota (NUEVO GRÁFICO DE LÍNEA) -->
        <div class="col-xl-8 col-lg-7 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Histórico de Eficiencia de Flota (7 Días)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="eficienciaHistoricoChart" style="max-height: 400px;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">Distribución por Estatus</h5>
            </div>
            <div class="card-body">
                <canvas id="vehiculosEstatusChart" height="120"></canvas>
            </div>
        </div>
    </div> <!-- Relación Kilometraje vs Consumo -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">Relación Kilometraje / Consumo  (Demo)</h5>
            </div>
            <div class="card-body">
                <canvas id="kmConsumoChart" height="120"></canvas>
                <small class="text-muted">Detecta incongruencias entre el kilometraje recorrido y el consumo de combustible.</small>
            </div>
        </div>
    </div>
    <!-- Relación Kilometraje vs Consumo (Top 10 camiones con mayor consumo) -->
    <div class="col-lg-6">
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

<div class="row g-4 mb-4">
    <!-- Nivel de combustible estimado -->
    <div class="col-md-4">
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
    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">Próximos Mantenimientos (Demo)</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex align-items-center">
                        <i class="fa fa-wrench text-warning me-2"></i>
                        Vehículo ABC-123 - 1,200 km restantes
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                        <i class="fa fa-wrench text-warning me-2"></i>
                        Vehículo XYZ-789 - 2,000 km restantes
                    </li>
                    <li class="list-group-item d-flex align-items-center">
                        <i class="fa fa-wrench text-warning me-2"></i>
                        Vehículo DEF-456 - 3,500 km restantes
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <!-- Gasto estimado mensual -->
    <div class="col-md-4">
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

<div class="row g-4 mb-4">
    <!-- Índice de reportes de falla mensuales -->
    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0">
                <h5 class="mb-0">Índice de Reportes de Falla  (Demo)</h5>
            </div>
            <div class="card-body">
                <canvas id="fallasChart" height="80"></canvas>
                <small class="text-muted">Cantidad de reportes de falla por mes.</small>
            </div>
        </div>
    </div>
    <!-- Camiones en ruta o servicio -->
    <div class="col-md-8">
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
                            <th>Consumo (L/100km)</th>
                            <th>Estatus</th>
                        </tr>
                    </thead>
                    <tbody>
                        
                        <tr>
                            <td>LMN-654</td>
                            <td>Cargo</td>
                            <td>Ford</td>
                            <td>Puerto Ordaz - Maturín</td>
                            <td>110,500</td>
                            <td>30</td>
                            <td><span class="badge bg-warning text-dark">En servicio</span></td>
                        </tr>
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
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Gráfica de vehículos por estatus
    var ctx = document.getElementById('vehiculosEstatusChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Disponible', 'En servicio', 'En Mantenimiento', 'Fuera Servicio'],
            datasets: [{
                data: [{{$unidades_disponibles}}, {{$unidades_en_servicio}}, {{$unidades_en_mantenimiento }}, {{ $unidades_con_orden_abierta-$unidades_en_mantenimiento}}],
                backgroundColor: ['#28a745', '#ffc107', '#007bff', '#dc3545'],
            }]
        },
        options: {
            plugins: {
                legend: { display: true, position: 'bottom' }
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
    var ctx3 = document.getElementById('fallasChart').getContext('2d');
    new Chart(ctx3, {
        type: 'bar',
        data: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago'],
            datasets: [{
                label: 'Reportes de Falla',
                data: [3, 5, 2, 4, 6, 3, 2, 5],
                backgroundColor: '#dc3545',
            }]
        },
        options: {
            plugins: {
                legend: { display: false }
            }
        }
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