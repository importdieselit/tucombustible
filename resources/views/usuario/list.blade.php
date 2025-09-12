@extends('layouts.app')

@section('title', 'Listado de usuarios')
@push('css')
<style>
    /* Estilos para el panel de permisos */
    .module-list li {
        margin-bottom: 0.5rem;
    }
    .permission-checkbox {
        margin-right: 1.5rem;
    }
    .modal-dialog.modal-fullscreen-lg-down {
        /* Permite que el modal sea de ancho completo en pantallas pequeñas */
        max-width: none;
    }
</style>
@endpush

@section('content')
<div class="container-fluid mt-4">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <h3 class="text-themecolor">Listado de Usuarios</h3>
        </div>
        <div class="col-md-6 align-self-center">
            <div class="d-flex justify-content-end">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('usuarios.index') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Listado</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title m-0">Lista de usuarios</h5>
            <a href="{{ route('usuarios.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i> Registrar Usuario
            </a>
        </div>
        <div class="card-body">
            @if(Session::has('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ Session::get('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Perfil</th>
                            
                            <th>Principal</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{$user->perfil()->first()->nombre}}</td>

                                <td>{{ !is_null($user->cliente_id)?dd($user->cliente()):'N/A' }} {{$user->id_master==0?'(P)':''}}</td>
                                 {{-- $user->cliente()->first()->nombre --}}
                                <td>
                                    <a href="{{ route('usuarios.show', $user->id) }}" class="btn btn-sm btn-info text-white" title="Ver Detalle">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('usuarios.edit', $user->id) }}" class="btn btn-sm btn-warning text-white" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                     @if($user->id_perfil != 1)
                                        <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#permissionsModal" data-user-id="{{ $user->id }}">
                                            <i class="fas fa-user-lock"></i> Administrar Permisos
                                        </button>
                                    @else
                                        <span class="badge bg-success">Super Usuario</span>
                                    @endif
                                    <form action="{{ route('usuarios.destroy', $user->id) }}" method="POST" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de que deseas eliminar a este user?')" title="Eliminar">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No hay usuarios registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Permisos -->
<div class="modal fade" id="permissionsModal" tabindex="-1" aria-labelledby="permissionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="permissionsModalLabel">Gestión de Permisos para <span id="userName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="permissionsForm">
                    @csrf
                    <input type="hidden" name="user_id" id="modalUserId">
                    <ul class="list-unstyled">
                        <!-- Aquí se insertarán los módulos y permisos dinámicamente -->
                    </ul>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="savePermissionsBtn">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const permissionsModal = document.getElementById('permissionsModal');
        const modalUserIdInput = document.getElementById('modalUserId');
        const userNameSpan = document.getElementById('userName');
        const moduleListContainer = permissionsModal.querySelector('.modal-body ul');
        const savePermissionsBtn = document.getElementById('savePermissionsBtn');

        // Escuchar el evento de apertura del modal
        permissionsModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-user-id');
            modalUserIdInput.value = userId;

            // Limpiar la lista de módulos antes de cargar nuevos
            moduleListContainer.innerHTML = '<div class="text-center my-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>';

            // Realizar la llamada AJAX para obtener los permisos del usuario
            fetch(`/api/permisos/${userId}/get`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error al obtener los permisos. Estatus: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    userNameSpan.textContent = data.user.name;
                    moduleListContainer.innerHTML = ''; // Limpiar el spinner

                    const modules = data.modules;
                    const permissions = data.permissions;

                    modules.forEach(module => {
                        const hasReadAccess = permissions[module.id] && permissions[module.id].read === 1;
                        const hasCreateAccess = permissions[module.id] && permissions[module.id].create === 1;
                        const hasUpdateAccess = permissions[module.id] && permissions[module.id].update === 1;
                        const hasDeleteAccess = permissions[module.id] && permissions[module.id].delete === 1;

                        const moduleItem = document.createElement('li');
                        moduleItem.innerHTML = `
                            <strong>${module.modulo}</strong>
                            <div class="form-check form-check-inline permission-checkbox">
                                <input class="form-check-input permission-read" type="checkbox" id="read-${module.id}"
                                       data-module-id="${module.id}" ${hasReadAccess ? 'checked' : ''}>
                                <label class="form-check-label" for="read-${module.id}">Ver</label>
                            </div>
                            <div class="form-check form-check-inline permission-checkbox">
                                <input class="form-check-input permission-create" type="checkbox" id="create-${module.id}"
                                       data-module-id="${module.id}" ${hasCreateAccess ? 'checked' : ''} ${!hasReadAccess ? 'disabled' : ''}>
                                <label class="form-check-label" for="create-${module.id}">Crear</label>
                            </div>
                            <div class="form-check form-check-inline permission-checkbox">
                                <input class="form-check-input permission-update" type="checkbox" id="update-${module.id}"
                                       data-module-id="${module.id}" ${hasUpdateAccess ? 'checked' : ''} ${!hasReadAccess ? 'disabled' : ''}>
                                <label class="form-check-label" for="update-${module.id}">Actualizar</label>
                            </div>
                            <div class="form-check form-check-inline permission-checkbox">
                                <input class="form-check-input permission-delete" type="checkbox" id="delete-${module.id}"
                                       data-module-id="${module.id}" ${hasDeleteAccess ? 'checked' : ''} ${!hasReadAccess ? 'disabled' : ''}>
                                <label class="form-check-label" for="delete-${module.id}">Eliminar</label>
                            </div>
                        `;
                        moduleListContainer.appendChild(moduleItem);
                        
                        // Añadir evento a los checkboxes de "Ver" para habilitar/deshabilitar los demás
                        moduleItem.querySelector('.permission-read').addEventListener('change', function() {
                            const isChecked = this.checked;
                            moduleItem.querySelector('.permission-create').disabled = !isChecked;
                            moduleItem.querySelector('.permission-update').disabled = !isChecked;
                            moduleItem.querySelector('.permission-delete').disabled = !isChecked;
                            if (!isChecked) {
                                moduleItem.querySelector('.permission-create').checked = false;
                                moduleItem.querySelector('.permission-update').checked = false;
                                moduleItem.querySelector('.permission-delete').checked = false;
                            }
                        });
                    });

                })
                .catch(error => {
                    console.error('Error:', error);
                    moduleListContainer.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
                });
        });

        // Escuchar el click en el botón de guardar
        savePermissionsBtn.addEventListener('click', function() {
            const userId = modalUserIdInput.value;
            const allModules = moduleListContainer.querySelectorAll('li');
            const permissionsData = [];

            allModules.forEach(moduleItem => {
                const moduleId = moduleItem.querySelector('.permission-read').getAttribute('data-module-id');
                const read = moduleItem.querySelector('.permission-read').checked ? 1 : 0;
                
                // Solo guardar si el permiso de lectura está activo
                if (read) {
                    permissionsData.push({
                        id_modulo: moduleId,
                        read: read,
                        create: moduleItem.querySelector('.permission-create').checked ? 1 : 0,
                        update: moduleItem.querySelector('.permission-update').checked ? 1 : 0,
                        delete: moduleItem.querySelector('.permission-delete').checked ? 1 : 0,
                    });
                }
            });

            // Realizar la llamada AJAX para actualizar los permisos
            fetch(`/api/permisos/${userId}/update`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ permissions: permissionsData })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error al guardar los permisos. Estatus: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: data.message
                    });
                    const myModalEl = document.getElementById('permissionsModal');
                    const modal = bootstrap.Modal.getInstance(myModalEl);
                    modal.hide();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message
                });
                console.error('Error:', error);
            });
        });
    });
</script>
@endpush

