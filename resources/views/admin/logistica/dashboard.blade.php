@extends('layouts.app')
@section('title', 'Dashboard de Logística - ImporDiesel')

@section('content')
<div class="container-fluid">
    <div class="row page-titles mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center bg-white p-3 shadow-sm rounded border border-gray-300">
            <div>
                <h3 class="text-orange mb-0 fw-bold uppercase italic">| Resumen Ejecutivo Logístico</h3>
                <div class="text-[11px] font-black text-gray-500 uppercase mt-1">Torre de Control de Operaciones de Combustible</div>
            </div>
        </div>
    </div>

    <div class="card mb-4 border border-gray-300 shadow-sm">
        <div class="card-body bg-light">
            <form method="GET" action="{{ route('logistica.dashboard') }}" id="formFiltros" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label text-xs font-black text-gray-600 uppercase">Cliente / RIF</label>
                    <input type="text" name="search" class="form-control form-control-sm" value="{{ $search }}" placeholder="Ej: ImporDiesel o J-123456...">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-xs font-black text-gray-600 uppercase">Fecha Desde</label>
                    <input type="date" name="fecha_desde" class="form-control form-control-sm" value="{{ $fechaDesde }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-xs font-black text-gray-600 uppercase">Fecha Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="{{ $fechaHasta }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label text-xs font-black text-gray-600 uppercase">Estatus Planif.</label>
                    <select name="status_planificacion" class="form-select form-select-sm">
                        <option value="">TODOS</option>
                        <option value="PROGRAMADO" {{ $statusPlanificacion == 'PROGRAMADO' ? 'selected' : '' }}>PROGRAMADO</option>
                        <option value="EN RUTA" {{ $statusPlanificacion == 'EN RUTA' ? 'selected' : '' }}>EN RUTA</option>
                        <option value="COMPLETADO" {{ $statusPlanificacion == 'COMPLETADO' ? 'selected' : '' }}>COMPLETADO</option>
                        <option value="CANCELADO" {{ $statusPlanificacion == 'CANCELADO' ? 'selected' : '' }}>CANCELADO</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label text-xs font-black text-gray-600 uppercase">Estatus Pedido</label>
                    <select name="status_pedido" class="form-select form-select-sm">
                        <option value="">TODOS</option>
                        <option value="pendiente" {{ $statusPedido == 'pendiente' ? 'selected' : '' }}>PENDIENTE</option>
                        <option value="aprobado" {{ $statusPedido == 'aprobado' ? 'selected' : '' }}>APROBADO</option>
                        <option value="rechazado" {{ $statusPedido == 'rechazado' ? 'selected' : '' }}>RECHAZADO</option>
                        <option value="cancelado" {{ $statusPedido == 'cancelado' ? 'selected' : '' }}>CANCELADO</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end gap-1">
                    <button type="submit" class="btn btn-sm btn-dark w-100 uppercase font-bold text-xs" title="Filtrar">
                        <i class="fas fa-filter"></i>
                    </button>
                    {{-- NUEVO: Botón de Limpieza de Filtros Rápida --}}
                    <a href="{{ route('logistica.dashboard') }}" class="btn btn-sm btn-outline-secondary bg-white border w-100 text-xs" title="Limpiar Filtros">
                        <i class="fas fa-undo text-muted"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border border-gray-300 shadow-sm h-100">
                <div class="card-header bg-gray-800 py-2"><h6 class="text-white mb-0 text-xs font-black uppercase"><i class="fas fa-check-circle text-orange mr-2"></i> Tasa de Cumplimiento Logístico (Service Level)</h6></div>
                <div class="card-body d-flex flex-column justify-content-center align-items-center py-4">
                    <div class="display-4 font-black text-dark">{{ $tasaCumplimiento }}%</div>
                    <p class="text-gray-500 font-bold uppercase text-xs mt-2">Viajes Completados: {{ $viajesCompletados }} de {{ $viajesTotales }} planificaciones totales</p>
                    <div class="w-100 bg-gray-200 rounded-full h-3 mt-2">
                        <div class="bg-orange h-3 rounded-full" style="width: {{ $tasaCumplimiento }}%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border border-gray-300 shadow-sm h-100">
                <div class="card-header bg-gray-800 py-2"><h6 class="text-white mb-0 text-xs font-black uppercase"><i class="fas fa-gas-pump text-orange mr-2"></i> Comparativa de Volumen de Producto (Litros Cargados)</h6></div>
                <div class="card-body d-flex justify-content-around align-items-center">
                    <div class="text-center">
                        <span class="text-sm font-black text-gray-500 uppercase block">DIESEL</span>
                        <div class="text-xl font-black text-dark">{{ number_format($volumenTotalProducto->total_diesel ?? 0, 0, ',', '.') }} Lts</div>
                    </div>
                    <div style="width: 140px; height: 140px;">
                        <canvas id="chartProductoMix"></canvas>
                    </div>
                    <div class="text-center">
                        <span class="text-sm font-black text-gray-500 uppercase block">MGO (Marino)</span>
                        <div class="text-xl font-black text-orange">{{ number_format($volumenTotalProducto->total_mgo ?? 0, 0, ',', '.') }} Lts</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4 border border-gray-300 shadow-sm">
        <div class="card-header bg-gray-800 py-2"><h6 class="text-white mb-0 text-xs font-black uppercase"><i class="fas fa-chart-line text-orange mr-2"></i> Distribución de Litros Despachados por Cliente (Diesel vs MGO)</h6></div>
        <div class="card-body">
            <div style="height: 350px;">
                <canvas id="chartParetoClientes"></canvas>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-4 mb-4 mb-lg-0">
            <div class="card border border-gray-300 shadow-sm h-100">
                <div class="card-header bg-gray-800 py-2"><h6 class="text-white mb-0 text-xs font-black uppercase"><i class="fas fa-calendar-alt text-orange mr-2"></i> Estatus de Planificaciones</h6></div>
                <div class="card-body d-flex justify-content-center align-items-center">
                    <canvas id="chartPlanificaciones"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            {{-- NUEVA TABLA INTEGRADA DE HISTORIAL CON FORMATO INDEX --}}
            <div class="card shadow-sm border border-gray-300 h-100">
                <div class="card-header bg-white border-bottom py-2">
                    <h6 class="mb-0 fw-black text-uppercase small text-dark"><i class="fas fa-list me-2 text-orange"></i>Historial de Planificaciones</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0 text-xs">
                            <thead class="bg-light sticky-top" style="z-index: 10;">
                                <tr class="text-uppercase text-muted" style="font-size: 11px;">
                                    <th class="ps-3">ID / Fecha</th>
                                    <th>Movimiento / Producto</th> 
                                    <th>Transporte</th>
                                    <th>Origen / Destino</th>
                                    <th>Volumen (L)</th>
                                    <th>Estatus</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tablaPlanificaciones as $viaje)
                                    <tr>
                                        <td class="ps-3">
                                            <span class="fw-black text-dark d-block">V-{{ str_pad($viaje->id, 5, '0', STR_PAD_LEFT) }}</span>
                                            <small class="text-muted fw-bold">{{ \Carbon\Carbon::parse($viaje->fecha_salida)->format('d/m/Y') }}</small>
                                        </td>
                                        <td>
                                            @php
                                                $movimiento = match($viaje->tipo_planificacion) {
                                                    1, 2    => ['text' => 'DESPACHO', 'color' => 'bg-success'],
                                                    3       => ['text' => 'FLETE', 'color' => 'bg-secondary'],
                                                    4       => ['text' => 'COMPRA', 'color' => 'bg-info'],
                                                    default => ['text' => 'OTRO', 'color' => 'bg-light text-dark']
                                                };

                                                $productoEspecifico = match($viaje->tipo_planificacion) {
<<<<<<< HEAD
                                                    1       => 'DIESEL',
                                                    2       => 'MGO',
                                                    3       => $viaje->producto_flete ?? 'Sin Especificar',
                                                    4       => match((int)$viaje->tipo) {
                                                                1 => 'DIESEL',
                                                                2 => 'MGO',
=======
                                                    2       => 'DIESEL',
                                                    1       => 'MGO',
                                                    3       => $viaje->producto_flete ?? 'Sin Especificar',
                                                    4       => match((int)$viaje->tipo) {
                                                                2 => 'DIESEL',
                                                                1 => 'MGO',
>>>>>>> main
                                                                default => 'Sin Especificar'
                                                            },
                                                    default => 'Sin especificar'
                                                };
                                            @endphp
                                            <span class="badge {{ $movimiento['color'] }} text-white text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">
                                                {{ $movimiento['text'] }}
                                            </span>
                                            <small class="d-block fw-black text-dark mt-1 text-uppercase" style="font-size: 11px;">
                                                {{ $productoEspecifico }}
                                            </small>
                                        </td>
                                        <td>
                                            @if($viaje->es_transporte_externo)
                                                <span class="text-muted small fw-bold d-block"><i class="fas fa-truck me-1"></i> Externo</span>
                                                <span class="fw-bold" style="font-size: 11px;">{{ $viaje->vehiculo_externo }}</span>
                                            @else
                                                <span class="text-success small fw-bold d-block"><i class="fas fa-truck-moving me-1"></i> ImporDiesel</span>
                                                <span class="fw-bold" style="font-size: 11px;">{{ $viaje->vehiculo->placa ?? 'S/A' }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($viaje->tipo_planificacion == 4)
                                                <small class="d-block fw-black text-primary text-uppercase"><i class="fas fa-handshake me-1"></i> Proveedor: {{ $viaje->compraCombustible->first()->proveedor->nombre ?? 'Sin especificar' }}</small>
                                                <small class="d-block fw-bold text-muted">A: {{ $viaje->sede->nombre ?? 'Planta' }}</small>
                                                <small class="d-block text-dark fw-bold" style="font-size: 10px;">SAP: {{ $viaje->codigo_sap ?? 'N/A' }}</small>
                                            @elseif($viaje->tipo_planificacion == 3)
                                                <small class="d-block fw-bold">De: {{ Str::limit($viaje->punto_salida, 15) }}</small>
                                                <small class="d-block text-muted">A: {{ Str::limit($viaje->punto_llegada, 15) }}</small>
                                            @else
                                                <small class="d-block fw-bold">De: {{ $viaje->sede->nombre ?? 'Planta' }}</small>
                                                <small class="d-block text-muted">Destinos: {{ $viaje->detalles->count() }}</small>
                                            @endif
                                        </td>
                                        <td class="fw-black">{{ number_format($viaje->litros_totales ?? $viaje->litros, 0, ',', '.') }} L</td>
                                        <td>
                                            @php
                                                $statusColor = match($viaje->status) {
                                                    'PROGRAMADO' => 'warning text-dark',
                                                    'EN RUTA' => 'primary',
                                                    'COMPLETADO' => 'success',
                                                    'CANCELADO' => 'danger',
                                                    default => 'secondary'
                                                };
                                            @endphp
                                            <span class="badge bg-{{ explode(' ', $statusColor)[0] }} {{ str_contains($statusColor, 'text-dark') ? 'text-dark' : 'text-white' }} text-uppercase" style="font-size: 10px;">{{ $viaje->status }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <button class="btn btn-sm btn-light border shadow-sm px-2 py-1" onclick="verDetalles({{ $viaje->id }})" title="Ver Detalles">
                                                    <i class="fas fa-eye text-primary"></i>
                                                </button>
                                                @if($viaje->status === 'PROGRAMADO')
                                                    <a href="{{ route('logistica.edit', $viaje->id) }}" class="btn btn-sm btn-light border shadow-sm px-2 py-1" title="Editar">
                                                        <i class="fas fa-edit text-warning"></i>
                                                    </a>
                                                @endif

                                                @if($viaje->status !== 'CANCELADO' && $viaje->status !== 'COMPLETADO' && $viaje->status !== 'EN RUTA')
                                                    <button class="btn btn-sm btn-light border shadow-sm" onclick="cancelarPlanificacion({{ $viaje->id }})" title="Cancelar">
                                                        <i class="fas fa-times-circle text-danger"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <img src="https://cdn-icons-png.flaticon.com/512/2362/2362252.png" width="40" class="opacity-25 mb-2">
                                            <p class="text-muted fw-bold">No hay operaciones registradas con estos filtros.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light border-top p-2">
                    {{ $tablaPlanificaciones->appends(request()->except('planif_page'))->links() }}
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-4 mb-4 mb-lg-0">
            <div class="card border border-gray-300 shadow-sm h-100">
                <div class="card-header bg-gray-800 py-2"><h6 class="text-white mb-0 text-xs font-black uppercase"><i class="fas fa-shopping-cart text-orange mr-2"></i> Estatus de Pedidos Comerciales</h6></div>
                <div class="card-body d-flex justify-content-center align-items-center">
                    <canvas id="chartPedidos"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card border border-gray-300 shadow-sm h-100">
                <div class="card-header bg-gray-800 py-2"><h6 class="text-white mb-0 text-xs font-black uppercase"><i class="fas fa-list text-orange mr-2"></i> Detalle Operativo de Pedidos</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 text-xs align-middle">
                            <thead class="bg-light font-black uppercase">
                                <tr>
                                    <th class="ps-3">ID</th>
                                    <th>Cliente</th>
                                    <th>Solicitado</th>
                                    <th>Fecha Solicitud</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tablaPedidos as $p)
                                <tr>
                                    <td class="font-bold ps-3">#{{ str_pad($p->id, 5, '0', STR_PAD_LEFT) }}</td>
                                    <td class="uppercase font-bold text-gray-700">{{ $p->cliente->nombre ?? 'N/A' }}</td>
                                    <td class="font-black text-dark">{{ number_format($p->cantidad_solicitada, 0, ',', '.') }} L</td>
                                    <td>{{ \Carbon\Carbon::parse($p->fecha_solicitud)->format('d/m/Y h:i A') }}</td>
                                    <td>
                                        <span class="badge px-2 py-1 text-[10px] uppercase {{ $p->estado == 'completado' || $p->estado == 'aprobado' ? 'bg-success' : ($p->estado == 'cancelado' || $p->estado == 'rechazado' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                            {{ $p->estado }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center py-3 font-bold text-gray-400">No se encontraron pedidos con los filtros indicados.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light border-top p-2">
                    {{ $tablaPedidos->appends(request()->except('pedidos_page'))->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL DE DETALLES ESTRUCTURAL (Usado por tu función AJAX) --}}
<div class="modal fade" id="modalDetalles" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div id="contenidoModalDetalles">
                <div class="p-5 text-center">
                    <div class="spinner-border text-orange" role="status"></div>
                    <p class="mt-2 fw-bold text-uppercase small">Cargando información del viaje...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .text-orange { color: #ff6600 !important; }
    .bg-orange { background-color: #ff6600 !important; }
    .fw-black { font-weight: 900; }
    .sticky-top { position: sticky; top: 0; background: white; }
</style>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // FUNCIÓN AJAX PARA INTERCEPTAR E INYECTAR LOS DETALLES DINÁMICOS EN EL MODAL
    function verDetalles(id) {
        var modal = new bootstrap.Modal(document.getElementById('modalDetalles'));
        document.getElementById('contenidoModalDetalles').innerHTML = `
            <div class="p-5 text-center">
                <div class="spinner-border text-orange" role="status"></div>
                <p class="mt-2 fw-bold text-uppercase small">Cargando información...</p>
            </div>`;
        modal.show();
<<<<<<< HEAD

        fetch(`/logistica/${id}`)
=======
        let urlBase = "{{ route('logistica.show', ':id') }}";

        let urlFinal = urlBase.replace(':id', id);

        // Usamos el helper url() de Laravel para evitar problemas de rutas relativas
        fetch(urlFinal)
>>>>>>> main
            .then(response => {
                if (!response.ok) throw new Error('Error al cargar datos');
                return response.text();
            })
            .then(html => {
                document.getElementById('contenidoModalDetalles').innerHTML = html;
            })
            .catch(error => {
                document.getElementById('contenidoModalDetalles').innerHTML = `
                    <div class="p-4 text-center text-danger fw-bold">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>
                        Error al cargar los detalles del viaje.
                    </div>`;
            });
    }

<<<<<<< HEAD
=======

>>>>>>> main
    function cancelarPlanificacion(id) {
        Swal.fire({
            title: '¿ESTÁS SEGURO?',
            text: "Esta acción cancelará la planificación y no se podrá revertir.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#212529',
            cancelButtonColor: '#d33',
            confirmButtonText: 'SÍ, CANCELAR',
            cancelButtonText: 'NO'
        }).then((result) => {
            if (result.isConfirmed) {
<<<<<<< HEAD
                fetch(`/logistica/${id}/cancelar`, {
=======
                let urlBase = "{{ route('logistica.cancelar', ':id') }}";
                let urlFinal = urlBase.replace(':id', id);
                // Usamos el helper url() de Laravel para evitar problemas de rutas relativas
                fetch(urlFinal, {
>>>>>>> main
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('¡CANCELADO!', data.success, 'success')
                        .then(() => location.reload());
                    } else {
                        Swal.fire('ERROR', data.error, 'error');
                    }
                });
            }
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        const ctxPareto = document.getElementById('chartParetoClientes').getContext('2d');

        // GRADIENTES PROFESIONALES PARA EL GRÁFICO DE PARETO
        const gradientDiesel = ctxPareto.createLinearGradient(0, 0, 0, 400);
        gradientDiesel.addColorStop(0, '#003a94'); 
        gradientDiesel.addColorStop(1, '#001a4d');

        const gradientMgo = ctxPareto.createLinearGradient(0, 0, 0, 400);
        gradientMgo.addColorStop(0, '#ff7a1a'); 
        gradientMgo.addColorStop(1, '#b34700');

        // 1. CHART: COMPARATIVA PRODUCTO MIX
        new Chart(document.getElementById('chartProductoMix'), {
            type: 'doughnut',
            data: {
                labels: ['DIESEL', 'MGO'],
                datasets: [{
                    data: [{{ $volumenTotalProducto->total_diesel ?? 0 }}, {{ $volumenTotalProducto->total_mgo ?? 0 }}],
                    backgroundColor: ['#002d72', '#ff6b00'],
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });

        // 2. CHART: PARETO CLIENTES (BARRAS ALTAMENTE ESTILIZADAS CON BORDES REDONDEADOS)
        const rawPareto = @json($clientesPareto);
        const labelsPareto = rawPareto.map(item => item.cliente_nombre.substring(0, 15) + (item.cliente_nombre.length > 15 ? '...' : ''));
        const dataDiesel = rawPareto.map(item => item.litros_diesel);
        const dataMgo = rawPareto.map(item => item.litros_mgo);

        new Chart(ctxPareto, {
            type: 'bar',
            data: {
                labels: labelsPareto,
                datasets: [
                    { 
                        label: 'Diesel (Lts)', 
                        data: dataDiesel, 
                        backgroundColor: gradientDiesel,
                        borderRadius: { topLeft: 6, topRight: 6, bottomLeft: 0, bottomRight: 0 },
                        borderSkipped: false,
                        barPercentage: 0.6
                    },
                    { 
                        label: 'MGO (Lts)', 
                        data: dataMgo, 
                        backgroundColor: gradientMgo,
                        borderRadius: { topLeft: 6, topRight: 6, bottomLeft: 0, bottomRight: 0 },
                        borderSkipped: false,
                        barPercentage: 0.6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: { 
                    x: { stacked: true, grid: { display: false } }, 
                    y: { stacked: true, grid: { color: 'rgba(200, 200, 200, 0.2)' } } 
                },
                plugins: { 
                    legend: { position: 'top', labels: { font: { weight: 'bold' } } } 
                }
            }
        });

        // 3. CHART: STATUS PLANIFICACIONES
        const rawPlanif = @json($graficoPlanificaciones);
        new Chart(document.getElementById('chartPlanificaciones'), {
            type: 'pie',
            data: {
                labels: rawPlanif.map(i => i.status),
                datasets: [{
                    data: rawPlanif.map(i => i.total),
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6c757d']
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });

        // 4. CHART: STATUS PEDIDOS
        const rawPedidos = @json($graficoPedidos);
        new Chart(document.getElementById('chartPedidos'), {
            type: 'polarArea',
            data: {
                labels: rawPedidos.map(i => i.estado.toUpperCase()),
                datasets: [{
                    data: rawPedidos.map(i => i.total),
                    backgroundColor: ['#ffc107', '#28a745', '#dc3545', '#6c757d', '#007bff']
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    });
</script>
@endpush