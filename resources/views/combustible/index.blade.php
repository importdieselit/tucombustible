@extends('layouts.app')
@section('title', 'TuCombustible - Dashboard de Operaciones')

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
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
            background-color: var(--bg-light);
            color: var(--text-dark);
        }

        .card {
            background-color: var(--bg-card);
            border: none;
            border-radius: 1rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1), 0 2px 4px rgba(0, 0, 0, 0.06);
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15), 0 4px 8px rgba(0, 0, 0, 0.08);
        }

        .btn-primary-custom {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary-custom:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .btn-secondary-custom {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-secondary-custom:hover {
            background-color: var(--secondary-dark);
            border-color: var(--secondary-dark);
        }

        .progress-bar-custom {
            background-color: var(--primary-color);
        }

        .progress-bar-danger {
            background-color: #ef4444;
        }

        .stat-card-icon {
            font-size: 2rem;
            color: #495057;
        }

        .main-hero-card {
            background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%);
            color: var(--text-dark);
        }

        .main-hero-card .text-muted {
            color: #6c757d !important;
        }
        
        .card-hover:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            transform: translateY(-5px);
            transition: all 0.3s ease-in-out;
        }
        .highcharts-yaxis-grid .highcharts-grid-line {
            stroke-dasharray: 2px;
        }

        .highcharts-yaxis-labels text {
            fill: #6c757d;
        }
        .hidden {
            display: none !important;
        }
        .sucursal-card-container {
            cursor: pointer;
            transition: transform 0.2s ease-in-out;
        }
        .sucursal-card-container:hover {
            transform: translateY(-5px);
        }
        .highcharts-color-0 {
            fill: #3b82f6;
            stroke: #3b82f6;
        }
        .highcharts-color-1 {
            fill: #ef4444;
            stroke: #ef4444;
        }
        /* Para ocultar el sidebar en la vista de operaciones, asumiendo un layout blade */
        @media (min-width: 992px) {
            body.sidebar-cliente .sidebar {
                display: none !important;
            }
        }

    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <h1 class="display-8 fw-bold mb-5 text-center text-black">Dashboard de Operaciones</h1>
        <p class="text-center text-sm mb-5" id="user-role-info">  </p>

        <!-- Sección de Visualización de Cupo y Disponibilidad -->
        <div class="card p-4 mb-5 main-hero-card">
            @php
                // --- SIMULACIÓN DE DATOS DESDE EL CONTROLADOR ---
                // Estos datos deben ser proporcionados por el controlador de Laravel.
                // Se asume la siguiente estructura para los datos de los clientes y sus sucursales.
                // $clientes es un array de objetos o un Collection de Eloquent.
                // La lógica del drilldown se basa en el ID y el 'parent'.

                // $clientes = collect([
                //     (object)['id' => 1, 'nombre' => 'Cliente A', 'parent' => 0, 'cupo' => 50000, 'disponible' => 25000, 'contacto' => 'Juan Perez', 'direccion' => 'Calle Falsa 123', 'telefono' => '555-1234'],
                //     (object)['id' => 2, 'nombre' => 'Cliente B', 'parent' => 0, 'cupo' => 75000, 'disponible' => 60000, 'contacto' => 'Maria Lopez', 'direccion' => 'Avenida Siempre Viva 742', 'telefono' => '555-5678'],
                //     (object)['id' => 3, 'nombre' => 'Sucursal A1', 'parent' => 1, 'cupo' => 30000, 'disponible' => 15000, 'contacto' => 'Pedro Gonzalez', 'direccion' => 'Las Acacias 101', 'telefono' => '555-0001'],
                //     (object)['id' => 4, 'nombre' => 'Sucursal A2', 'parent' => 1, 'cupo' => 20000, 'disponible' => 10000, 'contacto' => 'Ana Ramirez', 'direccion' => 'Los Robles 202', 'telefono' => '555-0002'],
                //     (object)['id' => 5, 'nombre' => 'Sucursal B1', 'parent' => 2, 'cupo' => 50000, 'disponible' => 40000, 'contacto' => 'Luis Hernandez', 'direccion' => 'Calle Principal 303', 'telefono' => '555-0003'],
                //     (object)['id' => 6, 'nombre' => 'Sucursal B2', 'parent' => 2, 'cupo' => 25000, 'disponible' => 20000, 'contacto' => 'Sofia Gomez', 'direccion' => 'Camino Real 404', 'telefono' => '555-0004'],
                // ]);
                $clientesPrincipales = $clientes->where('parent', 0);
                $sucursales = $clientes->where('parent', '!=', 0);

                // Prepara los datos para la gráfica de Highcharts
                $chartData = [];
                $drilldownSeries = [];

                foreach ($clientesPrincipales as $cliente) {
                    $consumido = $cliente->cupo - $cliente->disponible;
                    $chartData[] = [
                        'name' => $cliente->nombre,
                        'y' => $consumido,
                        'disponible' => $cliente->disponible,
                        'cupo' => $cliente->cupo,
                        'drilldown' => 'sucursales-'. $cliente->id
                    ];
                    
                    $sucursalesCliente = $sucursales->where('parent', $cliente->id);
                    $sucursalesData = [];
                    foreach ($sucursalesCliente as $sucursal) {
                         $consumidoSucursal = $sucursal->cupo - $sucursal->disponible;
                         $sucursalesData[] = [
                            'name' => $sucursal->nombre,
                            'y' => $consumidoSucursal,
                            'disponible' => $sucursal->disponible,
                            'cupo' => $sucursal->cupo
                         ];
                    }

                    $drilldownSeries[] = [
                        'id' => 'sucursales-'. $cliente->id,
                        'name' => 'Sucursales de '.$cliente->nombre,
                        'data' => $sucursalesData,
                    ];
                }

                $totalCapacity = $clientesPrincipales->sum('cupo');
                $totalCurrent = $clientesPrincipales->sum('disponible');
                $percentage = $totalCapacity > 0 ? ($totalCurrent / $totalCapacity) * 100 : 0;
                $isAlert = $totalCurrent <= ($totalCapacity * 0.1);

                //$pedidos = $pedidosDashboard ?? [];
                $solicitudes = [['id' => 's1', 'estado' => 'Pendiente'], ['id' => 's2', 'estado' => 'Aprobada']];
                $notificaciones = [['id' => 'n1', 'leido' => false], ['id' => 'n2', 'leido' => true]];

            @endphp
            <div class="d-flex align-items-center">
                <i class="fas fa-money-bill-wave text-info me-3" style="font-size: 3rem;"></i>
                <div>
                    <h2 class="h4 fw-bold text-black mb-0" id="main-title">
                        Estado de Cupo General por Clientes
                    </h2>
                    <p class="text-sm text-muted mb-0" id="main-subtitle">
                        Resumen del cupo disponible de todos los clientes principales.
                    </p>
                </div>
            </div>
            <div class="mt-4">
                <p class="fw-bold mb-2">Total Disponible / Cupo total SIAVCOM</p>
                <div class="d-flex align-items-center mb-2">
                    <h3 class="fw-bold mb-0 me-2">{{ number_format($totalCurrent, 2) }} L</h3>
                    <p class="text-muted mb-0">/ {{ $totalCapacity }} L</p>
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

            <div class="printableArea" style="width: fit-content;">
            <div class="d-flex align-items-center mt-5">
                <i class="fas fa-money-bill-wave text-info me-3" style="font-size: 3rem;"></i>
                <div>
                    <h2 class="h4 fw-bold text-black mb-0" id="main-title">
                        Estado General de los Tanques
                    </h2>
                    <p class="text-sm text-muted mb-0" id="main-subtitle">
                        Resumen del cupo disponible para venta.
                    </p>
                </div>
            </div>
            <div class="mt-4">
                <p class="fw-bold mb-2">Total Disponible / Capacidad total</p>
                <div class="d-flex align-items-center mb-2">
                    <h3 class="fw-bold mb-0 me-2">{{ number_format($totalCombustible, 2) }} L</h3>
                    <p class="text-muted mb-0">/ {{ $capacidadTotal }} L</p>
                </div>
                <div class="progress" style="height: 10px; display:none">
                    <div class="progress-bar {{ $isAlert ? 'progress-bar-danger' : 'progress-bar-custom' }}"
                         role="progressbar"
                         style="width: {{ $percentage }}%;"
                         aria-valuenow="{{ $percentage }}"
                         aria-valuemin="0"
                         aria-valuemax="100"></div>
                </div>
            </div>
            <div class="mt-4">
                <p class="fw-bold mb-2">Total Disponible 00 / Capacidad total 00</p>
                <div class="d-flex align-items-center mb-2">
                    <h3 class="fw-bold mb-0 me-2">{{ number_format($tanque00->nivel_actual_litros, 2) }} L</h3>
                    <p class="text-muted mb-0">/ {{ $tanque00->capacidad_litros }} L</p>
                </div>
                <div class="progress" style="height: 10px; display:none" >
                    <div class="progress-bar {{ $isAlert ? 'progress-bar-danger' : 'progress-bar-custom' }}"
                         role="progressbar"
                         style="width: {{ $percentage }}%;"
                         aria-valuenow="{{ $percentage }}"
                         aria-valuemin="0"
                         aria-valuemax="100"></div>
                </div>
            </div>
            <div class="mt-4">
                <p class="fw-bold mb-2">Resguardo</p>
                <div class="d-flex align-items-center mb-2">
                    <h3 class="fw-bold mb-0 me-2">{{ number_format($resguardo, 2) }} L</h3>
                </div>
            </div>
            </div>
            
        </div>

        <!-- Secciones de Acciones y Vistas -->
        <div class="row g-4 mb-5">



            <div class="col-12 col-md-6 col-lg-3">
                <a href="{{route('combustible.list')}}" class="card-link" >
                    <div class="card h-100 p-4 d-flex flex-column justify-content-center text-center">
                        <i class="fas fa-truck-ramp-box stat-card-icon mb-2 text-warning"></i>
                        <h4 class="fw-bold mb-1">Compras</h4>
                        <h3>{{ $totales['entradas'] }} Ltrs</h3>
                        <h5>desde {{date('d/m/Y',strtotime($totales['periodo_inicio']))}}</h5>
                    </div>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <a href="{{route('combustible.list')}}" class="card-link" >
                    <div class="card h-100 p-4 d-flex flex-column justify-content-center text-center">
                        <i class="fas fa-truck-ramp-box stat-card-icon mb-2 text-warning"></i>
                        <h4 class="fw-bold mb-1">Ventas</h4>
                        <h3>{{ $totales['salidas'] }} Ltrs</h3>
                        <h5>desde {{date('d/m/Y',strtotime($totales['periodo_inicio']))}}</h5>
                    </div>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <a href="{{route('combustible.list')}}" class="card-link" >
                    <div class="card h-100 p-4 d-flex flex-column justify-content-center text-center">
                        <i class="fas fa-list stat-card-icon mb-2 text-black"></i>
                        <h3 class="fw-bold mb-1">Ver Movimientos</h3>
                    </div>
                </a>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <a href="{{route('combustible.pedidos')}}" class="card-link" >
                    <div class="card h-100 p-4 d-flex flex-column justify-content-center text-center">
                        <i class="fas fa-truck-ramp-box stat-card-icon mb-2 text-warning"></i>
                        <h5 class="fw-bold mb-1">Pedidos</h5>
                        <p class="text-muted mb-0">{{ count(array_filter($pedidos, fn($p) => $p['estado'] == 'En proceso' || $p['estado'] == 'Pendiente')) }} en proceso</p>
                    </div>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <a href="#" class="card-link" onclick="showDetails('solicitudes-details', event)">
                    <div class="card h-100 p-4 d-flex flex-column justify-content-center text-center">
                        <i class="fas fa-clipboard-list stat-card-icon mb-2 text-primary"></i>
                        <h5 class="fw-bold mb-1">Solicitudes</h5>
                        <p class="text-muted mb-0">{{ count(array_filter($solicitudes, fn($s) => $s['estado'] == 'Pendiente')) }} pendientes</p>
                   </div>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <a href="#" class="card-link" onclick="showDetails('notificaciones-details', event)">
                    <div class="card h-100 p-4 d-flex flex-column justify-content-center text-center">
                        <i class="fas fa-bell stat-card-icon mb-2 text-danger"></i>
                        <h5 class="fw-bold mb-1">Notificaciones</h5>
                        <p class="text-muted mb-0">{{ count(array_filter($notificaciones, fn($n) => !$n['leido'])) }} nuevas</p>
                    </div>
                </a>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100 p-4 d-flex flex-column justify-content-center text-center sucursal-card-container" id="sucursales-card">
                    <i class="fas fa-sitemap stat-card-icon mb-2 text-success"></i>
                    <h5 class="fw-bold mb-1">Ver Clientes</h5>
                    <p class="text-muted mb-0">{{ count($clientesPrincipales) }} activos</p>
                </div>
            </div>
        </div>
        
        <!-- Botón para Hacer Pedido -->
        <div class="text-center my-5">
            {{-- <button class="btn btn-primary-custom btn-lg rounded-pill px-4 py-2 shadow-lg fs-5" data-bs-toggle="modal" data-bs-target="#hacerPedidoModal">
                <i class="fas fa-plus-circle me-2"></i> Hacer Pedido
            </button> --}}
            <a href="{{route('combustible.recarga')}}" class="btn btn-primary-custom btn-lg rounded-pill px-4 py-2 shadow-lg fs-5" id="btn-crear-carga">
                <i class="fa fa-truck me-2"></i> Carga
            </a>
            
            <a href="{{route('combustible.despacho')}}" class="btn btn-primary-custom btn-lg rounded-pill px-4 py-2 shadow-lg fs-5" id="btn-crear-despacho">
                <i class="fa fa-truck me-2"></i> Nuevo Despacho
            </a>


            <button class="btn btn-warning btn-lg rounded-pill px-4 py-2 shadow-lg fs-5" id="btn-inspeccion-salida">
                <i class="fa fa-clipboard-check me-2"></i> Checkout Vehículo
            </button>


        </div>

        <!-- Secciones de Contenido Dinámico -->
        <div id="content-sections">
            <div id="clientes-list-container" >
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0">Clientes Principales</h4>
                    <button class="btn btn-outline-secondary" id="back-to-dashboard-btn">
                        <i class="fas fa-arrow-left me-1"></i> Volver al Dashboard
                    </button>
                </div>
               <div class="row g-4" id="clientes-cards">
                    @foreach ($clientesPrincipales as $cliente)
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card h-100 p-4 sucursal-card-container" data-sucursal-id="{{ $cliente->id }}">
                                <h5 class="fw-bold mb-1">{{ $cliente->nombre }}</h5>
                                <p class="text-muted mb-0">Contacto: {{ $cliente->contacto }}</p>
                                <div class="mt-3">
                                    <p class="fw-bold mb-1">Disponible: <span class="text-success">{{ number_format($cliente->disponible, 0) }} L</span></p>
                                    <p class="fw-bold mb-0">Cupo: <span class="text-muted">{{ number_format($cliente->cupo, 0) }} L</span></p>
                                </div>
                                
                                @php
                                    // Calcula el porcentaje disponible
                                    $porcentajeDisponible = ($cliente->cupo > 0) ? ($cliente->disponible / $cliente->cupo) * 100 : 0;
                                    $porcentajeConsumido = 100 - $porcentajeDisponible;
                                @endphp

                                <div class="progress mt-3" style="height: 25px;">
                                    <div class="progress-bar" role="progressbar" style="width: {{  $porcentajeDisponible }}%; background-color: {{  $porcentajeDisponible > 50 ? '#28a745' : ( $porcentajeDisponible > 15 ? '#ffc107' : '#dc3545') }};" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div id="sucursal-details-container" class="hidden">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bold mb-0" id="sucursal-details-title"></h4>
                    <button class="btn btn-outline-secondary" id="back-to-list-btn">
                        <i class="fas fa-arrow-left me-1"></i> Ver todos los Clientes
                    </button>
                </div>
                
                <div class="card p-4 mb-4">
                    <h5 class="fw-bold mb-3">Información General</h5>
                    <ul class="list-unstyled">
                        <li><strong>Dirección:</strong> <span id="details-direccion"></span></li>
                        <li><strong>Persona de Contacto:</strong> <span id="details-contacto"></span></li>
                        <li><strong>Teléfono:</strong> <span id="details-telefono"></span></li>
                    </ul>
                </div>
                
                <div class="card p-4 mb-4">
                    <h5 class="fw-bold mb-3">Estado de Cupo</h5>
                    <p class="fw-bold mb-2">Disponible / Cupo</p>
                    <div class="d-flex align-items-center mb-2">
                        <h3 class="fw-bold mb-0 me-2" id="details-disponible"></h3>
                        <p class="text-muted mb-0" id="details-cupo"></p>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar" role="progressbar" id="details-progress-bar"></div>
                    </div>
                </div>

                <div class="card p-4 mb-4" class="hidden">
                    <h5 class="fw-bold mb-3">Histórico de Consumo Semanal</h5>
                    <div id="consumo-chart-container"></div>
                </div>

                <div class="d-flex justify-content-center gap-2 mt-4">
                    <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#hacerPedidoModal" id="btn-details-pedido">
                        <i class="fas fa-plus-circle me-1"></i> Hacer Pedido
                    </button>
                    <button class="btn btn-secondary-custom" data-bs-toggle="modal" data-bs-target="#editarSucursalModal" id="btn-details-edicion">
                        <i class="fas fa-edit me-1"></i> Editar Datos
                    </button>
                    <button class="btn btn-info text-white" id="btn-send-message">
                        <i class="fas fa-envelope me-1"></i> Enviar Mensaje
                    </button>
                </div>
                 <div class="d-flex justify-content-center gap-2 mt-4">
                    <button class="btn btn-warning text-white" id="btn-create-user">
                        <i class="fas fa-user-plus me-1"></i> Crear Usuario
                    </button>
                    <button class="btn btn-warning text-white" id="btn-assign-user">
                        <i class="fas fa-user-check me-1"></i> Asignar Usuario
                    </button>
                </div>

            </div>

            <div id="dashboard-main-view" class="hidden">
                <div class="card p-4 mb-4">
                    <h4 class="fw-bold mb-3">Consumo por Clientes</h4>
                    <div id="chart-container" style="height: {{ 40 * count($chartData) + 150 }}px;"></div>
                </div>
            </div>
        </div>
<!-- Sección de Detalle de Pedidos -->
<div id="pedidos-details" style="display: none;" tabindex="-1">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 fw-bold mb-0">Detalle de Pedidos</h2>
        <button class="btn btn-outline-secondary" onclick="showDashboard()">
            <i class="fas fa-arrow-left me-2"></i> Volver al Dashboard
        </button>
    </div>
    <div class="card p-4">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th scope="col"># Pedido</th>
                        <th scope="col">Cliente</th>
                        <th scope="col">Cantidad (L)</th>
                        <th scope="col">Observacion</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Fecha de Creación</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="pedidos-table-body">
                    <!-- Los datos se inyectarán con JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Sección de Detalle de Solicitudes -->
<div id="solicitudes-details" style="display: none;" tabindex="-1">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 fw-bold mb-0">Detalle de Solicitudes</h2>
        <button class="btn btn-outline-secondary" onclick="showDashboard()">
            <i class="fas fa-arrow-left me-2"></i> Volver al Dashboard
        </button>
    </div>
    <div class="card p-4">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th scope="col"># Solicitud</th>
                        <th scope="col">Cliente</th>
                        <th scope="col">Tipo</th>
                        <th scope="col">Observacion</th>
                        <th scope="col">Estado</th>
                    </tr>
                </thead>
                <tbody id="solicitudes-table-body">
                    <!-- Los datos se inyectarán con JS -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Sección de Detalle de Notificaciones -->
<div id="notificaciones-details" style="display: none;" tabindex="-1">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 fw-bold mb-0">Detalle de Notificaciones</h2>
        <button class="btn btn-outline-secondary" onclick="showDashboard()">
            <i class="fas fa-arrow-left me-2"></i> Volver al Dashboard
        </button>
    </div>
    <div class="card p-4">
        <div class="list-group">
            <!-- Las notificaciones se inyectarán con JS -->
        </div>
    </div>
</div>
    </div>
<div class="row g-4 mb-5">
        @foreach ($tipoDeposito as $tipo )
    <div class="col-lg-6">        
        
        <div class="card shadow-sm p-4 h-100">
            <div class="card-header bg-white">
                <h5 class="card-title m-0">Niveles de Tanques tipo {{ $tipo->producto }}</h5>
            </div>
            <div class="card-body bg-white">
                <ul class="list-group list-group-flush">
                    @php($percentage = 0)
                    @php($total = 0)
                    @php($capacidadTotal = 0) 
                     @foreach($tipo->depositos as $deposito)
                        @php($total += $deposito->nivel_actual_litros)
                        @php($capacidadTotal += $deposito->capacidad_litros)
                        @php($percentage = $deposito->nivel)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div id="deposito-info-{{ $deposito->id }}" data-nivel="{{ $deposito->nivel_actual_litros }}" data-capacidad="{{ $deposito->capacidad_litros }}">
                                <h6 class="m-0">{{ $deposito->serial }} ({{ $deposito->producto }})</h6>
                                <p class="text-muted m-0"><small>Nivel: {{ $percentage }}%</small></p>
                                <p class="text-black m-0 "><small>{{ $deposito->nivel_actual_litros }}/{{ $deposito->capacidad_litros }} Litros</small>  
                                    <i class="rounded fa fa-pencil ajustar-btn" style="cursor: pointer" data-id="{{$deposito->id}}" onclick="openAjusteModal({{$deposito->id}})"></i>
                                    <a href="{{ route('depositos.aforo.show', ['deposito' => $deposito->id]) }}" target="_blank">
                                        <i class="rounded fa fa-table"></i>
                                    </a>
                                </p>    
                            </div>
                            <div class="progress" style="width: 150px; height: 20px;">
                                <div class="progress-bar" 
                                     role="progressbar" 
                                     style="width: {{  $percentage }}%; background-color: {{  $percentage > 50 ? '#28a745' : ( $percentage > 15 ? '#ffc107' : '#dc3545') }};" 
                                     aria-valuenow="{{  $percentage }}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    {{ $deposito->nivel_actual_litros }}L - {{  $percentage }}%
                                </div>
                            </div>
                        </li>
                    @endforeach
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div id="resguardo-info" data-nivel="{{ $resguardo }}" >
                                <h6 class="m-0">Resguardo </h6>
                                <p class="text-black m-0 "><small><span id="reguardo-span">{{ $resguardo }} Litros</span></small>  
                                    <i class="rounded fa fa-pencil ajustar-btn" style="cursor: pointer" data-id="" onclick="openAjusteResguardo()"></i>
                                </p>    
                            </div>
                        </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="m-0">Total </h6>
                                <p>{{$total}}/{{$capacidadTotal }}</p>
                                <p class="text-muted m-0"><small>Nivel: {{$tipo->nivel}}%</small></p>
                            </div>
                            <div class="progress" style="width: 150px; height: 20px;">
                                <div class="progress-bar" 
                                     role="progressbar" 
                                     style="width: {{  $tipo->nivel }}%; background-color: {{  $tipo->nivel > 50 ? '#28a745' : ( $tipo->nivel > 15 ? '#ffc107' : '#dc3545') }};" 
                                     aria-valuenow="{{  $tipo->nivel }}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    {{ $tipo->total }}L / {{  $tipo->nivel }}%
                                </div>
                            </div>
                    </li>
                </ul>
                <button id="sendTelegramButton" class="btn btn-info shadow-sm">
                    <i class="fa fa-telegram me-2"></i> Enviar a Telegram
                </button>
            </div>
        </div>

</div>
        @endforeach
</div>


    <!-- Modal para Ajuste de Nivel -->
    <div class="modal fade" id="ajustarNivelModal" tabindex="-1" aria-labelledby="ajustarNivelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered text-dark">
            <div class="modal-content bg-custom-dark rounded-3 shadow-lg">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title" id="ajustarNivelModalLabel">Ajustar Nivel del Depósito</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="ajustarNivelForm">
                        <input type="hidden" id="deposito-id">
                        <p class="text-sm"><strong>Nivel Actual:</strong> <span id="modal-nivel-actual"></span> / <span id="capacidad-litros"></span> Litros</p>
                        <div class="mb-3">
                            <label for="nuevo_nivel" class="form-label">Nuevo Nivel (Cm)</label>
                            <input type="number" step="0.01" class="form-control " id="nuevo_nivel" name="nuevo_nivel" required>
                        </div>
                        <div class="mb-3">
                            <label for="observacion" class="form-label">Observación</label>
                            <textarea class="form-control " id="observacion" name="observacion" rows="3" required placeholder="Describe el motivo del ajuste."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn-submit-ajuste">Guardar Ajuste</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Ajuste de Nivel -->
    <div class="modal fade" id="ajustarResguardoModal" tabindex="-1" aria-labelledby="ajustarResguardoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered text-dark">
            <div class="modal-content bg-custom-dark rounded-3 shadow-lg">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title" id="ajustarResguardoModalLabel">Ajustar Cantidad de Resguardo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="ajustarReguardoForm">
                        <input type="hidden" id="resguardo">
                        <p class="text-sm"><strong>Cantidad Actual:</strong> <span id="actual-resguardo"></span> Litros</p>
                        <div class="mb-3">
                            <label for="nuevo_resguardo" class="form-label">Nuevo Valor</label>
                            <input type="number" step="0.01" class="form-control " id="nuevo_resguardo" name="nuevo_resguardo" required>
                        </div>
                        <div class="mb-3">
                            <label for="observacion" class="form-label">Observación</label>
                            <textarea class="form-control " id="observacion_resguardo" name="observacion_resguardo" rows="3" required placeholder="Describe el motivo del ajuste."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn-submit-resguardo">Guardar Ajuste</button>
                </div>
            </div>
        </div>
    </div>
    

    <!-- Modal para Hacer Pedido -->
    <div class="modal fade" id="hacerPedidoModal" tabindex="-1" aria-labelledby="hacerPedidoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-card text-dark rounded-3 shadow-lg">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title" id="hacerPedidoModalLabel">Realizar Nuevo Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="hacerPedidoForm">
                        @csrf
                        <div class="mb-3">
                            <label for="sucursalSelect" class="form-label">Seleccionar Cliente/Sucursal</label>
                            <select class="form-select" id="sucursalSelect" name="cliente_id" required>
                                @foreach ($clientes as $cliente)
                                    <option value="{{ $cliente->id }}">{{ $cliente->nombre }} (Disponible: {{ $cliente->disponible }} L)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="cantidadPedido" class="form-label">Cantidad (Litros)</label>
                            <input type="number" step="1" class="form-control" id="cantidadPedido" name="cantidad_solicitada" required min="1">
                        </div>
                        <div class="mb-3">
                            <label for="observacionPedido" class="form-label">Observación (opcional)</label>
                            <textarea class="form-control" id="observacionPedido" name="observaciones" rows="3" placeholder="Detalles adicionales para el pedido."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-primary-custom" id="btn-submit-pedido">Enviar Pedido</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Sucursal -->
    <div class="modal fade" id="editarSucursalModal" tabindex="-1" aria-labelledby="editarSucursalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-card text-dark rounded-3 shadow-lg">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title" id="editarSucursalModalLabel">Editar Cliente/Sucursal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editarSucursalForm">
                        @csrf
                        <input type="hidden" id="editSucursalId" name="id">
                        <div class="mb-3">
                            <label for="editNombreSucursal" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="editNombreSucursal" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="editDireccionSucursal" class="form-label">Dirección</label>
                            <input type="text" class="form-control" id="editDireccionSucursal" name="direccion">
                        </div>
                        <div class="mb-3">
                            <label for="editContactoSucursal" class="form-label">Persona de Contacto</label>
                            <input type="text" class="form-control" id="editContactoSucursal" name="contacto">
                        </div>
                        <div class="mb-3">
                            <label for="editTelefonoSucursal" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="editTelefonoSucursal" name="telefono">
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-primary-custom" id="btn-submit-edicion">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>
    
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/drilldown.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    {{-- <script src="{{ asset('js/dashboard-operaciones.js') }}"></script> --}}

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" defer></script>
    <script>
        console.log('Dashboard Operaciones cargado');
        console.log('Pedidos:', @json($pedidos));
        console.log('Solicitudes:', @json($solicitudes));    
        console.log('Notificaciones:', @json($notificaciones));
        const pedidos = @json($pedidos);
// const solicitudes = @json($solicitudes);
// const notificaciones = @json($notificaciones);

//    const pedidos = [
//             { id: 1, cliente: 'Empresa Alfa', cantidad: 5000, estado: 'Pendiente', fecha: '2024-10-26' },
//             { id: 2, cliente: 'Transportes Delta', cantidad: 800, estado: 'Pendiente', fecha: '2024-10-25' },
//             { id: 3, cliente: 'Distribuidora Beta', cantidad: 1000, estado: 'En Ruta', fecha: '2024-10-25' },
//             { id: 4, cliente: 'Empresa Alfa', cantidad: 2000, estado: 'Pendiente', fecha: '2024-10-24' },
//         ];

        const solicitudes = [
            { id: 1, cliente: 'Empresa Alfa', tipo: 'Mantenimiento', descripcion: 'Revisión de bomba de sucursal centro.', estado: 'En Proceso' },
            { id: 2, cliente: 'Transportes Delta', tipo: 'Consulta', descripcion: 'Duda sobre la facturación de octubre.', estado: 'Pendiente' },
            { id: 3, cliente: 'Distribuidora Beta', tipo: 'Actualización', descripcion: 'Actualizar contacto de gerencia.', estado: 'Completado' },
        ];
        
        const notificaciones = [
            { id: 1, cliente: 'Transportes Delta', mensaje: 'El depósito de la sucursal 1 está por debajo del 10% de su capacidad.', fecha: '2024-10-26 09:30' },
            { id: 2, cliente: 'Empresa Alfa', mensaje: 'Cambio de horario en el envío programado de hoy.', fecha: '2024-10-26 08:15' },
        ];
 
        let detailSections = [
                
           document.getElementById('pedidos-details'),
                document.getElementById('solicitudes-details'),
                document.getElementById('notificaciones-details')

                 ];
               // Funciones para manejar la vista del dashboard y los detalles
        function hideAllDetails() {
            detailSections.forEach(section => {
                section.style.display = 'none';
            });
            document.getElementById('clientes-list-container').classList.add('hidden');
            document.getElementById('sucursal-details-container').classList.add('hidden');
        }

        function showDetails(sectionId,event) {
            event.preventDefault(); 

            document.getElementById('dashboard-main-view').style.display = 'none';
            hideAllDetails();
            document.getElementById(sectionId).style.display = 'block';

            // Lógica para renderizar los datos de cada sección
            if (sectionId === 'pedidos-details') {
                renderizarPedidos();
            } else if (sectionId === 'solicitudes-details') {
                renderizarSolicitudes();
            } else if (sectionId === 'notificaciones-details') {
                renderizarNotificaciones();
            }
        }

        function showDashboard() {
            hideAllDetails();
           document.getElementById('dashboard-main-view').style.display = 'block';
        }


// Data de prueba para vehículos y tanques
const vehiculosDisponibles = @json($vehiculosDisponibles);
const tanquesDisponibles = @json($tanquesDisponibles);
// [
//     { id: 1, nombre: 'Tanque Principal 10k L', capacidad: 10000, disponible: 8500 },
//     { id: 2, nombre: 'Tanque Secundario 5k L', capacidad: 5000, disponible: 4000 }
// ];

function renderizarPedidos() {
    const tbody = document.getElementById('pedidos-table-body');
    tbody.innerHTML = '';
    
    pedidos.forEach(pedido => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${pedido.id}</td>
            <td>${pedido.cliente}</td>
            <td>${pedido.cantidad} L</td>
            <td>${pedido.cantidad} L</td>
            <td><span class="badge ${pedido.estado === 'Pendiente' ? 'bg-danger' : 'bg-success'}">${pedido.estado}</span></td>
            <td>${pedido.fecha}</td>
            <td>
                <button class="btn btn-sm btn-info ver-btn" data-id="${pedido.id}">Ver</button>
                
                ${pedido.estado === 'Pendiente' ? `
                    <button class="btn btn-sm btn-success aprobar-btn" data-id="${pedido.id}">Aprobar</button>
                    <select class="form-select form-select-sm vehiculo-select mt-1" data-id="${pedido.id}">
                        <option value="">Asignar Vehículo</option>
                        ${vehiculosDisponibles.map(vehiculo => `<option value="${vehiculo}">${vehiculo}</option>`).join('')}
                    </select>
                ` : `
                    <button class="btn btn-sm btn-primary crear-despacho-btn" data-id="${pedido.id}">Crear Despacho</button>
                `}
            </td>
        `;
        tbody.appendChild(row);
    });
    
    document.getElementById('pedidos-details').focus();
    
    // Llamar a la función para agregar los eventos a los nuevos elementos
    agregarListeners();
}

function agregarListeners() {
    // Escucha los clics en los botones "Aprobar"
    document.querySelectorAll('.aprobar-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            const pedidoId = parseInt(e.target.dataset.id);
            aprobarPedido(pedidoId);
        });
    });

    // Escucha los clics en los botones "Crear Despacho"
    document.querySelectorAll('.crear-despacho-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            const pedidoId = parseInt(e.target.dataset.id);
            mostrarFormularioDespacho(pedidoId);
        });
    });

    // Escucha los cambios en los selectores de vehículos (solo para pedidos pendientes)
    document.querySelectorAll('.vehiculo-select').forEach(select => {
        select.addEventListener('change', (e) => {
            const pedidoId = parseInt(e.target.dataset.id);
            const vehiculoAsignado = e.target.value;
            asignarVehiculo(pedidoId, vehiculoAsignado);
        });
    });
    
    // Escucha para el botón "Ver"
    document.querySelectorAll('.ver-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            const pedidoId = parseInt(e.target.dataset.id);
            mostrarDetallesPedido(pedidoId);
        });
    });
}

function aprobarPedido(id) {
    const pedido = pedidos.find(p => p.id === id);
    if (pedido) {
        if (!pedido.vehiculo) {
            Swal.fire('Error', 'Debe asignar un vehículo antes de aprobar el pedido.', 'warning');
            return; // No aprobar si no hay vehículo asignado
        }
        
        pedido.estado = 'Aprobado';
        renderizarPedidos();
        Swal.fire('¡Aprobado!', `El pedido #${id} ha sido aprobado y está listo para ser despachado.`, 'success');
    }
}

function asignarVehiculo(id, vehiculo) {
    const pedido = pedidos.find(p => p.id === id);
    if (pedido) {
        pedido.vehiculo = vehiculo;
        Swal.fire('Vehículo Asignado', `El vehículo ${vehiculo} ha sido asignado al pedido #${id}.`, 'success');
    }
}

function mostrarFormularioDespacho(id) {
    const pedido = pedidos.find(p => p.id === id);
    if (!pedido) return;

    // Crear las opciones para el selector de tanques
    const tanqueOptions = tanquesDisponibles.map(tanque => 
        `<option value="${tanque.id}">${tanque.nombre} (${tanque.disponible} L disponibles)</option>`
    ).join('');

    Swal.fire({
        title: `Crear Despacho para Pedido #${pedido.id}`,
        html: `
            <div class="mb-3 text-start">
                <label class="form-label fw-bold">Cantidad:</label>
                <p>${pedido.cantidad} L</p>
            </div>
            <div class="mb-3 text-start">
                <label for="swal-vehiculo" class="form-label fw-bold">Vehículo Asignado:</label>
                <input id="swal-vehiculo" class="form-control" value="${pedido.vehiculo}" readonly>
            </div>
            <div class="mb-3 text-start">
                <label for="swal-tanque" class="form-label fw-bold">Seleccionar Tanque:</label>
                <select id="swal-tanque" class="form-select">${tanqueOptions}</select>
            </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Confirmar Despacho',
        preConfirm: () => {
            const tanqueId = document.getElementById('swal-tanque').value;
            if (!tanqueId) {
                Swal.showValidationMessage('Debe seleccionar un tanque.');
                return false;
            }
            return {
                tanqueId: tanqueId,
                vehiculo: pedido.vehiculo
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const { tanqueId, vehiculo } = result.value;
            crearDespacho(id, tanqueId, vehiculo);
        }
    });
}

async function aprobarPedido(id, vehiculo) {
    try {
        const response = await fetch(`/pedidos/${id}/aprobar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ vehiculo: vehiculo })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            const pedido = pedidos.find(p => p.id === id);
            pedido.estado = 'Aprobado';
            renderizarPedidos();
            Swal.fire('¡Aprobado!', data.message, 'success');
        } else {
            Swal.fire('Error', data.error || 'No se pudo aprobar el pedido.', 'error');
        }
    } catch (error) {
        Swal.fire('Error', 'Hubo un problema de conexión con el servidor.', 'error');
    }
}

async function crearDespacho(pedidoId, tanqueId) {
    try {
        const response = await fetch(`/pedidos/${pedidoId}/despachar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ tanque_id: tanqueId })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            const pedido = pedidos.find(p => p.id === pedidoId);
            pedido.estado = 'Despachado';
            renderizarPedidos();
            Swal.fire('¡Despacho Creado!', data.message, 'success');
        } else {
            Swal.fire('Error', data.error || 'No se pudo crear el despacho.', 'error');
        }
    } catch (error) {
        Swal.fire('Error', 'Hubo un problema de conexión con el servidor.', 'error');
    }
}
// La función para abrir el modal y llenar sus campos
function openAjusteModal(id) {
    const depositoDiv = document.getElementById(`deposito-info-${id}`);
    
    // Obtener los datos del HTML usando .dataset
    const nivelActual = parseFloat(depositoDiv.dataset.nivel);
    const capacidad = parseFloat(depositoDiv.dataset.capacidad);

    const ajustarNivelModal = new bootstrap.Modal(document.getElementById('ajustarNivelModal'));
    
    // Llenar los campos del modal
    document.getElementById('deposito-id').value = id;
    document.getElementById('modal-nivel-actual').textContent = nivelActual;
    document.getElementById('capacidad-litros').textContent = capacidad;
    //document.getElementById('nuevo_nivel').value = nivelActual;
    document.getElementById('observacion').value = '';
    
    // Mostrar el modal
    ajustarNivelModal.show();
}

function openAjusteResguardo() {
    const resguardoDiv = document.getElementById(`resguardo-info`);
    
    // Obtener los datos del HTML usando .dataset
    const nivelActual = parseFloat(resguardoDiv.dataset.nivel);
    
    const ajustarResguardoModal = new bootstrap.Modal(document.getElementById('ajustarResguardoModal'));
    
    // Llenar los campos del modal
    document.getElementById('actual-resguardo').textContent =nivelActual;
    document.getElementById('nuevo_resguardo').value = nivelActual;
    document.getElementById('observacion_resguardo').value = '';
    
    // Mostrar el modal
    ajustarResguardoModal.show();
}

// Asegúrate de que este token CSRF esté en tu <head> de Blade
// <meta name="csrf-token" content="{{ csrf_token() }}">
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

async function submitAjuste(e) {
    e.preventDefault(); // Evita el envío tradicional del formulario

    const id_deposito = document.getElementById('deposito-id').value;
    const nuevoNivel = document.getElementById('nuevo_nivel').value;
    const observacion = document.getElementById('observacion').value;

    const btnSubmit = document.getElementById('btn-submit-ajuste');
    btnSubmit.disabled = true; // Deshabilita el botón mientras se envía

    try {
        const response = await fetch(`/depositos/ajustedinamic`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                id: id_deposito,
                nuevo_nivel: nuevoNivel,
                observacion: observacion
            })
        });

        const data = await response.json();

        if (response.ok) {
            // Actualizar la vista dinámicamente
            const depositoInfo = document.getElementById(`deposito-info-${id_deposito}`);
            const progressParent = depositoInfo.closest('li').querySelector('.progress');
            
            const nuevoPorcentaje = (data.nuevo_nivel / data.capacidad) * 100;
            const progressBar = progressParent.querySelector('.progress-bar');
            
            // Actualizar los datos del DOM
            depositoInfo.dataset.nivel = data.nuevo_nivel;
            depositoInfo.dataset.capacidad = data.capacidad;
            depositoInfo.querySelector('p:nth-child(2) small').textContent = `Nivel: ${nuevoPorcentaje.toFixed(2)}%`;
            depositoInfo.querySelector('p:nth-child(3) small').textContent = `${data.nuevo_nivel}L/${data.capacidad}L Litros`;
            
            progressBar.style.width = `${nuevoPorcentaje}%`;
            progressBar.textContent = `${data.nuevo_nivel}L - ${nuevoPorcentaje.toFixed(2)}%`;
            progressBar.ariaValueNow = nuevoPorcentaje;
            
            // Cambiar color de la barra según el nivel
            progressBar.style.backgroundColor = nuevoPorcentaje > 50 ? '#28a745' : (nuevoPorcentaje > 15 ? '#ffc107' : '#dc3545');

            // Cerrar el modal y mostrar un mensaje de éxito
            const ajustarNivelModal = bootstrap.Modal.getInstance(document.getElementById('ajustarNivelModal'));
            ajustarNivelModal.hide();
            Swal.fire('¡Ajuste Guardado!', data.message, 'success');
        } else {
            Swal.fire('Error', data.error || 'Ocurrió un error al guardar el ajuste.', 'error');
        }
    } catch (error) {
        console.error('Error en la solicitud:', error);
        Swal.fire('Error', 'Hubo un problema de conexión. Intente de nuevo.', 'error');
    } finally {
        btnSubmit.disabled = false;
    }
}


async function submitResguardo(e) {
    e.preventDefault(); // Evita el envío tradicional del formulario

    const nuevoResguardo = document.getElementById('nuevo_resguardo').value;
    const observacionresg = document.getElementById('observacion_resguardo').value;

    const btnSubmit = document.getElementById('btn-submit-resguardo');
    btnSubmit.disabled = true; // Deshabilita el botón mientras se envía

    try {
        const response = await fetch(`/depositos/ajusteresguardo`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                id: 'resguardo-span',
                nuevo_resguardo: nuevoResguardo ,
                observacion: observacionresg
            })
        });

        const data = await response.json();

        if (response.ok) {
            // Actualizar la vista dinámicamente
            const depositoInfo = document.getElementById(`${id}`);
            
            // Actualizar los datos del DOM
            depositoInfo.dataset.nivel = data.nuevo_resguardo;
            depositoInfo.textContent = nuevoResguardo.toFixed(2);
            const ajustarResguardoModal = bootstrap.Modal.getInstance(document.getElementById('ajustarResguardoModal'));
            ajustarResguardoModal.hide();
            Swal.fire('¡Ajuste Guardado!', data.message, 'success');
        } else {
            Swal.fire('Error', data.error || 'Ocurrió un error al guardar el ajuste.', 'error');
        }
    } catch (error) {
        console.error('Error en la solicitud:', error);
        Swal.fire('Error', 'Hubo un problema de conexión. Intente de nuevo.', 'error');
    } finally {
        btnSubmit.disabled = false;
    }
}

function mostrarDetallesPedido(id) {
    const pedido = pedidos.find(p => p.id === id);
    if (pedido) {
        Swal.fire({
            title: `Detalles del Pedido #${pedido.id}`,
            html: `
                <p><strong>Cliente:</strong> ${pedido.cliente}</p>
                <p><strong>Cantidad:</strong> ${pedido.cantidad} L</p>
                <p><strong>Estado:</strong> <span class="badge ${pedido.estado === 'Pendiente' ? 'bg-danger' : 'bg-success'}">${pedido.estado}</span></p>
                <p><strong>Fecha:</strong> ${pedido.fecha}</p>
                <p><strong>Vehículo Asignado:</strong> ${pedido.vehiculo || 'No asignado'}</p>
                <p><strong>Observacion:</strong> ${pedido.observaciones}'}</p>
            `,
            confirmButtonText: 'Cerrar'
        });
    }
}

        function renderizarSolicitudes() {
            const tbody = document.getElementById('solicitudes-table-body');
            tbody.innerHTML = '';
            solicitudes.forEach(solicitud => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${solicitud.id}</td>
                    <td>${solicitud.cliente}</td>
                    <td>${solicitud.tipo}</td>
                    <td>${solicitud.descripcion}</td>
                    <td><span class="badge ${solicitud.estado === 'Pendiente' ? 'bg-warning text-dark' : (solicitud.estado === 'Completado' ? 'bg-success' : 'bg-primary')}">${solicitud.estado}</span></td>
                `;
                tbody.appendChild(row);
            });
                document.getElementById('solicitudes-details').focus();
            }
        
        function renderizarNotificaciones() {
            const listGroup = document.querySelector('#notificaciones-details .list-group');
            listGroup.innerHTML = '';
            notificaciones.forEach(notificacion => {
                const item = document.createElement('a');
                item.href = '#';
                item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-start';
                item.innerHTML = `
                    <div class="ms-2 me-auto">
                        <div class="fw-bold">${notificacion.cliente}</div>
                        ${notificacion.mensaje}
                    </div>
                    <span class="badge bg-secondary rounded-pill">${notificacion.fecha}</span>
                `;
                listGroup.appendChild(item);
            });
            document.getElementById('notificaciones-details').focus();
        }



        document.addEventListener('DOMContentLoaded', function () {
            // Datos simulados pasados desde PHP
            const clientes = {!! json_encode($clientes) !!};
            const chartData = {!! json_encode($chartData) !!};
            const drilldownSeries = {!! json_encode($drilldownSeries) !!};
            
            // Referencias a los contenedores
            const clientesListContainer = document.getElementById('clientes-list-container');
            const sucursalDetailsContainer = document.getElementById('sucursal-details-container');
            const dashboardMainView = document.getElementById('dashboard-main-view');

            detailSections = [
                document.getElementById('pedidos-details'),
                document.getElementById('solicitudes-details'),
                document.getElementById('notificaciones-details')
            ];
   

            // Botones de navegación
            const verClientesBtn = document.getElementById('sucursales-card');
            const backToDashboardBtn = document.getElementById('back-to-dashboard-btn');
            const backToListBtn = document.getElementById('back-to-list-btn');

            // Ocultar vistas por defecto
            clientesListContainer.classList.add('hidden');
            sucursalDetailsContainer.classList.add('hidden');

             document.querySelectorAll('.ajustar-btn').forEach(button => {
                button.addEventListener('click', (e) => openAjusteModal(e.target.dataset.id));
            });
             document.getElementById('btn-submit-ajuste').addEventListener('click', submitAjuste);
            document.getElementById('btn-crear-despacho').addEventListener('click', mostrarSelectorTipoDespacho);

            // Lógica para el gráfico de Highcharts con drilldown
            Highcharts.chart('chart-container', {
                chart: {
                    type: 'bar'
                },
                title: {
                    text: 'Consumo y Disponibilidad por Clientes'
                },
                xAxis: {
                    categories: chartData.map(d => d.name)
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Porcentaje de Cupo'
                    },
                    labels: {
                        formatter: function () {
                            return this.value + '%';
                        }
                    }
                },
                tooltip: {
                    formatter: function () {
                        // Encuentra el dato original para obtener los valores en litros
                        const pointData = chartData.find(d => d.name === this.x);
                        const cupo = pointData ? pointData.cupo : null;
                        const disponible = pointData ? pointData.disponible : null;
                        const consumido = cupo - disponible;
                        
                        return `<b>${this.x}</b><br/>
                                Consumido: <b>${(consumido / cupo * 100).toFixed(2)}%</b> (${consumido.toFixed(2)} L)<br/>
                                Disponible: <b>${(disponible / cupo * 100).toFixed(2)}%</b> (${disponible.toFixed(2)} L)<br/>
                                Cupo Total: ${cupo.toFixed(2)} L`;
                    }
                },
                plotOptions: {
                    series: {
                        stacking: 'percent' // Apilamiento en porcentaje
                    }
                },
                series: [{
                    name: 'Consumido',
                    color: 'rgb(204, 74, 58)', // N
                    data: chartData.map(d => ({
                        y: d.cupo - d.disponible,
                        drilldown: d.drilldown
                    }))
                }, {
                    name: 'Disponible',
                    color: 'rgb(69, 155, 100)', // Nuevo color para el disponible
                    data: chartData.map(d => ({
                        y: d.disponible,
                        drilldown: d.drilldown
                    }))
                }],
                drilldown: {
                    series: drilldownSeries.map(s => ({
                        id: s.id,
                        name: s.name,
                        data: s.data.map(d => {
                            const consumido = d.cupo - d.disponible;
                            return [d.name, consumido];
                        })
                    }))
                }
            });

            // Manejadores de eventos de navegación
            if (verClientesBtn) {
                verClientesBtn.addEventListener('click', () => {
                    document.getElementById('dashboard-main-view').classList.add('hidden');
                    clientesListContainer.classList.remove('hidden');
                });
            }

            if (backToDashboardBtn) {
                backToDashboardBtn.addEventListener('click', () => {
                    clientesListContainer.classList.add('hidden');
                    document.getElementById('dashboard-main-view').classList.remove('hidden');
                });
            }

            if (backToListBtn) {
                backToListBtn.addEventListener('click', () => {
                    sucursalDetailsContainer.classList.add('hidden');
                    clientesListContainer.classList.remove('hidden');
                });
            }

            // Lógica para mostrar los detalles de la sucursal al hacer clic en la tarjeta
            document.querySelectorAll('.sucursal-card-container').forEach(card => {
                card.addEventListener('click', (e) => {
                    const sucursalId = parseInt(e.currentTarget.dataset.sucursalId, 10); 
                    const sucursal = clientes.find(s => parseInt(s.id, 10) === sucursalId);

                    if (sucursal) {
                        clientesListContainer.classList.add('hidden');
                        sucursalDetailsContainer.classList.remove('hidden');

                        document.getElementById('sucursal-details-title').textContent = sucursal.nombre;
                        document.getElementById('details-direccion').textContent = sucursal.direccion;
                        document.getElementById('details-contacto').textContent = sucursal.contacto;
                        document.getElementById('details-telefono').textContent = sucursal.telefono || 'No especificado';
                        document.getElementById('details-disponible').textContent = `${sucursal.disponible} L`;
                        document.getElementById('details-cupo').textContent = `/ ${sucursal.cupo} L`;

                        const percentage = (sucursal.disponible / sucursal.cupo) * 100;
                        const progressBar = document.getElementById('details-progress-bar');
                        progressBar.style.width = `${percentage}%`;
                        progressBar.classList.remove('progress-bar-custom', 'progress-bar-danger');
                        progressBar.classList.add(percentage < 10 ? 'progress-bar-danger' : 'progress-bar-custom');

                        const consumoHistorico = {
                            categorias: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
                            data: [1000, 800, 1200, 1500, 900, 1100, 1300]
                        };

                        Highcharts.chart('consumo-chart-container', {
                            chart: {
                                type: 'line'
                            },
                            title: {
                                text: 'Histórico de Consumo Semanal'
                            },
                            xAxis: {
                                categories: consumoHistorico.categorias
                            },
                            yAxis: {
                                title: {
                                    text: 'Litros Consumidos'
                                }
                            },
                            series: [{
                                name: 'Consumo',
                                data: consumoHistorico.data,
                                color: '#3b82f6'
                            }],
                            credits: {
                                enabled: false
                            }
                        });
                        
                        const btnDetailsPedido = document.getElementById('btn-details-pedido');
                        const btnDetailsEdicion = document.getElementById('btn-details-edicion');
                        
                        btnDetailsPedido.setAttribute('data-sucursal-id', sucursalId);
                        
                        btnDetailsEdicion.setAttribute('data-id', sucursalId);
                        btnDetailsEdicion.setAttribute('data-nombre', sucursal.nombre);
                        btnDetailsEdicion.setAttribute('data-direccion', sucursal.direccion);
                        btnDetailsEdicion.setAttribute('data-contacto', sucursal.contacto);
                        btnDetailsEdicion.setAttribute('data-telefono', sucursal.telefono);
                    }
                });
            });

            // Lógica para el modal de pedidos
            const pedidoModal = document.getElementById('hacerPedidoModal');
            const btnSubmitPedido = document.getElementById('btn-submit-pedido');
            const hacerPedidoForm = document.getElementById('hacerPedidoForm');
            const sucursalSelect = document.getElementById('sucursalSelect');
            
            // Lógica para preseleccionar la sucursal en el modal de pedidos
            document.querySelectorAll('.make-order-btn, #btn-details-pedido').forEach(button => {
                button.addEventListener('click', (e) => {
                    const sucursalId = e.currentTarget.dataset.sucursalId;
                    if (sucursalSelect) {
                        sucursalSelect.value = sucursalId;
                    }
                });
            });

            // Lógica para el modal de edición de sucursal
            const editarSucursalModal = document.getElementById('editarSucursalModal');
            const btnSubmitEdicion = document.getElementById('btn-submit-edicion');
            const editarSucursalForm = document.getElementById('editarSucursalForm');

            editarSucursalModal.addEventListener('show.bs.modal', (e) => {
                const button = e.relatedTarget;
                const id = button.getAttribute('data-id');
                const nombre = button.getAttribute('data-nombre');
                const direccion = button.getAttribute('data-direccion');
                const contacto = button.getAttribute('data-contacto');
                const telefono = button.getAttribute('data-telefono');
                
                document.getElementById('editSucursalId').value = id;
                document.getElementById('editNombreSucursal').value = nombre;
                document.getElementById('editDireccionSucursal').value = direccion;
                document.getElementById('editContactoSucursal').value = contacto;
                document.getElementById('editTelefonoSucursal').value = telefono;
            });

            // Lógica para manejar los backdrops de los modales (si se quedan)
            const allModals = document.querySelectorAll('.modal');
            allModals.forEach(modal => {
                modal.addEventListener('hidden.bs.modal', function() {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => {
                        backdrop.remove();
                    });
                });
            });
            

            const btnInspeccion = document.getElementById('btn-inspeccion-salida');
            if (btnInspeccion) {
                btnInspeccion.addEventListener('click', mostrarSelectorVehiculoParaInspeccion);
            }
        });


// Data simulada para el ejemplo
const cisternasDisponibles = @json($vehiculos);
const clientesDisponibles = @json($clientes);



async function mostrarSelectorTipoDespacho() {
    // Muestra un modal con las dos opciones de despacho
    const { value: tipoDespacho } = await Swal.fire({
        title: 'Selecciona el tipo de Despacho',
        input: 'radio',
        inputOptions: {
            'despacho': 'Despacho (Clientes)',
            'surtir': 'Surtir Vehículo (Consumo Interno)'
        },
        inputValidator: (value) => {
            if (!value) {
                return 'Debes seleccionar un tipo de despacho.';
            }
        },
        showCancelButton: true,
        confirmButtonText: 'Continuar'
    });

    if (tipoDespacho) {
        // Llama a la función del formulario correspondiente
        if (tipoDespacho === 'despacho') {
            mostrarFormularioDespacho();
        } else {
            mostrarFormularioRepostar();
        }
    }
}

async function mostrarFormularioDespacho() {
    // Genera las opciones para los selectores
    const cisternaOptions = cisternasDisponibles.map(c => `<option value="${c.id}">[${c.id}] - ${c.placa}</option>`).join('');
    const depositoOptions = tanquesDisponibles.map(d => `<option value="${d.id}">${d.nombre}</option>`).join('');
    const clienteOptions = clientesDisponibles.map(c => `<option value="${c.id}">${c.nombre}</option>`).join('');

    const { value: formValues } = await Swal.fire({
        title: 'Crear Despacho',
        html: `
            <div class="row g-3">
                <div class="col-12 text-start">
                    <label class="form-label">Cisterna</label>
                    <select id="swal-cisterna" class="form-select">${cisternaOptions}</select>
                </div>
                <div class="col-12 text-start">
                    <label class="form-label">Depósito</label>
                    <select id="swal-deposito" class="form-select">${depositoOptions}</select>
                </div>
                <div class="col-12 text-start">
                    <label class="form-label">Cliente</label>
                    <select id="swal-cliente" class="form-select">${clienteOptions}</select>
                </div>
                <div class="col-12 text-start">
                    <label class="form-label">Cantidad (Litros)</label>
                    <input id="swal-cantidad" type="number" step="0.01" class="form-control" required>
                </div>
                <div class="col-12 text-start">
                    <label class="form-label">Observación</label>
                    <textarea id="swal-observaciones" class="form-control"></textarea>
                </div>
            </div>
        `,
        focusConfirm: false,
        preConfirm: () => {
            const cantidad = parseFloat(document.getElementById('swal-cantidad').value);
            if (!cantidad || cantidad <= 0) {
                Swal.showValidationMessage('La cantidad debe ser un número positivo.');
                return false;
            }
            return {
                vehiculo_id: document.getElementById('swal-cisterna').value,
                deposito_id: document.getElementById('swal-deposito').value,
                cliente_id: document.getElementById('swal-cliente').value,
                cantidad_litros: cantidad,
                observaciones: document.getElementById('swal-observaciones').value,
                tipo:'DIESEL'
            };
        }
    });

    if (formValues) {
        submitDespacho(formValues);
    }
}

async function mostrarFormularioRepostar() {
    // Genera las opciones para los selectores
    const vehiculoOptions = vehiculosDisponibles.map(v => `<option value="${v}">${v}</option>`).join('');

    const { value: formValues } = await Swal.fire({
        title: 'Surtir Vehículo',
        html: `
            <div class="row g-3">
                <div class="col-12 text-start">
                    <label class="form-label">Vehículo</label>
                    <select id="swal-vehiculo" class="form-select">${vehiculoOptions}</select>
                </div>
                <div class="col-12 text-start">
                    <label class="form-label">Cantidad (Litros)</label>
                    <input id="swal-cantidad" type="number" step="0.01" class="form-control" required>
                </div>
                <div class="col-12 text-start">
                    <label class="form-label">Observación</label>
                    <textarea id="swal-observacion" class="form-control"></textarea>
                </div>
            </div>
        `,
        focusConfirm: false,
        preConfirm: () => {
            const cantidad = parseFloat(document.getElementById('swal-cantidad').value);
            if (!cantidad || cantidad <= 0) {
                Swal.showValidationMessage('La cantidad debe ser un número positivo.');
                return false;
            }
            return {
                tipo: 'repostar',
                vehiculo: document.getElementById('swal-vehiculo').value,
                cantidad: cantidad,
                observacion: document.getElementById('swal-observacion').value
            };
        }
    });

    if (formValues) {
        submitDespacho(formValues);
    }
}

async function submitDespacho(data) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    Swal.fire({
        title: 'Procesando...',
        didOpen: () => Swal.showLoading()
    });
    
    try {
        const response = await fetch('/despacho', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (response.ok) {
            Swal.fire('¡Despacho Creado!', result.message, 'success');
            // Aquí puedes actualizar la vista de tus tanques/depósitos
            // o recargar los datos del dashboard si es necesario.
            // Por ejemplo: recargarDatosDashboard();
        } else {
            Swal.fire('Error', result.error || 'No se pudo crear el despacho.', 'error');
        }
    } catch (error) {
        Swal.fire('Error', 'Hubo un problema de conexión con el servidor.', 'error');
    }
}
function getTipoVehiculoString(tipoId) {
    // Mapeo de tipos de vehículo según tu solicitud
    const tipoMap = {
        1: 'camion sencillo',
        2: 'cisterna',
        3: 'chuto',
    };
    // Devuelve el tipo mapeado o 'otro' por defecto
    return tipoMap[tipoId] || 'otro';
}


async function mostrarSelectorVehiculoParaInspeccion() {
    // 💡 NOTA: En un sistema real, esta data debería venir de un endpoint API real:
    // fetch('/api/vehiculos/activos').then(res => res.json())
    const vehiculosActivos = @json($vehiculosDisponibles);
    
    // Convertir el array de vehículos a opciones para el input de SweetAlert2
    const inputOptions = vehiculosActivos.reduce((options, vehiculo) => {
        const tipoString = getTipoVehiculoString(vehiculo.tipo);
        options[vehiculo.id] = `${vehiculo.placa} (${tipoString})`;

        return options;
    }, {});


    const { value: vehiculoId } = await Swal.fire({
        title: 'Selecciona el Vehículo para Inspección',
        input: 'select',
        inputPlaceholder: 'Selecciona un vehículo...',
        inputOptions: inputOptions,
        inputValidator: (value) => {
            if (!value) {
                return 'Debes seleccionar un vehículo para continuar.';
            }
        },
        showCancelButton: true,
        confirmButtonText: 'Abrir Checklist'
    });

    if (vehiculoId) {
        // Redirigir a la ruta definida en el InspeccionController
        // La ruta usa el ID del vehículo seleccionado
        const urlInspeccion = `/vehiculos/${vehiculoId}/inspeccion/salida`;
        
        Swal.fire({
            title: 'Cargando Checklist...',
            didOpen: () => Swal.showLoading()
        });
        
        // Redirección
        window.location.href = urlInspeccion;
    }
}

document.addEventListener('DOMContentLoaded', function() {

const printableArea = document.querySelector('.printableArea');
const sendTelegramButton = document.querySelector('#sendTelegramButton');
const elementToCaptureSelector = '.printableArea';

async function sendReportToTelegram() {
        sendTelegramButton.disabled = true;
       try {

            const responseC = await fetch(`/combustible/resumen`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
                // Si el backend no necesita un body, puedes omitir 'body: JSON.stringify({})'
            });

            // 2. Verificar si la respuesta HTTP fue exitosa (código 200-299)
            if (!responseC.ok) {
                // Si hay un error HTTP, lanzar una excepción para el bloque catch
                throw new Error(`Error en el servidor: ${responseC.status} ${responseC.statusText}`);
            }

            // 3. CAPTURAR EL TEXTO DEL CUERPO: Usar await response.text() para leer el string.
            const caption = await responseC.text();
            
            // 4. Mostrar el texto capturado correctamente
            console.log("Caption capturado correctamente:");
            console.log(caption);
            
            // Buscamos el primer elemento con la clase .printableArea
            const element = printableArea;
            if (!element) {
                throw new Error(`Elemento con selector '${elementToCaptureSelector}' no encontrado. ¡Verifique la clase!`);
            }

            // 1. Capturar el elemento con html2canvas
            const canvas = await html2canvas(element, {
                allowTaint: true, 
                useCORS: true,
                // Mejor calidad para la imagen
                scale: 2, 
            });

            // 2. Obtener la imagen como un Blob (archivo binario)
            const imageBlob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
            
            // 3. Crear FormData para enviar el archivo al servidor (POST request)
            const formData = new FormData();
            formData.append('chart_image', imageBlob, 'reporte_disponibilidad.png');
            formData.append('caption', `*Reporte de Inventario de Combustible*\nGenerado el: ${new Date().toLocaleString('es-VE')}\n`+caption);

            
            // 4. Enviar al endpoint de Laravel (ruta que debe existir: telegram.send.photo)
            const response = await fetch('{{ route('telegram.send.photo') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}' // Protección CSRF de Laravel
                },
                body: formData
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || `Error ${response.status}: Fallo en el servidor al enviar a Telegram.`);
            }

            // 5. Éxito
          

        } catch (error) {
            console.error('Error al enviar a Telegram:', error);
            // Mostrar mensaje amigable al usuario
       //     showStatus(`Error al enviar a Telegram: ${error.message}`, 'error');

        } finally {
            // 6. Reestablecer el botón
            sendTelegramButton.disabled = false;
        }
    }

    // 7. Asignar evento al nuevo botón
    if (sendTelegramButton) {
        sendTelegramButton.addEventListener('click', sendReportToTelegram);
    }
});
    </script>
@endpush
