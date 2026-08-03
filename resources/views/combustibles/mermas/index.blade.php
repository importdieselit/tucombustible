@extends('layouts.app')
@section('title', 'Historial de Mermas')

@section('content')
<div class="container-fluid py-4 px-4">

    {{-- ENCABEZADO --}}
    <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h2 class="h4 fw-black text-dark text-uppercase mb-1">
                <i class="fas fa-balance-scale-right text-orange me-2"></i> Historial de Mermas y Ajustes
            </h2>
            <p class="text-muted small mb-0">Registro y trazabilidad de diferencias físicas vs teóricas en los tanques</p>
        </div>
        
        {{-- TOTALIZADOR DE MERMAS --}}
        <div class="d-flex flex-wrap align-items-center justify-content-md-end gap-2">
            <div class="card shadow-sm border-0 bg-white px-4 py-2" style="border-right: 4px solid #ff6600;">
                <span class="text-muted small fw-bold text-uppercase d-block" style="font-size: 10px;">Balance de Mermas</span>
                <span class="fw-black text-orange" style="font-size: 18px;">
                    <i class="fas fa-calculator me-1" style="font-size: 14px;"></i> 
                    {{ number_format($totalLitrosMerma ?? 0, 2, ',', '.') }} Lts
                </span>
            </div>
        </div>
    </div>

    {{-- BLOQUE DE FILTROS AVANZADOS --}}
    <div class="card shadow-sm border-0 mb-4 p-3 bg-white">
        <form action="{{ url()->current() }}" method="GET" class="row g-2 align-items-end">
            
            {{-- FILTRO POR SEDE --}}
            <div class="col-md-3">
                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Sede</label>
                <select name="sede_id" class="form-select form-select-sm fw-bold text-dark" style="font-size: 13px;">
                    <option value="">TODAS LAS SEDES</option>
                    @foreach($sedes ?? [] as $sede)
                        <option value="{{ $sede->id }}" {{ request('sede_id') == $sede->id ? 'selected' : '' }}>
                            {{ $sede->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- FILTRO POR TIPO DE COMBUSTIBLE --}}
            <div class="col-md-3">
                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Combustible</label>
                <select name="tipo_combustible_id" class="form-select form-select-sm fw-bold text-dark" style="font-size: 13px;">
                    <option value="">TODOS LOS TIPOS</option>
                    @foreach($tipos ?? [] as $tipo)
                        <option value="{{ $tipo->id }}" {{ request('tipo_combustible_id') == $tipo->id ? 'selected' : '' }}>
                            {{ $tipo->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- RANGO DE FECHAS: DESDE --}}
            <div class="col-md-2">
                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Fecha Desde</label>
                <input type="date" name="fecha_inicio" class="form-control form-control-sm fw-bold text-dark" style="font-size: 13px;" value="{{ request('fecha_inicio') }}">
            </div>

            {{-- RANGO DE FECHAS: HASTA --}}
            <div class="col-md-2">
                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Fecha Hasta</label>
                <input type="date" name="fecha_fin" class="form-control form-control-sm fw-bold text-dark" style="font-size: 13px;" value="{{ request('fecha_fin') }}">
            </div>
            
            {{-- ACCIONES DEL FILTRO --}}
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-dark w-100 fw-bold text-uppercase d-inline-flex align-items-center justify-content-center" style="font-size: 11px; height: 31px;">
                    <i class="fas fa-filter me-1"></i> Filtrar
                </button>
                
                @if(request()->filled('sede_id') || request()->filled('tipo_combustible_id') || request()->filled('fecha_inicio') || request()->filled('fecha_fin'))
                    <a href="{{ url()->current() }}" class="btn btn-outline-secondary btn-sm w-100 fw-bold text-uppercase d-inline-flex align-items-center justify-content-center" style="font-size: 11px; height: 31px;">
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- ALERTAS DE ÉXITO --}}
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
                <i class="fas fa-list text-orange me-2"></i> Auditoría de Mermas por Chequeo
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="min-width: 1000px;">
                    <thead class="bg-light">
                        <tr class="text-uppercase text-muted" style="font-size: 12px; letter-spacing: 0.5px;">
                            <th class="ps-4">Fecha y Hora</th>
                            <th>Registrado Por</th>
                            <th>Sede</th>
                            <th>Tanque</th>
                            <th>Combustible</th>
                            <th>Diferencia</th>
                            <th class="pe-4 text-end">Litros</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mermas as $merma)
                            <tr>
                                {{-- Fecha y Hora --}}
                                <td class="ps-4 font-monospace small fw-bold text-dark">
                                    {{ $merma->created_at ? \Carbon\Carbon::parse($merma->created_at)->format('d/m/Y h:i A') : 'N/A' }}
                                </td>

                                {{-- Usuario que registró --}}
                                <td>
                                    <span class="fw-bold text-dark" style="font-size: 13px;">
                                        <i class="fas fa-user-circle text-muted me-1"></i> 
                                        {{ $merma->user->name ?? 'Sistema' }}
                                    </span>
                                </td>

                                {{-- Sede --}}
                                <td>
                                    <span class="badge bg-light text-secondary border text-uppercase fw-bold" style="font-size: 11px;">
                                        {{ $merma->sede->nombre ?? 'N/A' }}
                                    </span>
                                </td>

                                {{-- Tanque --}}
                                <td>
                                    <span class="fw-bold text-dark" style="font-size: 13px;">
                                        <i class="fas fa-database text-muted me-1"></i> 
                                        {{ $merma->deposito->serial ?? $merma->deposito->nombre ?? 'N/A' }}
                                    </span>
                                </td>

                                {{-- Tipo de Combustible --}}
                                <td>
                                    <span class="badge bg-warning text-dark fw-bold text-uppercase" style="font-size: 10px; background-color: #ffa500 !important;">
                                        {{ $merma->tipoCombustible->nombre ?? 'N/A' }}
                                    </span>
                                </td>

                                {{-- Tipo de Diferencia (Merma o Sobrante) --}}
                                <td>
                                    @if(in_array($merma->tipo_movimiento, ['merma', 'ajuste_negativo']))
                                        <span class="badge text-uppercase fw-black px-2 py-1" style="font-size: 11px; background-color: #ffebee; color: #c62828; border: 1px solid #ffcdd2;">
                                            <i class="fas fa-arrow-down me-1"></i> Faltante (Merma)
                                        </span>
                                    @else
                                        <span class="badge text-uppercase fw-black px-2 py-1" style="font-size: 11px; background-color: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9;">
                                            <i class="fas fa-arrow-up me-1"></i> Sobrante Físico
                                        </span>
                                    @endif
                                </td>

                                {{-- Volumen Neto Ajustado --}}
                                <td class="pe-4 text-end fw-black text-orange" style="font-size: 15px;">
                                    {{ number_format($merma->cantidad_litros ?? 0, 2, ',', '.') }} Lts
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted small fw-bold">
                                    <i class="fas fa-info-circle me-1 text-warning"></i> No se han registrado mermas ni ajustes para los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        {{-- PAGINACIÓN --}}
        @if(method_exists($mermas, 'hasPages') && $mermas->hasPages())
            <div class="card-footer bg-white border-top py-3 px-4">
                {{ $mermas->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

<style>
    .text-orange { color: #ff6600 !important; }
    .fw-black { font-weight: 900; }
</style>
@endsection