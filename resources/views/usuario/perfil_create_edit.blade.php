@extends('layouts.app')

@section('title', isset($item) ? 'Editar Perfil: ' . $item->nombre : 'Crear Nuevo Perfil')

@section('content')
<div class="container-fluid py-3">
    <form action="{{ isset($item) ? route('perfiles.update', $item->id) : route('perfiles.store') }}" method="POST">
        @csrf
        @if (isset($item)) @method('PUT') @endif

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-dark mb-0">{{ isset($item) ? 'Configurar Perfil de Acceso' : 'Nuevo Perfil de Acceso' }}</h4>
                <small class="text-muted">Defina el nombre del rol y su plantilla jerárquica de permisos base</small>
            </div>
            <div>
                <a href="{{ route('perfiles.index') }}" class="btn btn-outline-secondary btn-sm me-2">Cancelar</a>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-save me-1"></i> Guardar Perfil
                </button>
            </div>
        </div>

        <div class="row g-4">
            <!-- Datos Básicos -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white fw-bold py-3">
                        <i class="fas fa-id-card me-2 text-primary"></i> Información General
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="nombre" class="form-label fw-bold small">Nombre del Perfil (*)</label>
                            <input type="text" class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" value="{{ old('nombre', $item->nombre ?? '') }}" placeholder="Ej: Gerente de Operaciones" required>
                            @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label fw-bold small">Descripción Operativa</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3" placeholder="Breve resumen de responsabilidades...">{{ old('descripcion', $item->descripcion ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Matriz Jerárquica -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-sitemap me-2 text-primary"></i> Matriz Jerárquica de Permisos</span>
                        <span class="badge bg-light text-dark border">Estructura Padre / Hijo</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-dark small text-uppercase">
                                    <tr>
                                        <th>Módulo / Estructura</th>
                                        <th class="text-center">Lectura</th>
                                        <th class="text-center">Creación</th>
                                        <th class="text-center">Edición</th>
                                        <th class="text-center">Eliminación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($modulos as $padre)
                                        @php
                                            $actions = ['read', 'create', 'update', 'delete'];
                                            $currentPermisosPadre = $item->permisos[$padre->id] ?? [];
                                        @endphp
                                        <!-- FILA PADRE -->
                                        <tr class="table-primary-subtle fw-bold" data-parent-id="{{ $padre->id }}">
                                            <td>
                                                <i class="{{ $padre->icono ?? 'fas fa-folder' }} me-2 text-primary"></i> 
                                                {{ $padre->modulo }}
                                            </td>
                                            @foreach ($actions as $action)
                                                <td class="text-center">
                                                    <input class="form-check-input parent-check" type="checkbox" name="permisos[{{ $padre->id }}][{{ $action }}]" value="1" data-action="{{ $action }}" {{ old("permisos.{$padre->id}.{$action}", $currentPermisosPadre[$action] ?? false) ? 'checked' : '' }}>
                                                </td>
                                            @endforeach
                                        </tr>

                                        <!-- FILAS HIJAS -->
                                        @foreach ($padre->hijos as $hijo)
                                            @php $currentPermisosHijo = $item->permisos[$hijo->id] ?? []; @endphp
                                            <tr class="child-row-of-{{ $padre->id }}">
                                                <td class="ps-5 text-muted small">
                                                    <i class="fas fa-level-up-alt fa-rotate-90 me-2"></i> 
                                                    {{ $hijo->modulo }}
                                                </td>
                                                @foreach ($actions as $action)
                                                    <td class="text-center">
                                                        <input class="form-check-input child-check" type="checkbox" name="permisos[{{ $hijo->id }}][{{ $action }}]" value="1" data-action="{{ $action }}" {{ old("permisos.{$hijo->id}.{$action}", $currentPermisosHijo[$action] ?? false) ? 'checked' : '' }}>
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sincronización visual automatizada Padre -> Hijos
    $('.parent-check').on('change', function() {
        const isChecked = $(this).is(':checked');
        const action = $(this).data('action');
        const parentId = $(this).closest('tr').data('parent-id');

        $(`.child-row-of-${parentId} .child-check[data-action="${action}"]`).prop('checked', isChecked);
    });
});
</script>
@endpush