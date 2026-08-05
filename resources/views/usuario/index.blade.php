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
<div class="card border-0 shadow-sm rounded-3 overflow-hidden">
    <!-- Header del Directorio -->
    <div class="card-header bg-white py-3 d-flex flex-wrap align-items-center justify-content-between border-bottom gap-2">
        <div>
            <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="fas fa-users-gear text-primary"></i> Directorio Ejecutivo de Usuarios
            </h6>
            <small class="text-muted">Gestión centralizada de identidades, perfiles y matriz de acceso</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="input-group input-group-sm" style="width: 280px;">
                <span class="input-group-text bg-light border-end-0 text-muted ps-3">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" id="userSearch" class="form-control form-control-sm bg-light border-start-0 ps-0" placeholder="Filtrar por nombre, correo, cliente...">
            </div>
            @if(auth()->user()->canAccess('create', $moduloIdUsuarios ?? 51))
                <a href="{{ route('usuarios.create') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 fw-semibold shadow-sm">
                    <i class="fas fa-user-plus"></i> Nuevo Usuario
                </a>
            @endif
        </div>
    </div>

    <!-- Cuerpo de la Tabla -->
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="usersTable">
                <thead class="table-light text-uppercase text-secondary extra-small fw-bold border-bottom">
                    <tr>
                        <th class="ps-3 py-3">Usuario / Identidad</th>
                        <th class="ps-3 py-3">Nombre</th>
                        <th class="py-3">Perfil / Rol Asignado</th>
                        <th class="py-3">Entidad / Cliente</th>
                        <th class="text-center py-3">Estatus</th>
                        <th class="text-end pe-3 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody class="small">
                    @forelse ($usuarios ?? $data ?? [] as $user)
                        <tr class="user-row">
                            <!-- Usuario / Email -->
                            <td class="ps-3 py-2.5">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-primary text-white rounded-circle fw-bold d-flex align-items-center justify-content-center me-3 shadow-sm flex-shrink-0" style="width: 38px; height: 38px; font-size: 0.85rem;">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    
                                    <div class="text-truncate" style="max-width: 240px;">
                                        <div class="fw-bold text-dark text-truncate">{{ $user->name }}</div>
                                        <div class="text-muted extra-small text-truncate">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;">
                                    <div class="fw-semibold text-dark text-truncate">{{ $user->persona->nombre ?? 'N/A' }}</div>
                                 </div>
                            </td>

                            <!-- Perfil / Rol -->
                            <td>
                                @php
                                    $perfilNombre = $user->perfil->nombre ?? 'Sin Perfil';
                                    $badgeClass = match(strtolower($perfilNombre)) {
                                        'administrador', 'super admin' => 'bg-danger-subtle text-danger border-danger-subtle',
                                        'operador', 'analista'        => 'bg-primary-subtle text-primary border-primary-subtle',
                                        'cliente'                    => 'bg-info-subtle text-info-emphasis border-info-subtle',
                                        default                      => 'bg-secondary-subtle text-secondary border-secondary-subtle'
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }} border rounded-pill px-2.5 py-1 fw-semibold">
                                    <i class="fas fa-user-shield me-1 extra-small"></i>{{ $perfilNombre }}
                                </span>
                            </td>

                            <!-- Entidad / Cliente -->
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    @if($user->cliente)
                                        <i class="fas fa-building text-muted me-1"></i>
                                        <span class="fw-semibold text-dark">{{ $user->cliente->nombre }}</span>
                                    @else
                                        <span class="badge bg-dark-subtle text-dark border rounded-pill px-2 py-1">
                                            <i class="fas fa-globe me-1"></i>Acceso Global
                                        </span>
                                    @endif

                                    @if($user->id_master == 0)
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill ms-1" title="Usuario Administrador Principal del Cliente">
                                            <i class="fas fa-crown extra-small"></i> Master
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Estatus de Cuenta -->
                            <td class="text-center">
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 fw-semibold d-inline-flex align-items-center gap-1">
                                    <span class="spinner-grow spinner-grow-sm text-success" style="width: 6px; height: 6px;" role="status"></span>
                                    Activo
                                </span>
                            </td>

                            <!-- Acciones -->
                            <td class="text-end pe-3">
                                <div class="btn-group btn-group-sm shadow-sm" role="group">
                                    <a href="{{ route('usuarios.show', $user->id) }}" class="btn btn-light border text-secondary" title="Ver Expediente" data-bs-toggle="tooltip">
                                        <i class="fas fa-eye text-primary"></i>
                                    </a>
                                    <a href="{{ route('usuarios.edit', $user->id) }}" class="btn btn-light border text-secondary" title="Editar Usuario" data-bs-toggle="tooltip">
                                        <i class="fas fa-pen text-warning"></i>
                                    </a>
                                    <button type="button" class="btn btn-light border text-secondary btn-permisos" data-user-id="{{ $user->id }}" title="bloquear" data-bs-toggle="tooltip">
                                        <i class="fas fa-shield-halved text-info"></i>
                                    </button>
                                    @if(auth()->user()->canAccess('delete', $moduloIdUsuarios ?? 51))
                                        <form action="{{ route('usuarios.destroy', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar permanentemente a este usuario?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-light border text-secondary rounded-end" title="Eliminar" data-bs-toggle="tooltip">
                                                <i class="fas fa-trash-alt text-danger"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fas fa-users-slash fa-3x mb-3 text-secondary opacity-50"></i>
                                <p class="fw-semibold mb-0">No se encontraron usuarios registrados.</p>
                                <small class="text-muted">Intente modificar los filtros o registre un nuevo usuario.</small>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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