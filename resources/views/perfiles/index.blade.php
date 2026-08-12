@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800"><i class="fas fa-users-cog"></i> Gestión de Perfiles y Accesos</h1>
        <button class="btn btn-primary shadow-sm" onclick="openProfileModal()">
            <i class="fas fa-plus"></i> Nuevo Perfil
        </button>
    </div>

    <!-- Tabla Principal -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead class="table-dark">
                        <tr>
                            <th width="5%">ID</th>
                            <th>Nombre del Perfil</th>
                            <th>Descripción</th>
                            <th class="text-center">Usuarios Asignados</th>
                            <th class="text-center">Estatus</th>
                            <th class="text-center" width="20%">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($perfiles as $perfil)
                        <tr>
                            <td>{{ $perfil->id }}</td>
                            <td class="fw-bold">{{ $perfil->nombre }}</td>
                            <td>{{ $perfil->descripcion ?? 'N/A' }}</td>
                            <td class="text-center">
                                <span class="badge bg-info text-dark">{{ $perfil->users_count }} Usuarios</span>
                            </td>
                            <td class="text-center">
                                <div class="form-check form-switch d-flex justify-content-center">
                                    <input class="form-check-input toggle-status" type="checkbox" 
                                           data-id="{{ $perfil->id }}" {{ $perfil->activo ? 'checked' : '' }}>
                                </div>
                            </td>
                            <td class="text-center">
                                <!-- Editar Perfil -->
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="openProfileModal({{ $perfil->toJson() }})" title="Editar Datos">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <!-- Editar Permisos -->
                                <button class="btn btn-sm btn-outline-warning" 
                                        onclick="openPermissionsModal({{ $perfil->id }}, '{{ $perfil->nombre }}')" title="Gestionar Permisos">
                                    <i class="fas fa-key"></i> Permisos
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ================= MODAL: CREAR / EDITAR PERFIL ================= -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="profileForm" method="POST" action="">
                @csrf
                <input type="hidden" name="_method" id="profileMethod" value="POST">
                
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="profileModalTitle">Nuevo Perfil</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre del Perfil <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" id="perfilNombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" id="perfilDesc" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="activo" id="perfilActivo" value="1" checked>
                        <label class="form-check-label" for="perfilActivo">Perfil Activo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= MODAL: GESTIONAR PERMISOS ================= -->
<div class="modal fade" id="permissionsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="permissionsForm" method="POST" action="">
                @csrf
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">Matriz de Permisos: <strong id="permTitleName"></strong></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
                        <table class="table table-sm table-bordered table-hover">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Módulo / Sección</th>
                                    <th class="text-center">Lectura</th>
                                    <th class="text-center">Creación</th>
                                    <th class="text-center">Edición</th>
                                    <th class="text-center">Eliminación</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($modulos as $padre)
                                    <!-- Fila del Módulo Padre -->
                                    <tr class="table-secondary font-weight-bold">
                                        <td><i class="fas fa-folder-open"></i> {{ $padre->nombre ?? $padre->modulo }}</td>
                                        <td class="text-center"><input type="checkbox" class="chk-read" name="permissions[{{ $padre->id }}][]" value="read" id="chk_{{ $padre->id }}_read"></td>
                                        <td class="text-center"><input type="checkbox" class="chk-create" name="permissions[{{ $padre->id }}][]" value="create" id="chk_{{ $padre->id }}_create"></td>
                                        <td class="text-center"><input type="checkbox" class="chk-update" name="permissions[{{ $padre->id }}][]" value="update" id="chk_{{ $padre->id }}_update"></td>
                                        <td class="text-center"><input type="checkbox" class="chk-delete" name="permissions[{{ $padre->id }}][]" value="delete" id="chk_{{ $padre->id }}_delete"></td>
                                    </tr>
                                    <!-- Filas de los Módulos Hijos -->
                                    @foreach($padre->hijos as $hijo)
                                    <tr>
                                        <td class="ps-4"><i class="fas fa-angle-right text-muted"></i> {{ $hijo->nombre ?? $hijo->modulo }}</td>
                                        <td class="text-center"><input type="checkbox" class="chk-read" name="permissions[{{ $hijo->id }}][]" value="read" id="chk_{{ $hijo->id }}_read"></td>
                                        <td class="text-center"><input type="checkbox" class="chk-create" name="permissions[{{ $hijo->id }}][]" value="create" id="chk_{{ $hijo->id }}_create"></td>
                                        <td class="text-center"><input type="checkbox" class="chk-update" name="permissions[{{ $hijo->id }}][]" value="update" id="chk_{{ $hijo->id }}_update"></td>
                                        <td class="text-center"><input type="checkbox" class="chk-delete" name="permissions[{{ $hijo->id }}][]" value="delete" id="chk_{{ $hijo->id }}_delete"></td>
                                    </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-warning text-dark"><i class="fas fa-save"></i> Sincronizar Permisos</button>
                </div>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>
    const urlBase = '{{ url("perfiles") }}';
    const profileModal = new bootstrap.Modal(document.getElementById('profileModal'));
    const permissionsModal = new bootstrap.Modal(document.getElementById('permissionsModal'));

    // 1. Lógica Modal Perfil (Crear / Editar)
    function openProfileModal(perfil = null) {
        const form = document.getElementById('profileForm');
        const methodInput = document.getElementById('profileMethod');
        const title = document.getElementById('profileModalTitle');

        if (perfil) {
            // Modo Edición
            title.textContent = 'Editar Perfil: ' + perfil.nombre;
            form.action = `${urlBase}/${perfil.id}`;
            methodInput.value = 'PUT';
            document.getElementById('perfilNombre').value = perfil.nombre;
            document.getElementById('perfilDesc').value = perfil.descripcion || '';
            document.getElementById('perfilActivo').checked = perfil.activo;
        } else {
            // Modo Creación
            title.textContent = 'Nuevo Perfil';
            form.action = urlBase;
            methodInput.value = 'POST';
            form.reset();
            document.getElementById('perfilActivo').checked = true;
        }
        profileModal.show();
    }

    // 2. Lógica Modal Permisos (Carga por AJAX)
   // 2. Lógica Modal Permisos (Carga por AJAX)
    function openPermissionsModal(id, nombre) {
        document.getElementById('permTitleName').textContent = nombre;
        document.getElementById('permissionsForm').action = `${urlBase}/${id}/permisos`;
        
        // Limpiar checkboxes usando la propiedad real del DOM, no el atributo
        document.querySelectorAll('#permissionsForm input[type=checkbox]').forEach(chk => {
            chk.checked = false;
        });

        fetch(`${urlBase}/${id}/permisos`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            const permisos = data.permisos;
            
            Object.keys(permisos).forEach(moduloId => {
                const acciones = permisos[moduloId];
                
                // Obtener los elementos del DOM
                const chkRead = document.getElementById(`chk_${moduloId}_read`);
                const chkCreate = document.getElementById(`chk_${moduloId}_create`);
                const chkUpdate = document.getElementById(`chk_${moduloId}_update`);
                const chkDelete = document.getElementById(`chk_${moduloId}_delete`);

                // Asignar el estado directamente a la propiedad .checked (método infalible)
                if(chkRead && (acciones.read == 1 || acciones.read === true)) chkRead.checked = true;
                if(chkCreate && (acciones.create == 1 || acciones.create === true)) chkCreate.checked = true;
                if(chkUpdate && (acciones.update == 1 || acciones.update === true)) chkUpdate.checked = true;
                if(chkDelete && (acciones.delete == 1 || acciones.delete === true)) chkDelete.checked = true;
            });
            
            permissionsModal.show();
        })
        .catch(error => console.error('Error al cargar permisos:', error));
    }

    // 3. Lógica Toggle Estatus (Switch en la tabla)
    document.querySelectorAll('.toggle-status').forEach(switchBtn => {
        switchBtn.addEventListener('change', function() {
            const perfilId = this.getAttribute('data-id');
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            fetch(`${urlBase}/${perfilId}/toggle-estatus`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            })
            .then(res => res.json())
            .then(data => {
                if(!data.success) {
                    alert('Ocurrió un error al cambiar el estatus');
                    this.checked = !this.checked; // Revertir visualmente
                }
            });
        });
    });
</script>
@endpush
@endsection