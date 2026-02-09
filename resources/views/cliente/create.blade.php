@extends('layouts.app')

@section('title', 'Crear Cliente')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="mb-2">Crear Cliente</h1>
        <p class="text-muted">Introduce los datos del nuevo cliente para agregarlo al sistema.</p>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="card-title m-0">Formulario de Cliente</h5>
        <div>
            <a href="{{ route('clientes.index') }}" class="btn btn-secondary">
                <i class="bi bi-list me-1"></i>
                Volver al Listado
            </a>
        </div>
    </div>
    <div class="card-body">
        @if(Session::has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ Session::get('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form action="{{ route('clientes.store') }}" method="POST">
            @csrf
            
            <div class="row">
                <!-- Nombre -->
                <div class="col-md-6 mb-3">
                    <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" value="{{ old('nombre') }}" required>
                    @error('nombre')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <!-- RUC -->
                <div class="col-md-6 mb-3">
                    <label for="rif" class="form-label">RIF</label>
                    <input type="text" class="form-control @error('rif') is-invalid @enderror" id="rif" name="rif" value="{{ old('rif') }}">
                    @error('rif')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Cédula -->
                <div class="col-md-6 mb-3">
                    <label for="contacto" class="form-label">Persona de Contacto</label>
                    <input type="text" class="form-control @error('contacto') is-invalid @enderror" id="contacto" name="contacto" value="{{ old('contacto') }}">
                    @error('contacto')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <!-- Teléfono -->
                <div class="col-md-6 mb-3">
                    <label for="telefono" class="form-label">Teléfono</label>
                    <input type="text" class="form-control @error('telefono') is-invalid @enderror" id="telefono" name="telefono" value="{{ old('telefono') }}">
                    @error('telefono')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Email -->
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <!-- Dirección -->
                <div class="col-md-12 mb-3">
                    <label for="direccion" class="form-label">Dirección</label>
                    <textarea class="form-control @error('direccion') is-invalid @enderror" id="direccion" name="direccion" rows="3">{{ old('direccion') }}</textarea>
                    @error('direccion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary mt-3">Guardar Cliente</button>
        </form>
    </div>
</div>
@endsection
