@extends('layouts.app')

@section('title', 'Dashboard de Usuarios')

@section('content')

<h1 class="mb-4">Dashboard de Usuarios por Perfil</h1>

<div class="row g-4 mb-4">
    
    <div class="col-12 col-md-4 col-lg-3">
        {{-- Enlace principal: va al listado completo sin filtros (solo el de seguridad) --}}
        <a href="{{ route('usuarios.list') }}" class="text-decoration-none">
            <div class="card shadow-sm border-secondary h-100">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-3x text-secondary mb-3"></i>
                    <h5 class="card-title text-secondary">Total General</h5>
                    <p class="card-text display-4 fw-bold text-secondary">{{ $totalGeneral }}</p>
                </div>
            </div>
        </a>
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
            {{-- **ACTUALIZACIÓN CRÍTICA DEL ENLACE** --}}
            {{-- Pasa 'filter' y 'value' a la ruta usuarios.list --}}
            <a href="{{ route('usuarios.list', ['filter' => 'perfil', 'value' => $perfil->id]) }}" class="text-decoration-none">
                <div class="card shadow-sm border-{{ $cardColor }} h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-user-tag fa-2x text-{{ $cardColor }} mb-3"></i>
                        <h5 class="card-title text-{{ $cardColor }}">{{ Str::title($perfil->perfil) }}</h5>
                        <p class="card-text display-4 fw-bold text-{{ $cardColor }}">{{ $perfil->total }}</p>
                    </div>
                </div>
            </a>
        </div>
    @endforeach

</div>

<div class="alert alert-info" role="alert">
    Haga clic en cualquier tarjeta para ver el listado de usuarios filtrado.
</div>

@endsection