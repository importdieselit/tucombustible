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
                                    {{-- Recorre la colección jerárquica --}}
                                    @foreach ($modulos as $padre)
                                        @php
                                            $actions = ['read', 'create', 'update', 'delete'];
                                            // Obtener el estado del permiso actual para el padre
                                            $currentPermisosPadre = $item->permisos[$padre->id] ?? [];
                                        @endphp

                                        {{-- FILA DEL MÓDULO PADRE (Resaltada) --}}
                                        <tr class="table-info fw-bold"> {{-- Usa table-info para resaltar --}}
                                            <td>
                                                <i class="{{ $padre->icono ?? 'fas fa-cogs' }} me-2"></i> 
                                                {{ $padre->modulo }}
                                            </td>
                                            @foreach ($actions as $action)
                                                 <td class="text-center">
                                                    <div class="form-check d-inline-block">
                                                        @php
                                                            // Lógica para determinar si el checkbox debe estar marcado
                                                            $isCheckedPadre = old("permisos.{$padre->id}.{$action}", $currentPermisosPadre[$action] ?? false);
                                                        @endphp
                                                        
                                                        {{-- FIX: Usar el atributo 'checked' condicionalmente --}}
                                                        <input class="form-check-input" 
                                                            type="checkbox" 
                                                            value="1" 
                                                            name="permisos[{{ $padre->id }}][{{ $action }}]"
                                                            id="permiso-{{ $padre->id }}-{{ $action }}"
                                                            {{ $isCheckedPadre ? 'checked' : '' }} {{-- Esto resuelve el error --}}
                                                        >
                                                    </div>
                                                </td>
                                            @endforeach
                                        </tr>

                                        {{-- Recorre los módulos Hijos --}}
                                        @foreach ($padre->hijos as $hijo)
                                            @php
                                                // Obtener el estado del permiso actual para el hijo
                                                $currentPermisosHijo = $item->permisos[$hijo->id] ?? [];
                                            @endphp
                                            
                                            <tr class="table-light"> {{-- Fila normal o con indentación visual --}}
                                                <td class="ps-5"> 
                                                    <i class="fas fa-level-up-alt fa-rotate-90 me-2 text-muted"></i> 
                                                    {{ $hijo->modulo }}
                                                </td>
                                                @foreach ($actions as $action)
                                                      <td class="text-center">
                                                            <div class="form-check d-inline-block">
                                                                @php
                                                                    // Lógica para determinar si el checkbox debe estar marcado
                                                                    $isCheckedHijo = old("permisos.{$hijo->id}.{$action}", $currentPermisosHijo[$action] ?? false);
                                                                @endphp

                                                                {{-- FIX: Usar el atributo 'checked' condicionalmente --}}
                                                                <input class="form-check-input" 
                                                                    type="checkbox" 
                                                                    value="1" 
                                                                    name="permisos[{{ $hijo->id }}][{{ $action }}]"
                                                                    id="permiso-{{ $hijo->id }}-{{ $action }}"
                                                                    {{ $isCheckedHijo ? 'checked' : '' }} {{-- Esto resuelve el error --}}
                                                                >
                                                            </div>
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