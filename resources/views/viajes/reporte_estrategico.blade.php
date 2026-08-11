@extends('layouts.app') <!-- Ajusta a tu layout principal -->

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 text-gray-800">Dashboard Estratégico de Operaciones</h2>
        <button onclick="window.print()" class="btn btn-primary shadow-sm">
            <i class="fas fa-download fa-sm text-white-50"></i> Exportar PDF
        </button>
    </div>

    <!-- Filtros Dinámicos -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter"></i> Filtros de Búsqueda</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('viajes.reporte_estrategico') }}" class="row g-3">
                <div class="col-md-2">
                    <label>Desde</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="{{ $fechaInicio }}">
                </div>
                <div class="col-md-2">
                    <label>Hasta</label>
                    <input type="date" name="fecha_fin" class="form-control" value="{{ $fechaFin }}">
                </div>
                <div class="col-md-3">
                    <label>Chofer</label>
                    <select name="chofer_id" class="form-control">
                        <option value="">Todos los Choferes</option>
                        @foreach($choferes as $chofer)
                            <option value="{{ $chofer->id }}" {{ $choferId == $chofer->id ? 'selected' : '' }}>
                                {{ $chofer->persona->nombre }} {{ $chofer->persona->apellido }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Destino</label>
                    <select name="destino_ciudad" class="form-control">
                        <option value="">Todos los Destinos</option>
                        @foreach($destinos as $dest)
                            <option value="{{ $dest }}" {{ $destino == $dest ? 'selected' : '' }}>{{ $dest }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100"><i class="fas fa-search"></i> Generar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tarjetas de KPIs -->
    <div class="row mb-4">
        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Viajes Realizados</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalViajes) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-truck fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Volumen Movilizado (Litros)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalLitros, 2) }} Lts</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tint fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos Estratégicos -->
    <div class="row">
        <!-- Gráfico de Destinos -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Top Destinos</h6>
                </div>
                <div class="card-body">
                    <canvas id="destinosChart"></canvas>
                </div>
            </div>
        </div>
        <!-- Gráfico de Rendimiento Choferes -->
        <div class="col-xl-3 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Viajes por Chofer</h6>
                </div>
                <div class="card-body">
                    <canvas id="choferesChart"></canvas>
                </div>
            </div>
        </div>

        <!-- NUEVO: Gráfico de Rendimiento Ayudantes -->
        <div class="col-xl-3 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">Viajes por Ayudante</h6>
                </div>
                <div class="card-body">
                    <canvas id="ayudantesChart"></canvas>
                </div>
            </div>
        </div>
        <!-- Gráfico de Estatus -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Distribución de Estatus</h6>
                </div>
                <div class="card-body">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla Detallada -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Detalle de Operaciones</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Destino</th>
                            <th>Chofer</th>
                            <th>Unidad</th>
                            <th>Volumen (Lts)</th>
                            <th>Estatus</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($viajes as $viaje)
                            <tr>
                                <td>#{{ $viaje->id }}</td>
                                <td>{{ \Carbon\Carbon::parse($viaje->fecha_salida)->format('d/m/Y H:i') }}</td>
                                <td>{{ $viaje->destino_ciudad }}</td>
                                <td>{{ $viaje->chofer->persona->nombre ?? 'N/A' }}</td>
                                <td>{{ $viaje->vehiculo->placa ?? 'N/A' }}</td>
                                <td>{{ number_format($viaje->despachos->sum('litros') + ($viaje->litros ?? 0), 2) }}</td>
                                <td><span class="badge bg-secondary">{{ $viaje->status }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No se encontraron viajes para los criterios seleccionados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Importar Chart.js vía CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        
        // Data preparada desde Laravel a JSON
        const dataDestinos = @json($viajesPorDestino);
        
        const dataStatus = @json($viajesPorStatus);

        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        };

        // 1. Gráfico Top Destinos (Doughnut)
        new Chart(document.getElementById('destinosChart'), {
            type: 'doughnut',
            data: {
                labels: Object.keys(dataDestinos),
                datasets: [{
                    data: Object.values(dataDestinos),
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b']
                }]
            },
            options: commonOptions
        });

        // 2. Gráfico Top Choferes (Bar)
       const dataChoferes = @json($viajesPorChofer);
        const dataAyudantes = @json($viajesPorAyudante); // Nueva data

        // Gráfico de Choferes
        new Chart(document.getElementById('choferesChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(dataChoferes),
                datasets: [{
                    label: 'Viajes Realizados',
                    data: Object.values(dataChoferes),
                    backgroundColor: '#4e73df'
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        // NUEVO: Gráfico de Ayudantes
        new Chart(document.getElementById('ayudantesChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(dataAyudantes),
                datasets: [{
                    label: 'Viajes Realizados',
                    data: Object.values(dataAyudantes),
                    backgroundColor: '#36b9cc' // Color distinto para diferenciarlos
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        // 3. Gráfico de Estatus (Pie)
        new Chart(document.getElementById('statusChart'), {
            type: 'pie',
            data: {
                labels: Object.keys(dataStatus),
                datasets: [{
                    data: Object.values(dataStatus),
                    backgroundColor: ['#1cc88a', '#f6c23e', '#e74a3b', '#858796', '#36b9cc']
                }]
            },
            options: commonOptions
        });
    });
</script>
@endsection