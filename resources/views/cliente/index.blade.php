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
            z-index: 1051;
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
                $currentUserRole = 'principal'; // 'principal' o 'sucursal'
                $currentUserBranchId = 'branch-A';
                
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
                            'disponible' => $sucursal['disponible'],
                            'consumido' => $sucursal['cupo'] - $sucursal['disponible']
                        ];
                    }
                } else {
                     $sucursalActual = collect($sucursales)->firstWhere('id', $currentUserBranchId);
                     $chartData[] = [
                        'name' => $sucursalActual['nombre'],
                        'cupo' => $sucursalActual['cupo'],
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
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100 p-4 d-flex flex-column justify-content-center text-center" id="sucursales-card">
                    <i class="fas fa-sitemap stat-card-icon mb-2 text-success"></i>
                    <h5 class="fw-bold mb-1">Sucursales</h5>
                    <p class="text-muted mb-0">{{ count($sucursales) }} activas</p>
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
             @if ($cliente->parent==0)
                <div class="card p-4 mb-4">
                    <h4 class="fw-bold mb-3">Histórico de Cupo por Sucursal</h4>
                    <div id="chart-container"></div>
                </div>

                <div class="mt-5">
                    <h4 class="fw-bold mb-3">Administración de Sucursales</h4>
                    <div class="accordion" id="sucursalesAccordion">
                        @foreach ($sucursales as $sucursal)
                        <div class="accordion-item card shadow-sm mb-3">
                            <h2 class="accordion-header" id="heading-{{ $sucursal['id'] }}">
                                <button class="accordion-button collapsed fw-bold d-flex justify-content-between align-items-center" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $sucursal['id'] }}" aria-expanded="false" aria-controls="collapse-{{ $sucursal['id'] }}">
                                    <span>{{ $sucursal['nombre'] }}</span>
                                    <div class="d-flex align-items-center">
                                        <span class="badge rounded-pill bg-success me-2">Disponible: {{ number_format($sucursal['disponible'], 0) }} L</span>
                                        <i class="fas fa-chevron-down ms-2"></i>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapse-{{ $sucursal['id'] }}" class="accordion-collapse collapse" aria-labelledby="heading-{{ $sucursal['id'] }}" data-bs-parent="#sucursalesAccordion">
                                <div class="accordion-body">
                                    <p class="fw-bold mb-1">Información Básica:</p>
                                    <ul>
                                        <li><strong>Dirección:</strong> <span id="direccion-{{ $sucursal['id'] }}">{{ $sucursal['direccion'] }}</span></li>
                                        <li><strong>Contacto:</strong> <span id="contacto-{{ $sucursal['id'] }}">{{ $sucursal['contacto'] }}</span></li>
                                        <li><strong>Cupo Total:</strong> {{ number_format($sucursal['cupo'], 0) }} L</li>
                                        <li><strong>Disponible:</strong> {{ number_format($sucursal['disponible'], 0) }} L</li>
                                    </ul>
                                    <h6 class="fw-bold mt-3 mb-2">Histórico de Pedidos y Despachos:</h6>
                                    <ul class="list-group">
                                        <li class="list-group-item">Pedido #XYZ - 1500 L - <span class="badge bg-success">Entregado</span></li>
                                        <li class="list-group-item">Pedido #ABC - 2000 L - <span class="badge bg-warning text-dark">Pendiente</span></li>
                                        <li class="list-group-item">Despacho #QWE - 500 L - <span class="badge bg-info">En Ruta</span></li>
                                    </ul>
                                    <div class="d-flex justify-content-end mt-4">
                                        <button class="btn btn-outline-secondary me-2 edit-sucursal-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editarSucursalModal"
                                                data-id="{{ $sucursal['id'] }}"
                                                data-nombre="{{ $sucursal['nombre'] }}"
                                                data-direccion="{{ $sucursal['direccion'] }}"
                                                data-contacto="{{ $sucursal['contacto'] }}">
                                            <i class="fas fa-edit me-1"></i> Editar
                                        </button>
                                        <button class="btn btn-primary-custom make-order-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#hacerPedidoModal"
                                                data-sucursal-id="{{ $sucursal['id'] }}">
                                            <i class="fas fa-plus-circle me-1"></i> Hacer Pedido
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
             @endif
        </div>

    </div>

    <!-- Modal para Hacer Pedido -->
    <div class="modal fade z-100" id="hacerPedidoModal" tabindex="-1" aria-labelledby="hacerPedidoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-card text-dark rounded-3 shadow-lg">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title" id="hacerPedidoModalLabel">Realizar Nuevo Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="hacerPedidoForm">
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
                            <textarea class="form-control" id="observacionPedido" name="observacion" rows="3" placeholder="Detalles adicionales para el pedido."></textarea>
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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Datos simulados pasados desde PHP
            const chartData = {!! json_encode($chartData) !!};
            const currentUserRole = '{!! $currentUserRole !!}';
            
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
                        categories: categories
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
                        headerFormat: '<b>{point.x}</b><br/>',
                        pointFormat: '{series.name}: {point.y}<br/>Total: {point.stackTotal}'
                    },
                    plotOptions: {
                        column: {
                            stacking: 'normal',
                            dataLabels: {
                                enabled: true
                            }
                        }
                    },
                    series: [{
                        name: 'Consumido',
                        data: consumidoData,
                        color: '#ef4444' // Rojo para lo consumido
                    }, {
                        name: 'Disponible',
                        data: chartData.map(d => d.disponible),
                        color: '#10b981' // Verde para lo disponible
                    }]
                });
            }

            // Lógica para el modal de pedidos
            const pedidoModal = document.getElementById('hacerPedidoModal');
            const btnSubmitPedido = document.getElementById('btn-submit-pedido');
            const hacerPedidoForm = document.getElementById('hacerPedidoForm');
            const sucursalSelect = document.getElementById('sucursalSelect');

            btnSubmitPedido.addEventListener('click', async () => {
                if (!hacerPedidoForm.reportValidity()) {
                    return;
                }

                const formData = new FormData(hacerPedidoForm);
                const pedidoData = Object.fromEntries(formData.entries());

                try {
                    const response = await fetch('/api/pedidos', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(pedidoData)
                    });
                    
                    const result = await response.json();

                    if (response.ok && result.success) {
                        alert('Pedido realizado con éxito!');
                        bootstrap.Modal.getInstance(pedidoModal).hide();
                        window.location.reload();
                    } else {
                        alert('Error al realizar el pedido: ' + result.message);
                    }

                } catch (error) {
                    console.error('Error en la llamada a la API:', error);
                    alert('Ocurrió un error inesperado. Intenta de nuevo.');
                }
            });

            // Lógica para preseleccionar la sucursal en el modal de pedidos
            document.querySelectorAll('.make-order-btn').forEach(button => {
                button.addEventListener('click', (e) => {
                    const sucursalId = e.currentTarget.dataset.sucursalId;
                    if (sucursalSelect) {
                        sucursalSelect.value = sucursalId;
                    }
                    const modalbackdrop = document.querySelector('.modal-backdrop');
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

            });

            btnSubmitEdicion.addEventListener('click', async () => {
                if (!editarSucursalForm.reportValidity()) {
                    return;
                }

                const formData = new FormData(editarSucursalForm);
                const sucursalData = Object.fromEntries(formData.entries());

                try {
                    // Endpoint simulado para guardar la edición
                    const response = await fetch('/api/sucursales/update', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(sucursalData)
                    });
                    
                    const result = await response.json();

                    if (response.ok && result.success) {
                        alert('Cambios guardados con éxito!');
                        bootstrap.Modal.getInstance(editarSucursalModal).hide();
                        // Actualizar la UI sin recargar la página (mejor práctica)
                        document.getElementById(`direccion-${sucursalData.id}`).textContent = sucursalData.direccion;
                        document.getElementById(`contacto-${sucursalData.id}`).textContent = sucursalData.contacto;
                        // También podrías actualizar el título del accordion
                        // document.getElementById(`heading-${sucursalData.id}`).querySelector('span').textContent = sucursalData.nombre;

                    } else {
                        alert('Error al guardar los cambios: ' + result.message);
                    }

                } catch (error) {
                    console.error('Error en la llamada a la API:', error);
                    alert('Ocurrió un error inesperado. Intenta de nuevo.');
                }
            });

        });
    </script>
@endpush
