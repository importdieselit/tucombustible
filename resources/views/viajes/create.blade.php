@extends('layouts.app')

@section('title', 'Planificar Nuevo Viaje con Despachos')

@section('content')
<div class="container mt-5">
    <div class="card shadow-lg">
        <div class="card-header bg-success text-white">
            <h3 class="mb-0"><i class="bi bi-calendar-plus me-2"></i> Planificación de Viaje y Despachos</h3>
        </div>
        <div class="card-body">
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            
            <!-- El formulario enviará a ViajesController@store -->
            <form action="{{ route('viajes.store') }}" method="POST">
                @csrf
                
                <!-- 1. Detalle del Viaje (Fijo) -->
                <h4 class="mt-4 mb-3 text-success border-bottom pb-1">Detalles del Viaje</h4>
                <div class="row g-3 mb-4">
                    
                    {{-- Campo de FLETE --}}
                    <div class="col-md-3 d-flex align-items-center">
                        <div class="form-check form-switch pt-4">
                            <input class="form-check-input" type="checkbox" id="es_flete" name="es_flete" value="1" 
                                {{ old('es_flete') ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold" for="es_flete">
                                ¿Es Flete?
                            </label>
                        </div>
                    </div>
                    
                    <!-- Ciudad de Destino -->
                    <div class="col-md-9">
                        <label for="destino_ciudad" class="form-label fw-bold">Ciudad de Destino</label>
                        <select name="destino_ciudad" id="destino_ciudad" class="form-select @error('destino_ciudad') is-invalid @enderror" required>
                            <option value="">Seleccione un destino del Tabulador</option>
                            
                            <!-- Cargar las ciudades del tabulador (se asume que se pasan en $ciudades) -->
                             @foreach($destino as $ciudad)
                                <option value="{{ $ciudad }}" {{ old('destino_ciudad') == $ciudad ? 'selected' : '' }}>{{ $ciudad }}</option>
                            @endforeach
                            
                        </select>
                        @error('destino_ciudad')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- FECHA DE SALIDA --}}
                    <div class="col-md-6">
                        <label for="fecha_salida" class="form-label fw-bold">Fecha de Salida</label>
                        <input type="date" name="fecha_salida" id="fecha_salida" class="form-control @error('fecha_salida') is-invalid @enderror" value="{{ old('fecha_salida', date('Y-m-d')) }}" required>
                        @error('fecha_salida')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- DURACIÓN ESTIMADA (DÍAS) --}}
                    <div class="col-md-6">
                        <label for="duracion_dias" class="form-label fw-bold">Duración Estimada (Días)</label>
                        <input type="number" name="duracion_dias" id="duracion_dias" class="form-control @error('duracion_dias') is-invalid @enderror" value="{{ old('duracion_dias', 1) }}" min="1" required>
                        @error('duracion_dias')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- VEHÍCULO --}}
                    <div class="col-md-6">
                        <label for="vehiculo_id" class="form-label fw-bold">Vehículo (Flota)</label>
                        <select name="vehiculo_id" id="vehiculo_id" class="form-select select-or-other" data-other-field="otro_vehiculo">
                            <option value="">Seleccione un Vehículo</option>
                            <!-- Se asume que $vehiculos es un array de objetos Vehiculo -->
                            @foreach($vehiculos as $vehiculo)
                                <option value="{{ $vehiculo->id }}" @if(old('vehiculo_id') == $vehiculo->id) selected @endif>{{ $vehiculo->flota }} ({{ $vehiculo->placa }})</option>
                            @endforeach
                        </select>
                        @error('vehiculo_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6" style="display: none">
                        <label for="otro_vehiculo" class="form-label fw-bold">Vehiculo Externo</label>
                        <input type="text" name="otro_vehiculo" id="otro_vehiculo" class="form-control select-or-other-input" data-select-field="vehiculo_id" placeholder="Ej: Camión Externo X">
                        @error('otro_vehiculo')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- CHOFER --}}
                    <div class="col-md-6">
                        <label for="chofer_id" class="form-label fw-bold">Chofer</label>
                        <select name="chofer_id" id="chofer_id" class="form-select @error('chofer_id') is-invalid @enderror" >
                            <option value="">Seleccione el chofer</option>
                            <!-- Este loop debe cargar los usuarios con rol 'chofer' -->
                         
                            @foreach($choferes as $chofer)
                                @if($chofer->cargo == 'CHOFER' )                            
                                    <option value="{{ $chofer->id }}" {{ old('chofer_id') == $chofer->id ? 'selected' : '' }}>{{ $chofer->persona->nombre }}</option>
                                @endif
                          @endforeach
                        </select>
                        @error('chofer_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6" style="display: none">
                        <label for="otro_chofer" class="form-label fw-bold">Chofer Externo</label>
                        <input type="text" name="otro_chofer" id="otro_chofer" class="form-control select-or-other-input" data-select-field="chofer_id" placeholder="Ej: Pedro García (Externo)">
                        @error('otro_chofer')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    {{-- AYUDANTE --}}
                    <div class="col-md-6">
                        <label for="ayudante_id" class="form-label fw-bold">Ayudante</label>
                        <select name="ayudante" id="ayudante" class="form-select @error('ayudante') is-invalid @enderror">
                            <option value="">Seleccione el Ayudante</option>
                            <!-- Este loop debe cargar los usuarios con rol 'chofer' -->
                          
                            @foreach($choferes as $chofer)
                                @if($chofer->cargo == 'AYUDANTE' || $chofer->cargo == 'AYUDANTE DE CHOFER')
                                    <option value="{{ $chofer->id }}" {{ old('ayudante') == $chofer->id ? 'selected' : '' }}>{{ $chofer->persona->nombre }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('ayudante')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6" style="display: none">
                        <label for="otro_ayudante" class="form-label fw-bold">Ayudante</label>
                        <input type="text" name="otro_ayudante" id="otro_ayudante" class="form-control select-or-other-input" data-select-field="ayudante_id" placeholder="Ej: José Martínez (Externo)">
                        @error('otro_ayudante')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- 2. Tabla de Despachos (Se mantiene la funcionalidad actual) -->
                <h4 class="mt-4 mb-3 text-success border-bottom pb-1">Despachos del Viaje</h4>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40%;">Cliente/Otro Cliente</th>
                                <th style="width: 25%;">Litros Despachados</th>
                                <th style="width: 25%;">Observación</th>
                                <th style="width: 10%;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="despachos-table-body">
                            {{-- Las filas se gestionan con JavaScript. Se usa PHP para restaurar en caso de error. --}}
                            @if(old('despachos'))
                                @foreach(old('despachos') as $index => $despacho)
                                    @include('viajes._despacho_row', ['index' => $index, 'clientes' => $clientes, 'despacho' => $despacho])
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="add-despacho-btn">
                    <i class="bi bi-plus-circle me-1"></i> Agregar Otro Despacho
                </button>


                <div class="mt-5 d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="bi bi-send-check me-2"></i> Planificar y Crear Viaje
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- TEMPLATE para una fila de despacho (se asume que existe _despacho_row.blade.php o se define aquí) --}}
@if(!View::exists('viajes._despacho_row'))
    @php
        // Si la vista parcial no existe, la definimos aquí para que el JS pueda usarla como base
        $clientes = $clientes ?? []; // Asumir que $clientes está disponible
    @endphp
    <script id="despacho-row-template" type="text/template">
        <tr data-row-id="{INDEX}">
            {{-- Cliente/Otro Cliente --}}
            <td>
                <div class="input-group">
                    <select name="despachos[{INDEX}][cliente_id]" id="cliente_id_{INDEX}" class="form-select form-select-sm client-select select-or-other-client" data-other-field="otro_cliente_{INDEX}">
                        <option value="">Seleccione Cliente</option>
                        @foreach($clientes as $cliente)
                            <option value="{{ $cliente->id }}">{{ $cliente->nombre }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="despachos[{INDEX}][otro_cliente]" id="otro_cliente_{INDEX}" class="form-control form-control-sm other-client-input select-or-other-input" data-select-field="cliente_id_{INDEX}" placeholder="Otro Cliente Manual">
                </div>
            </td>
            {{-- Litros Despachados --}}
            <td>
                <input type="number" name="despachos[{INDEX}][litros]" class="form-control form-control-sm" min="1" required>
            </td>
            {{-- Observación --}}
            <td>
                <input type="text" name="despachos[{INDEX}][observacion]" class="form-control form-control-sm" placeholder="Detalles del despacho">
            </td>
            {{-- Acción --}}
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm remove-despacho" title="Eliminar Despacho">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    </script>
@endif
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const despachosTableBody = document.getElementById('despachos-table-body');
        const addDespachoButton = document.getElementById('add-despacho-btn');
        
        let rowIndex = despachosTableBody.rows.length; // Inicializa el índice con el número de filas existentes

        // --- LÓGICA DE ASIGNACIÓN MANUAL (VEHÍCULO, CHOFER, AYUDANTE) ---
               
       const esFleteSwitch = document.getElementById('es_flete');
       
        // Selects de unidades internas
        const vehiculoSelect = document.getElementById('vehiculo_id');
        const choferSelect = document.getElementById('chofer_id');
        const ayudanteSelect = document.getElementById('ayudante'); // ID original: ayudante
        
        // Inputs de flete
        const otroVehiculoInput = document.getElementById('otro_vehiculo');
        const otroChoferInput = document.getElementById('otro_chofer');
        const otroAyudanteInput = document.getElementById('otro_ayudante'); // ID original: otro_ayudante
        
        // Función para alternar la visibilidad y el atributo 'required'
        function toggleFleteFields() {
            const isFlete = esFleteSwitch && esFleteSwitch.checked;

            const show = (el) => el && (el.closest('div').style.display = 'flex');
            const hide = (el) => el && (el.closest('div').style.display = 'none');
            const setReq = (el, req = true) => el && (req ? el.setAttribute('required', 'required') : el.removeAttribute('required'));
            const clearVal = (el) => el && (el.value = '');

            if (isFlete) {
                // Modo FLETE
                hide(vehiculoSelect);
                hide(choferSelect);
                hide(ayudanteSelect);
                show(otroVehiculoInput);
                show(otroChoferInput);
                show(otroAyudanteInput);

                setReq(vehiculoSelect, false);
                setReq(choferSelect, false);
                clearVal(vehiculoSelect);
                clearVal(choferSelect);
                clearVal(ayudanteSelect);

                setReq(otroVehiculoInput, true);
                setReq(otroChoferInput, true);
            } else {
                // Modo INTERNO
                show(vehiculoSelect);
                show(choferSelect);
                show(ayudanteSelect);
                hide(otroVehiculoInput);
                hide(otroChoferInput);
                hide(otroAyudanteInput);

                setReq(vehiculoSelect, true);
                setReq(choferSelect, true);
                setReq(otroVehiculoInput, false);
                setReq(otroChoferInput, false);

                clearVal(otroVehiculoInput);
                clearVal(otroChoferInput);
                clearVal(otroAyudanteInput);
            }

        }
        // Listener del switch
        if (esFleteSwitch) {
            esFleteSwitch.addEventListener('change', toggleFleteFields);
        }

// Estado inicial correctoa función para asegurar el estado inicial correcto (manejo de old() data)
        toggleFleteFields(); 

        // --- LÓGICA DE DESPACHOS DINÁMICOS (Se mantiene la lógica anterior y se integra el select-or-other en cada fila) ---

        /**
         * Aplica la lógica de exclusividad de Cliente/Otro Cliente a una fila.
         * @param {HTMLTableRowElement} row - El elemento <tr>
         */
        function applyExclusivityLogic(row) {
            const selectEl = row.querySelector('.client-select');
            const otherEl = row.querySelector('.other-client-input');
            
            if (!selectEl || !otherEl) return;

            selectEl.addEventListener('change', function() {
                if (this.value) {
                    otherEl.value = '';
                    otherEl.disabled = true;
                } else {
                    otherEl.disabled = false;
                }
            });

            otherEl.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    selectEl.value = '';
                    selectEl.disabled = true;
                } else {
                    selectEl.disabled = false;
                }
            });
            
            // Inicialización
            if (selectEl.value) {
                otherEl.disabled = true;
            } else if (otherEl.value) {
                selectEl.disabled = true;
            }
        }


        /**
         * Crea una nueva fila de despacho.
         */
        function createRow() {
            let template = document.getElementById('despacho-row-template').innerHTML;
            // Reemplazar los marcadores de posición
            template = template.replace(/{INDEX}/g, rowIndex);
            
            // Insertar la fila y obtener la referencia
            despachosTableBody.insertAdjacentHTML('beforeend', template);
            const newRow = despachosTableBody.lastElementChild;
            
            // Aplicar la lógica de exclusividad a la nueva fila
            applyExclusivityLogic(newRow);

            rowIndex++; // Aumentar el índice para la próxima fila
        }
        
        // ------------------- INICIALIZACIÓN -------------------
        
        // 1. Si no hay filas de old('despachos'), agrega la primera fila.
        // Esto previene duplicados si la validación falla y Laravel ya restauró las filas.
        if (despachosTableBody.rows.length === 0) {
             createRow();
        } else {
             // 2. Si hay filas de old('despachos'), aplicarles la lógica de exclusividad
            Array.from(despachosTableBody.rows).forEach(applyExclusivityLogic);
        }

        
        // Manejador del botón 'Agregar Despacho'
        addDespachoButton.addEventListener('click', createRow);

        // Manejador del botón 'Eliminar' (Delegación de eventos)
        despachosTableBody.addEventListener('click', function(e) {
            if (e.target.closest('.remove-despacho')) {
                const button = e.target.closest('.remove-despacho');
                const row = button.closest('tr');
                
                // Solo eliminar si quedan más de 1 filas
                if (despachosTableBody.rows.length > 1) {
                    row.remove();
                } else {
                    // Mensaje de feedback alternativo a alert()
                    console.warn('Debe haber al menos un despacho por viaje.'); 
                }
            }
        });

    });
</script>
@endpush
