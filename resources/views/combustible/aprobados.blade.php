@extends('layouts.app')

@section('title', 'Despacho de Combustible')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="mb-2">Despachos de Combustible</h1>
        <p class="text-muted">Gestiona los pedidos de combustible que han sido aprobados.</p>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="card-title m-0">Pedidos Aprobados para Despacho</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID Pedido</th>
                        <th>Cliente</th>
                        <th>Cantidad Aprobada</th>
                        <th>Fecha de Aprobación</th>
                        <th>Estatus</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pedidos as $pedido)
                        <tr>
                            <td>{{ $pedido->id }}</td>
                            <td>{{ $pedido->cliente->nombre ?? 'N/A' }}</td>
                            <td>{{ number_format($pedido->cantidad_aprobada, 2, ',', '.') }} Lt</td>
                            <td>{{ $pedido->fecha_aprobacion->format('d/m/Y') }}</td>
                            <td>
                                <span class="badge bg-success">{{ $pedido->estado }}</span>
                            </td>
                            <td class="text-center">
                                <form id="despachar-form-{{ $pedido->id }}" action="{{ route('combustible.despachar', $pedido->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="cantidad_aprobada" value="{{ $pedido->cantidad_aprobada }}">
                                    <button type="button" class="btn btn-primary btn-sm me-1 btn-despachar"
                                            data-pedido-id="{{ $pedido->id }}"
                                            data-cantidad-aprobada="{{ $pedido->cantidad_aprobada }}"
                                            title="Despachar">
                                        <i class="bi bi-truck"></i> Despachar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No hay pedidos aprobados para despacho.</td>
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
        // Seleccionamos todos los botones con la clase 'btn-despachar'
        document.querySelectorAll('.btn-despachar').forEach(button => {
            button.addEventListener('click', function (e) {
                e.preventDefault();

                const pedidoId = this.dataset.pedidoId;
                const cantidadAprobada = this.dataset.cantidadAprobada;
                const form = document.getElementById(`despachar-form-${pedidoId}`);

                // SweetAlert modal
                Swal.fire({
                    title: 'Confirmar Despacho',
                    html: `
                        <p>Pedido #${pedidoId} - Cantidad a despachar: <strong>${parseFloat(cantidadAprobada).toFixed(2)} Lt</strong></p>
                        <hr>
                        <div class="mb-3">
                            <label for="swal-vehiculo" class="form-label">Vehículo que recibe</label>
                            <select id="swal-vehiculo" class="swal2-select form-select w-50">
                                <option value="">Seleccione un vehículo</option>
                                @foreach ($vehiculos as $vehiculo)
                                    <option value="{{ $vehiculo->id }}">{{ $vehiculo->flota }} - {{ $vehiculo->placa }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="swal-deposito" class="form-label">Depósito de origen</label>
                            <select id="swal-deposito" class="swal2-select form-select w-50">
                                <option value="">Seleccione un depósito</option>
                                @foreach ($depositos as $deposito)
                                    <option value="{{ $deposito->id }}">{{ $deposito->nombre }} (Disp: {{ number_format($deposito->nivel_actual_litros, 2, ',', '.') }} Lt)</option>
                                @endforeach
                            </select>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Confirmar Despacho',
                    cancelButtonText: 'Cancelar',
                    preConfirm: () => {
                        const vehiculoId = document.getElementById('swal-vehiculo').value;
                        const depositoId = document.getElementById('swal-deposito').value;

                        if (!vehiculoId || !depositoId) {
                            Swal.showValidationMessage('Por favor, selecciona un vehículo y un depósito.');
                            return false;
                        }

                        return { vehiculoId, depositoId };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const { vehiculoId, depositoId } = result.value;
                        
                        // Creamos campos de input ocultos para enviar los IDs seleccionados
                        const vehiculoInput = document.createElement('input');
                        vehiculoInput.type = 'hidden';
                        vehiculoInput.name = 'vehiculo_id';
                        vehiculoInput.value = vehiculoId;
                        form.appendChild(vehiculoInput);

                        const depositoInput = document.createElement('input');
                        depositoInput.type = 'hidden';
                        depositoInput.name = 'deposito_id';
                        depositoInput.value = depositoId;
                        form.appendChild(depositoInput);

                        // Enviamos el formulario
                        form.submit();
                    }
                });
            });
        });
    });
</script>
@endpush
