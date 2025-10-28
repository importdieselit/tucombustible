@extends('layouts.app')

@section('title', 'Editar Viaje #' . $viaje->id)

@section('content')
<div class="container mt-5">
    <div class="alert alert-info" id="ajax-message" style="display: none;"></div>
    
    <div class="card shadow-lg">
        <div class="card-header bg-warning text-white">
            <h3 class="mb-0"><i class="bi bi-pencil me-2"></i> Edición Dinámica Viaje #{{ $viaje->id }}</h3>
        </div>
        <div class="card-body">
            
            <form id="viaje-edit-form">
                @csrf
                {{-- Formulario para la data del Viaje (Campos Select y Text) --}}

                {{-- Campo Destino --}}
                <div class="mb-3">
                    <label for="destino_ciudad" class="form-label fw-bold">Destino / Ciudad</label>
                    <select name="destino_ciudad" id="destino_ciudad" class="form-select editable-field @error('destino_ciudad') is-invalid @enderror" data-field="destino_ciudad" required>
                            <option value="">Seleccione un destino del Tabulador</option>
                            
                            @foreach($destino as $ciudad)
                                <option value="{{ $ciudad }}" {{ old('destino_ciudad', $viaje->destino_ciudad) == $ciudad ? 'selected' : '' }}>{{ $ciudad }}</option>
                            @endforeach
                        </select>
                        @error('destino_ciudad')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Campo Fecha de Salida --}}
                <div class="mb-3">
                    <label for="fecha_salida" class="form-label fw-bold">Fecha de Salida</label>
                    <input type="date" class="form-control editable-field @error('fecha_salida') is-invalid @enderror" id="fecha_salida" name="fecha_salida" value="{{ old('fecha_salida', \Carbon\Carbon::parse($viaje->fecha_salida)->format('Y-m-d')) }}" data-field="fecha_salida" required>
                    @error('fecha_salida')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="chofer_id" class="form-label fw-bold">Chofer Principal</label>
                         <select name="chofer_id" id="chofer_id" class="form-select editable-field @error('chofer_id') is-invalid @enderror" data-field="chofer_id">
                            <option value="">Seleccione el chofer</option>
                            @foreach($choferes as $chofer)
                                @if($chofer->cargo == 'CHOFER' )                            
                                    <option value="{{ $chofer->id }}" {{ old('chofer_id', $viaje->chofer_id) == $chofer->id ? 'selected' : '' }}>{{ $chofer->persona->nombre }}</option>
                                @endif
                          @endforeach
                        </select>
                        @error('chofer_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-3">
                        <label for="ayudante" class="form-label fw-bold">Ayudante</label>
                       <select name="ayudante" id="ayudante" class="form-select editable-field @error('ayudante') is-invalid @enderror" data-field="ayudante">
                            <option value="">Seleccione el Ayudante</option>
                            @foreach($choferes as $chofer)
                                @if($chofer->cargo == 'AYUDANTE' || $chofer->cargo == 'AYUDANTE DE CHOFER')
                                    <option value="{{ $chofer->id }}" {{ old('ayudante_id', $viaje->ayudante_id) == $chofer->id ? 'selected' : '' }}>{{ $chofer->persona->nombre }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('ayudante')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                 <h4 class="mt-5 mb-3 text-danger border-bottom pb-1">Asignación de Vehículo</h4>
                
                <div class="mb-4">
                    <label for="vehiculo_id" class="form-label fw-bold">Vehículo Asignado</label>
                    <select name="vehiculo_id" id="vehiculo_id" class="form-select editable-field @error('vehiculo_id') is-invalid @enderror" data-field="vehiculo_id" required>
                        <option value="">Seleccione el vehículo</option>
                        @foreach($vehiculos as $vehiculo)
                            <option value="{{ $vehiculo->id }}" {{ old('vehiculo_id', $viaje->vehiculo_id) == $vehiculo->id ? 'selected' : '' }}>
                                {{ $vehiculo->placa }} - {{ $vehiculo->flota }}
                            </option>
                        @endforeach
                    </select>
                    @error('vehiculo_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Campo Estatus --}}
                <div class="mb-3">
                    <label for="status" class="form-label fw-bold">Estatus del Viaje</label>
                    <select class="form-select editable-field @error('status') is-invalid @enderror" id="status" name="status" data-field="status" required>
                        @php
                            $statuses = ['PENDIENTE',  'EN_CURSO', 'FINALIZADO', 'CANCELADO'];
                        @endphp
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" {{ old('status', $viaje->status) == $status ? 'selected' : '' }}>
                                {{ str_replace('_', ' ', $status) }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </form>
            
            {{-- SECCIÓN DE DESPACHOS --}}
            <h4 class="mt-4 mb-3 text-info border-bottom pb-1">Detalle de Despachos ({{ $viaje->despachos->count() }})</h4>
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-sm" id="despachos-table">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 50%;">Cliente</th>
                                <th style="width: 35%;">Otro Cliente (Si no está en lista)</th>
                            <th style="width: 25%;">Litros</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($viaje->despachos as $despacho)
                                    <tr id="row-{{ $despacho->id }}">
                                        <td>
                                            <select name="despachos[{{ $despacho->id }}][cliente_id]" class="form-select form-select-sm cliente-select @error("despachos[{{$despacho->id}}][cliente_id]") is-invalid @enderror" {{ $despacho->otro_cliente? 'disabled' : '' }}>
                                                <option value="">-- Seleccione Cliente --</option>
                                                @foreach($clientes as $cliente)
                                                    <option value="{{ $cliente->id }}" {{ $despacho->cliente_id == $cliente->id ? 'selected' : '' }}>{{ $cliente->nombre }}</option>
                                                @endforeach
                                            </select>
                                            @error("despachos[{{ $despacho->id }}][cliente_id]")
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="text" name="despachos[{{$despacho->id}}][otro_cliente]" class="form-control form-control-sm otro-cliente-input @error("despachos[{{$despacho->id}}][otro_cliente]") is-invalid @enderror" placeholder="Nombre o Razón Social" value="{{ $despacho->otro_cliente }}" {{ $despacho->cliente_id ? 'disabled' : '' }}>
                                            @error("despachos.{$despacho->id}.otro_cliente")
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="number" name="despachos[{{ $despacho->id }}][litros]" class="form-control form-control-sm @error("despachos.{$despacho->id}.litros") is-invalid @enderror" placeholder="Cantidad" step="any" required value="{{ $despacho->litros }}">
                                            @error("despachos.{$despacho->id}.litros")
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm remove-despacho" data-index="{{ $despacho->id }}"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-secondary">
                        <tr>
                            <td colspan="2" class="fw-bold text-end">Total Litros:</td>
                            <td colspan="2" class="fw-bold" id="total-litros">{{ number_format($viaje->despachos->sum('litros'), 2) }} L</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <a href="{{ route('viajes.show', $viaje->id) }}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Volver al Detalle</a>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const viajeId = '{{ $viaje->id }}';
        const csrfToken = '{{ csrf_token() }}';
        const ajaxMessage = document.getElementById('ajax-message');

        function showMessage(message, type = 'success') {
            ajaxMessage.textContent = message;
            ajaxMessage.className = `alert alert-${type}`;
            ajaxMessage.style.display = 'block';
            setTimeout(() => {
                ajaxMessage.style.display = 'none';
            }, 3000);
        }

        /**
         * Maneja la petición AJAX para actualizar un campo del Viaje.
         * @param {string} field - Nombre del campo a actualizar (ej. 'status').
         * @param {string|number} value - Nuevo valor.
         */
        async function updateViajeField(field, value) {
            const url = `{{ url('viajes') }}/${viajeId}/update-field`;
            
            try {
                const response = await fetch(url, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ field: field, value: value, _method: 'PUT' })
                });

                const data = await response.json();

                if (response.ok) {
                    showMessage(data.message);
                } else {
                    showMessage(data.message || 'Error desconocido al actualizar el viaje.', 'danger');
                }
            } catch (error) {
                showMessage('Error de conexión con el servidor.', 'danger');
                console.error('Fetch error:', error);
            }
        }
        
        // ----------------------------------------------------
        // LÓGICA DE ACTUALIZACIÓN DE DESPACHOS
        // ----------------------------------------------------

        /**
         * Maneja la petición AJAX para actualizar un campo de Despacho.
         * @param {number} despachoId - ID del Despacho.
         * @param {string} field - Nombre del campo a actualizar (ej. 'litros').
         * @param {string|number} value - Nuevo valor.
         */
        async function updateDespachoField(despachoId, field, value) {
             const url = `{{ url('viajes') }}/${viajeId}/despachos/${despachoId}`;
            
            try {
                const response = await fetch(url, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ field: field, value: value, _method: 'PUT' })
                });

                const data = await response.json();
                const row = document.querySelector(`tr[data-despacho-id="${despachoId}"]`);

                if (response.ok) {
                    showMessage(data.message);
                    
                    // 1. Actualizar el total de litros
                    document.getElementById('total-litros').textContent = data.total_litros + ' L';
                    
                    // 2. Actualizar el badge (Tipo) si se cambió el cliente
                    if (field === 'cliente_id') {
                        const badgeCell = row.cells[3].querySelector('.badge');
                        if (value) {
                            badgeCell.className = 'badge bg-primary';
                            badgeCell.textContent = 'Registrado';
                        } else {
                            badgeCell.className = 'badge bg-success';
                            badgeCell.textContent = 'Otro Cliente';
                        }
                    }

                } else {
                    showMessage(data.message || 'Error desconocido al actualizar el despacho.', 'danger');
                }
            } catch (error) {
                showMessage('Error de conexión con el servidor.', 'danger');
                console.error('Fetch error:', error);
            }
        }
        
        // ----------------------------------------------------
        // LISTENERS
        // ----------------------------------------------------

        // Listener para los campos del VIAJE (Selects e Inputs)
        document.querySelectorAll('.editable-field').forEach(input => {
            input.addEventListener('change', function() {
                const field = this.dataset.field;
                const value = this.value;
                updateViajeField(field, value);
            });
        });

        // Listener para los campos de DESPACHO (Selects e Inputs)
        document.querySelectorAll('.despacho-field').forEach(input => {
             // Usamos 'change' para selects y 'blur' para inputs de texto/número
            const eventType = input.tagName === 'SELECT' ? 'change' : 'blur'; 

            input.addEventListener(eventType, function() {
                const despachoId = this.dataset.despachoId;
                const field = this.dataset.field;
                let value = this.value;
                
                // Para el campo litros, asegurarse de que no haya comas y que sea un número
                if (field === 'litros') {
                    value = parseFloat(value.replace(',', '.'));
                }
                
                updateDespachoField(despachoId, field, value);
            });
        });
    });
</script>
@endsection