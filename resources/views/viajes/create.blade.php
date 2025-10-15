@extends('layouts.app')

@section('title', 'Planificar Nuevo Viaje')

@section('content')
<div class="container mt-5">
    <div class="card shadow-lg">
        <div class="card-header bg-success text-white">
            <h3 class="mb-0"><i class="bi bi-calendar-plus me-2"></i> Planificación y Asignación de Viaje</h3>
        </div>
        <div class="card-body">
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            
            <!-- El formulario enviará a ViajesController@store -->
            <form action="{{ route('viajes.store') }}" method="POST">
                @csrf
                
                <div class="row g-3 mb-4">
                    <!-- Destino -->
                    <div class="col-md-6">
                        <label for="destino_ciudad" class="form-label fw-bold">Ciudad de Destino</label>
                        <select name="destino_ciudad" id="destino_ciudad" class="form-select @error('destino_ciudad') is-invalid @enderror" required>
                            <option value="">Seleccione un destino del Tabulador</option>
                            
                            <!-- Ejemplo: Este array debe ser cargado desde el controlador con las ciudades del TabuladorViatico -->
                            {{-- @php
                                $ciudades_tabulador = ['BARQUISIMETO', 'VALENCIA', 'MARACAIBO', 'PLANTA GUATIRE', 'PLANTA PALITO'];
                            @endphp --}}
                            
                            @foreach($destino as $ciudad)
                                <option value="{{ $ciudad }}" {{ old('destino_ciudad') == $ciudad ? 'selected' : '' }}>{{ $ciudad }}</option>
                            @endforeach
                            
                        </select>
                        @error('destino_ciudad')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <!-- Fecha de Salida -->
                    <div class="col-md-6">  
                        <label for="fecha_salida" class="form-label fw-bold">Fecha de Salida</label>
                        <input type="date" name="fecha_salida" id="fecha_salida" class="form-control @error('fecha_salida') is-invalid @enderror" value="{{ old('fecha_salida') }}" required>
                        @error('fecha_salida')
                            <div class="invalid-feedback">{{ $message }}</div>      
                        @enderror
                        

                </div>

                <h4 class="mt-4 mb-3 text-success border-bottom pb-1">Asignación de Personal</h4>

                <div class="row g-3 mb-4">
                    <!-- Chofer Asignado -->
                    <div class="col-md-6">
                        <label for="chofer_id" class="form-label fw-bold">Chofer Principal</label>
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
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Cantidad de Ayudantes -->
                    <div class="col-md-3">
                        <label for="ayudantes" class="form-label fw-bold">Ayudante</label>
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
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <!-- Cantidad de Custodia/Seguridad -->
                    <div class="col-md-3">
                        <label for="custodia_count" class="form-label fw-bold">Custodia</label>
                        <input type="number" name="custodia_count" id="custodia_count" class="form-control @error('custodia_count') is-invalid @enderror" value="{{ old('custodia_count', 0) }}" min="0">
                        @error('custodia_count')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <h4 class="mt-4 mb-3 text-success border-bottom pb-1">Asignación de Vehículo</h4>
                
                <!-- ID del Vehículo (Campo nuevo para una asignación real) -->
                <div class="mb-4">
                    <label for="vehiculo_id" class="form-label fw-bold">Vehículo Asignado</label>
                    <select name="vehiculo_id" id="vehiculo_id" class="form-select @error('vehiculo_id') is-invalid @enderror" >
                        <option value="">Seleccione el vehículo</option>
                        <!-- Este loop debe cargar los vehículos disponibles -->
                       
                        @foreach($vehiculos as $vehiculo)
                            <option value="{{ $vehiculo['id'] }}" {{ old('vehiculo_id') == $vehiculo['id'] ? 'selected' : '' }}>{{ $vehiculo['placa'] }} - {{ $vehiculo['modelo'] }}</option>
                        @endforeach
                    </select>
                    @error('vehiculo_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>


                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success btn-lg mt-3">
                        <i class="bi bi-save me-2"></i> Crear Viaje y Generar Cuadro de Viáticos
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
