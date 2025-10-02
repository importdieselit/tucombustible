@extends('layouts.app')

@section('title', 'Detalle del Perfil: ' . $item->nombre)

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="mb-0">Detalle del Perfil: <span class="text-primary">{{ $item->nombre }}</span></h1>
                <div>
                    {{-- Botón de Edición --}}
                    @if (auth()->user()->canAccess('update', $MODULO_PERFILES))
                        <a href="{{ route('perfiles.edit', $item->id) }}" class="btn btn-warning me-2">
                            <i class="fas fa-edit"></i> Editar Perfil
                        </a>
                    @endif
                    
                    {{-- Botón de Regreso --}}
                    <a href="{{ url()->previous() }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>

            {{-- SECCIÓN 1: INFORMACIÓN GENERAL --}}
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Información General</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>ID del Perfil:</strong> {{ $item->id }}</p>
                            <p><strong>Nombre:</strong> {{ $item->nombre }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Creado el:</strong> {{ $item->created_at->format('d/m/Y H:i') }}</p>
                            <p><strong>Última Actualización:</strong> {{ $item->updated_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SECCIÓN 2: MATRIZ DE PERMISOS ASIGNADOS --}}
            <div class="card shadow mb-5">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Permisos Asignados por Módulo</h5>
                    <small class="text-muted">Estado de los permisos CRUD para este perfil.</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Módulo</th>
                                    <th class="text-center">Leer</th>
                                    <th class="text-center">Crear</th>
                                    <th class="text-center">Editar</th>
                                    <th class="text-center">Eliminar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $actions = ['read', 'create', 'update', 'delete'];
                                @endphp
                                
                                {{-- Recorre la colección jerárquica ($modulosJerarquicos) --}}
                                @foreach ($modulos as $padre)
                                    @php
                                        // Cargar los permisos actuales (si el campo 'permisos' es un JSON/Array en el modelo)
                                        $currentPermisosPadre = $item->permisos[$padre->id] ?? [];
                                    @endphp

                                    {{-- FILA DEL MÓDULO PADRE (Resaltada) --}}
                                    <tr class="table-info fw-bold"> 
                                        <td>
                                            <i class="{{ $padre->icono ?? 'fas fa-cogs' }} me-2"></i> 
                                            {{ $padre->nombre }}
                                        </td>
                                        @foreach ($actions as $action)
                                            <td class="text-center">
                                                {{-- Muestra un ícono de verificación o de denegación --}}
                                                @if ($currentPermisosPadre[$action] ?? false)
                                                    <i class="fas fa-check-circle text-success" title="Permitido"></i>
                                                @else
                                                    <i class="fas fa-times-circle text-danger" title="Denegado"></i>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>

                                    {{-- Recorre los módulos Hijos --}}
                                    @foreach ($padre->hijos as $hijo)
                                        @php
                                            $currentPermisosHijo = $item->permisos[$hijo->id] ?? [];
                                        @endphp
                                        
                                        <tr class="table-light">
                                            <td class="ps-5"> 
                                                <i class="fas fa-level-up-alt fa-rotate-90 me-2 text-muted"></i> 
                                                {{ $hijo->nombre }}
                                            </td>
                                            @foreach ($actions as $action)
                                                <td class="text-center">
                                                    @if ($currentPermisosHijo[$action] ?? false)
                                                        <i class="fas fa-check-circle text-success" title="Permitido"></i>
                                                    @else
                                                        <i class="fas fa-times-circle text-danger" title="Denegado"></i>
                                                    @endif
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
            
            <div class="d-flex justify-content-end mb-5">
                 <a href="{{ url()->previous() }}" class="btn btn-secondary me-2">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                @if (auth()->user()->canAccess('update', $MODULO_PERFILES))
                    <a href="{{ route('perfiles.edit', $item->id) }}" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Modificar Permisos
                    </a>
                @endif
            </div>

        </div>
    </div>
</div>
@endsection