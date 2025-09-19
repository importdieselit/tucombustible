@extends('layouts.app')

@section('title', 'Dashboard de Combustible')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="mb-2">Dashboard de Combustible</h1>
        <p class="text-muted">Información clave y monitoreo en tiempo real para la gestión de combustible.</p>
    </div>
</div>
<div class="col-12 d-flex justify-content-between align-items-center">
        <h1 class="mb-2">Depósitos de Combustible</h1>
        <div>
            <!-- Botón para abrir el modal de creación de depósito -->
            <a href="{{ route('depositos.list') }}" type="button" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Ver Depositos
            </a>
        </div>
    </div>
<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="card p-3 h-100 d-flex align-items-center justify-content-center">
            <div class="d-flex align-items-center">
                <div class="rounded-circle-icon bg-warning me-3 p-3">
                     <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-clock-fill text-white" viewBox="0 0 16 16">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                     </svg>
                </div>
                <div>
                    <h2 class="h5 m-0">{{ $pedidosPendientes }}</h2>
                    <p class="text-muted m-0">Pedidos Pendientes</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card p-3 h-100 d-flex align-items-center justify-content-center">
            <div class="d-flex align-items-center">
                <div class="rounded-circle-icon bg-info me-3 p-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-truck text-white" viewBox="0 0 16 16">
                        <path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5V5h-1v-1a.5.5 0 0 0-.5-.5H1.5A.5.5 0 0 0 1 3.5v-.5A.5.5 0 0 1 1.5 2H10a.5.5 0 0 1 .5.5v2.5a.5.5 0 0 1-.5.5v1.898A4.212 4.212 0 0 0 11.5 8h1.298l2.64 3.298A.5.5 0 0 1 15.5 12H14a1.5 1.5 0 0 1-1.5-1.5V10h-1v.5a.5.5 0 0 0 .5.5H12a.5.5 0 0 0 .5-.5V8.5A2.502 2.502 0 0 1 14.5 6h.814a.5.5 0 0 0 .47-.648L15.352 3.5H1.5A1.5 1.5 0 0 1 0 3.5zM12.5 10A.5.5 0 0 0 12 10.5v.5a.5.5 0 0 0 .5.5h.5a.5.5 0 0 0 .5-.5v-.5a.5.5 0 0 0-.5-.5h-.5zm-1.5 0a.5.5 0 0 0-.5.5v.5a.5.5 0 0 0 .5.5h.5a.5.5 0 0 0 .5-.5v-.5a.5.5 0 0 0-.5-.5h-.5zM10.5 10A.5.5 0 0 0 10 10.5v.5a.5.5 0 0 0 .5.5h.5a.5.5 0 0 0 .5-.5v-.5a.5.5 0 0 0-.5-.5h-.5zM9.5 10A.5.5 0 0 0 9 10.5v.5a.5.5 0 0 0 .5.5h.5a.5.5 0 0 0 .5-.5v-.5a.5.5 0 0 0-.5-.5h-.5z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="h5 m-0">{{ $pedidosEnProceso }}</h2>
                    <p class="text-muted m-0">Pedidos en Proceso</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card p-3 h-100 d-flex align-items-center justify-content-center">
            <div class="d-flex align-items-center">
                <div class="rounded-circle-icon bg-success me-3 p-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-truck-flatbed text-white" viewBox="0 0 16 16">
                        <path d="M11.5 4a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5zM14 6h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 0 1z"/>
                        <path d="M12.352 2.302L15.422 4.29a.5.5 0 0 1 .184.654L14.755 8.44a.5.5 0 0 1-.497.382H13a.5.5 0 0 1-.5-.5V6h-1v2.5a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5V6H5v4.5a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5V6H2v3.5A2.5 2.5 0 0 0 .5 12h.793a1.5 1.5 0 0 1 2.493-1.002L4 11.5V14h-.5a.5.5 0 0 1 0-1h1a.5.5 0 0 1 .5.5v.5a.5.5 0 0 1-.5.5H3a.5.5 0 0 1-.5-.5v-.5a.5.5 0 0 1-.5-.5H1.5a.5.5 0 0 1-.5.5V12a2.5 2.5 0 0 0 2.5 2.5H14a.5.5 0 0 1 .5.5v.5a.5.5 0 0 1-.5.5H13a.5.5 0 0 1-.5-.5v-.5a.5.5 0 0 1-.5-.5H11.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v.5a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5V10h-.5a.5.5 0 0 1-.5-.5V9h-1a.5.5 0 0 1-.5-.5V8.5h-1a.5.5 0 0 1-.5-.5V7h-1a.5.5 0 0 1-.5-.5V6H5v-.5A1.5 1.5 0 0 0 3.5 4H2.293L1.578 2.302A.5.5 0 0 1 2 2h9.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="h5 m-0">{{ $camionesCargados }}</h2>
                    <p class="text-muted m-0">Camiones Cargados</p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="card p-3 h-100 d-flex align-items-center justify-content-center">
            <div class="d-flex align-items-center">
                <div class="rounded-circle-icon bg-success me-3 p-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-truck-flatbed text-white" viewBox="0 0 16 16">
                        <path d="M11.5 4a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5zM14 6h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 0 1z"/>
                        <path d="M12.352 2.302L15.422 4.29a.5.5 0 0 1 .184.654L14.755 8.44a.5.5 0 0 1-.497.382H13a.5.5 0 0 1-.5-.5V6h-1v2.5a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5V6H5v4.5a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5V6H2v3.5A2.5 2.5 0 0 0 .5 12h.793a1.5 1.5 0 0 1 2.493-1.002L4 11.5V14h-.5a.5.5 0 0 1 0-1h1a.5.5 0 0 1 .5.5v.5a.5.5 0 0 1-.5.5H3a.5.5 0 0 1-.5-.5v-.5a.5.5 0 0 1-.5-.5H1.5a.5.5 0 0 1-.5.5V12a2.5 2.5 0 0 0 2.5 2.5H14a.5.5 0 0 1 .5.5v.5a.5.5 0 0 1-.5.5H13a.5.5 0 0 1-.5-.5v-.5a.5.5 0 0 1-.5-.5H11.5a.5.5 0 0 1-.5-.5V12h-.5a.5.5 0 0 1-.5-.5V11a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v.5a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5V10h-.5a.5.5 0 0 1-.5-.5V9h-1a.5.5 0 0 1-.5-.5V8.5h-1a.5.5 0 0 1-.5-.5V7h-1a.5.5 0 0 1-.5-.5V6H5v-.5A1.5 1.5 0 0 0 3.5 4H2.293L1.578 2.302A.5.5 0 0 1 2 2h9.5a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="h5 m-0">{{ $totalCombustible }}Litros</h2>
                    <p class="text-muted m-0">Combustible Disponible</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card shadow-sm p-4 h-100">
            <div class="card-header bg-white">
                <h5 class="card-title m-0">Nivel de Disponibilidad de Clientes (Padre)</h5>
            </div>
            <div class="card-body bg-white">
                <canvas id="disponibilidadChart"></canvas>
            </div>
        </div>
    </div>

    
        @foreach ($tipoDeposito as $tipo )
    <div class="col-lg-6">        
        
        <div class="card shadow-sm p-4 h-100">
            <div class="card-header bg-white">
                <h5 class="card-title m-0">Niveles de Depósitos tipo {{ $tipo->producto }}</h5>
            </div>
            <div class="card-body bg-white">
                <ul class="list-group list-group-flush">
                    @foreach($tipo->depositos as $deposito)
                        @php($percentage = $deposito->nivel)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="m-0">{{ $deposito->serial }} ({{ $deposito->producto }})</h6>
                                <p class="text-muted m-0"><small>Nivel: {{ $percentage }}%</small></p>
                            </div>
                            <div class="progress" style="width: 150px; height: 20px;">
                                <div class="progress-bar" 
                                     role="progressbar" 
                                     style="width: {{  $percentage }}%; background-color: {{  $percentage > 50 ? '#28a745' : ( $percentage > 25 ? '#ffc107' : '#dc3545') }};" 
                                     aria-valuenow="{{  $percentage }}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    {{ $deposito->nivel_actual_litros }}L - {{  $percentage }}%
                                </div>
                            </div>
                        </li>
                    @endforeach
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="m-0">Total </h6>
                                <p class="text-muted m-0"><small>Nivel: {{$tipo->nivel}}%</small></p>
                            </div>
                            <div class="progress" style="width: 150px; height: 20px;">
                                <div class="progress-bar" 
                                     role="progressbar" 
                                     style="width: {{  $tipo->nivel }}%; background-color: {{  $tipo->nivel > 50 ? '#28a745' : ( $tipo->nivel > 25 ? '#ffc107' : '#dc3545') }};" 
                                     aria-valuenow="{{  $tipo->nivel }}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    {{ $tipo->total }}L / {{  $tipo->nivel }}%
                                </div>
                            </div>
                    </li>
                </ul>
            </div>
        </div>

</div>
        @endforeach
    
</div>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card shadow-sm p-4">
            <div class="card-header bg-white">
                <h5 class="card-title m-0">Clientes Totales</h5>
            </div>
            <div class="card-body bg-white">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Cupo Disponible</th>
                                <th>Cupo Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($clientesPadre as $cliente)
                                <tr>
                                    <td>{{ $cliente->nombre }}</td>
                                    <td>{{ number_format($cliente->disponible, 2, ',', '.') }}</td>
                                    <td>{{ number_format($cliente->cupo, 2, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('disponibilidadChart').getContext('2d');
        const data = @json($disponibilidadData);
        
        const labels = data.map(d => d.nombre);
        const disponibles = data.map(d => d.disponible);
        const cupos = data.map(d => d.cupo);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: '% Disponibles',
                    data: disponibles,
                    backgroundColor: 'rgba(54, 162, 235, 0.8)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }
                // , {
                //     label: 'Cupo Total',
                //     data: cupos,
                //     backgroundColor: 'rgba(255, 99, 132, 0.8)',
                //     borderColor: 'rgba(255, 99, 132, 1)',
                //     borderWidth: 1
                // }
            ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Disponibilidad de Clientes Principales'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: '%'
                        }
                    }
                }
            }
        });
    });
</script>
@endpush
