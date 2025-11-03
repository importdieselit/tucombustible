@extends('layouts.app')

@section('title', 'Listado de Solicitudes de Compra')

@push('styles')
    <style>
        .card-summary {
            transition: transform 0.3s ease;
        }
        .card-summary:hover {
            transform: translateY(-5px);
        }
        .status-badge {
            font-weight: bold;
            padding: 0.4em 0.6em;
            border-radius: 0.375rem;
            min-width: 90px;
            display: inline-block;
            text-align: center;
        }
        .status-1 { background-color: #ffda6a; color: #8a6d3b; } /* Pendiente */
        .status-2 { background-color: #5bc0de; color: #fff; } /* Cotizando (Info) */
        .status-3 { background-color: #4CAF50; color: #fff; } /* Comprado (Success) */
        .status-4 { background-color: #337ab7; color: #fff; } /* Recibido (Primary) */
        .status-5 { background-color: #d9534f; color: #fff; } /* Cancelado (Danger) */
    </style>
@endpush

@section('content')
<div class="container-fluid mt-4">
    <h1 class="mb-4 text-primary text-center">
        <i class="bi bi-cart-check-fill me-2"></i> Gestión de Solicitudes de Compra
    </h1>

    {{-- Bloque de Resumen de Combustible --}}
    <div class="row mb-5">
        <div class="col-12">
            <h3 class="mb-3 text-secondary border-bottom pb-2">
                <i class="bi bi-fuel-pump-fill me-2"></i> Inventario de Combustible
            </h3>
        </div>

        @foreach($disponibleCombustible as $tipo => $data)
        <div class="col-md-6 mb-4">
            <div class="card shadow card-summary border-{{ $tipo === 'DIESEL' ? 'success' : 'info' }}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-1">Stock de {{ $tipo }}</h5>
                            <p class="card-text text-muted">Para {{ $data['total_vehiculos'] }} vehículos</p>
                        </div>
                        <h2 class="display-6 m-0 text-{{ $tipo === 'DIESEL' ? 'success' : 'info' }}">
                            {{ number_format($data['stock'], 0, ',', '.') }} Lts
                        </h2>
                    </div>
                    <div class="progress mt-3" style="height: 10px;">
                        {{-- Simulando una barra de progreso basada en una capacidad máxima (ej. 20,000L) --}}
                        @php
                            $capacidad_max = 20000;
                            $porcentaje = ($data['stock'] / $capacidad_max) * 100;
                            $color = $porcentaje > 50 ? 'bg-success' : ($porcentaje > 20 ? 'bg-warning' : 'bg-danger');
                        @endphp
                        <div class="progress-bar {{ $color }}" role="progressbar" style="width: {{ $porcentaje }}%;" aria-valuenow="{{ $data['stock'] }}" aria-valuemin="0" aria-valuemax="{{ $capacidad_max }}"></div>
                    </div>
                    <small class="text-muted mt-1 d-block">Capacidad total aprox. {{ number_format($capacidad_max, 0, ',', '.') }} Lts</small>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    {{-- Fin Bloque de Resumen --}}

    {{-- Listado de Solicitudes --}}
    <div class="card shadow">
        <div class="card-header bg-white">
            <h5 class="card-title m-0">
                <i class="bi bi-list-task me-1"></i> Solicitudes de Compra Pendientes
            </h5>
        </div>
        <div class="card-body">
            @if($solicitudes->isEmpty())
                <div class="alert alert-info text-center">
                    No hay solicitudes de compra pendientes en este momento. ¡Buen trabajo!
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>OT / Solicitud ID</th>
                                <th>Vehículo (Flota/Placa)</th>
                                <th>Tipo de Unidad</th>
                                <th>Cantidad Solicitado</th>
                                <th>Cantidad Recibida</th>
                                <th>Fecha</th>
                                <th>Estatus</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($solicitudes as $solicitud)
                            <tr data-id="{{ $solicitud['id'] }}">
                                <td>
                                    <span class="badge bg-primary me-1">OT-{{ $solicitud['orden_id'] }}</span>
                                    <small class="text-muted">#{{ $solicitud['id'] }}</small>
                                </td>
                                <td>
                                    <strong>{{ $solicitud['vehiculo_flota'] }}</strong>
                                    ({{ $solicitud['vehiculo_placa'] }})
                                </td>
                                <td>
                                    <span class="badge bg-{{ $solicitud['unidad_consumo'] === 'DIESEL' ? 'success' : 'info' }}">{{ $solicitud['unidad_consumo'] ?? 'N/A' }}</span>
                                </td>
                                <td>{{ $solicitud['descripcion'] }}</td>
                                <td><strong>{{ number_format($solicitud['cantidad_solicitada'], 0, ',', '.') }}</strong></td>
                                <td>{{ $solicitud['solicitante'] }}</td>
                                <td>
                                    <span class="status-badge status-{{ $solicitud['estatus_id'] }}" id="status-text-{{ $solicitud['id'] }}">{{ $solicitud['estatus'] }}</span>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm estatus-select" data-id="{{ $solicitud['id'] }}">
                                        @foreach($estatusOpciones as $id => $nombre)
                                            <option value="{{ $id }}" {{ $solicitud['estatus_id'] == $id ? 'selected' : '' }}>
                                                {{ $nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
    {{-- Fin Listado de Solicitudes --}}

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const estatusSelects = document.querySelectorAll('.estatus-select');

        estatusSelects.forEach(select => {
            select.addEventListener('change', function() {
                const solicitudId = this.dataset.id;
                const nuevoEstatusId = this.value;
                const estatusTexto = this.options[this.selectedIndex].text;
                const statusTextElement = document.getElementById(`status-text-${solicitudId}`);

                // SweetAlert para confirmación
                Swal.fire({
                    title: '¿Confirmar Cambio?',
                    text: `Desea cambiar el estatus de la solicitud #${solicitudId} a "${estatusTexto}"?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, Cambiar Estatus',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Realizar la llamada AJAX para actualizar el estatus
                        fetch(`/solicitudes/${solicitudId}/estatus`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}' // Asumiendo que el token CSRF está disponible
                            },
                            body: JSON.stringify({ estatus_id: nuevoEstatusId })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Actualizado!', data.message, 'success');
                                
                                // Actualizar el badge de estatus en la UI
                                statusTextElement.textContent = estatusTexto;
                                statusTextElement.className = `status-badge status-${nuevoEstatusId}`;
                                
                                // Opcional: Deseleccionar el nuevo estado en el select para que el 'change' event se dispare de nuevo al seleccionarlo
                                this.querySelector(`option[value='${nuevoEstatusId}']`).selected = true;

                            } else {
                                Swal.fire('Error', data.message, 'error');
                                // Devolver el select a su estado anterior en caso de error
                                this.value = statusTextElement.className.match(/status-(\d+)/)[1];
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire('Error de Conexión', 'Ocurrió un error al comunicarse con el servidor.', 'error');
                            // Devolver el select a su estado anterior en caso de error
                            this.value = statusTextElement.className.match(/status-(\d+)/)[1];
                        });
                    } else {
                        // Si se cancela, volver al estatus original (el que está en el badge)
                        this.value = statusTextElement.className.match(/status-(\d+)/)[1];
                    }
                });
            });
        });
    });
</script>
@endpush
