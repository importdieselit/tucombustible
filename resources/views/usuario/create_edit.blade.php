@extends('layouts.app')
@section('title', isset($item) ? 'Editar Usuario: ' . $item->name : 'Crear Nuevo Usuario')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">{{ isset($item) ? 'Editar ' . $item->name : 'Crear Usuario' }}</h5>
        </div>
        <div class="card-body">
            
            <form action="{{ isset($item) ? route('usuarios.update', $item->id) : route('usuarios.store') }}" 
                  method="POST">
                @csrf
                @if(isset($item)) @method('PUT') @endif

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label for="name" class="form-label fw-bold">Nombre Completo (*)</label>
                        <input type="text" class="form-control" id="name" name="name" required
                               value="{{ old('name', $item->name ?? '') }}">
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <label for="email" class="form-label fw-bold">Correo Electrónico (*)</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="{{ old('email', $item->email ?? '') }}">
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <label for="password" class="form-label fw-bold">Contraseña {{ isset($item) ? '(Dejar vacío para no cambiar)' : '(*)' }}</label>
                        <input type="password" class="form-control" id="password" name="password" 
                               {{ isset($item) ? '' : 'required' }}>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <label for="perfil" class="form-label fw-bold">Perfil Base (*)</label>
                        <select class="form-control" id="perfil" name="perfil" required>
                            <option value="">-- Seleccione un Perfil --</option>
                            @foreach($perfiles as $perfil)
                            
                                <option value="{{ $perfil->id }}" 
                                    {{ old('perfil', $item->perfil ?? '') == $perfil->nombre ? 'selected' : '' }}>
                                    {{ Str::ucfirst($perfil->nombre) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-4" id="cliente-selector-group" 
                         style="display: none;"> {{-- Oculto por defecto --}}
                        <label for="id_cliente" class="form-label fw-bold text-danger">Cliente Asociado (*)</label>
                        <select class="form-control" id="id_cliente" name="id_cliente">
                            <option value="">-- Seleccione el Cliente --</option>
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id }}" 
                                    {{ old('id_cliente', $item->id_cliente ?? '') == $cliente->id ? 'selected' : '' }}>
                                    {{ $cliente->name }}    
                                </option>
                            @endforeach
                        </select>
                        <small class="text-danger">Solo requerido para el perfil 'cliente'.</small>
                    </div>

                    {{-- Este div es un placeholder para que la fila se vea bien cuando el selector de cliente está oculto --}}
                    <div class="col-md-6 mb-4" id="cliente-placeholder-group"></div> 
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-lg btn-success me-2">
                        <i class="fa-solid fa-save"></i> {{ isset($item) ? 'Guardar Cambios' : 'Crear Usuario' }}
                    </button>
                    @if(isset($item))
                        <a href="{{ route('usuarios.edit_permissions', $item->id) }}" class="btn btn-lg btn-warning">
                            <i class="fa-solid fa-user-lock"></i> Permisos Específicos
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const perfilSelector = document.getElementById('perfil');
        const clienteGroup = document.getElementById('cliente-selector-group');
        const clienteInput = document.getElementById('id_cliente');
        const clientePlaceholder = document.getElementById('cliente-placeholder-group');
        const perfilCliente = 3; // Obtiene el nombre 'cliente' desde el controlador

        function toggleClienteSelector() {
            if (perfilSelector.value === perfilCliente) {
                // Mostrar y hacer requerido para el perfil 'cliente'
                clienteGroup.style.display = 'block';
                clienteInput.setAttribute('required', 'required');
                clientePlaceholder.style.display = 'none';
            } else {
                // Ocultar y remover requerido para otros perfiles
                clienteGroup.style.display = 'none';
                clienteInput.removeAttribute('required');
                // Esto es crucial para que el backend no reciba un id_cliente que podría confundir la lógica
                clienteInput.value = ''; 
                clientePlaceholder.style.display = 'block'; 
            }
        }

        // Ejecutar al cargar y al cambiar el selector
        toggleClienteSelector();
        perfilSelector.addEventListener('change', toggleClienteSelector);
    });
</script>
@endpush
@endsection