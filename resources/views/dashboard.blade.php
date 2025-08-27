@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
{{-- Se realizan las consultas a la base de datos directamente en la vista --}}
<?php
use App\Models\Vehiculo;
use App\Models\Orden;
use App\Models\Deposito;
use App\Models\User;
use App\Models\Alerta;
use App\Models\Mantenimiento;
use App\Models\Inventario;
use Illuminate\Support\Facades\DB;

// KPI: Vehículos en operación
$totalVehiculos = Vehiculo::count();

// KPI: Órdenes activas (ejemplo: estatus 1 = activo/en proceso, 2 = pendiente)
$ordenesActivas = Orden::whereIn('estatus', [1, 2])->count();

// KPI: Depósitos operativos (ejemplo: estatus 1 = operativo)
$depositosOperativos = Deposito::count();

// KPI: Usuarios activos (ejemplo: estatus 1 = activo)
$usuariosActivos = User::where('status', 1)->count();

// KPI: Alertas críticas
$alertasCriticas = 2; //Alerta::where('prioridad', 'critica')->count();

// KPI: Mantenimientos pendientes
$mantenimientosPendientes = 4; //Mantenimiento::where('estatus', 'pendiente')->count();

// KPI: Inventario bajo (existencia < existencia_minima)
$inventarioBajo = Inventario::whereColumn('existencia', '<', 'existencia_minima')->count();

// KPI: Eficiencia de flota - Se deja como un valor fijo ya que la lógica es muy compleja
$eficienciaFlota = 92; 

// Tabla de órdenes recientes
$ordenesRecientes = Orden::orderBy('created_at', 'desc')->limit(3)->get();
$alertasRecientes = Alerta::orderBy('id_alerta', 'desc')->limit(5)->get();

// Datos para el gráfico de órdenes por estatus
$ordenesPorEstatus = Orden::select('estatus', DB::raw('count(*) as total'))
                         ->groupBy('estatus')
                         ->pluck('total', 'estatus')
                         ->toArray();
$labels = ['Completada', 'Pendiente', 'En Proceso'];
$data = [
    $ordenesPorEstatus[3] ?? 0, // Suponiendo 3 es completada
    $ordenesPorEstatus[2] ?? 0, // Suponiendo 2 es pendiente
    $ordenesPorEstatus[1] ?? 0  // Suponiendo 1 es en proceso
];
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="mb-2">Panel de Control</h1>
            <p class="text-muted">Bienvenido, {{ Auth::user()->name ?? 'Usuario' }}. Visualiza el estado actual de la operación logística y toma decisiones informadas.</p>
        </div>
    </div>
    <div class="row g-4 mb-4">
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
                        <h2 class="fw-bold">{{ $totalVehiculos }}</h2>
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
                        <h2 class="fw-bold">{{ $ordenesActivas }}</h2>
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
                        <h2 class="fw-bold">{{ $depositosOperativos }}</h2>
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
                        <h2 class="fw-bold">{{ $usuariosActivos }}</h2>
                        <small class="text-muted">Activos</small>
                    </div>
                </div>
            </div>
        </div>
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
                        <h2 class="fw-bold">{{ $alertasCriticas }}</h2>
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
                        <h2 class="fw-bold">{{ $mantenimientosPendientes }}</h2>
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
                        <h2 class="fw-bold">{{ $inventarioBajo }}</h2>
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
                        <h2 class="fw-bold">{{ $eficienciaFlota }}%</h2>
                        <small class="text-muted">Último mes</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Órdenes por Estatus</h5>
                </div>
                <div class="card-body">
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
                        {{-- Aquí iría la lógica para mostrar alertas dinámicas --}}
                          @foreach ($alertasRecientes as $alerta)
                        <li class="list-group-item d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                            {{ $alerta->observacion }}
                        </li>
                        @endforeach
                        @if($alertasRecientes->isEmpty())
                        <li class="list-group-item">No hay alertas recientes.</li>
                        @endif
                        
                    </ul>
                </div>
            </div>
        </div>
    </div>
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
                            @foreach ($ordenesRecientes as $orden)
                                <tr>
                                    <td>{{ $orden->nro_orden }}</td>
                                    <td>{{ $orden->vehiculo()->placa ?? 'N/A' }}</td>
                                    <td>{{ $orden->responsable ?? 'N/A' }}</td>
                                    <td>
                                        @if ($orden->estatus == 3)
                                            <span class="badge bg-success">Completada</span>
                                        @elseif ($orden->estatus == 2)
                                            <span class="badge bg-warning text-dark">Pendiente</span>
                                        @elseif ($orden->estatus == 1)
                                            <span class="badge bg-danger">En Proceso</span>
                                        @else
                                            <span class="badge bg-secondary">Desconocido</span>
                                        @endif
                                    </td>
                                    <td>{{ $orden->created_at->format('Y-m-d') }}</td>
                                    <td><a href="{{ route('ordenes.show', $orden->id) }}" class="btn btn-sm btn-outline-primary">Ver</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var ctx = document.getElementById('ordenesEstadoChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: @json($labels),
            datasets: [{
                data: @json($data),
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
