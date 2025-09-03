@extends('layouts.app')

@section('title', 'Gestión de Pedidos de Combustible')

@push('styles')
    <!-- Enlace al CSS de Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet" />
    <style>
        /* Estilos personalizados para el Select2 dentro del modal */
        .modal-body .select2-container {
            width: 100% !important; /* Ocupa el 100% del contenedor */
        }
    </style>
@endpush
@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h1 class="mb-2">Pedidos de Combustible</h1>
        <div>
            <!-- Botón para abrir el modal de creación de pedido -->
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#crearPedidoModal">
                <i class="bi bi-plus-circle"></i> Crear Pedido Aprobado
            </button>
        </div>
    </div>
    <div class="col-12">
        <p class="text-muted">Gestiona las solicitudes de combustible de los clientes.</p>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="card-title m-0">Pedidos Pendientes de Aprobación</h5>
    </div>
    <div class="card-body">
        @if(Session::has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ Session::get('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(Session::has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ Session::get('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID Pedido</th>
                        <th>Cliente</th>
                        <th>Cantidad Solicitada</th>
                        <th>Fecha de Solicitud</th>
                        <th>Estatus</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pedidos as $pedido)
                        <tr>
                            <td>{{ $pedido->id }}</td>
                            <td>{{ $pedido->cliente->nombre ?? 'N/A' }}</td>
                            <td>{{ number_format($pedido->cantidad_solicitada, 2, ',', '.') }} Lt</td>
                            <td>{{ $pedido->fecha_solicitud->format('d/m/Y') }}</td>
                            <td>
                                <span class="badge" style="background-color: {{ $pedido->color_estado }};">
                                    {{ ucfirst($pedido->estado) }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if ($pedido->estado === 'pendiente')
                                    <form action="{{ route('combustible.aprobar', $pedido->id) }}" method="POST" class="d-inline-block aprobar-pedido-form">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" data-cantidad-solicitada="{{ $pedido->cantidad_solicitada }}">
                                            <i class="bi bi-check-circle"></i> Aprobar
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted">No Aplica</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No hay pedidos pendientes de aprobación.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Crear Pedido -->
<div class="modal fade" id="crearPedidoModal" tabindex="-1" aria-labelledby="crearPedidoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="crearPedidoModalLabel">Crear Pedido Aprobado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('combustible.storeAprobado') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="cliente_id" class="form-label">Cliente</label>
                        <select class="form-select w-100" id="cliente_id" name="cliente_id" required>
                            <option value="">Seleccione un cliente</option>
                            @foreach ($clientes as $cliente)
                                <option value="{{ $cliente->id }}">{{ $cliente->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="cantidad_aprobada" class="form-label">Cantidad Aprobada (Lt)</label>
                        <input type="number" class="form-control" id="cantidad_aprobada" name="cantidad_aprobada" step="0.01" min="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="observaciones_admin" class="form-label">Observaciones (Opcional)</label>
                        <textarea class="form-control" id="observaciones_admin" name="observaciones_admin" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-success">Crear y Aprobar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<!-- jQuery, necesario para Select2 -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- JavaScript de Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar Select2 en el select del cliente dentro del modal
        $('#cliente_id').select2({
            dropdownParent: $('#crearPedidoModal'),
            placeholder: "Buscar cliente...",
            allowClear: true
        });

        const forms = document.querySelectorAll('.aprobar-pedido-form');
        forms.forEach(form => {
            form.addEventListener('submit', function(event) {
                event.preventDefault();
                const cantidadSolicitada = this.querySelector('button').dataset.cantidadSolicitada;

                Swal.fire({
                    title: 'Aprobar Pedido',
                    html: `
                        <p>El cliente solicitó <strong>${cantidadSolicitada} Lt</strong> de combustible. ¿Cuánto desea aprobar?</p>
                        <input id="swal-cantidad" class="swal2-input" type="number" step="0.01" value="${cantidadSolicitada}">
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Aprobar',
                    cancelButtonText: 'Cancelar',
                    preConfirm: () => {
                        const cantidadAprobar = document.getElementById('swal-cantidad').value;
                        if (!cantidadAprobar || isNaN(cantidadAprobar) || cantidadAprobar < 0) {
                            Swal.showValidationMessage('Por favor, ingresa una cantidad válida.');
                            return false;
                        }
                        return cantidadAprobar;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const cantidadAprobada = result.value;
                        
                        // Creamos un campo de input oculto para enviar la cantidad aprobada al servidor
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'cantidad_aprobada';
                        hiddenInput.value = cantidadAprobada;
                        form.appendChild(hiddenInput);

                        // Enviamos el formulario
                        form.submit();
                    }
                });
            });
        });
    });
</script>
@endpush
