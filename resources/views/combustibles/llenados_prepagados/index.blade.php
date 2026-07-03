@extends('layouts.app')
@section('title', 'Historial de Llenados Prepagados')

@section('content')
<div class="container-fluid py-4 px-4">

    {{-- ENCABEZADO --}}
    <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h2 class="h4 fw-black text-dark text-uppercase mb-1">
                <i class="fas fa-history text-orange me-2"></i> Historial de Llenados con Cupo Prepagado
            </h2>
            <p class="text-muted small mb-0">Historial general de vehículos surtidos en sedes</p>
        </div>
        <div class="d-flex flex-wrap align-items-center justify-content-md-end gap-2">
            <a href="{{ route('combustibles.llenados_prepagados.create') }}" class="btn btn-sm btn-warning fw-black text-uppercase shadow-sm px-3 d-inline-flex align-items-center" style="font-size: 12px; height: 32px; color: #000; background-color: #ff6600; border-color: #ff6600;">
                <i class="fas fa-plus-circle me-1"></i> Registrar Llenado
            </a>
        </div>
    </div>

    {{-- FILTROS POR SEDE --}}
    <form action="{{ url()->current() }}" method="GET" class="row g-2 align-items-end mb-4">
        <div class="col-md-3">
            <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Filtrar por Sede</label>
            <select name="id_sede" class="form-select form-select-sm fw-bold" style="font-size: 13px;" onchange="this.form.submit()">
                <option value="">TODAS LAS SEDES OPERATIVAS</option>
                @foreach($sedes as $sede)
                    <option value="{{ $sede->id }}" {{ request('id_sede') == $sede->id ? 'selected' : '' }}>
                        {{ $sede->nombre }}
                    </option>
                @endforeach
            </select>
        </div>
        
        @if(request('id_sede'))
            <div class="col-md-1">
                <a href="{{ url()->current() }}" class="btn btn-outline-secondary btn-sm w-100 fw-bold text-uppercase" style="font-size: 12px; padding: 5px 0;">
                    Limpiar
                </a>
            </div>
        @endif
    </form>

    {{-- ALERTAS DEL SISTEMA --}}
    @if(Session::has('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm fw-bold small" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ Session::get('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- TABLA HISTÓRICA --}}
    <div class="card shadow-sm border-0 mb-4" style="border-left: 4px solid #ff6600;">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-black text-uppercase small text-dark">
                <i class="fas fa-list text-orange me-2"></i> Registro Cronológico de Despachos
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="text-uppercase text-muted" style="font-size: 12px; letter-spacing: 0.5px;">
                            <th class="ps-4">Fecha y Hora</th>
                            <th>Cliente Corporativo</th>
                            <th>Sede</th>
                            <th>Tanque / Depósito</th>
                            <th>Combustible</th>
                            <th class="pe-4 text-end">Litros Despachados</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($llenados as $llenado)
                            <tr>
                                <td class="ps-4 font-monospace small fw-bold text-dark">
                                    {{ $llenado->created_at->format('d/m/Y h:i A') }}
                                </td>
                                <td>
                                    <span class="fw-black text-dark d-block" style="font-size: 14px;">{{ $llenado->cliente->nombre }}</span>
                                    <small class="text-muted font-monospace">{{ $llenado->cliente->rif }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border text-uppercase fw-bold" style="font-size: 11px;">
                                        {{ $llenado->sede->nombre }}
                                    </span>
                                </td>
                                <td>
                                    <span class="fw-bold text-secondary" style="font-size: 13px;">
                                        <i class="fas fa-database text-muted me-1"></i> {{ $llenado->deposito->serial }}
                                    </span>
                                </td>
                                <td>
                                    @if($llenado->tipo_combustible_id == 2)
                                        <span class="badge bg-warning text-dark fw-bold text-uppercase" style="font-size: 10px; background-color: #ffa500 !important;">DIESEL</span>
                                    @else
                                        <span class="badge bg-info text-white fw-bold text-uppercase" style="font-size: 10px; background-color: #00a8ff !important;">MGO</span>
                                    @endif
                                </td>
                                <td class="pe-4 text-end fw-black text-dark" style="font-size: 15px;">
                                    {{ number_format($llenado->litros, 2, ',', '.') }} Lts
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted small fw-bold">
                                    <i class="fas fa-info-circle me-1 text-warning"></i> No se han registrado movimientos de llenado prepagado para los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($llenados->hasPages())
            <div class="card-footer bg-white border-top py-3 px-4">
                {{ $llenados->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

<style>
    .text-orange { color: #ff6600 !important; }
    .fw-black { font-weight: 900; }
</style>
@endsection