@extends('layouts.app')
@section('title', 'Vehículos Precargados Actualmente')

@section('content')
<div class="container-fluid py-4 px-4">

    {{-- ENCABEZADO --}}
    <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h2 class="h4 fw-black text-dark text-uppercase mb-1">
                <i class="fas fa-truck-loading text-orange me-2"></i> Vehículos Precargados Actualmente
            </h2>
            <p class="text-muted small mb-0">Control de cisternas y camiones con combustible cargado disponible en patio</p>
        </div>
        <div class="d-flex flex-wrap align-items-center justify-content-md-end gap-2">
            <a href="{{ route('combustibles.precargas_vehiculos.historico') }}" class="btn btn-sm btn-outline-dark fw-black text-uppercase shadow-sm px-3 d-inline-flex align-items-center" style="font-size: 12px; height: 32px;">
                <i class="fas fa-history text-orange me-1"></i> Ver Histórico
            </a>
            <a href="{{ route('combustibles.precargas_vehiculos.create') }}" class="btn btn-sm btn-warning fw-black text-uppercase shadow-sm px-3 d-inline-flex align-items-center" style="font-size: 12px; height: 32px; color: #000; background-color: #ff6600; border-color: #ff6600;">
                <i class="fas fa-plus-circle me-1"></i> Registrar Precarga
            </a>
        </div>
    </div>

    {{-- BLOQUE DE FILTROS --}}
    <div class="card shadow-sm border-0 mb-4 p-3 bg-white">
        <form action="{{ url()->current() }}" method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Sede Operativa</label>
                <select name="id_sede" class="form-select form-select-sm fw-bold text-dark" style="font-size: 13px;">
                    <option value="">TODAS LAS SEDES</option>
                    @foreach($sedes as $sede)
                        <option value="{{ $sede->id }}" {{ request('id_sede') == $sede->id ? 'selected' : '' }}>
                            {{ $sede->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-dark w-100 fw-bold text-uppercase d-inline-flex align-items-center justify-content-center" style="font-size: 11px; height: 31px;">
                    <i class="fas fa-filter me-1"></i> Filtrar
                </button>
                @if(request()->filled('id_sede'))
                    <a href="{{ url()->current() }}" class="btn btn-outline-secondary btn-sm w-100 fw-bold text-uppercase d-inline-flex align-items-center justify-content-center" style="font-size: 11px; height: 31px;">
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- ALERTAS DE ESTADO --}}
    @if(Session::has('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm fw-bold small" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ Session::get('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(Session::has('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm fw-bold small" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ Session::get('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- TABLA DE PRECARGAS ACTIVAS CON SCROLL --}}
    <div class="card shadow-sm border-0 mb-4" style="border-left: 4px solid #ff6600;">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-black text-uppercase small text-dark">
                <i class="fas fa-truck-moving text-orange me-2"></i> Unidades Precargadas en Patio
            </h6>
            <span class="badge bg-warning text-dark fw-black px-2 py-1" style="font-size: 11px; background-color: #ff6600 !important; color: #fff !important;">
                {{ $precargasActivas->count() }} ACTIVAS
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 560px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0" style="min-width: 950px;">
                    <thead class="bg-light sticky-top" style="z-index: 2;">
                        <tr class="text-uppercase text-muted" style="font-size: 12px; letter-spacing: 0.5px;">
                            <th class="ps-4"># ID / Fecha Carga</th>
                            <th>Vehículo / Placa</th>
                            <th>Sede</th>
                            <th>Combustible</th>
                            <th style="min-width: 200px; max-width: 300px;">Observaciones</th>
                            <th class="text-end">Cantidad Cargada</th>
                            <th class="text-center">Usuario</th>
                            <th class="pe-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($precargasActivas as $precarga)
                            <tr>
                                <td class="ps-4 font-monospace small fw-bold text-dark">
                                    #{{ $precarga->id }}
                                    <span class="d-block text-muted" style="font-size: 11px;">
                                        {{ $precarga->fecha_hora_carga ? $precarga->fecha_hora_carga->format('d/m/Y h:i A') : '-' }}
                                    </span>
                                </td>

                                <td>
                                    <span class="fw-black text-dark text-uppercase" style="font-size: 14px;">
                                        <i class="fas fa-truck text-orange me-1"></i> {{ $precarga->vehiculo->placa ?? 'SIN PLACA' }}
                                    </span>
                                </td>

                                <td>
                                    <span class="badge bg-light text-secondary border text-uppercase fw-bold" style="font-size: 11px;">
                                        {{ $precarga->sede->nombre ?? 'N/A' }}
                                    </span>
                                </td>

                                <td>
                                    @if(($precarga->id_tipo_combustible) == 2)
                                        <span class="badge bg-warning text-dark fw-bold text-uppercase" style="font-size: 10px; background-color: #ffa500 !important;">DIESEL</span>
                                    @else
                                        <span class="badge bg-info text-white fw-bold text-uppercase" style="font-size: 10px; background-color: #00a8ff !important;">{{ $precarga->tipoCombustible->nombre ?? 'COMBUSTIBLE' }}</span>
                                    @endif
                                </td>

                                <td style="min-width: 200px; max-width: 300px; white-space: normal;">
                                    @if(!empty($precarga->observaciones))
                                        <div class="small text-secondary lh-sm">
                                            <i class="fas fa-comment-alt text-muted me-1"></i>{{ $precarga->observaciones }}
                                        </div>
                                    @else
                                        <span class="text-muted small opacity-50">Sin observaciones</span>
                                    @endif
                                </td>

                                <td class="text-end fw-black text-orange" style="font-size: 15px;">
                                    {{ number_format($precarga->cantidad_litros, 2, ',', '.') }} Lts
                                </td>

                                <td class="text-center small fw-bold text-muted" style="font-size: 11px;">
                                    <i class="fas fa-user-circle me-1"></i> {{ $precarga->usuario->name ?? ($precarga->usuario->nombre ?? 'N/A') }}
                                </td>

                                <td class="pe-4 text-center">
                                    <form action="{{ route('combustibles.precargas_vehiculos.finalizar', $precarga->id) }}" method="POST" onsubmit="return confirm('¿Confirmas que este vehículo ya despachó/utilizó el combustible precargado?');" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-outline-success fw-bold text-uppercase shadow-sm py-1 px-2" style="font-size: 11px;" title="Marcar como Despachada">
                                            <i class="fas fa-check-circle me-1"></i> Vaciar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted small fw-bold">
                                    <i class="fas fa-info-circle me-1 text-warning"></i> No hay vehículos precargados actualmente en patio.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .text-orange { color: #ff6600 !important; }
    .fw-black { font-weight: 900; }
    
    .table-responsive::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .table-responsive::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    .table-responsive::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 4px;
    }
    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #ff6600;
    }
</style>
@endsection