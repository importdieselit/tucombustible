@extends('layouts.app')

@section('title', 'Dashboard de Usuarios')
@php
    $MODULO_USUARIOS = 51; // Asumiendo tu ID de módulo
    $MODULO_PERFILES = 52; // Asumiendo un ID para la gestión de Perfiles
@endphp

@section('content')
<div class="container-fluid py-3">
    <!-- Header principal -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">Gestión General de Usuarios</h3>
            <p class="text-muted small mb-0">Monitoreo de cuentas, perfiles asignados y matriz de seguridad</p>
        </div>
        <div>
            <a href="{{ route('usuarios.create') }}" class="btn btn-primary shadow-sm">
                <i class="fas fa-user-plus me-1"></i> Nuevo Usuario
            </a>
        </div>
    </div>

    <!-- TARJETAS DE KPIS / METRICAS CORPORATIVAS -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary text-white rounded-3">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-white-50 text-uppercase small fw-bold">Total Registrados</span>
                        <h2 class="fw-bold mb-0 mt-1">{{ $totalGeneral ?? count($usuarios ?? []) }}</h2>
                    </div>
                    <div class="fs-1 text-white-50">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>

        @if(isset($perfilesConteo))
            @foreach($perfilesConteo->take(3) as $kpi)
            <div class="col-md-3">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted text-uppercase small fw-bold">{{ $kpi->perfil }}</span>
                            <h2 class="fw-bold mb-0 mt-1 text-dark">{{ $kpi->total }}</h2>
                        </div>
                        <div class="fs-1 text-primary opacity-25">
                            <i class="fas fa-user-shield"></i>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        @endif
    </div>

    <!-- TABLA CENTRAL DE USUARIOS -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
            <h6 class="fw-bold text-dark mb-0">Directorio de Usuarios Activos</h6>
            <div class="d-flex gap-2">
                <input type="text" id="userSearch" class="form-control form-control-sm" style="width: 250px;" placeholder="Buscar por nombre, correo...">
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="usersTable">
                    <thead class="table-light text-uppercase small">
                        <tr>
                            <th class="ps-3">Usuario / Email</th>
                            <th>Perfil / Rol</th>
                            <th>Entidad / Cliente</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @forelse ($usuarios ?? $data ?? [] as $user)
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary-subtle text-primary rounded-circle fw-bold d-flex align-items-center justify-content-center me-3" style="width: 38px; height: 38px;">
                                            {{ strtoupper(substr($user->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">{{ $user->name }}</div>
                                            <div class="text-muted extra-small">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill px-2 py-1">
                                        {{ $user->perfil->nombre ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    {{ !is_null($user->cliente) ? $user->cliente->nombre : 'Acceso Global' }}
                                    {!! $user->id_master == 0 ? '<span class="badge bg-secondary ms-1">Master</span>' : '' !!}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Activo</span>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('usuarios.show', $user->id) }}" class="btn btn-outline-secondary" title="Ver Expediente">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('usuarios.edit', $user->id) }}" class="btn btn-outline-warning" title="Editar">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-primary btn-permisos" data-user-id="{{ $user->id }}" title="Matriz Expresa de Permisos">
                                            <i class="fas fa-shield-halved"></i>
                                        </button>
                                        <form action="{{ route('usuarios.destroy', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar este usuario?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="fas fa-folder-open fa-2x mb-2 d-block"></i> No se encontraron usuarios registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DINÁMICO DE PERMISOS VIA AJAX -->
<div class="modal fade" id="permissionsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fs-6"><i class="fas fa-user-lock me-2"></i>Gestión de Accesos Específicos: <span id="modalUserName" class="text-warning"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="modalUserId">
                <div id="modalLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted small mt-2">Cargando matriz de permisos...</p>
                </div>
                <div id="modalContent" style="display:none;">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Módulo</th>
                                    <th class="text-center">Lectura</th>
                                    <th class="text-center">Creación</th>
                                    <th class="text-center">Edición</th>
                                    <th class="text-center">Eliminación</th>
                                </tr>
                            </thead>
                            <tbody id="modalPermissionsBody">
                                <!-- Filas inyectadas dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSavePermissions">
                    <i class="fas fa-save me-1"></i> Guardar Excepciones
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Búsqueda en tabla
    $('#userSearch').on('keyup', function() {
        let value = $(this).val().toLowerCase();
        $("#usersTable tbody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

    // Abrir Modal de Permisos por AJAX
    $('.btn-permisos').on('click', function() {
        const userId = $(this).data('user-id');
        $('#modalUserId').val(userId);
        $('#modalLoading').show();
        $('#modalContent').hide();
        
        const modal = new bootstrap.Modal(document.getElementById('permissionsModal'));
        modal.show();

        fetch(`/api/permisos/${userId}/get`)
            .then(res => res.json())
            .then(data => {
                $('#modalUserName').text(data.user.name);
                let html = '';
                
                data.modules.forEach(mod => {
                    const p = data.permissions[mod.id] || {};
                    html += `
                        <tr>
                            <td class="fw-bold">${mod.modulo}</td>
                            <td class="text-center"><input class="form-check-input perm-read" type="checkbox" data-mod="${mod.id}" ${p.read ? 'checked' : ''}></td>
                            <td class="text-center"><input class="form-check-input perm-create" type="checkbox" data-mod="${mod.id}" ${p.create ? 'checked' : ''}></td>
                            <td class="text-center"><input class="form-check-input perm-update" type="checkbox" data-mod="${mod.id}" ${p.update ? 'checked' : ''}></td>
                            <td class="text-center"><input class="form-check-input perm-delete" type="checkbox" data-mod="${mod.id}" ${p.delete ? 'checked' : ''}></td>
                        </tr>
                    `;
                });

                $('#modalPermissionsBody').html(html);
                $('#modalLoading').hide();
                $('#modalContent').show();
            })
            .catch(() => {
                Swal.fire('Error', 'No se pudieron obtener los permisos', 'error');
                modal.hide();
            });
    });
});
</script>
@endpush