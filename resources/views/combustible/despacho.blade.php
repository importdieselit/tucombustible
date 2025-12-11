@extends('layouts.app')

@section('title', 'Despacho de Combustible')

@section('content')
<div class="container-fluid mt-4">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <h3 class="text-themecolor">Despacho de Combustible</h3>
        </div>
        <div class="col-md-6 align-self-center">
            <div class="d-flex justify-content-end">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
                    <li class="breadcrumb-item active">Despacho de Combustible</li>
                </ol>
            </div>
        </div>
    </div>
<div class="card shadow-sm">
        <div class="card-body text-center">
            <h5 class="card-title">Precarga de Cisterna</h5>
            <p class="card-text">Si necesita cargar combustible en una cisterna para un futuro despacho.</p>
            <a href="{{ route('combustible.precarga') }}" class="btn btn-outline-info">Realizar Precarga</a>
        </div>
    </div>
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="card-title m-0">Registrar Nuevo Despacho</h5>
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
 
            <form action="{{ route('combustible.storeDespacho') }}" method="POST">
                @csrf

                <div class="row">
                    <!-- Campo Depósito -->
                    <div class="col-md-6 mb-3">
                        <label for="deposito_id" class="form-label">Tanque <span class="text-danger">*</span></label>
                        <select class="form-select @error('deposito_id') is-invalid @enderror" id="deposito_id" name="deposito_id" required>
                            <option value="">Seleccione un depósito</option>
                            @foreach ($depositos as $deposito)
                                <option value="{{ $deposito->id }}" {{ $deposito->id == 3 ? 'selected' : '' }}>
                                    {{ $deposito->serial }} (Nivel: {{ $deposito->nivel_actual_litros }} L / {{ $deposito->capacidad_litros }} L)
                                </option>
                            @endforeach
                        </select>
                        @error('deposito_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Campo Cantidad -->
                    <div class="col-md-6 mb-3">
                        <label for="cantidad_litros" class="form-label">Cantidad (Litros) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control @error('cantidad_litros') is-invalid @enderror" id="cantidad_litros" name="cantidad_litros" value="{{ old('cantidad_litros') }}" required>
                        @error('cantidad_litros')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Tipo de Despacho (radio buttons) -->
                    <div class="col-md-12 mb-3">
                        <label class="form-label">Despacho para <span class="text-danger">*</span></label>
                        <div class="d-flex">
                            <div class="form-check me-4">
                                <input class="form-check-input" type="radio" name="tipo_despacho" id="tipoVehiculo" value="vehiculo" {{ old('tipo_despacho') == 'vehiculo' ? 'checked' : '' }}>
                                <label class="form-check-label" for="tipoVehiculo">Vehículo Particular</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo_despacho" id="tipoCliente" value="cliente" {{ old('tipo_despacho') == 'cliente' ? 'checked' : '' }}>
                                <label class="form-check-label" for="tipoCliente">Cisterna</label>
                            </div>
                        </div>
                        @error('tipo_despacho')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Bloque de campos para Vehículo Propio -->
                <div id="vehiculo-block" style="display: none;">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="vehiculo_id" class="form-label">Vehículo Existente</label>
                            <select class="form-select @error('vehiculo_id') is-invalid @enderror" id="vehiculo_id" name="vehiculo_id">
                                <option value="">Seleccione un vehículo</option>
                                @foreach ($vehiculos as $vehiculo)
                                    <option value="{{ $vehiculo->id }}" {{ old('vehiculo_id') == $vehiculo->id ? 'selected' : '' }}>
                                        {{ $vehiculo->placa }} - {{ $vehiculo->marca }} {{ $vehiculo->modelo }}
                                    </option>
                                @endforeach
                            </select>
                            @error('vehiculo_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-check mt-5">
                                <input class="form-check-input" type="checkbox" id="nuevoVehiculoCheck">
                                <label class="form-check-label" for="nuevoVehiculoCheck">
                                    El vehículo no está en la base de datos
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Campos para un nuevo vehículo (visibles si el checkbox está marcado) -->
                    <div id="nuevo-vehiculo-fields" class="row" style="display: none;">
                        <div class="col-md-6 mb-3">
                            <label for="placa" class="form-label">Placa <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('placa') is-invalid @enderror" id="placa" name="placa" value="{{ old('placa') }}">
                            @error('placa')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="nombre_despacho" class="form-label">Nombre de a quien se Despachó <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('nombre_despacho') is-invalid @enderror" id="nombre_despacho" name="nombre_despacho" value="{{ old('nombre_despacho') }}">
                            @error('nombre_despacho')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Bloque de campos para Cliente -->
                <div id="cliente-block" style="display: none;">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="cliente_id" class="form-label">Cliente <span class="text-danger">*</span></label>
                            <select class="form-select @error('cliente_id') is-invalid @enderror" id="cliente_id" name="cliente_id">
                                <option value="">Seleccione un cliente</option>
                                @foreach ($clientes as $cliente)
                                    <option value="{{ $cliente->id }}" {{ old('cliente_id') == $cliente->id ? 'selected' : '' }}>
                                        {{ $cliente->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            @error('cliente_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="cisterna_id" class="form-label">Cisterna Utilizada <span class="text-danger">*</span></label>
                            <select class="form-select @error('cisterna_id') is-invalid @enderror" id="cisterna_id" name="cisterna_id">
                                <option value="">Seleccione una cisterna</option>
                                @foreach ($cisternas as $cisterna)
                                    <option value="{{ $cisterna->id }}" {{ old('cisterna_id') == $cisterna->id ? 'selected' : '' }}>
                                        {{ $cisterna->flota }} {{ $cisterna->placa }}
                                    </option>
                                @endforeach
                            </select>
                            @error('cisterna_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Campo Fecha -->
                    <div class="col-md-6 mb-3">
                        <label for="fecha" class="form-label">Fecha <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control @error('fecha') is-invalid @enderror" id="fecha" name="fecha" value="{{ old('fecha', $hoy) }}" required>
                        @error('fecha')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Campo Observaciones -->
                    <div class="col-md-6 mb-3">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="3">{{ old('observaciones') }}</textarea>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary me-2">Registrar Despacho</button>
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
    $(document).ready(function() {
        // Función para mostrar/ocultar los bloques principales
        function toggleBlocks(tipo) {
            if (tipo === 'vehiculo') {
                $('#vehiculo-block').show();
                $('#cliente-block').hide();
                // Opcional: limpiar campos del bloque oculto
                $('#cliente_id').val('');
                $('#vehiculo_surtido').val('');
                $('#cisterna_id').val('');
                // Y manejar la visibilidad inicial del bloque de nuevo vehículo
                toggleNuevoVehiculoFields();
            } else if (tipo === 'cliente') {
                $('#cliente-block').show();
                $('#vehiculo-block').hide();
                // Opcional: limpiar campos del bloque oculto
                $('#vehiculo_id').val('');
                $('#nuevoVehiculoCheck').prop('checked', false);
                $('#placa').val('');
                $('#marca').val('');
                $('#modelo').val('');
                $('#nombre_despacho').val('');
            }
        }

        // Función para mostrar/ocultar los campos de "nuevo vehículo"
        function toggleNuevoVehiculoFields() {
            if ($('#nuevoVehiculoCheck').is(':checked')) {
                $('#nuevo-vehiculo-fields').show();
                // Limpiar y deshabilitar el select de vehículos existentes para evitar conflictos
                $('#vehiculo_id').val('').prop('disabled', true);
            } else {
                $('#nuevo-vehiculo-fields').hide();
                // Habilitar el select de vehículos existentes
                $('#vehiculo_id').prop('disabled', false);
                // Opcional: limpiar los campos del nuevo vehículo
                $('#placa').val('');
                $('#marca').val('');
                $('#modelo').val('');
                $('#nombre_despacho').val('');
            }
        }

        // Evento change para los radio buttons
        $('input[name="tipo_despacho"]').on('change', function() {
            toggleBlocks($(this).val());
        });

        // Evento change para el checkbox de nuevo vehículo
        $('#nuevoVehiculoCheck').on('change', function() {
            toggleNuevoVehiculoFields();
        });

        // Ejecutar al cargar la página para mostrar el estado inicial correcto
        const initialTipo = $('input[name="tipo_despacho"]:checked').val();
        if (initialTipo) {
            toggleBlocks(initialTipo);
        }
    });
</script>
@endpush
