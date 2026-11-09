@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 mt-4">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0 text-secondary fw-bold"><i class="fas fa-tools me-2"></i> Gestor de Esquemas (Builder)</h3>
            <p class="text-muted mb-0">Administración de la estructura de parámetros del sistema.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('parametros.index') }}" class="btn btn-outline-secondary shadow-sm">
                <i class="fas fa-arrow-left me-1"></i> Volver al Panel
            </a>
            <a href="{{ route('parametros.manager.create') }}" class="btn btn-primary shadow-sm fw-bold">
                <i class="fas fa-plus me-1"></i> Nuevo Parámetro
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}</div>
    @endif

    <!-- Tabla -->
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-4">Grupo</th>
                            <th>Clave Única</th>
                            <th>Etiqueta UI (Label)</th>
                            <th>Tipo de Control</th>
                            <th class="text-center pe-4">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($parametros as $param)
                            @php $schema = is_array($param->valor) ? ($param->valor['schema'] ?? []) : []; @endphp
                            <tr>
                                <td class="ps-4">
                                    <span class="badge bg-secondary px-2 py-1">{{ $param->grupo }}</span>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark">{{ $param->clave }}</span>
                                    @if(!empty($param->descripcion))
                                        <small class="d-block text-muted text-truncate" style="max-width: 250px;" title="{{ $param->descripcion }}">
                                            {{ $param->descripcion }}
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-primary fw-semibold">{{ $schema['label'] ?? 'Sin Etiqueta' }}</span>
                                </td>
                                <td>
                                    @php $uiType = $schema['ui_type'] ?? 'Desconocido'; @endphp
                                    <span class="badge bg-info text-dark">{{ strtoupper($uiType) }}</span>
                                    
                                    @if(in_array($uiType, ['db_select', 'db_matrix']) && isset($schema['source']['table']))
                                        <small class="d-block text-muted mt-1">
                                            <i class="fas fa-database me-1"></i> {{ $schema['source']['table'] }}
                                        </small>
                                    @endif
                                </td>
                                <td class="text-center pe-4">
                                    <form action="{{ route('parametros.manager.destroy', $param) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Estás absolutamente seguro de eliminar este parámetro? Si está en uso en producción, vas a romper el sistema y será tu culpa.');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar Esquema">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-folder-open fs-1 d-block mb-3 opacity-50"></i>
                                    <h5 class="fw-normal">No hay parámetros configurados</h5>
                                    <p class="small">Usa el botón "Nuevo Parámetro" para empezar a construir esquemas.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection