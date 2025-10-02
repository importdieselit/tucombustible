@extends('layouts.app')

@section('title', 'Dashboard de Usuarios')
@php
    $MODULO_USUARIOS = 51; // Asumiendo tu ID de módulo
    $MODULO_PERFILES = 52; // Asumiendo un ID para la gestión de Perfiles
@endphp

@section('content')
    
     

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Dashboard de Usuarios por Perfil</h1>
    
    {{-- BOTONES DE ACCIÓN GLOBAL --}}
    <div>
        {{-- Botón para Crear Perfil --}}
        @if (auth()->user()->canAccess('create', $MODULO_PERFILES)) 
            <a href="{{ route('perfiles.create') }}" class="btn btn-primary me-2">
                <i class="fas fa-plus"></i> Crear Perfil
            </a>
        @endif
        
        {{-- Botón para Crear Usuario --}}
        @if (auth()->user()->canAccess('create', $MODULO_USUARIOS)) 
            <a href="{{ route('usuarios.create') }}" class="btn btn-success">
                <i class="fas fa-user-plus"></i> Crear Usuario
            </a>
        @endif
    </div>
</div>


<div class="row g-4 mb-4">
    
    <div class="col-12 col-md-4 col-lg-3">
        <div class="card shadow-sm border-secondary h-100 d-flex flex-column">
            <a href="{{ route('usuarios.list') }}" class="text-decoration-none">
                <div class="card-body text-center flex-grow-1">
                    <i class="fas fa-users fa-3x text-secondary mb-3"></i>
                    <h5 class="card-title text-secondary">Total General</h5>
                    <p class="card-text display-4 fw-bold text-secondary">{{ $totalGeneral }}</p>
                </div>
            </a>
            {{-- SIN BOTONES DE ACCIÓN AQUÍ --}}
            <div class="card-footer bg-white border-0 py-2">
                 <small class="text-muted">Lista completa de usuarios.</small>
            </div>
        </div>
    </div>

    @foreach ($perfilesConteo as $perfil)
           @php
            // Lógica de color de la card (ajustar según tu diseño)
            $cardColor = match($perfil->perfil) {
                'administrador' => 'primary',
                'cliente' => 'info',
                'chofer' => 'success',
                default => 'warning',
            };
        @endphp
        <div class="col-12 col-md-4 col-lg-3">
            <div class="card shadow-sm border-{{ $cardColor }} h-100 d-flex flex-column">
                
                {{-- SECCIÓN DE KPI (Enlace para filtrar) --}}
                <a href="{{ route('usuarios.list', ['filter' => 'id_perfil', 'value' => $perfil->id]) }}" class="text-decoration-none flex-grow-1">
                    <div class="card-body text-center">
                        <i class="fas fa-user-tag fa-2x text-{{ $cardColor }} mb-3"></i>
                        <h5 class="card-title text-{{ $cardColor }}">{{ Str::title($perfil->perfil) }}</h5>
                        <p class="card-text display-4 fw-bold text-{{ $cardColor }}">{{ $perfil->total }}</p>
                    </div>
                </a>

                {{-- **SECCIÓN DE ACCIÓN DEL PERFIL** --}}
                <div class="card-footer bg-light border-0 py-2 d-flex justify-content-end">
                    
                    {{-- Botón para Editar Perfil (Nombre y Permisos) --}}
                    @if (auth()->user()->canAccess('update', $MODULO_PERFILES))
                        <a href="{{ route('perfiles.edit', $perfil->id_perfil) }}" class="btn btn-sm btn-outline-{{ $cardColor }} me-1" title="Editar Permisos y Nombre">
                            <i class="fas fa-edit"></i>
                        </a>
                    @endif
                    
                    {{-- Botón para Ver Listado --}}
                    <a href="{{ route('usuarios.list', ['filter' => 'id_perfil', 'value' => $perfil->id_perfil]) }}" class="btn btn-sm btn-{{ $cardColor }}" title="Ver Usuarios">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    @endforeach

</div>
@endsection