@extends('layouts.app')

@section('title', 'Editar Cliente')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="mb-2">Editar Cliente: {{ $cliente->nombre }}</h1>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="card-title m-0">Modificar Datos</h5>
        <a href="{{ route('clientes.index') }}" class="btn btn-secondary btn-sm">Volver</a>
    </div>
    <div class="card-body">
        <form action="{{ route('clientes.update', $cliente->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label">Razón Social <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nombre" value="{{ old('nombre', $cliente->nombre) }}" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">RIF <span class="text-danger">*</span></label>
                    <div class="input-group">
                        @php 
                            // Separamos el RIF (Letra y Número) para el formulario
                            $rif_parts = explode('-', $cliente->rif);
                            $tipo = $rif_parts[0] ?? 'J';
                            $numero = $rif_parts[1] ?? '';
                        @endphp
                        <select class="form-select" name="rif_tipo" style="max-width: 80px;">
                            @foreach(['J','G','V','E'] as $t)
                                <option value="{{ $t }}" {{ old('rif_tipo', $tipo) == $t ? 'selected' : '' }}>{{ $t }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="rif_num" class="form-control" value="{{ old('rif_num', $numero) }}" required onkeypress="return isNumber(event)">
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Persona de Contacto <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="contacto" value="{{ old('contacto', $cliente->contacto) }}" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Teléfono <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="telefono" value="{{ old('telefono', $cliente->telefono) }}" required onkeypress="return isNumber(event)">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" value="{{ old('email', $cliente->email) }}">
                </div>
            </div>
            <button type="submit" class="btn btn-success mt-3">Actualizar Cliente</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function isNumber(evt) {
        evt = (evt) ? evt : window.event;
        var charCode = (evt.which) ? evt.which : evt.keyCode;
        return !(charCode > 31 && (charCode < 48 || charCode > 57)) ? true : false;
    }
</script>
@endpush