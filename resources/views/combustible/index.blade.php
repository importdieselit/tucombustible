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

                $pedidos = [['id' => 'p1', 'estado' => 'En proceso'], ['id' => 'p2', 'estado' => 'Pendiente']];
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
                <p class="fw-bold mb-2">Total Disponible / Cupo</p>
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
        </div>

        <!-- Secciones de Acciones y Vistas -->
        <div class="row g-4 mb-5">
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100 p-4 d-flex flex-column justify-content-center text-center">
                    <i class="fas fa-truck-ramp-box stat-card-icon mb-2 text-warning"></i>
                    <h5 class="fw-bold mb-1">Pedidos</h5>
                    <p class="text-muted mb-0">{{ count(array_filter($pedidos, fn($p) => $p['estado'] == 'En proceso' || $p['estado'] == 'Pendiente')) }} en proceso</p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100 p-4 d-flex flex-column justify-content-center text-center">
                    <i class="fas fa-clipboard-list stat-card-icon mb-2 text-primary"></i>
                    <h5 class="fw-bold mb-1">Solicitudes</h5>
                    <p class="text-muted mb-0">{{ count(array_filter($solicitudes, fn($s) => $s['estado'] == 'Pendiente')) }} pendientes</p>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100 p-4 d-flex flex-column justify-content-center text-center">
                    <i class="fas fa-bell stat-card-icon mb-2 text-danger"></i>
                    <h5 class="fw-bold mb-1">Notificaciones</h5>
                    <p class="text-muted mb-0">{{ count(array_filter($notificaciones, fn($n) => !$n['leido'])) }} nuevas</p>
                </div>
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
            <button class="btn btn-primary-custom btn-lg rounded-pill px-5 py-3 shadow-lg fs-5" data-bs-toggle="modal" data-bs-target="#hacerPedidoModal">
                <i class="fas fa-plus-circle me-2"></i> Hacer Pedido
            </button>
        </div>

        <!-- Secciones de Contenido Dinámico -->
        <div id="content-sections">
            <div id="clientes-list-container" class="hidden">
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

                <div class="card p-4 mb-4">
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

            <div id="dashboard-main-view">
                <div class="card p-4 mb-4">
                    <h4 class="fw-bold mb-3">Consumo por Clientes</h4>
                    <div id="chart-container"></div>
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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Datos simulados pasados desde PHP
            const clientes = {!! json_encode($clientes) !!};
            const chartData = {!! json_encode($chartData) !!};
            const drilldownSeries = {!! json_encode($drilldownSeries) !!};
            
            // Referencias a los contenedores
            const clientesListContainer = document.getElementById('clientes-list-container');
            const sucursalDetailsContainer = document.getElementById('sucursal-details-container');
            const dashboardMainView = document.getElementById('dashboard-main-view');

            // Botones de navegación
            const verClientesBtn = document.getElementById('sucursales-card');
            const backToDashboardBtn = document.getElementById('back-to-dashboard-btn');
            const backToListBtn = document.getElementById('back-to-list-btn');

            // Ocultar vistas por defecto
            clientesListContainer.classList.add('hidden');
            sucursalDetailsContainer.classList.add('hidden');

            // Lógica para el gráfico de Highcharts con drilldown
            Highcharts.chart('chart-container', {
                chart: {
                    type: 'column',
                    events: {
                        drilldown: function(e) {
                            if (!e.seriesOptions) {
                                let chart = this;
                                chart.showLoading('Cargando sucursales de ' + e.point.name + '...');
                                // Aquí se podría hacer una llamada AJAX para obtener los datos de sucursales
                                // Para esta simulación, los datos ya están en el arreglo drilldownSeries
                                setTimeout(function() {
                                    chart.addSeriesAsDrilldown(e.point, {
                                        name: 'Sucursales de ' + e.point.name,
                                        data: drilldownSeries.find(s => s.id === e.point.drilldown).data.map(d => [d.name, d.y])
                                    });
                                    chart.hideLoading();
                                }, 500);
                            }
                        }
                    }
                },
                title: {
                    text: 'Consumo por Clientes Principales'
                },
                xAxis: {
                    type: 'category'
                },
                yAxis: {
                    title: {
                        text: 'Litros Consumidos'
                    }
                },
                legend: {
                    enabled: false
                },
                tooltip: {
                    pointFormat: 'Consumido: <b>{point.y:.2f} L</b>'
                },
                plotOptions: {
                    column: {
                        cursor: 'pointer',
                        point: {
                            events: {
                                click: function() {
                                    // Highcharts ya maneja el drilldown por sí solo, pero aquí se podría
                                    // agregar lógica adicional si fuera necesario.
                                    // Por ejemplo, para cambiar la vista de la UI.
                                }
                            }
                        }
                    }
                },
                series: [{
                    name: 'Consumido',
                    colorByPoint: true,
                    data: chartData.map(d => ({
                        name: d.name,
                        y: d.y,
                        drilldown: d.drilldown,
                        disponible: d.disponible,
                        cupo: d.cupo
                    }))
                }],
                drilldown: {
                    allowPointDrilldown: false,
                    series: drilldownSeries.map(s => ({
                        id: s.id,
                        name: s.name,
                        data: s.data.map(d => ({
                             name: d.name,
                             y: d.y,
                             disponible: d.disponible,
                             cupo: d.cupo
                        }))
                    }))
                }
            });

            // Manejadores de eventos de navegación
            if (verClientesBtn) {
                verClientesBtn.addEventListener('click', () => {
                    dashboardMainView.classList.add('hidden');
                    clientesListContainer.classList.remove('hidden');
                });
            }

            if (backToDashboardBtn) {
                backToDashboardBtn.addEventListener('click', () => {
                    clientesListContainer.classList.add('hidden');
                    dashboardMainView.classList.remove('hidden');
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
        });
    </script>
@endpush
