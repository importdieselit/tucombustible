@extends('layouts.app')
@section('title', isset($item) ? 'Editar Usuario: ' . $item->name : 'Crear Nuevo Usuario')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white p-2">
            <ul class="nav nav-pills" id="userTab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active text-white" id="datos-tab" data-bs-toggle="tab" href="#datos" role="tab">
                        <i class="fas fa-user-edit me-1"></i> {{ isset($item) ? 'Editar ' . $item->name : 'Crear Usuario' }}
                    </a>
                </li>
                @if(isset($item))
                <li class="nav-item">
                    <a class="nav-link text-white" id="permisos-tab" data-bs-toggle="tab" href="#permisos" role="tab">
                        <i class="fas fa-user-lock me-1"></i> Matriz de Permisos
                    </a>
                </li>
                @endif
            </ul>
        </div>
        
        <div class="card-body">
            <div class="tab-content" id="userTabContent">
                <div class="tab-pane fade show active" id="datos" role="tabpanel">
                    <form action="{{ isset($item) ? route('usuarios.update', $item->id) : route('usuarios.store') }}" method="POST">
                        @csrf
                        @if(isset($item)) @method('PUT') @endif

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label for="name" class="form-label fw-bold">Nombre Completo (*)</label>
                                <input type="text" class="form-control" id="name" name="name" required value="{{ old('name', $item->name ?? '') }}">
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <label for="email" class="form-label fw-bold">Correo Electrónico (*)</label>
                                <input type="email" class="form-control" id="email" name="email" required value="{{ old('email', $item->email ?? '') }}">
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <label for="password" class="form-label fw-bold">Contraseña {{ isset($item) ? '(Vacío para no cambiar)' : '(*)' }}</label>
                                <input type="password" class="form-control" id="password" name="password" {{ isset($item) ? '' : 'required' }}>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <label for="perfil" class="form-label fw-bold">Perfil Base (*)</label>
                                <select class="form-control" id="perfil" name="perfil" required>
                                    <option value="">-- Seleccione un Perfil --</option>
                                    @foreach($perfiles as $perfil)
                                        <option value="{{ $perfil->id }}" {{ old('perfil', $item->id_perfil ?? '') == $perfil->id ? 'selected' : '' }}>
                                            {{ Str::ucfirst($perfil->nombre) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-4" id="cliente-selector-group" style="display: none;">
                                <label for="id_cliente" class="form-label fw-bold text-danger">Cliente Asociado (*)</label>
                                <select class="form-control" id="id_cliente" name="id_cliente">
                                    <option value="">-- Seleccione el Cliente --</option>
                                    @foreach($clientes as $cliente)
                                        <option value="{{ $cliente->id }}" {{ old('id_cliente', $item->id_cliente ?? '') == $cliente->id ? 'selected' : '' }}>
                                            {{ $cliente->name }}    
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-lg btn-success">
                                <i class="fa-solid fa-save"></i> {{ isset($item) ? 'Guardar Datos' : 'Crear Usuario' }}
                            </button>
                        </div>
                    </form>
                </div>

                @if(isset($item))
                <div class="tab-pane fade" id="permisos" role="tabpanel">
                    <div class="alert alert-warning mb-3">
                        <i class="fas fa-info-circle"></i> Los cambios se guardan automáticamente al activar o desactivar cada switch.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Módulo</th>
                                    <th class="text-center">Lectura</th>
                                    <th class="text-center">Escritura</th>
                                    <th class="text-center">Eliminación</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($modulos as $modulo)
                                <tr>
                                    <td class="fw-bold">{{ $modulo->nombre }}</td>
                                    @foreach(['read', 'update', 'delete'] as $accion)
                                    <td class="text-center">
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input permission-switch" type="checkbox" 
                                                role="switch"
                                                id="sw_{{ $modulo->id }}_{{ $accion }}"
                                                data-modulo="{{ $modulo->id }}"
                                                data-accion="{{ $accion }}"
                                                data-user="{{ $item->id }}"
                                                {{ $item->canAccess($accion, $modulo->id) ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- Lógica del Selector de Cliente ---
        const perfilSelector = document.getElementById('perfil');
        const clienteGroup = document.getElementById('cliente-selector-group');
        const clienteInput = document.getElementById('id_cliente');
        const perfilCliente = "3"; // Asegúrate que coincida con el ID real del perfil cliente

        function toggleClienteSelector() {
            if (perfilSelector.value === perfilCliente) {
                clienteGroup.style.display = 'block';
                clienteInput.setAttribute('required', 'required');
            } else {
                clienteGroup.style.display = 'none';
                clienteInput.removeAttribute('required');
                clienteInput.value = ''; 
            }
        }
        perfilSelector.addEventListener('change', toggleClienteSelector);
        toggleClienteSelector();

        // --- Lógica AJAX para Permisos en Tiempo Real ---
        $('.permission-switch').on('change', function() {
            const $switch = $(this);
            const payload = {
                modulo_id: $switch.data('modulo'),
                accion: $switch.data('accion'),
                estado: $switch.is(':checked') ? 1 : 0,
                _token: "{{ csrf_token() }}"
            };

            // Efecto visual de carga
            $switch.addClass('opacity-50');

            $.ajax({
                url: `/usuarios/${$switch.data('user')}/update-single-permission`,
                method: 'POST',
                data: payload,
                success: function(response) {
                    $switch.removeClass('opacity-50');
                    toastr.success('Permiso actualizado con éxito');
                },
                error: function() {
                    $switch.removeClass('opacity-50');
                    $switch.prop('checked', !$switch.is(':checked')); // Revertir si falla
                    toastr.error('Error al actualizar el permiso');
                }
            });
        });
    });
</script>
@endpush
@endsection