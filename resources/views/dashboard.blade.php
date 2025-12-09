@extends('layouts.app')

@section('title', 'Dashboard')

@php
    // IDs de Módulos (referencia de tu tabla)
    $MODULO_VEHICULOS = 1;
    $MODULO_ORDENES = 2;
    $MODULO_INVENTARIO = 30;
    $MODULO_COMBUSTIBLE = 4;
    $MODULO_DESPACHOS = 42;
    $MODULO_USUARIOS = 51;
    $MODULO_ADMINISTRAR = 5;
    $MODULO_CHECKLIST = 6;
    $MODULO_REPORTES = 7;
    $MODULO_VIAJES = 8;
    $MODULO_CLIENTES = 52;
@endphp

@section('content')
{{-- Se realizan las consultas a la base de datos directamente en la vista --}}
<?php
use App\Models\Vehiculo;
use App\Models\Orden;
use App\Models\Deposito;
use App\Models\User;
use App\Models\Alerta;
use App\Models\Mantenimiento;
use App\Models\ResumenDiario;
use App\Models\Inventario;
use App\Models\Viaje;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

// KPI: Vehículos en operación
$totalVehiculos = Vehiculo::where('es_flota',true)->count();

// KPI: Órdenes activas (ejemplo: estatus 1 = activo/en proceso, 2 = pendiente)
$ordenesActivas = Orden::whereIn('estatus', [1, 2])->count();

// KPI: Depósitos operativos (ejemplo: estatus 1 = operativo)
$depositosOperativos = Deposito::count();

// KPI: Usuarios activos (ejemplo: estatus 1 = activo)
$usuariosActivos = User::where('status', 1)->count();

// KPI: Alertas críticas
$alertasCriticas = Alerta::where('prioridad', 'critica')->count();

// KPI: Mantenimientos pendientes
$mantenimientosPendientes = 4; //Mantenimiento::where('estatus', 'pendiente')->count();

// KPI: Inventario bajo (existencia < existencia_minima)
$inventarioBajo = Inventario::whereColumn('existencia', '<', 'existencia_minima')->count();

// Fórmula de Eficiencia: (Unidades Disponibles / Total Unidades) * 100
$eficienciaActual = $totalVehiculos > 0 
    ? ($unidades_disponibles / $totalVehiculos) * 100 
    : 0; 
$eficienciaFlota = round($eficienciaActual, 0);
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



$historicoReal = ResumenDiario::where('fecha', '>=', Carbon::today()->subDays(6))
    ->orderBy('fecha', 'asc')
    ->get();

// Preparar datos para Chart.js
$chartLabels = $historicoReal->map(function($item) {
    return Carbon::parse($item->fecha)->format('d/M');
})->toArray();

// Usamos el campo 'disponibilidad' (eficiencia)
$chartDataCierre = $historicoReal->pluck('disponibilidad')->toArray();

// Para simular el valor de 'Inicio del Día' para el gráfico,
// usamos el valor de cierre del día anterior (o 0 si es el primer día).
$chartDataInicio = $historicoReal->map(function($item, $key) use ($historicoReal) {
    if ($key === 0) {
        return 0; // O un valor inicial de referencia
    }
    return $historicoReal[$key - 1]->disponibilidad;
})->toArray();


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
                <a href="{{route('vehiculos.list')}}" target="_blank">
                    
                    <div class="card-body d-flex align-items-center">
                        <div class="me-3">
                            <span class="bg-primary text-white rounded-circle p-3">
                                <i class="bi bi-truck" style="font-size:2rem;"></i>
                            </span>
                        </div>
                        <div>
                            <h5 class="card-title mb-0">Vehículos</h5>
                            <h2 class="fw-bold">{{ $unidades_disponibles }}</h2>
                            <small class="text-muted">En operación</small>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <a href="{{route('ordenes.list')}}" target="_blank">

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
                </a>
            </div>
        </div>
        @if(in_array($user->id_perfil,[1,2,18]))
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <a href="{{route('ordenes.compra')}}" target="_blank">

                <div class="card-body d-flex align-items-center">
                    <div class="me-3">
                        <span class="bg-success text-white rounded-circle p-3">
                            <i class="bi bi-clipboard-check" style="font-size:2rem;"></i>
                        </span>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">Requerimientos de Suministros</h5>
                        <h2 class="fw-bold">{{ $suministros_compra }}</h2>
                        <small class="text-muted">Activas</small>
                    </div>
                </div>
                </a>
            </div>
        </div>
        @endif
        {{-- <div class="col-md-3">
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
        </div> --}}
        <div class="col-md-3">
                <a href="{{route('alertas.index')}}" target="_blank">

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
                </a>
        </div>
        {{-- <div class="col-md-3">
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
        </div> --}}
        <div class="col-md-3">
                <a href="{{route('inventario.list')}}" target="_blank">

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
                </a>
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

        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body d-flex align-items-center">
                    <div>
                        <h5 class="card-title mb-0">Mantenimientos Programados</h5>
                        <h2 class="fw-bold">{{ $programados }}</h2>
                        <h2 class="fw-bold text-danger">{{ $programadosHoy }} hoy</h2>
                        <small class="text-muted">Último mes</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

       {{-- Contenedor de Tarjetas de Acceso --}}
    <div class="row g-4 mb-4 justify-content-center">
        
        {{-- =============================================== --}}
        {{-- TARJETA DE VEHÍCULOS (ID 1) --}}
        {{-- El usuario necesita al menos permiso de LECTURA para ver este módulo --}}
        {{-- =============================================== --}}
        @if(Auth::user()->canAccess('read', $MODULO_VEHICULOS))
        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
            @include('partials.access_card', [
                'route' => route('vehiculos.index'),
                'icon' => 'fa-truck',
                'title' => 'Vehículos',
                'color' => 'bg-info',
                'target' => '_blank',
                'bg_opacity' => 'rgba(23, 162, 184, 0.15)'
            ])
        </div>
        @endif
        
        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
            @include('partials.access_card', [
                'route' => route('mantenimiento.planificacion.index'),
                'icon' => 'fa-calendar',
                'title' => 'Planificacion Mantenimiento',
                'color' => 'bg-warning',
                'target' => '_blank',
                'bg_opacity' => 'rgba(0, 123, 255, 0.15)'
            ])
        </div>
        
        {{-- =============================================== --}}
        {{-- TARJETA DE ÓRDENES DE MANTENIMIENTO (ID 2) --}}
        {{-- =============================================== --}}
        @if(Auth::user()->canAccess('read', $MODULO_ORDENES))
        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
            @include('partials.access_card', [
                'route' => route('ordenes.index'),
                'icon' => 'fa-screwdriver-wrench',
                'title' => 'Mantenimiento',
                'color' => 'bg-warning',
                'target' => '_blank',
                'bg_opacity' => 'rgba(255, 193, 7, 0.15)'
            ])
        </div>
        @endif
        
        {{-- =============================================== --}}
        {{-- TARJETA DE INVENTARIO / ALMACÉN (ID 30) --}}
        {{-- =============================================== --}}
        @if(Auth::user()->canAccess('read', $MODULO_INVENTARIO))
        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
            @include('partials.access_card', [
                'route' => route('inventario.index'),
                'icon' => 'fa-box-open',
                'title' => 'Inventario',
                'color' => 'bg-success',
                'target' => '_blank',
                'bg_opacity' => 'rgba(40, 167, 69, 0.15)'
            ])
        </div>
        @endif

        {{-- =============================================== --}}
        {{-- TARJETA DE COMBUSTIBLE (ID 4) --}}
        {{-- =============================================== --}}
        @if(Auth::user()->canAccess('read', $MODULO_COMBUSTIBLE))
        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
            @include('partials.access_card', [
                'route' => route('combustible.index'),
                'icon' => 'fa-gas-pump',
                'title' => 'Combustible',
                'target' => '_blank',
                'color' => 'bg-secondary',
                'bg_opacity' => 'rgba(108, 117, 125, 0.15)'
            ])
        </div>
        @endif
        
{{-- =============================================== --}}
        {{-- TARJETA DE inspecciones (ID 42) --}}
        {{-- =============================================== --}}
        @if(Auth::user()->canAccess('create', $MODULO_CHECKLIST))
        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
            @include('partials.access_card', [
                'route' => route('inspeccion.index'),
                'icon' => 'fa-list',
                'title' => 'Checklist',
                'color' => 'bg-primary',
                'target' => '_blank',
                'bg_opacity' => 'rgba(0, 123, 255, 0.15)'
            ])
        </div>
        @endif

        {{-- =============================================== --}}
        {{-- TARJETA DE DESPACHOS / LOGÍSTICA (ID 42) --}}
        {{-- =============================================== --}}
        @if(Auth::user()->canAccess('create', $MODULO_DESPACHOS))
        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
            @include('partials.access_card', [
                'route' => route('combustible.despacho'),
                'icon' => 'fa-truck-fast',
                'title' => 'Despachos',
                'color' => 'bg-primary',
                'target' => '_blank',
                'bg_opacity' => 'rgba(0, 123, 255, 0.15)'
            ])
        </div>
        @endif

        @if(Auth::user()->canAccess('create', $MODULO_CLIENTES))
        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
            @include('partials.access_card', [
                'route' => route('captacion.index'),
                'icon' => 'fa-address-book',
                'title' => 'Clientes',
                'color' => 'bg-primary',
                'target' => '_blank',
                'bg_opacity' => 'rgba(0, 123, 255, 0.15)'
            ])
        </div>
        @endif
        
{{-- TARJETA DE reportes (ID 42) --}}
        {{-- =============================================== --}}
        @if(Auth::user()->canAccess('create', $MODULO_REPORTES))
        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
            @include('partials.access_card', [
                'route' => route('reportes.index'),
                'icon' => 'fa-list',
                'title' => 'Reportes',
                'color' => 'bg-primary',
                'target' => '_blank',
                'bg_opacity' => 'rgba(0, 123, 255, 0.15)'
            ])
        </div>
        @endif

        {{-- TARJETA DE viajes (ID 8) --}}
        {{-- =============================================== --}}
        @if(Auth::user()->canAccess('create', $MODULO_VIAJES))
        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
            @include('partials.access_card', [
                'route' => route('viajes.index'),
                'icon' => 'fa-route',
                'title' => 'Cargas / Despachos',
                'color' => 'bg-primary',
                'target' => '_blank',
                'bg_opacity' => 'rgba(0, 123, 255, 0.15)'
            ])
        </div>
        @endif

        {{-- =============================================== --}}
        {{-- TARJETA DE ADMINISTRACIÓN DE USUARIOS (ID 51) --}}
        {{-- =============================================== --}}
        @if(Auth::user()->canAccess('read', $MODULO_USUARIOS))
        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
            @include('partials.access_card', [
                'route' => route('usuarios.index'),
                'icon' => 'fa-users-gear',
                'title' => 'Usuarios',
                'color' => 'bg-danger',
                'target' => '_blank',
                'bg_opacity' => 'rgba(220, 53, 69, 0.15)'
            ])
        </div>
        @endif

        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
            @include('partials.access_card', [
                'route' => route('viajes.calendario'),
                'icon' => 'fa-calendar',
                'title' => 'Planificacion Combustible',
                'color' => 'bg-warning',
                'target' => '_blank',
                'bg_opacity' => 'rgba(0, 123, 255, 0.15)'
            ])
        </div>

        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
            @include('partials.access_card', [
                'route' => route('choferes.index'),
                'icon' => 'fa-user',
                'title' => 'Choferes',
                'color' => 'bg-warning',
                'target' => '_blank',
                'bg_opacity' => 'rgba(0, 123, 255, 0.15)'
            ])
        </div>
        {{-- =============================================== --}}
        {{-- TARJETA DE CONFIGURACIÓN GENERAL (ID 5) --}}
        {{-- =============================================== --}}
        {{-- @if(Auth::user()->canAccess('read', $MODULO_ADMINISTRAR))
        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
            @include('partials.access_card', [
                'route' => route('admin.settings'),
                'icon' => 'fa-gear',
                'title' => 'Configuración',
                'color' => 'bg-dark',
                'bg_opacity' => 'rgba(33, 37, 41, 0.15)'
            ])
        </div>
        @endif
         --}}
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
                                    <td> @php
                                        // Verifica si la variable existe y si no está vacía
                                        if (isset($orden->created_at) && !empty($orden->created_at)) {
                                            // Convierte la cadena de fecha a una marca de tiempo y luego la formatea
                                            $fecha_formateada = date('Y-m-d', strtotime($orden->created_at));
                                            echo $fecha_formateada;
                                        } else {
                                            // Si la fecha no existe, muestra 'N/A' o algún otro valor por defecto
                                            echo 'N/A';
                                        }
                                    @endphp
                                    </td>
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
