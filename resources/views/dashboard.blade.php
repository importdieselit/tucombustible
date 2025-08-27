@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="mb-2">Panel de Control</h1>
            <p class="text-muted">Bienvenido, {{ Auth::user()->name ?? 'Usuario' }}. Visualiza el estado actual de la operación logística y toma decisiones informadas.</p>
        </div>
    </div>
    <div class="row g-4 mb-4">
        <!-- Tarjetas resumen (KPI) -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3">
                        <span class="bg-primary text-white rounded-circle p-3">
                            <i class="bi bi-truck" style="font-size:2rem;"></i>
                        </span>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">Vehículos</h5>
                        <h2 class="fw-bold">128</h2>
                        <small class="text-muted">En operación</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3">
                        <span class="bg-success text-white rounded-circle p-3">
                            <i class="bi bi-clipboard-check" style="font-size:2rem;"></i>
                        </span>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">Órdenes</h5>
                        <h2 class="fw-bold">54</h2>
                        <small class="text-muted">Activas</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3">
                        <span class="bg-warning text-white rounded-circle p-3">
                            <i class="bi bi-fuel-pump" style="font-size:2rem;"></i>
                        </span>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">Depositos</h5>
                        <h2 class="fw-bold">5</h2>
                        <small class="text-muted">Operativos</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3">
                        <span class="bg-info text-white rounded-circle p-3">
                            <i class="bi bi-people" style="font-size:2rem;"></i>
                        </span>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">Usuarios</h5>
                        <h2 class="fw-bold">23</h2>
                        <small class="text-muted">Activos</small>
                    </div>
                </div>
            </div>
        </div>
        <!-- KPIs adicionales -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3">
                        <span class="bg-danger text-white rounded-circle p-3">
                            <i class="bi bi-exclamation-triangle" style="font-size:2rem;"></i>
                        </span>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">Alertas Críticas</h5>
                        <h2 class="fw-bold">4</h2>
                        <small class="text-muted">Sin resolver</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3">
                        <span class="bg-secondary text-white rounded-circle p-3">
                            <i class="bi bi-tools" style="font-size:2rem;"></i>
                        </span>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">Mantenimientos</h5>
                        <h2 class="fw-bold">12</h2>
                        <small class="text-muted">Pendientes</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3">
                        <span class="bg-dark text-white rounded-circle p-3">
                            <i class="bi bi-box-seam" style="font-size:2rem;"></i>
                        </span>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">Inventario Bajo</h5>
                        <h2 class="fw-bold">6</h2>
                        <small class="text-muted">Productos críticos</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body d-flex align-items-center">
                    <div class="me-3">
                        <span class="bg-primary text-white rounded-circle p-3">
                            <i class="bi bi-graph-up-arrow" style="font-size:2rem;"></i>
                        </span>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">Eficiencia Flota</h5>
                        <h2 class="fw-bold">92%</h2>
                        <small class="text-muted">Último mes</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Gráficos y tablas de muestra -->
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Órdenes por Estatus</h5>
                </div>
                <div class="card-body">
                    <!-- Aquí puedes integrar un gráfico JS (ejemplo Chart.js) -->
                    <canvas id="ordenesEstadoChart" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Alertas recientes</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                            Mantenimiento pendiente en Vehículo #23
                        </li>
                        <li class="list-group-item d-flex align-items-center">
                            <i class="bi bi-fuel-pump text-danger me-2"></i>
                            Nivel bajo en Tanque Central
                        </li>
                        <li class="list-group-item d-flex align-items-center">
                            <i class="bi bi-clipboard-check text-success me-2"></i>
                            Nueva orden asignada a Juan Pérez
                        </li>
                        <li class="list-group-item d-flex align-items-center">
                            <i class="bi bi-box-seam text-dark me-2"></i>
                            Inventario crítico: Filtros de aceite
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <!-- Tabla de órdenes recientes -->
    <div class="row g-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Órdenes recientes</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Vehículo</th>
                                <th>Responsable</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1023</td>
                                <td>IVECO Daily</td>
                                <td>Juan Pérez</td>
                                <td><span class="badge bg-success">Completada</span></td>
                                <td>2025-08-10</td>
                                <td><a href="#" class="btn btn-sm btn-outline-primary">Ver</a></td>
                            </tr>
                            <tr>
                                <td>1024</td>
                                <td>Mercedes Sprinter</td>
                                <td>María López</td>
                                <td><span class="badge bg-warning text-dark">Pendiente</span></td>
                                <td>2025-08-11</td>
                                <td><a href="#" class="btn btn-sm btn-outline-primary">Ver</a></td>
                            </tr>
                            <tr>
                                <td>1025</td>
                                <td>Ford Cargo</td>
                                <td>Carlos Ruiz</td>
                                <td><span class="badge bg-danger">En Proceso</span></td>
                                <td>2025-08-12</td>
                                <td><a href="#" class="btn btn-sm btn-outline-primary">Ver</a></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ejemplo de integración de Chart.js -->
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var ctx = document.getElementById('ordenesEstadoChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Completadas', 'Pendientes', 'En Proceso'],
            datasets: [{
                data: [12, 8, 5],
                backgroundColor: ['#4e73df', '#f6c23e', '#e74a3b'],
            }]
        },
        options: {
            plugins: {
                legend: { display: true, position: 'bottom' }
            }
        }
    });
});
</script>
@endpush
@endsection