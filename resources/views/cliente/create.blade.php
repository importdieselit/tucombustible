@extends('layouts.app')

@section('title', 'Crear Cliente')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="mb-2">Crear Cliente</h1>
        <p class="text-muted">Introduce los datos del nuevo cliente. Los campos con <span class="text-danger">*</span> son obligatorios.</p>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="card-title m-0">Formulario de Registro de Cliente</h5>
        <a href="{{ route('clientes.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-list me-1"></i> Volver al Listado
        </a>
    </div>
    <div class="card-body">
        <form action="{{ route('clientes.store') }}" method="POST">
            @csrf
            
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label for="nombre" class="form-label">Razón Social / Nombre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('nombre') is-invalid @enderror" 
                           id="nombre" name="nombre" value="{{ old('nombre') }}" placeholder="Ej: Distribuidora Gasolín C.A." required>
                    @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">RIF <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <select class="form-select @error('rif_tipo') is-invalid @enderror" name="rif_tipo" style="max-width: 80px;">
                            <option value="J" {{ old('rif_tipo') == 'J' ? 'selected' : '' }}>J</option>
                            <option value="G" {{ old('rif_tipo') == 'G' ? 'selected' : '' }}>G</option>
                            <option value="V" {{ old('rif_tipo') == 'V' ? 'selected' : '' }}>V</option>
                            <option value="E" {{ old('rif_tipo') == 'E' ? 'selected' : '' }}>E</option>
                        </select>
                        <input type="text" name="rif_num" class="form-control @error('rif_num') is-invalid @enderror" 
                               value="{{ old('rif_num') }}" placeholder="Ej: 123456789" required onkeypress="return isNumber(event)">
                        @error('rif_num') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="contacto" class="form-label">Persona de Contacto <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('contacto') is-invalid @enderror" 
                           id="contacto" name="contacto" value="{{ old('contacto') }}" required>
                    @error('contacto') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="telefono" class="form-label">Teléfono <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('telefono') is-invalid @enderror" 
                           id="telefono" name="telefono" value="{{ old('telefono') }}" 
                           placeholder="Ej: 02125556677" required onkeypress="return isNumber(event)">
                    <small class="text-muted">Solo números, sin espacios ni guiones.</small>
                    @error('telefono') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                           id="email" name="email" value="{{ old('email') }}">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                
                <div class="col-md-12 mb-3">
                    <label for="direccion" class="form-label">Dirección <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('direccion') is-invalid @enderror" 
                              id="direccion" name="direccion" rows="2" required>{{ old('direccion') }}</textarea>
                    @error('direccion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" class="btn btn-primary px-5">
                    <i class="bi bi-save me-2"></i>Guardar Cliente
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Función para permitir solo números en el teclado
    function isNumber(evt) {
        evt = (evt) ? evt : window.event;
        var charCode = (evt.which) ? evt.which : evt.keyCode;
        if (charCode > 31 && (charCode < 48 || charCode > 57)) {
            return false;
        }
        return true;
    }
</script>
@endpush