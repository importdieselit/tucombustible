@extends('layouts.app')
@section('title', 'TuCombustible - '.Auth::user()->name)

@push('styles')
<style>
    :root {
        --bg-light: #f4f6f8;
        --bg-card: #ffffff;
        --text-dark: #333333;
        --text-muted: #6c757d;
        --primary-color: #3b82f6;
        --primary-dark: #2563eb;
        --secondary-color: #10b981;
        --secondary-dark: #059669;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        background-color: var(--bg-light);
        color: var(--text-dark);
    }

    .card {
        z-index: 1;
        background-color: var(--bg-card);
        border: none;
        border-radius: 1rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
    }

    .btn-primary-custom { background-color: var(--primary-color); border-color: var(--primary-color); }
    .btn-secondary-custom { background-color: var(--secondary-color); border-color: var(--secondary-color); }
    .progress-bar-custom { background-color: var(--primary-color); }
    .progress-bar-danger { background-color: #ef4444; }

    .main-hero-card {
        background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%);
        color: var(--text-dark);
    }

    .hidden { display: none !important; }

    .sucursal-card-container {
        cursor: pointer;
        transition: transform 0.2s ease-in-out;
    }

    .modal-backdrop.show { opacity: 0.1; }
</style>
@endpush

@section('content')
    <div class="container-fluid">
        <h1 class="display-8 fw-bold mb-5 text-center text-black">{{ ucwords(Auth::user()->name)}}</h1>

        <div class="card p-4 mb-5 main-hero-card">
            @php
                /**
                 * CORRECCIÓN DE SEGURIDAD:
                 * Se valida la existencia de $cliente para evitar errores de objeto nulo.
                 */
                $isParent = isset($cliente) && $cliente->parent == 0;
                $currentUserRole = $isParent ? 'principal' : 'sucursal';
                $currentUserBranchId = Auth::user()->cliente_id;

                // Asegurar que las colecciones existan aunque vengan vacías del controlador
                $pedidos = $pedidosDashboard ?? [];
                $solicitudes = $solicitudes ?? [['id' => 's1', 'estado' => 'Pendiente']];
                $notificaciones = $notificaciones ?? [['id' => 'n1', 'leido' => false]];
                $sucursales = $sucursales ?? [];

                $totalCapacity = $cliente->cupo ?? 0;
                $totalCurrent = $cliente->disponible ?? 0;
                $percentage = $totalCapacity > 0 ? ($totalCurrent / $totalCapacity) * 100 : 0;
                $isAlert = $totalCurrent <= ($totalCapacity * 0.1);

                // Datos para el gráfico de Highcharts
                $chartData = [];
                if ($isParent) {
                    foreach ($sucursales as $sucursal) {
                        $chartData[] = [
                            'name' => $sucursal['nombre'] ?? 'S/N',
                            'cupo' => $sucursal['cupo'] ?? 0,
                            'id'   => $sucursal['id'] ?? 0,
                            'disponible' => $sucursal['disponible'] ?? 0,
                            'consumido'  => ($sucursal['cupo'] ?? 0) - ($sucursal['disponible'] ?? 0)
                        ];
                    }
                } else {
                     // CORRECCIÓN: Uso de operador null-safe para evitar "offset on null"
                     $sucursalActual = collect($sucursales)->firstWhere('id', $currentUserBranchId);
                     $chartData[] = [
                        'name'       => $sucursalActual['nombre'] ?? 'Mi Sucursal',
                        'cupo'       => $sucursalActual['cupo'] ?? ($cliente->cupo ?? 0),
                        'id'         => $sucursalActual['id'] ?? $currentUserBranchId,
                        'disponible' => $sucursalActual['disponible'] ?? ($cliente->disponible ?? 0),
                        'consumido'  => ($sucursalActual['cupo'] ?? 0) - ($sucursalActual['disponible'] ?? 0)
                    ];
                }
            @endphp

            <div class="d-flex align-items-center">
                <i class="fas fa-money-bill-wave text-info me-3" style="font-size: 3rem;"></i>
                <div>
                    <h2 class="h4 fw-bold text-black mb-0">Estado de Cupo {{ $isParent ? 'General' : 'Actual' }}</h2>
                    <p class="text-sm text-muted mb-0">{{ $isParent ? 'Resumen de todas las sucursales' : 'Detalle de sucursal' }}</p>
                </div>
            </div>

            <div class="mt-4">
                <p class="fw-bold mb-2">Total Disponible / Cupo</p>
                <div class="d-flex align-items-center mb-2">
                    <h3 class="fw-bold mb-0 me-2">{{ number_format($totalCurrent, 2) }} L</h3>
                    <p class="text-muted mb-0">/ {{ number_format($totalCapacity, 2) }} L</p>
                </div>
                <div class="progress" style="height: 10px;">
                    <div class="progress-bar {{ $isAlert ? 'progress-bar-danger' : 'progress-bar-custom' }}"
                         role="progressbar"
                         style="width: {{ $percentage }}%;"
                         aria-valuenow="{{ $percentage }}"
                         aria-valuemin="0"
                         aria-valuemax="100"></div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100 p-4 text-center" onclick="showDetails('pedidos-details')" style="cursor:pointer">
                    <i class="fas fa-truck-ramp-box mb-2 text-warning fs-2"></i>
                    <h5 class="fw-bold mb-1">Pedidos</h5>
                    <p class="text-muted mb-0">{{ count(array_filter($pedidos, fn($p) => isset($p['estado']) && ($p['estado'] == 'En proceso' || $p['estado'] == 'Pendiente'))) }} en proceso</p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100 p-4 text-center" onclick="showDetails('solicitudes-details')" style="cursor:pointer">
                    <i class="fas fa-clipboard-list mb-2 text-primary fs-2"></i>
                    <h5 class="fw-bold mb-1">Solicitudes</h5>
                    <p class="text-muted mb-0">{{ count(array_filter($solicitudes, fn($s) => $s['estado'] == 'Pendiente')) }} pendientes</p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100 p-4 text-center" onclick="showDetails('notificaciones-details')" style="cursor:pointer">
                    <i class="fas fa-bell mb-2 text-danger fs-2"></i>
                    <h5 class="fw-bold mb-1">Notificaciones</h5>
                    <p class="text-muted mb-0">{{ count(array_filter($notificaciones, fn($n) => !$n['leido'])) }} nuevas</p>
                </div>
            </div>
            @if ($isParent)
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card h-100 p-4 text-center sucursal-card-container" id="sucursales-card">
                        <i class="fas fa-sitemap mb-2 text-success fs-2"></i>
                        <h5 class="fw-bold mb-1">Ver Sucursales</h5>
                        <p class="text-muted mb-0">{{ count($sucursales) }} activas</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="text-center my-5">
            <button class="btn btn-primary-custom btn-lg rounded-pill px-5 py-3 shadow-lg fs-5" data-bs-toggle="modal" data-bs-target="#hacerPedidoModal">
                <i class="fas fa-plus-circle me-2"></i> Hacer Pedido
            </button>
        </div>

        <div id="content-sections">
            @if ($isParent)
                <div id="sucursales-list-container" class="hidden">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold mb-0">Mis Sucursales</h4>
                        <button class="btn btn-outline-secondary" onclick="location.reload()">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </button>
                    </div>
                    <div class="row g-4">
                        @foreach ($sucursales as $sucursal)
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card h-100 p-4">
                                <h5 class="fw-bold mb-1">{{ $sucursal['nombre'] }}</h5>
                                <div class="mt-3">
                                    <p class="fw-bold mb-1">Disponible: <span class="text-success">{{ number_format($sucursal['disponible'], 0) }} L</span></p>
                                    <p class="fw-bold mb-0">Cupo: <span class="text-muted">{{ number_format($sucursal['cupo'], 0) }} L</span></p>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div id="dashboard-main-view">
                    <div class="card p-4 mb-4">
                        <h4 class="fw-bold mb-3">Gráfico de Consumo por Sucursal</h4>
                        <div id="chart-container" style="width:100%; height:400px;"></div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="hacerPedidoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Realizar Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="hacerPedidoForm">
                        @csrf
                        @if ($isParent)
                            <div class="mb-3">
                                <label class="form-label">Seleccionar Sucursal</label>
                                <select class="form-select" name="cliente_id" required>
                                    @foreach ($sucursales as $sucursal)
                                        <option value="{{ $sucursal['id'] }}">{{ $sucursal['nombre'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                             <input type="hidden" name="cliente_id" value="{{ $currentUserBranchId }}">
                             <p class="alert alert-info">Pedido para: <strong>{{ Auth::user()->name }}</strong></p>
                        @endif
                        <div class="mb-3">
                            <label class="form-label">Cantidad (Litros)</label>
                            <input type="number" class="form-control" name="cantidad_solicitada" required min="1">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary btn-primary-custom">Enviar Pedido</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://code.highcharts.com/highcharts.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const chartData = {!! json_encode($chartData) !!};
        const currentUserRole = '{{ $currentUserRole }}';

        if (currentUserRole === 'principal' && chartData.length > 0) {
            Highcharts.chart('chart-container', {
                chart: { type: 'column' },
                title: { text: '' },
                xAxis: { categories: chartData.map(d => d.name) },
                yAxis: { title: { text: 'Litros' }, stacking: 'normal' },
                plotOptions: { column: { stacking: 'normal', dataLabels: { enabled: true } } },
                series: [{
                    name: 'Consumido',
                    data: chartData.map(d => d.consumido),
                    color: '#ef4444'
                }, {
                    name: 'Disponible',
                    data: chartData.map(d => d.disponible),
                    color: '#10b981'
                }]
            });
        }

        // Lógica simple para mostrar sucursales
        const sucursalesCard = document.getElementById('sucursales-card');
        if(sucursalesCard) {
            sucursalesCard.addEventListener('click', function() {
                document.getElementById('dashboard-main-view').classList.add('hidden');
                document.getElementById('sucursales-list-container').classList.remove('hidden');
            });
        }
    });

    function showDetails(sectionId) {
        alert('Funcionalidad de detalles para: ' + sectionId);
    }
</script>
@endpush