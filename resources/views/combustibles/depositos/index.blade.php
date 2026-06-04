@extends('layouts.app')
@section('title', 'Infraestructura de Tanques')

@section('content')
{{-- DICCIONARIO DE FORMAS GEOMÉTRICAS PARA EL MAPEO --}}
@php
    $formasGeometricas = [
        'CH' => 'Cilíndrico Horizontal',
        'CV' => 'Cilíndrico Vertical',
        'OH' => 'Oval Horizontal',
        'OV' => 'Oval Vertical',
        'R'  => 'Rectangular',
        'C'  => 'Cúbico',
        'E'  => 'Esférico'
    ];
@endphp

<div class="container-fluid py-4 px-4">

    {{-- ENCABEZADO HOMOGÉNEO --}}
    <div class="mb-4 d-flex justify-content-between align-items-end">
        <div>
            <h2 class="h4 fw-black text-dark text-uppercase mb-1">
                <i class="fas fa-database text-orange me-2"></i> Infraestructura de Tanques
            </h2>
            <p class="text-muted small mb-0">Gestión, cubicidad y control físico de almacenamiento de combustibles de ImporDiesel.</p>
        </div>
        <div>
            {{-- Aumentado a 12px para mayor legibilidad en el botón --}}
            <a href="{{ route('combustibles.depositos.create') }}" class="btn btn-sm btn-dark fw-bold text-uppercase shadow-sm py-2 px-3" style="font-size: 12px;">
                <i class="fas fa-plus-circle me-1"></i> Registrar Tanque
            </a>
        </div>
    </div>

    {{-- ALERTAS NATIVAS SIMÉTRICAS --}}
    @if(Session::has('success'))
        <div class="alert alert-success shadow-sm border-0 mb-4 rounded fw-bold text-white bg-success small text-uppercase">
            <i class="fas fa-check-circle me-2"></i>{{ Session::get('success') }}
        </div>
    @endif

    @if(Session::has('error'))
        <div class="alert alert-danger shadow-sm border-0 mb-4 rounded fw-bold text-white bg-danger small text-uppercase">
            <i class="fas fa-exclamation-circle me-2"></i>{{ Session::get('error') }}
        </div>
    @endif

    {{-- TABLA DE TANQUES CON EL ESTILO DE LOGÍSTICA (Borde izquierdo naranja y Scroll Interno) --}}
    <div class="card shadow-sm border-0" style="border-left: 4px solid #ff6600;">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-black text-uppercase small text-dark">
                <i class="fas fa-gas-pump text-orange me-2"></i> Historial de Tanques Registrados
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light sticky-top" style="z-index: 10;">
                        {{-- Encabezados de tabla subidos de 11px a 13px --}}
                        <tr class="text-uppercase text-muted" style="font-size: 13px;">
                            <th class="ps-4">Nombre / Serial</th>
                            <th>Sede</th>
                            <th>Tipo de Combustible</th>
                            <th class="text-center">Capacidad Máxima</th>
                            <th class="text-center">Forma Geométrica</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($depositos as $deposito)
                            <tr>
                                {{-- Identificador Principal subido de 13px a 15px --}}
                                <td class="ps-4 fw-black text-dark" style="font-size: 15px;">
                                    {{ $deposito->serial }}
                                </td>
                                
                                {{-- Sede con Iconografía subido de 12px a 14px --}}
                                <td class="text-muted fw-bold" style="font-size: 14px;">
                                    <i class="fas fa-map-marker-alt text-secondary me-1"></i> {{ $deposito->sedes->nombre ?? 'N/A' }}
                                </td>
                                
                                {{-- Combustible tipo Badge subido de 10px a 12px --}}
                                <td>
                                    <span class="badge bg-dark text-white text-uppercase" style="font-size: 12px; letter-spacing: 0.5px;">
                                        {{ $deposito->tipoCombustible->nombre ?? 'Sin Asignar' }}
                                    </span>
                                </td>
                                
                                {{-- Capacidad Resaltada en Naranja subida de 14px a 16px, y el 'Lts' a 12px --}}
                                <td class="text-center fw-black text-orange" style="font-size: 16px;">
                                    {{ number_format($deposito->capacidad_maxima, 2, ',', '.') }} 
                                    <span class="text-muted fw-bold" style="font-size: 12px;">Lts</span>
                                </td>
                                
                                {{-- Forma Geométrica Estilizada subida de 10px a 12px --}}
                                <td class="text-center">
                                    <span class="badge bg-light text-secondary border text-uppercase fw-bold" style="font-size: 12px; letter-spacing: 0.3px; padding: 5px 8px;">
                                        {{ $formasGeometricas[$deposito->forma] ?? $deposito->forma }}
                                    </span>
                                </td>
                                
                                {{-- Grupo de Botones Independientes con Sombra (Tamaño estándar de iconos conservado) --}}
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        {{-- Editar --}}
                                        <a href="{{ route('combustibles.depositos.edit', $deposito->id) }}" class="btn btn-sm btn-light border shadow-sm" title="Editar Geometría">
                                            <i class="fas fa-edit text-warning"></i>
                                        </a>
                                        {{-- Eliminar --}}
                                        <form action="{{ route('combustibles.depositos.destroy', $deposito->id) }}" 
                                              method="POST" 
                                              class="m-0" 
                                              onsubmit="return confirm('¿Estás seguro de que deseas eliminar el tanque de manera permanente? Esta acción borrará todas sus configuraciones asociadas.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light border shadow-sm" title="Eliminar Tanque permanentemente">
                                                <i class="fas fa-trash-alt text-danger"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="fas fa-gas-pump text-success fa-2x mb-2 opacity-50"></i>
                                    <p class="text-muted fw-bold mb-0 text-uppercase small">No hay tanques registrados en la plataforma</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        {{-- FOOTER CON PAGINACIÓN --}}
        @if(method_exists($depositos, 'links'))
            <div class="card-footer bg-light border-top">
                {{ $depositos->links() }}
            </div>
        @endif
    </div>
</div>

<style>
    .text-orange { color: #ff6600 !important; }
    .bg-orange { background-color: #ff6600 !important; }
    .fw-black { font-weight: 900; }
</style>
@endsection