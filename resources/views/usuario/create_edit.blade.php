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
                <!-- TAB 1: DATOS DE USUARIO, PERSONA Y CHOFER -->
                <div class="tab-pane fade show active" id="datos" role="tabpanel">
                    <form action="{{ isset($item) ? route('usuarios.update', $item->id) : route('usuarios.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @if(isset($item)) @method('PUT') @endif

                        <!-- SECCIÓN 1: ACCESO AL SISTEMA -->
                        <h6 class="fw-bold text-primary mb-3"><i class="fas fa-key me-1"></i> Datos de Acceso al Sistema</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="name" class="form-label fw-bold">Usuario / Alias (*)</label>
                                <input type="text" class="form-control" id="name" name="name" required value="{{ old('name', $item->name ?? '') }}">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="email" class="form-label fw-bold">Correo Electrónico (*)</label>
                                <input type="email" class="form-control" id="email" name="email" required value="{{ old('email', $item->email ?? '') }}">
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="password" class="form-label fw-bold">Contraseña {{ isset($item) ? '(Vacío para no cambiar)' : '(*)' }}</label>
                                <input type="password" class="form-control" id="password" name="password" {{ isset($item) ? '' : 'required' }}>
                            </div>
                            
                            <div class="col-md-4 mb-3">
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
                            
                            <div class="col-md-4 mb-3" id="cliente-selector-group" style="display: none;">
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

                        <hr class="my-4">

                        <!-- SECCIÓN 2: INFORMACIÓN PERSONAL / PERSONAL -->
                        <h6 class="fw-bold text-primary mb-3"><i class="fas fa-id-card me-1"></i> Datos de la Persona y Asignación</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="nombre" class="form-label fw-bold">Nombre Completo (*)</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required value="{{ old('nombre', $item->persona->nombre ?? $item->name ?? '') }}">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="dni" class="form-label fw-bold">Cédula / DNI (*)</label>
                                <input type="text" class="form-control" id="dni" name="dni" required value="{{ old('dni', $item->persona->dni ?? '') }}">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="telefono" class="form-label fw-bold">Teléfono</label>
                                <input type="text" class="form-control" id="telefono" name="telefono" value="{{ old('telefono', $item->persona->telefono ?? '') }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="cargo_id" class="form-label fw-bold">Cargo del Personal</label>
                                <select class="form-control" id="cargo_id" name="cargo_id">
                                    <option value="">-- Seleccione Cargo --</option>
                                    @foreach($cargos as $cargo)
                                        <option value="{{ $cargo->id }}" {{ old('cargo_id', $item->persona->cargo_id ?? '') == $cargo->id ? 'selected' : '' }}>
                                            {{ $cargo->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="id_sede" class="form-label fw-bold">Sede / Taller</label>
                                <select class="form-control" id="id_sede" name="id_sede">
                                    <option value="">-- Seleccione Sede --</option>
                                    @foreach($sedes as $sede)
                                        <option value="{{ $sede->id }}" {{ old('id_sede', $item->id_sede ?? '') == $sede->id ? 'selected' : '' }}>
                                            {{ $sede->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- SECCIÓN 3: MÓDULO OPCIONAL PARA CHOFERES -->
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="es_chofer" name="es_chofer" value="1" 
                                {{ old('es_chofer', isset($item->persona->chofer) ? 1 : 0) ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold text-dark" for="es_chofer">
                                ¿Registrar / Habilitar como Chofer o Conductor?
                            </label>
                        </div>

                        <div id="section-chofer" style="display: none;" class="border rounded p-3 bg-light">
                            <h6 class="fw-bold text-danger mb-3"><i class="fas fa-bus me-1"></i> Documentación y Soportes de Chofer</h6>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="licencia_numero" class="form-label fw-bold">N° Licencia</label>
                                    <input type="text" class="form-control" id="licencia_numero" name="licencia_numero" value="{{ old('licencia_numero', $item->persona->chofer->licencia_numero ?? '') }}">
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="tipo_licencia" class="form-label fw-bold">Grado / Tipo Licencia</label>
                                    <input type="text" class="form-control" id="tipo_licencia" name="tipo_licencia" placeholder="Ej: 5to Grado" value="{{ old('tipo_licencia', $item->persona->chofer->tipo_licencia ?? '') }}">
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="licencia_vencimiento" class="form-label fw-bold">Vencimiento Licencia</label>
                                    <input type="date" class="form-control" id="licencia_vencimiento" name="licencia_vencimiento" value="{{ old('licencia_vencimiento', $item->persona->chofer->licencia_vencimiento ?? '') }}">
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label for="certificado_medico_vencimiento" class="form-label fw-bold">Vencimiento Cert. Médico</label>
                                    <input type="date" class="form-control" id="certificado_medico_vencimiento" name="certificado_medico_vencimiento" value="{{ old('certificado_medico_vencimiento', $item->persona->chofer->certificado_medico_vencimiento ?? '') }}">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="soporte_licencia" class="form-label fw-bold">Soporte Licencia (PDF/Imagen)</label>
                                    <input type="file" class="form-control" id="soporte_licencia" name="soporte_licencia">
                                    @if(isset($item->persona->chofer->soporte_licencia))
                                        <small class="text-success"><i class="fas fa-file-check"></i> Archivo guardado</small>
                                    @endif
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="soporte_certificado" class="form-label fw-bold">Soporte Cert. Médico</label>
                                    <input type="file" class="form-control" id="soporte_certificado" name="soporte_certificado">
                                    @if(isset($item->persona->chofer->soporte_certificado))
                                        <small class="text-success"><i class="fas fa-file-check"></i> Archivo guardado</small>
                                    @endif
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="foto" class="form-label fw-bold">Foto del Conductor</label>
                                    <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                                    @if(isset($item->persona->chofer->foto))
                                        <small class="text-success"><i class="fas fa-image"></i> Foto guardada</small>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-lg btn-success">
                                <i class="fa-solid fa-save me-1"></i> {{ isset($item) ? 'Guardar Datos' : 'Crear Usuario' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- TAB 2: MATRIZ DE PERMISOS (ORIGINAL INTACTO) -->
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
                                    <tr class="{{ $modulo->id_padre == 0 ? 'table-light fw-bold' : '' }}">
                                        <td class="{{ $modulo->id_padre > 0 ? 'ps-4 text-muted' : 'fw-bold' }}">
                                            @if($modulo->id_padre > 0)
                                                <small class="me-1">└─</small>
                                            @endif 
                                            {{ $modulo->modulo }}
                                        </td>
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
        const perfilCliente = "3"; // ID correspondiente a Cliente

        function toggleClienteSelector() {
            if (perfilSelector && perfilSelector.value === perfilCliente) {
                clienteGroup.style.display = 'block';
                clienteInput.setAttribute('required', 'required');
            } else if (clienteGroup) {
                clienteGroup.style.display = 'none';
                clienteInput.removeAttribute('required');
                clienteInput.value = ''; 
            }
        }
        if (perfilSelector) {
            perfilSelector.addEventListener('change', toggleClienteSelector);
            toggleClienteSelector();
        }

        // --- Lógica del Modulo Adaptativo de Chofer ---
        const checkChofer = document.getElementById('es_chofer');
        const sectionChofer = document.getElementById('section-chofer');

        function toggleChoferSection() {
            if (checkChofer && checkChofer.checked) {
                sectionChofer.style.display = 'block';
            } else if (sectionChofer) {
                sectionChofer.style.display = 'none';
            }
        }
        if (checkChofer) {
            checkChofer.addEventListener('change', toggleChoferSection);
            toggleChoferSection();
        }

        // --- Lógica AJAX para Permisos en Tiempo Real ---
        $('.permission-switch').on('change', function() {
            const $switch = $(this);
            const payload = {
                modulo_id: $switch.data('modulo'),
                accion: $switch.data('accion'),
                estado: $switch.is(':checked') ? 1 : 0,
                _token: "{{ csrf_token() }}"
            };

            $switch.addClass('opacity-50');

            $.ajax({
                url: "{{ route('usuarios.update_single_permission', $item->id ?? '') }}",
                method: 'POST',
                data: payload,
                success: function(response) {
                    $switch.removeClass('opacity-50');
                    toastr.success('Permiso actualizado con éxito');
                },
                error: function() {
                    $switch.removeClass('opacity-50');
                    $switch.prop('checked', !$switch.is(':checked'));
                    toastr.error('Error al actualizar el permiso');
                }
            });
        });
    });
</script>
@endpush
@endsection