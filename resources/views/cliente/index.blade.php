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
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
            background-color: var(--bg-light);
            color: var(--text-dark);
        }

        .card {
            z-index: 1;
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
        #editarSucursalModal .modal-dialog {
            z-index: 10;
        }
        .hidden {
            display: none !important;
        }
        .modal-backdrop.show {
            opacity: 0.5;
            z-index: 5;
        }
        .sucursal-card-container {
            z-index: 20;
            cursor: pointer;
            transition: transform 0.2s ease-in-out;
        }
        .sucursal-card-container:hover {
            transform: translateY(-5px);
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid">
        <h1 class="display-8 fw-bold mb-5 text-center text-black">{{ ucwords(Auth::user()->name)}}</h1>
        <p class="text-center text-sm mb-5" id="user-role-info">  </p>

        <!-- Sección de Visualización de Cupo y Disponibilidad -->
        <div class="card p-4 mb-5 main-hero-card">
            @php
                // Simulación de datos pasados desde el controlador
                // En una aplicación real, estos datos vendrían de la base de datos
                if($cliente->parent==0){
                    $currentUserRole = 'principal'; // 'principal' o 'sucursal'
                } else {
                    $currentUserRole = 'sucursal'; // 'principal' o 'sucursal'
                }

                $currentUserBranchId = Auth::user()->cliente_id; // ID de la sucursal actual del usuario
                
                // $cliente = (object)[
                //     'cupo' => 50000,
                //     'disponible' => 35000,
                //     'is_principal' => $currentUserRole === 'principal'
                // ];

                // $sucursales = [
                //     ['id' => 'branch-A', 'nombre' => 'Sucursal Principal', 'cupo' => 25000, 'disponible' => 18000, 'direccion' => 'Calle Falsa 123', 'contacto' => 'Juan Pérez'],
                //     ['id' => 'branch-B', 'nombre' => 'Sucursal Sur', 'cupo' => 25000, 'disponible' => 17000, 'direccion' => 'Avenida Siempre Viva 742', 'contacto' => 'María López']
                // ];

                $pedidos = [['id' => 'p1', 'estado' => 'En proceso'], ['id' => 'p2', 'estado' => 'Pendiente']];
                $solicitudes = [['id' => 's1', 'estado' => 'Pendiente'], ['id' => 's2', 'estado' => 'Aprobada']];
                $notificaciones = [['id' => 'n1', 'leido' => false], ['id' => 'n2', 'leido' => true]];

                $totalCapacity = $cliente->cupo;
                $totalCurrent = $cliente->disponible;
                $percentage = $totalCapacity > 0 ? ($totalCurrent / $totalCapacity) * 100 : 0;
                $isAlert = $totalCurrent <= ($totalCapacity * 0.1);

                // Datos para el gráfico de Highcharts
                $chartData = [];
                if ($cliente->parent==0) {
                  //  dd($sucursales);
                    foreach ($sucursales as $sucursal) {
                        $chartData[] = [
                            'name' => $sucursal['nombre'],
                            'cupo' => $sucursal['cupo'],
                            'id' => $sucursal['id'],
                            'disponible' => $sucursal['disponible'],
                            'consumido' => $sucursal['cupo'] - $sucursal['disponible']
                        ];
                    }
                } else {
                     $sucursalActual = collect($sucursales)->firstWhere('id', $currentUserBranchId);
                     $chartData[] = [
                        'name' => $sucursalActual['nombre'],
                        'cupo' => $sucursalActual['cupo'],
                        'id' => $sucursalActual['id'],
                        'disponible' => $sucursalActual['disponible'],
                        'consumido' => $sucursalActual['cupo'] - $sucursalActual['disponible']
                    ];
                }
            @endphp
            <div class="d-flex align-items-center">
                <i class="fas fa-money-bill-wave text-info me-3" style="font-size: 3rem;"></i>
                <div>
                    <h2 class="h4 fw-bold text-black mb-0" id="main-title">
                        Estado de Cupo General
                    </h2>
                    <p class="text-sm text-muted mb-0" id="main-subtitle">
                        Resumen de todas las sucursales
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
             @if ($cliente->parent==0)
                 <div class="col-12 col-md-6 col-lg-3">
                    <div class="card h-100 p-4 d-flex flex-column justify-content-center text-center sucursal-card-container" id="sucursales-card">
                        <i class="fas fa-sitemap stat-card-icon mb-2 text-success"></i>
                        <h5 class="fw-bold mb-1">Ver Sucursales</h5>
                        <p class="text-muted mb-0">{{ count($sucursales) }} activas</p>
                    </div>
                </div>
             @endif
        </div>
        
        <!-- Botón para Hacer Pedido -->
        <div class="text-center my-5">
            <button class="btn btn-primary-custom btn-lg rounded-pill px-5 py-3 shadow-lg fs-5" data-bs-toggle="modal" data-bs-target="#hacerPedidoModal">
                <i class="fas fa-plus-circle me-2"></i> Hacer Pedido
            </button>
        </div>

        <!-- Secciones de Contenido Dinámico -->
        <div id="content-sections">
             @if ($cliente->parent==0)
                <div id="sucursales-list-container" class="hidden">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold mb-0">Sucursales</h4>
                        <button class="btn btn-outline-secondary" id="back-to-dashboard-btn">
                            <i class="fas fa-arrow-left me-1"></i> Volver al Dashboard
                        </button>
                    </div>
                    <div class="row g-4" id="sucursales-cards">
                        @foreach ($sucursales as $sucursal)
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card h-100 p-4 sucursal-card-container" data-id="{{ $sucursal['id'] }}">
                                <h5 class="fw-bold mb-1">{{ $sucursal['nombre'] }}</h5>
                                <p class="text-muted mb-0">Contacto: {{ $sucursal['contacto'] }}</p>
                                <div class="mt-3">
                                    <p class="fw-bold mb-1">Disponible: <span class="text-success">{{ number_format($sucursal['disponible'], 0) }} L</span></p>
                                    <p class="fw-bold mb-0">Cupo: <span class="text-muted">{{ number_format($sucursal['cupo'], 0) }} L</span></p>
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
                            <i class="fas fa-arrow-left me-1"></i> Ver todas las sucursales
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
                        <h4 class="fw-bold mb-3">Histórico de Cupo por Sucursal</h4>
                        <div id="chart-container"></div>
                    </div>
                </div>
                
             @endif
        </div>


    </div>

    <!-- Modal para Hacer Pedido -->
    <div class="modal fade z-100" id="hacerPedidoModal" tabindex="-1" aria-labelledby="hacerPedidoModalLabel" aria-hidden="true" style="z-index: 100">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-card text-dark rounded-3 shadow-lg">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title" id="hacerPedidoModalLabel">Realizar Nuevo Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="hacerPedidoForm">
                        @csrf
                        @if ($cliente->parent==0)
                            <div class="mb-3">
                                <label for="sucursalSelect" class="form-label">Seleccionar Sucursal</label>
                                <select class="form-select" id="sucursalSelect" name="cliente_id" required>
                                    @foreach ($sucursales as $sucursal)
                                        <option value="{{ $sucursal['id'] }}">{{ $sucursal['nombre'] }} (Disponible: {{ $sucursal['disponible'] }} L)</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                             <input type="hidden" name="cliente_id" value="{{ $currentUserBranchId }}">
                             <p><strong>Sucursal:</strong> {{ collect($sucursales)->firstWhere('id', $currentUserBranchId)['nombre'] }}</p>
                        @endif
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
                    <h5 class="modal-title" id="editarSucursalModalLabel">Editar Sucursal</h5>
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
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
    <script src="https://code.highcharts.com/modules/accessibility.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Datos simulados pasados desde PHP
            const chartData = {!! json_encode($chartData) !!};
            const currentUserRole = '{!! $currentUserRole !!}';

             const sucursales = {!! json_encode($sucursales) !!};
            
            // Referencias a los contenedores
            const sucursalesListContainer = document.getElementById('sucursales-list-container');
            const sucursalDetailsContainer = document.getElementById('sucursal-details-container');
            const dashboardMainView = document.getElementById('dashboard-main-view');

            // Botones de navegación
            const verSucursalesBtn = document.getElementById('sucursales-card');
            const backToDashboardBtn = document.getElementById('back-to-dashboard-btn');
            const backToListBtn = document.getElementById('back-to-list-btn');

            // Ocultar vistas por defecto
            sucursalesListContainer.classList.add('hidden');
            sucursalDetailsContainer.classList.add('hidden');
           
            
            // Lógica para el gráfico de Highcharts
            if (currentUserRole === 'principal') {
                const categories = chartData.map(d => d.name);
                const cupoData = chartData.map(d => d.cupo);
                const consumidoData = chartData.map(d => d.consumido);

                Highcharts.chart('chart-container', {
                    chart: {
                        type: 'column'
                    },
                    title: {
                        text: 'Consumo y Cupo por Sucursal',
                        align: 'center'
                    },
                    xAxis: {
                        categories: categories,
                        // Configurar los puntos del eje X para que sean interactivos
                        labels: {
                            style: {
                                cursor: 'pointer'
                            },
                            events: {
                                click: function (e) {
                                    // Simular el click en la tarjeta de la sucursal
                                    const sucursalName = this.value;
                                    console.log('Sucursal clickeada desde gráfico:', sucursalName);
                                    const sucursal = chartData.find(s => s.id === sucursalName);

                                    if (sucursal) {
                                        const card = document.querySelector(`.sucursal-card-container[data-id="${sucursal.id}"]`);
                                        if (card) {
                                            card.click();
                                        }
                                    }
                                }
                            }
                        }
                    },
                    yAxis: {
                        min: 0,
                        title: {
                            text: 'Litros'
                        },
                        stackLabels: {
                            enabled: true,
                            style: {
                                fontWeight: 'bold'
                            }
                        }
                    },
                    legend: {
                        align: 'right',
                        x: -30,
                        verticalAlign: 'top',
                        y: 25,
                        floating: true,
                        backgroundColor: Highcharts.defaultOptions.legend.backgroundColor || 'white',
                        shadow: false
                    },
                    tooltip: {
                        headerFormat: '<b>{point.name} {point.id}</b><br/>',
                        pointFormat: '{series.name}: {point.y}<br/>Total: {point.stackTotal}'
                    },
                    plotOptions: {
                        column: {
                            stacking: 'normal',
                            dataLabels: {
                                enabled: true
                            }
                        },
                           // Configuración para hacer las columnas clickeables
                            point: {
                                events: {
                                    click: function () {
                                        // Obtener el ID de la sucursal de la columna clickeada
                                        const sucursalId = this.options.id;
                                        console.log('Sucursal ID clickeada desde gráfico:', sucursalId);
                                        // Buscar y hacer clic en la tarjeta de la sucursal correspondiente
                                        const card = document.querySelector(`.sucursal-card-container[data-id="${sucursalId}"]`);
                                        if (card) {
                                            card.click();
                                        }
                                    }
                                }
                            }
                    },
                    series: [{
                        name: 'Consumido',
                        data: chartData.map(d => ({ name: d.name, y: d.consumido, id: d.id })),
                        color: '#ef4444' // Rojo para lo consumido
                    }, {
                        name: 'Disponible',
                        data: chartData.map(d => ({ name: d.name, y: d.disponible, id: d.id })),
                        color: '#10b981' // Verde para lo disponible
                    }]
                });
            }


// Manejadores de eventos de navegación
            if (verSucursalesBtn) {
                verSucursalesBtn.addEventListener('click', () => {
                    dashboardMainView.classList.add('hidden');
                    sucursalesListContainer.classList.remove('hidden');
                });
            }

            if (backToDashboardBtn) {
                backToDashboardBtn.addEventListener('click', () => {
                    sucursalesListContainer.classList.add('hidden');
                    dashboardMainView.classList.remove('hidden');
                });
            }

            if (backToListBtn) {
                backToListBtn.addEventListener('click', () => {
                    sucursalDetailsContainer.classList.add('hidden');
                    sucursalesListContainer.classList.remove('hidden');
                });
            }

            // Lógica para mostrar los detalles de la sucursal al hacer clic en la tarjeta
            document.querySelectorAll('.sucursal-card-container').forEach(card => {
                card.addEventListener('click', (e) => {
                    const sucursalId = parseInt(e.currentTarget.dataset.id, 10); 
                    console.log('Sucursal ID clickeada:', sucursalId);
                    console.log('Lista de sucursales:', sucursales);
                    let sucursal = sucursales.find(s => s.id === sucursalId);
                    console.log('Sucursal seleccionada:', sucursal);
                    if (sucursal) {
                        // Ocultar la lista y mostrar los detalles
                        sucursalesListContainer.classList.add('hidden');
                        sucursalDetailsContainer.classList.remove('hidden');

                        // Llenar los datos de la sucursal
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

                        // Lógica para el gráfico de histórico de consumo (datos simulados)
                        // Deberás reemplazar esto con datos reales de tu base de datos
                        const consumoHistorico = {
                            categorias: ['Sem 30', 'Sem 31', 'Sem 32', 'Sem 33', 'Sem 34', 'Sem 36', 'Sem 37'],
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
                            }]
                        });
                        
                        // Configurar los botones de acción para el modal de pedidos y edición
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
            

            btnSubmitPedido.addEventListener('click', async () => {
                if (!hacerPedidoForm.reportValidity()) {
                    return;
                }
                 let modalbackdrop = document.querySelector('.modal-backdrop');
                        if (modalbackdrop) {   
                            modalbackdrop.remove();
                        }
                const formData = new FormData(hacerPedidoForm);
                const pedidoData = Object.fromEntries(formData.entries());

                try {
                    const response = await fetch('/pedidos', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(pedidoData)
                    });
                    
                    const result = await response.json();

                    if (response.ok && result.success) {
                        showSuccessAlert('Pedido realizado con éxito.',null, () => {

                            document.getElementById('hacerPedidoModal').style.display='none';

                            window.location.reload();
                        });
                    } else {
                        showErrorAlert('Error al realizar el pedido: ' + result.message);
                    }

                } catch (error) {
                    console.error('Error en la llamada a la API:', error);
                    showErrorAlert('Ocurrió un error inesperado. Intenta de nuevo.');
                }
            });

            // Lógica para preseleccionar la sucursal en el modal de pedidos
            document.querySelectorAll('.make-order-btn').forEach(button => {
                button.addEventListener('click', (e) => {
                    const sucursalId = e.currentTarget.dataset.sucursalId;
                    if (sucursalSelect) {
                        sucursalSelect.value = sucursalId;
                    }
                    let modalbackdrop = document.querySelector('.modal-backdrop');
                        if (modalbackdrop) {   
                            modalbackdrop.remove();
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
                
                document.getElementById('editSucursalId').value = id;
                document.getElementById('editNombreSucursal').value = nombre;
                document.getElementById('editDireccionSucursal').value = direccion;
                document.getElementById('editContactoSucursal').value = contacto;
                
              
                           const modalBackdrops = document.querySelectorAll('.modal-backdrop');
                            modalBackdrops.forEach(backdrop => {
                                backdrop.remove();
                            });
            });

            btnSubmitEdicion.addEventListener('click', async () => {
                if (!editarSucursalForm.reportValidity()) {
                    return;
                }

                const formData = new FormData(editarSucursalForm);
                const sucursalData = Object.fromEntries(formData.entries());

                try {
                    // Endpoint simulado para guardar la edición
                    const response = await fetch('/sucursales/update', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(sucursalData)
                    });
                    
                    const result = await response.json();

                    if (response.ok && result.success) {   

                         showSuccessAlert('Cambios guardados con éxito!',null, () => {
                            document.getElementById('editarSucursalModal').style.display='none';
                            // Actualizar la UI sin recargar la página (mejor práctica)
                            document.getElementById(`direccion-${sucursalData.id}`).textContent = sucursalData.direccion;
                            document.getElementById(`contacto-${sucursalData.id}`).textContent = sucursalData.contacto;
                        });
                        // También podrías actualizar el título del accordion
                        // document.getElementById(`heading-${sucursalData.id}`).querySelector('span').textContent = sucursalData.nombre;

                    } else {
                        showErrorAlert('Error al guardar los cambios: ' + result.message);
                    }

                } catch (error) {
                    console.error('Error en la llamada a la API:', error);
                    showErrorAlert('Ocurrió un error inesperado. Intenta de nuevo.');
                }
            });

        });
    </script>
@endpush
