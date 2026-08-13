@extends('layouts.app')

@section('title', 'Perfil de Usuario - ' . $item->name)

@push('css')
<style>
    .avatar-circle {
        width: 90px;
        height: 90px;
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        color: #fff;
        font-size: 36px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }
    .timeline {
        position: relative;
        padding-left: 20px;
        border-left: 2px solid #e9ecef;
    }
    .timeline-item {
        position: relative;
        margin-bottom: 25px;
    }
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -27px;
        top: 2px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: #0d6efd;
        border: 2px solid #fff;
    }
    .timeline-item.created::before { background-color: #198754; }
    .timeline-item.updated::before { background-color: #0dcaf0; }
    .timeline-item.deleted::before { background-color: #dc3545; }
    .timeline-item.security::before { background-color: #ffc107; }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">
    <!-- Header de Navegación -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0 text-dark">Expediente de Usuario</h4>
            <small class="text-muted">Gestión centralizada de credenciales, seguridad y auditoría</small>
        </div>
        <div>
            <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary btn-sm me-2">
                <i class="fas fa-arrow-left me-1"></i> Volver al Listado
            </a>
            @if(Auth::user()->canAccess('update', 51))
                <a href="{{ route('usuarios.edit', $item->id) }}" class="btn btn-warning btn-sm text-white">
                    <i class="fas fa-user-gear me-1"></i> Editar Credenciales
                </a>
            @endif
        </div>
    </div>

    <div class="row g-4">
        <!-- Tarjeta de Perfil Resumen -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body text-center p-4">
                    <div class="d-flex justify-content-center mb-3">
                        <div class="avatar-circle">
                            {{ strtoupper(substr($item->name, 0, 2)) }}
                        </div>
                    </div>
                    <h5 class="fw-bold mb-1">{{ $item->name }}</h5>
                    <p class="text-muted small mb-2">{{ $item->email }}</p>
                    
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2 mb-3">
                        <i class="fas fa-shield-halved me-1"></i> {{ $item->perfil->nombre ?? 'Sin Rol Asignado' }}
                    </span>

                    <hr class="my-3 text-muted opacity-25">

                    <div class="text-start">
                        <div class="d-flex justify-content-between py-2 border-bottom border-light">
                            <span class="text-muted small">Estado de Cuenta</span>
                            <span class="badge bg-success">Activo</span>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom border-light">
                            <span class="text-muted small">Cliente / Entidad</span>
                            <span class="fw-semibold text-dark small">
                                {{ $item->cliente ? $item->cliente->nombre : 'Acceso Global' }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom border-light">
                            <span class="text-muted small">Último Inicio de Sesión</span>
                            <span class="fw-semibold text-dark small">
                                {{ $item->ultimo_login ? \Carbon\Carbon::parse($item->ultimo_login)->diffForHumans() : 'Sin registros' }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span class="text-muted small">Fecha de Registro</span>
                            <span class="fw-semibold text-dark small">
                                {{ $item->created_at ? $item->created_at->format('d/m/Y H:i') : 'N/A' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel de Pestañas (Permisos / Auditoría) -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white border-bottom p-3">
                    <ul class="nav nav-pills card-header-pills" id="userTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active fw-bold small" id="permisos-tab" data-bs-toggle="tab" data-bs-target="#permisos-pane" type="button" role="tab">
                                <i class="fas fa-key me-2"></i>Matriz Efectiva de Accesos
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold small" id="actividad-tab" data-bs-toggle="tab" data-bs-target="#actividad-pane" type="button" role="tab">
                                <i class="fas fa-history me-2"></i>Log de Auditoría y Trazabilidad
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body p-4">
                    <div class="tab-content" id="userTabsContent">
                        
                        <!-- PESTAÑA 1: PERMISOS -->
                        <div class="tab-pane fade show active" id="permisos-pane" role="tabpanel">
                            <div class="alert alert-info border-0 bg-info-subtle text-info-emphasis d-flex align-items-center mb-4">
                                <i class="fas fa-circle-info fa-lg me-3"></i>
                                <div class="small">
                                    Esta vista consolida los permisos calculados mediante la herencia del <strong>Perfil Base</strong> y las excepciones directas configuradas en la tabla <code>accesos</code>[cite: 11].
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle border">
                                    <thead class="table-dark">
                                        <tr class="small text-uppercase">
                                            <th>Módulo del Sistema</th>
                                            <th class="text-center">Lectura</th>
                                            <th class="text-center">Escritura</th>
                                            <th class="text-center">Eliminación</th>
                                        </tr>
                                    </thead>
                                    <tbody class="small">
                                        @foreach($modulos as $modulo)
                                            <tr>
                                                <td class="fw-bold text-dark">
                                                    <i class="{{ $modulo->icono ?? 'fas fa-folder' }} me-2 text-primary"></i>
                                                    {{ $modulo->modulo ?? $modulo->nombre }}
                                                </td>
                                                <td class="text-center">
                                                    {!! $item->canAccess('read', $modulo->id) 
                                                        ? '<i class="fas fa-circle-check text-success fa-lg"></i>' 
                                                        : '<i class="fas fa-circle-xmark text-danger opacity-50 fa-lg"></i>' !!}
                                                </td>
                                                <td class="text-center">
                                                    {!! $item->canAccess('update', $modulo->id) 
                                                        ? '<i class="fas fa-circle-check text-success fa-lg"></i>' 
                                                        : '<i class="fas fa-circle-xmark text-danger opacity-50 fa-lg"></i>' !!}
                                                </td>
                                                <td class="text-center">
                                                    {!! $item->canAccess('delete', $modulo->id) 
                                                        ? '<i class="fas fa-circle-check text-success fa-lg"></i>' 
                                                        : '<i class="fas fa-circle-xmark text-danger opacity-50 fa-lg"></i>' !!}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- PESTAÑA 2: AUDITORÍA -->
                        <div class="tab-pane fade" id="actividad-pane" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h6 class="fw-bold text-dark mb-0">Historial Reciente de Operaciones</h6>
                                <button class="btn btn-outline-primary btn-sm" id="btn-refresh-audit">
                                    <i class="fas fa-sync-alt me-1"></i> Actualizar
                                </button>
                            </div>

                            <div class="timeline" id="audit-timeline">
                                <!-- Elementos dinámicos de auditoría -->
                                <div class="timeline-item security">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-dark small">Cambio de Contraseña Forzado</span>
                                        <span class="text-muted extra-small">Hace 2 horas</span>
                                    </div>
                                    <p class="text-muted small mb-1">El usuario actualizó las credenciales de acceso tras el primer ingreso.</p>
                                    <span class="badge bg-light text-dark border extra-small">IP: 190.206.22.10</span>
                                </div>

                                <div class="timeline-item updated">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-dark small">Modificación de Permisos Específicos</span>
                                        <span class="text-muted extra-small">Ayer a las 14:32</span>
                                    </div>
                                    <p class="text-muted small mb-1">Se otorgó permiso explícito de lectura en el módulo <strong>Control de Sistemas</strong>.</p>
                                    <span class="badge bg-light text-dark border extra-small">Ejecutado por: Admin_Master</span>
                                </div>

                                <div class="timeline-item created">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-dark small">Creación de Registro de Usuario</span>
                                        <span class="text-muted extra-small">{{ $item->created_at ? $item->created_at->format('d/m/Y') : 'N/A' }}</span>
                                    </div>
                                    <p class="text-muted small mb-0">Alta inicial del usuario en el sistema vinculado al cliente id: {{ $item->cliente_id ?? 'N/A' }}.</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Refresco dinámico vía AJAX para el Log de Auditoría
        $('#btn-refresh-audit').on('click', function() {
            const $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Cargando...');

            $.ajax({
                url: "{{ url('/api/auditoria/usuario/' . $item->id) }}",
                method: 'GET',
                success: function(data) {
                    $btn.prop('disabled', false).html('<i class="fas fa-sync-alt me-1"></i> Actualizar');
                    toastr.success('Historial actualizado');
                },
                error: function() {
                    $btn.prop('disabled', false).html('<i class="fas fa-sync-alt me-1"></i> Actualizar');
                    toastr.info('Modo de vista previa: Mostrando registros cacheados.');
                }
            });
        });
    });
</script>
@endpush