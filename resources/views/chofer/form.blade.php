@extends('layouts.app')

@section('title', isset($chofer) ? 'Editar Chofer' : 'Registrar Chofer')

@section('content')
<div class="container-fluid mt-4">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <h3 class="text-themecolor">{{ isset($chofer) ? 'Editar Chofer' : 'Registrar Chofer' }}</h3>
        </div>
        <div class="col-md-6 align-self-center">
            <div class="d-flex justify-content-end">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('choferes.index') }}">Choferes</a></li>
                    <li class="breadcrumb-item active">{{ isset($chofer) ? 'Editar' : 'Registrar' }}</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="card-title m-0">{{ isset($chofer) ? 'Actualizar Información del Chofer' : 'Registrar Nuevo Chofer' }}</h5>
        </div>
        <div class="card-body">
            @if(Session::has('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ Session::get('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form action="{{ isset($chofer) ? route('choferes.update', $chofer->id) : route('choferes.store') }}" method="POST">
                @csrf
                @if(isset($chofer))
                    @method('PUT')
                @endif

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nombre" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" value="{{ old('nombre', $chofer->persona->nombre ?? '') }}" required>
                        @error('nombre')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="dni" class="form-label">Cédula <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('dni') is-invalid @enderror" id="dni" name="dni" value="{{ old('dni', $chofer->persona->dni ?? '') }}" required>
                        @error('dni')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="dni_exp" class="form-label">Cédula Fecha de Expedición <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('dni_exp') is-invalid @enderror" id="dni_exp" name="dni_exp" value="{{ old('dni_exp', $chofer->persona->dni_exp ?? '') }}" >
                        @error('dni_exp')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <input type="text" class="form-control @error('telefono') is-invalid @enderror" id="telefono" name="telefono" value="{{ old('telefono', $chofer->persona->telefono ?? '') }}">
                        @error('telefono')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <hr>
                <h6 class="mb-3">Información de Documentos del Chofer</h6>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="licencia_numero" class="form-label">Número de Licencia <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('licencia_numero') is-invalid @enderror" id="licencia_numero" name="licencia_numero" value="{{ old('licencia_numero', $chofer->licencia_numero ?? '') }}" >
                        @error('licencia_numero')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="tipo_licencia" class="form-label">Tipo de Licencia <span class="text-danger">*</span></label>
                        <input type="text" name="tipo_licencia" value="{{ old('tipo_licencia', $chofer->tipo_licencia ?? '') }}" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="licencia_vencimiento" class="form-label">Fecha de Vencimiento Licencia <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('licencia_vencimiento') is-invalid @enderror" id="licencia_vencimiento" name="licencia_vencimiento" value="{{ old('licencia_vencimiento', $chofer->licencia_vencimiento ?? '') }}" required>
                        @error('licencia_vencimiento')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="cerificado_medico" class="form-label">Certificado Médico <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="cerificado_medico" name="cerificado_medico" value="{{ old('cerificado_medico', $chofer->cerificado_medico ?? '') }}" >
                    </div>  
                    <div>
                        <label for="cetificado_medico_vencimiento" class="form-label">Fecha de Vencimiento Certificado Médico </label>
                        <input type="date" class="form-control" id="cetificado_medico_vencimiento" name="cetificado_medico_vencimiento" value="{{ old('cetificado_medico_vencimiento', $chofer->cetificado_medico_vencimiento ?? '') }}">

                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="documento_vialidad_numero" class="form-label">Aprobacion de Pdvsa</label>
                        <input type="checkbox" class="form-check-input" id="documento_vialidad_numero" name="documento_vialidad_numero" value="{{ old('documento_vialidad_numero', $chofer->documento_vialidad_numero ?? '1') }}">

                        @error('documento_vialidad_numero')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="documento_vialidad_vencimiento" class="form-label">Fecha de Vencimiento </label>
                        <input type="date" class="form-control @error('documento_vialidad_vencimiento') is-invalid @enderror" id="documento_vialidad_vencimiento" name="documento_vialidad_vencimiento" value="{{ old('documento_vialidad_vencimiento', $chofer->documento_vialidad_vencimiento ?? '') }}">
                        @error('documento_vialidad_vencimiento')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <hr>
                <h6 class="mb-3">Asignación de Vehículo</h6>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="vehiculo_id" class="form-label">Asignar Vehículo</label>
                        <select class="form-select @error('vehiculo_id') is-invalid @enderror" id="vehiculo_id" name="vehiculo_id">
                            <option value="">No asignar</option>
                            @foreach ($vehiculos as $vehiculo)
                                <option value="{{ $vehiculo->id }}" {{ old('vehiculo_id', $chofer->vehiculo_id ?? '') == $vehiculo->id ? 'selected' : '' }}>
                                    {{ $vehiculo->placa }} - {{ $vehiculo->marca }} {{ $vehiculo->modelo }}
                                </option>
                            @endforeach
                        </select>
                        @error('vehiculo_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-12 d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary me-2">
                        {{ isset($chofer) ? 'Actualizar Chofer' : 'Registrar Chofer' }}
                    </button>
                    <a href="{{ route('choferes.index') }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
