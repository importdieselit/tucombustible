@extends('layouts.app')

@section('title', 'Precarga de Cisterna')

@section('content')
<div class="container-fluid mt-4">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <h3 class="text-themecolor">Precarga de Cisterna</h3>
        </div>
        <div class="col-md-6 align-self-center">
            <div class="d-flex justify-content-end">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('combustible.index') }}">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('combustible.despacho') }}">Despacho</a></li>
                    <li class="breadcrumb-item active">Precarga de Cisterna</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="card-title m-0">Registrar Precarga</h5>
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
            
            <form action="{{ route('combustible.storePrecarga') }}" method="POST">
                @csrf
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="fecha_movimiento" class="form-label">Fecha de Movimiento</label>
                        <input type="date" class="form-control" id="fecha_movimiento" name="fecha_movimiento" value="{{ old('fecha_movimiento', \Carbon\Carbon::now()->format('Y-m-d')) }}" required>
                        @error('fecha_movimiento')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="vehiculo_id" class="form-label">Vehículo (Cisterna)</label>
                        <select class="form-select" id="vehiculo_id" name="vehiculo_id" required>
                            <option value="">Seleccione una cisterna</option>
                            @foreach($vehiculos_cisterna as $vehiculo)
                                <option value="{{ $vehiculo->id }}" {{ old('vehiculo_id') == $vehiculo->id ? 'selected' : '' }}>
                                   ({{ $vehiculo->flota }}) {{ $vehiculo->placa }} - @if(!is_null($vehiculo->marca())) {{ $vehiculo->marca()->marca }} @if(!is_null($vehiculo->modelo())) {{ $vehiculo->modelo()->modelo }} @endif @endif ({{ $vehiculo->capacidad_litros }} L)
                                </option>
                            @endforeach
                        </select>
                        @error('vehiculo_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="deposito_id" class="form-label">Depósito Origen</label>
                        <select class="form-select" id="deposito_id" name="deposito_id" required>
                            <option value="">Seleccione un depósito</option>
                            @foreach($depositos as $deposito)
                                <option value="{{ $deposito->id }}" {{ old('deposito_id') == $deposito->id ? 'selected' : '' }}>
                                    {{ $deposito->ubicacion }} {{ $deposito->serial }} ({{ number_format($deposito->nivel_actual_litros, 2, ',', '.') }}/{{ number_format($deposito->capacidad_litros, 2, ',', '.') }} L)
                                </option>
                            @endforeach
                        </select>
                        @error('deposito_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="cantidad_litros" class="form-label">Cantidad de Litros a Cargar</label>
                        <input type="number" step="0.01" class="form-control" id="cantidad_litros" name="cantidad_litros" value="{{ old('cantidad_litros') }}" required>
                        @error('cantidad_litros')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Registrar Precarga</button>
            </form>
        </div>
    </div>
</div>
@endsection
