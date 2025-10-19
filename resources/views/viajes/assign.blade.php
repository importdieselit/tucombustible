@extends('layouts.app')

@section('title', 'Asignar Recursos a Viaje')

@section('content')
<div class="container mt-5">
    <div class="card shadow-lg">
        <div class="card-header bg-danger text-white">
            <h3 class="mb-0"><i class="bi bi-truck me-2"></i> Asignación de Recursos para Viaje #{{ $viaje->id }}</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <strong>Viaje Pendiente:</strong> Destino a **{{ $viaje->destino_ciudad }}** con salida el **{{ $viaje->fecha_salida }}**.
            </div>
            
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            
            <!-- El formulario enviará a ViajesController@processAssignment -->
            <form action="{{ route('viajes.processAssignment', $viaje->id) }}" method="POST">
                @csrf
                @method('PUT') <!-- Usamos PUT para la actualización de recursos -->
                
                <h4 class="mt-4 mb-3 text-danger border-bottom pb-1">Asignación de Personal</h4>

                <div class="row g-3">
                    <!-- Chofer -->
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
                    
                    <!-- Ayudante -->
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
                    
                    {{-- <!-- Custodia -->
                    <div class="col-md-3">
                        <label for="custodia_count" class="form-label fw-bold">Cant. Custodia</label>
                        <input type="number" name="custodia_count" id="custodia_count" class="form-control @error('custodia_count') is-invalid @enderror" 
                            value="{{ old('custodia_count', $viaje->custodia_count ?? 0) }}" min="0">
                        @error('custodia_count')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div> --}}
                </div>

                <h4 class="mt-5 mb-3 text-danger border-bottom pb-1">Asignación de Vehículo</h4>
                
                <!-- ID del Vehículo -->
                <div class="mb-4">
                    <label for="vehiculo_id" class="form-label fw-bold">Vehículo Asignado</label>
                    <select name="vehiculo_id" id="vehiculo_id" class="form-select @error('vehiculo_id') is-invalid @enderror" required>
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


                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-danger btn-lg mt-3">
                        <i class="bi bi-check-circle me-2"></i> Confirmar Asignación y Pasar a Viáticos
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
