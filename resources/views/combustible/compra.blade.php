@extends('layouts.app')

@section('title', 'Crear Solicitud de Compra de Combustible')

@section('content')
<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="mb-2 text-primary">Solicitud de Compra de Combustible</h1>
            <p class="text-muted">Inicie el proceso de compra indicando la cantidad, proveedor y detalles de la carga.</p>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="card-title m-0">Detalles de la Solicitud</h5>
        </div>
        <div class="card-body">
            <!-- Formulario de Solicitud -->
            <form action="{{ route('combustible.storeCompra') }}" method="POST">
                @csrf
                <div class="row g-3">
                    
                     <!-- Planta Destino (Carga) -->
                    <div class="col-md-6">
                        <label for="planta_destino_id" class="form-label">Planta de Carga/Destino</label>
                        <select class="form-select" id="planta_destino_id" name="planta_destino_id" required>
                            <option value="">Seleccione una Planta</option>
                            {{-- Placeholder: Iterar sobre una colección de plantas --}}
                            @foreach($plantas as $planta)
                                <option value="{{ $planta->id }}">{{ $planta->alias ?? $planta->nombre }}</option>
                            @endforeach
                         </select>
                    </div>

                    <!-- Fecha Requerida (Día) -->
                    <div class="col-md-6">
                        <label for="fecha_requerida" class="form-label">Fecha Requerida de Carga</label>
                        <input type="date" class="form-control" id="fecha_requerida" name="fecha_requerida" min="{{ date('Y-m-d') }}" required>
                        <div class="form-text">Día en el que se debe realizar la carga.</div>
                    </div>                 
                    
                    
                    
                    <!-- Litros -->
                    <div class="col-md-6">
                        <label for="litros" class="form-label">Cantidad (Litros)</label>
                        <input type="number" class="form-control" id="litros" name="litros" value="{{ old('litros') }}" min="1" required>
                    </div>
                    
                    <!-- Tipo Compra -->
                    <div class="col-md-6">
                        <label for="tipo">Tipo Compra</label>
                        <select name="tipo" id="tipo" class="form-select" required>
                            <option value="INDUSTRIAL" @if(old('tipo') == 'INDUSTRIAL') selected @endif>DIESEL INDUSTRIAL</option>
                            <option value="M.G.O." @if(old('tipo') == 'M.G.O.') selected @endif>DIESEL MARINO (M.G.O.)</option>
                        </select>
                    </div>

                    <!-- Switch para FLETE -->
                    <div class="col-md-6 d-flex align-items-center">
                        <div class="form-check form-switch pt-4">
                            <input class="form-check-input" type="checkbox" id="es_flete_switch" name="es_flete" value="1" @if(old('es_flete')) checked @endif>
                            <label class="form-check-label fw-bold text-danger" for="es_flete_switch">
                                Unidad de Flete / Transporte Externo
                            </label>
                        </div>
                    </div>
                </div>

                <h5 class="mt-4 mb-3 text-secondary">Detalles de Logística</h5>
                <div class="row g-3">
                    <!-- Contenedor para UNIDAD INTERNA (Visible por defecto, con SELECTS) -->
                    <div id="unidades-internas" class="row g-3" style="display: {{ old('es_flete') ? 'none' : 'flex' }};">
                        
                        <!-- Unidad de Despacho (Vehículo) - SELECT (Interno) -->
                        <div class="col-md-6">
                            <label for="chofer">Unidad</label>
                            <select name="vehiculo_id" id="vehiculo_id" class="form-select" required>
                                <option value="">Seleccione un Vehiculo</option>
                                @foreach($vehiculos as $vehiculo)
                                    @if($vehiculo->tipo==3)
                                        <option value="{{ $vehiculo->id }}">{{ $vehiculo->flota }} {{ $vehiculo->placa }}</option>
                                    @endif
                                @endforeach 
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="chofer">Cisterna</label>
                            <select name="cisterna_id" id="cisterna_id" class="form-select" required>
                                <option value="">Seleccione una cisterna</option>
                                @foreach($vehiculos as $cisterna)
                                    @if($cisterna->tipo==2)
                                        <option value="{{ $cisterna->id }}">{{ $cisterna->flota }} {{ $cisterna->placa }}</option>
                                    @endif
                                @endforeach 
                            </select>
                        </div>
                        
                        <!-- Chofer - SELECT (Interno) -->
                        <div class="col-md-6">
                            <label for="chofer_id" class="form-label">Chofer (propio)</label>
                            <select class="form-select" id="chofer_id" name="chofer_id">
                                <option value="">Seleccione un Chofer</option>
                                @foreach($choferes as $chofer)
                                    <option value="{{ $chofer->id }}" @if(old('chofer_id') == $chofer->id) selected @endif>{{ $chofer->persona->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Ayudante - SELECT (Interno) -->
                        <div class="col-md-6">
                            <label for="ayudante" class="form-label">Ayudante (propio) <small>(Opcional)</small></label>
                            <select class="form-select" id="ayudante" name="ayudante">
                                <option value="">Seleccione un Ayudante</option>
                                @foreach($ayudantes as $ayudante)
                                    <option value="{{ $ayudante->id }}" @if(old('ayudante') == $ayudante->id) selected @endif>{{ $ayudante->persona->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                    </div>
                    
                    <!-- Contenedor para FLETE (Oculto por defecto, con TEXT INPUTS) -->
                    <div id="unidades-flete" class="row g-3" style="display: {{ old('es_flete') ? 'flex' : 'none' }};">
                        
                        <!-- Otro Vehículo - TEXT INPUT -->
                        <div class="col-md-6">
                            <label for="otro_vehiculo" class="form-label">Unidad (Externo)</label>
                            <input type="text" class="form-control" id="otro_vehiculo" name="otro_vehiculo" value="{{ old('otro_vehiculo') }}" placeholder="Ej: Placa ABC-123 o Nombre de la unidad">
                        </div>

                        <!-- Otro Chofer - TEXT INPUT -->
                        <div class="col-md-6">
                            <label for="otro_chofer" class="form-label">Chofer (Externo)</label>
                            <input type="text" class="form-control" id="otro_chofer" name="otro_chofer" value="{{ old('otro_chofer') }}" placeholder="Nombre del Chofer">
                        </div>

                        <!-- Otro Ayudante - TEXT INPUT -->
                        <div class="col-md-6">
                            <label for="otro_ayudante" class="form-label">Ayudante (Esterno)</label>
                            <input type="text" class="form-control" id="otro_ayudante" name="otro_ayudante" value="{{ old('otro_ayudante') }}" placeholder="Nombre del Ayudante">
                        </div>

                    </div>
                    
                    <!-- Observaciones (siempre visible) -->
                    <div class="col-md-12 mt-4">
                        <label for="observaciones">Observaciones</label>
                        <textarea name="observaciones" id="observaciones" class="form-control" cols="30" rows="5">@if(old('observaciones')){{ old('observaciones') }}@endif</textarea>
                    </div>

                </div>

                <hr class="my-4">

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-file-earmark-plus me-2"></i> Crear Solicitud y Planificar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const esFleteSwitch = document.getElementById('es_flete_switch');
        const unidadesInternasDiv = document.getElementById('unidades-internas');
        const unidadesFleteDiv = document.getElementById('unidades-flete');

        // Selects de unidades internas
        const vehiculoSelect = document.getElementById('vehiculo_id');
        const choferSelect = document.getElementById('chofer_id');
        const ayudanteSelect = document.getElementById('ayudante'); // ID original: ayudante
        
        // Inputs de flete
        const otroVehiculoInput = document.getElementById('otro_vehiculo');
        const otroChoferInput = document.getElementById('otro_chofer');
        
        // Función para alternar la visibilidad y el atributo 'required'
        function toggleFleteFields() {
            const isFlete = esFleteSwitch.checked;

            if (isFlete) {
                // Modo FLETE
                unidadesInternasDiv.style.display = 'none';
                unidadesFleteDiv.style.display = 'flex';
                
                // Desactivar 'required' para selects internos y limpiar
                vehiculoSelect.removeAttribute('required');
                choferSelect.removeAttribute('required');
                vehiculoSelect.value = '';
                choferSelect.value = '';
                ayudanteSelect.value = ''; // Opcional, solo se limpia

                // Establecer 'required' para inputs de flete (Unidad, Chofer, Proveedor)
                otroVehiculoInput.setAttribute('required', 'required');
                otroChoferInput.setAttribute('required', 'required');
                
            } else {
                // Modo INTERNO
                unidadesInternasDiv.style.display = 'flex';
                unidadesFleteDiv.style.display = 'none';

                // Establecer 'required' para selects internos
                vehiculoSelect.setAttribute('required', 'required');
                choferSelect.setAttribute('required', 'required');
                
                // Desactivar 'required' para inputs de flete y limpiar
                otroVehiculoInput.removeAttribute('required');
                otroChoferInput.removeAttribute('required');
                
                otroVehiculoInput.value = '';
                otroChoferInput.value = '';
                document.getElementById('otro_ayudante').value = ''; // Limpiar ayudante opcional
            }
        }

        // Agregar listener al switch
        esFleteSwitch.addEventListener('change', toggleFleteFields);

        // Asegurar que los campos 'required' iniciales se apliquen si es necesario
        if (!esFleteSwitch.checked) {
            vehiculoSelect.setAttribute('required', 'required');
            choferSelect.setAttribute('required', 'required');
        }
        
        // Ejecutar la función para asegurar el estado inicial correcto (manejo de old() data)
        toggleFleteFields(); 
    });
</script>
@endpush
