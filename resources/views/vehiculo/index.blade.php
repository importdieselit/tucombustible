@extends('layouts.app')

@section('title', 'Dashboard de Vehículos')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="mb-2">Dashboard de Vehículos</h1>
        <p class="text-muted">Monitorea el estado de la flota, consumo, mantenimientos y desempeño operativo.</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- KPIs principales -->
    <div class="col-md-2">
        <div class="card shadow-sm border-0 text-center">
            <div class="card-body">
                <span class="rounded-circle p-3 mb-2 d-inline-block" style="background:#28a74510;">
                    <i class="fa fa-flag text-success" style="font-size:2rem;"></i>
                </span>
                <h2 class="fw-bold text-success"></h2>
                <div class="text-muted small">Disponibles</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card shadow-sm border-0 text-center">
            <div class="card-body">
                <span class="rounded-circle p-3 mb-2 d-inline-block" style="background:#ffc10710;">
                    <i class="fa fa-car text-warning" style="font-size:2rem;"></i>
                </span>
                <h2 class="fw-bold text-warning"></h2>
                <div class="text-muted small">En Ruta/Servicio</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card shadow-sm border-0 text-center">
            <div class="card-body">
                <span class="rounded-circle p-3 mb-2 d-inline-block" style="background:#007bff10;">
                    <i class="fa fa-exclamation-triangle text-primary" style="font-size:2rem;"></i>
                </span>
                <h2 class="fw-bold text-primary"></h2>
                <div class="text-muted small">En Mantenimiento</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card shadow-sm border-0 text-center">
            <div class="card-body">
                <span class="rounded-circle p-3 mb-2 d-inline-block" style="background:#6c757d10;">
                    <i class="fa fa-exclamation-triangle text-secondary" style="font-size:2rem;"></i>
                </span>
                <h2 class="fw-bold text-secondary"></h2>
                <div class="text-muted small">Fuera Servicio</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card shadow-sm border-0 text-center">
            <div class="card-body">
                <span class="rounded-circle p-3 mb-2 d-inline-block" style="background:#d12638e0;">
                    <i class="fa fa-times text-danger" style="font-size:2rem;"></i>
                </span>
                <h2 class="fw-bold text-danger">{{ Vehiculos::getUnidadesConDocumentos()}}</h2>
                <div class="text-muted small">Documentos Vencidos</div>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card shadow-sm border-0 text-center">
            <div class="card-body">
                <span class="rounded-circle p-3 mb-2 d-inline-block bg-dark">
                    <i class="fa fa-car text-white" style="font-size:2rem;"></i>
                </span>
                <h2 class="fw-bold">62</h2>
                <div class="text-muted small">Total Vehículos</div>
            </div>
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
                <a href="{{ route('vehiculos.create') }}" class="btn btn-primary w-100 mb-2">
                    <i class="fa fa-plus"></i> Registrar Vehículo
                </a>
                <a href="{{ route('vehiculos.index') }}" class="btn btn-outline-secondary w-100 mb-2">
                    <i class="fa fa-list"></i> Ver Listado
                </a>
                <a href="#" class="btn btn-outline-info w-100">
                    <i class="fa fa-file-excel"></i> Exportar Excel
                </a>
            </div>
        </div>
    </div>
    
    <!-- Gráfica de vehículos por estatus -->
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
                <h5 class="mb-0">Relación Kilometraje / Consumo</h5>
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
                <h5 class="mb-0">Top 10 Camiones con Mayor Consumo</h5>
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
                <h5 class="mb-0">Nivel Combustible Estimado</h5>
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
                <h5 class="mb-0">Próximos Mantenimientos</h5>
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
                <h5 class="mb-0">Gasto Estimado Mensual</h5>
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
                <h5 class="mb-0">Índice de Reportes de Falla</h5>
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
                <h5 class="mb-0">Camiones en Ruta/Servicio</h5>
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
                            <td>XYZ-789</td>
                            <td>Daily</td>
                            <td>Iveco</td>
                            <td>Caracas - Valencia</td>
                            <td>120,000</td>
                            <td>28</td>
                            <td><span class="badge bg-warning text-dark">En servicio</span></td>
                        </tr>
                        <tr>
                            <td>JKL-321</td>
                            <td>Sprinter</td>
                            <td>Mercedes</td>
                            <td>Maracay - Barquisimeto</td>
                            <td>98,000</td>
                            <td>26</td>
                            <td><span class="badge bg-warning text-dark">En servicio</span></td>
                        </tr>
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
<div class="row g-4">
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
</div>
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
            labels: ['Disponible', 'En servicio', 'En Mantenimiento', 'Fuera Servicio', 'Desincorporado'],
            datasets: [{
                data: [32, 18, 7, 3, 2],
                backgroundColor: ['#28a745', '#ffc107', '#007bff', '#6c757d', '#dc3545'],
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