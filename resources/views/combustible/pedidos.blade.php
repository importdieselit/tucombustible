@extends('layouts.app')

@section('title', 'Gestión de Pedidos de Combustible')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="mb-2">Pedidos de Combustible</h1>
        <p class="text-muted">Gestiona las solicitudes de combustible de los clientes.</p>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="card-title m-0">Pedidos Pendientes de Aprobación</h5>
    </div>
    <div class="card-body">
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
                                <span class="badge bg-warning text-dark">{{ $pedido->estado }}</span>
                            </td>
                            <td class="text-center">
                                <form id="aprobar-form-{{ $pedido->id }}" action="{{ route('combustible.aprobar', $pedido->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="button" class="btn btn-success btn-sm me-1 btn-aprobar"
                                            data-pedido-id="{{ $pedido->id }}"
                                            data-cantidad-solicitada="{{ $pedido->cantidad_solicitada }}"
                                            title="Aprobar">
                                        <i class="bi bi-check-circle"></i> Aprobar
                                    </button>
                                </form>
                                <form action="{{ route('combustible.rechazar', $pedido->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-danger btn-sm" title="Rechazar">
                                        <i class="bi bi-x-circle"></i> Rechazar
                                    </button>
                                </form>
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
@endsection

{{-- Agregamos SweetAlert y el script para manejar la confirmación --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Seleccionamos todos los botones con la clase 'btn-aprobar'
        document.querySelectorAll('.btn-aprobar').forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();

                const pedidoId = this.dataset.pedidoId;
                const cantidadSolicitada = this.dataset.cantidadSolicitada;
                const form = document.getElementById(`aprobar-form-${pedidoId}`);

                Swal.fire({
                    title: 'Aprobar Pedido',
                    html: `
                        <p>Cantidad solicitada: <strong>${cantidadSolicitada} Lt</strong></p>
                        <label for="swal-cantidad" class="form-label">Cantidad a Aprobar (Lt)</label>
                        <input id="swal-cantidad" class="swal2-input" type="number" step="0.01" min="0" value="${cantidadSolicitada}">
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
