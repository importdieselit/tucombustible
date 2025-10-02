@extends('layouts.app')

@section('title', isset($item) ? 'Editar Perfil: ' . $item->nombre : 'Crear Nuevo Perfil')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <h1 class="mb-4">{{ isset($item) ? 'Editar Perfil' : 'Crear Perfil' }}</h1>

            {{-- Determinar la acción del formulario: update o store --}}
            @if (isset($item))
                <form action="{{ route('perfiles.update', $item->id) }}" method="POST">
                    @method('PUT')
            @else
                <form action="{{ route('perfiles.store') }}" method="POST">
            @endif
                
                @csrf

                {{-- SECCIÓN 1: DATOS BÁSICOS DEL PERFIL --}}
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Información General del Perfil</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nombre">Nombre del Perfil:</label>
                                    <input type="text" 
                                           class="form-control @error('nombre') is-invalid @enderror" 
                                           id="nombre" 
                                           name="nombre" 
                                           value="{{ old('nombre', $item->nombre ?? '') }}" 
                                           required>
                                    @error('nombre')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            {{-- Aquí puedes agregar otros campos si los necesitas (ej. descripción) --}}
                            
                        </div>
                    </div>
                </div>

                {{-- SECCIÓN 2: MATRIZ DE PERMISOS --}}
                <div class="card shadow mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">Asignación de Permisos por Módulo</h5>
                        <small class="text-muted">Marque los permisos (CRUD) que tendrá este perfil.</small>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Módulo</th>
                                        <th class="text-center">Leer (Read)</th>
                                        <th class="text-center">Crear (Create)</th>
                                        <th class="text-center">Editar (Update)</th>
                                        <th class="text-center">Eliminar (Delete)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($modulos as $modulo)
                                        @php
                                            // Obtener el estado del permiso actual del perfil
                                            // ASUMIMOS que los permisos se guardan en un array: 
                                            // $item->permisos['modulo_id']['action']
                                            $currentPermisos = $item->permisos[$modulo->id] ?? [];
                                            
                                            // Definir las acciones básicas de un CRUD
                                            $actions = ['read', 'create', 'update', 'delete'];
                                        @endphp
                                        
                                        <tr>
                                            <td>
                                                <i class="{{ $modulo->icono ?? 'fas fa-cogs' }} me-2"></i> 
                                                {{ $modulo->nombre }}
                                            </td>
                                            @foreach ($actions as $action)
                                                <td class="text-center">
                                                    <div class="form-check d-inline-block">
                                                        <input class="form-check-input" 
                                                               type="checkbox" 
                                                               value="1" 
                                                               name="permisos[{{ $modulo->id }}][{{ $action }}]"
                                                               id="permiso-{{ $modulo->id }}-{{ $action }}"
                                                               @checked(old('permisos.' . $modulo->id . '.' . $action, $currentPermisos[$action] ?? false))
                                                               >
                                                    </div>
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mb-5">
                    <a href="{{ url()->previous() }}" class="btn btn-secondary me-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> 
                        {{ isset($item) ? 'Guardar Cambios' : 'Crear Perfil' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection