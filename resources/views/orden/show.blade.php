@extends('layouts.app')

@section('title', 'Hoja Técnica de Orden')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h1 class="mb-2">Hoja Técnica de Orden #{{ $orden->nro_orden ?? 'N/A' }}</h1>
        <div>
            <a href="{{ route('ordenes.list') }}" class="btn btn-info me-2">
                <i class="bi bi-arrow-left"></i> Volver al Listado
            </a>
             @if ($orden->estatus ==2)
                <a href="{{ route('ordenes.edit', $orden->id ?? '') }}" class="btn btn-warning me-2">
                    <i class="bi bi-pencil"></i> Editar
                </a>
                <button id="cerrar-orden" class="btn btn-success me-2">
                    <i class="bi bi-check-circle"></i> Cerrar Orden
                </button>
                <button id="anular-orden" class="btn btn-danger">
                    <i class="bi bi-x-circle"></i> Anular Orden
                </button>
            @else
                <button id="reactivar-orden" class="btn btn-success me-2">
                    <i class="bi bi-check-circle"></i> Reactivar Orden
                </button>
            @endif
            <button id="print" class="btn btn-primary">
                    <i class="fa fa-print"></i> Imprimir
            </button>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4 printableArea">
    <div class="card-header bg-white">
        <h5 class="card-title m-0">Detalles de la Orden</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>Vehículo:</strong> {{ $orden->vehiculo()->flota ?? 'N/A' }} ({{ $orden->vehiculo()->placa ?? 'N/A' }})</li>
                    <li class="list-group-item"><strong>Responsable Asignado:</strong> {{ $orden->responsable ?? 'N/A' }}</li>
                    <li class="list-group-item"><strong>Kilometraje:</strong> {{ number_format($orden->kilometraje ?? 0, 0, ',', '.') }}</li>
                    <li class="list-group-item"><strong>Tipo de Orden:</strong> {{ $orden->tipo_orden->nombre ?? 'N/A' }}</li>
                    <li class="list-group-item"><strong>Estatus:</strong> <span class="noPrint badge bg-{{ $orden->estatus()->css }}" title="{{ $orden->estatus()->descripcion }}">
                            <i class="mr-1 fa-solid {{ $orden->estatus()->icon_orden }}"></i>
                           {{ $orden->estatus()->orden }}
                        </span>
                        <span class="siPrint" style="display: none">
                           {{ $orden->estatus()->orden }}
                        </span>
                    </li>
                </ul>
            </div>
            <div class="col-md-6">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>Apertura:</strong> 
                        @php
                        use Carbon\Carbon;
                        Carbon::setLocale('es');
                        @endphp
                           {{ Carbon::parse($orden->fecha_in)->format('d/m/Y') ?? 'N/A' }}
                        a las 
                        {{ Carbon::parse($orden->hora_in)->format('h:i a') ?? 'N/A' }}
                     <li class="list-group-item">
                    <strong>Cierre:</strong> 
                        {{ Carbon::parse($orden->fecha_out)->format('d/m/Y') ?? 'N/A' }}
                        a las 
                        {{ Carbon::parse($orden->hora_out)->format('h:i a') ?? 'N/A' }}</li>
                    {{-- <li class="list-group-item"><strong>Tiempo Promedio:</strong> {{ $orden->tiempo_promedio ?? 'N/A' }} días</li> --}}
                </ul>
            </div>
        </div>
        <hr>
        <h5>Descripción del Problema/Tarea</h5>
        <p>{{ $orden->descripcion_1 ?? 'No hay descripción.' }}</p>

        <hr>
        <h5>Observaciones</h5>
        <p>{{ $orden->observacion ?? 'No hay observaciones.' }}</p>

        <hr>
        <h5>Insumos Utilizados</h5>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Cantidad</th>
                    <th>Costo</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($insumos_usados as $insumo)
                    <tr>
                        <td>{{ $insumo->inventario->codigo ?? 'N/A' }}</td>
                        <td>{{ $insumo->inventario->descripcion ?? 'N/A' }}</td>
                        <td>{{ $insumo->cantidad ?? 'N/A' }}</td>
                        <td>${{ number_format($insumo->inventario->costo * $insumo->cantidad, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center">No se han registrado insumos para esta orden.</td>
                    </tr>
                @endforelse
                <tr>
                    <td colspan="3" class="text-end"><strong>Total Costo:</strong></td>
                    <td><strong>${{ number_format($insumos_usados->sum(fn($insumo) => $insumo->inventario->costo * $insumo->cantidad), 2, ',', '.') }}</strong></td>
            </tbody>
        </table>
    </div>
</div>

{{-- SweetAlert2 CDN --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const orderId = '{{ $orden->id ?? '' }}';
    @if ($orden->estatus ==2)
        // Botón para cerrar la orden
        const cerrarBtn = document.getElementById('cerrar-orden');
        if (cerrarBtn) {
            cerrarBtn.addEventListener('click', function() {
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "No podrás revertir el cierre de esta orden.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, cerrar orden',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/ordenes/${orderId}/cerrar`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire(
                                    '¡Cerrada!',
                                    'La orden ha sido cerrada exitosamente.',
                                    'success'
                                ).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire(
                                    'Error',
                                    data.message,
                                    'error'
                                );
                            }
                        })
                        .catch(error => {
                            Swal.fire(
                                'Error',
                                'No se pudo conectar con el servidor.',
                                'error'
                            );
                        });
                    }
                });
            });
        }

        // Botón para anular la orden
        const anularBtn = document.getElementById('anular-orden');
        if (anularBtn) {
            anularBtn.addEventListener('click', function() {
                Swal.fire({
                    title: 'Anular Orden',
                    text: 'Por favor, introduce el motivo de la anulación:',
                    icon: 'warning',
                    input: 'text',
                    inputPlaceholder: 'Motivo de anulación...',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, anular orden',
                    cancelButtonText: 'Cancelar',
                    inputValidator: (value) => {
                        if (!value) {
                            return '¡Debes escribir un motivo!';
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/ordenes/${orderId}/anular`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ anulacion: result.value })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire(
                                    '¡Anulada!',
                                    'La orden ha sido anulada exitosamente.',
                                    'success'
                                ).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire(
                                    'Error',
                                    data.message,
                                    'error'
                                );
                            }
                        })
                        .catch(error => {
                            Swal.fire(
                                'Error',
                                'No se pudo conectar con el servidor.',
                                'error'
                            );
                        });
                    }
                });
            });
        }
    @else
        const reactivarBtn = document.getElementById('reactivar-orden');
        if (reactivarBtn) {
            reactivarBtn.addEventListener('click', function() {
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "desea revertir el cierre de esta orden.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, reactivar orden',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/ordenes/${orderId}/reactivar`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire(
                                    'Reactivada!',
                                    'La orden ha sido reactivada exitosamente.',
                                    'success'
                                ).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire(
                                    'Error',
                                    data.message,
                                    'error'
                                );
                            }
                        })
                        .catch(error => {
                            Swal.fire(
                                'Error',
                                'No se pudo conectar con el servidor.',
                                'error'
                            );
                        });
                    }
                });
            });
        }
    @endif
    });
</script>

@endsection
