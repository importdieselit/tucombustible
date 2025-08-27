@extends('layouts.app')

@section('title', 'Recarga de Combustible')

@section('content')
<div class="container-fluid mt-4">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <h3 class="text-themecolor">Recarga de Combustible</h3>
        </div>
        <div class="col-md-6 align-self-center">
            <div class="d-flex justify-content-end">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
                    <li class="breadcrumb-item active">Recarga de Combustible</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="card-title m-0">Registrar Nueva Recarga</h5>
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

            <form action="{{ route('combustible.storeRecarga') }}" method="POST">
                @csrf

                <div class="row">
                    <!-- Campo Depósito -->
                    <div class="col-md-6 mb-3">
                        <label for="deposito_id" class="form-label">Depósito <span class="text-danger">*</span></label>
                        <select class="form-select @error('deposito_id') is-invalid @enderror" id="deposito_id" name="deposito_id" required>
                            <option value="">Seleccione un depósito</option>
                            @foreach ($depositos as $deposito)
                                <option value="{{ $deposito->id }}" {{ old('deposito_id') == $deposito->id ? 'selected' : '' }}>
                                    {{ $deposito->nombre }} (Nivel: {{ $deposito->nivel_actual_litros }} L / {{ $deposito->capacidad_litros }} L)
                                </option>
                            @endforeach
                        </select>
                        @error('deposito_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Campo Proveedor -->
                    <div class="col-md-6 mb-3">
                        <label for="proveedor_id" class="form-label">Proveedor <span class="text-danger">*</span></label>
                        <select class="form-select @error('proveedor_id') is-invalid @enderror" id="proveedor_id" name="proveedor_id" required>
                            <option value="">Seleccione un proveedor</option>
                            @foreach ($proveedores as $proveedor)
                                <option value="{{ $proveedor->id }}" {{ old('proveedor_id') == $proveedor->id ? 'selected' : '' }}>
                                    {{ $proveedor->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('proveedor_id')
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
                     <div class="col-md-6 mb-3">
                        <label for="fecha" class="form-label">Fecha <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control @error('fecha') is-invalid @enderror" id="fecha" name="fecha" value="{{ old('fecha', $hoy) }}" required>
                        @error('fecha')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Campo Observaciones -->
                    <div class="col-md-12 mb-3">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="3">{{ old('observaciones') }}</textarea>
                    </div>

                    <!-- Botones de acción -->
                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary me-2">Registrar Recarga</button>
                        <a href="{{ route('dashboard') }}" class="btn btn-secondary">Cancelar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
