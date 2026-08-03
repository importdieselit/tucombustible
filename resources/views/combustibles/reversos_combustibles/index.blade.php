@extends('layouts.app')
@section('title', 'Historial de Reversos de Combustible')

@section('content')
<div class="container-fluid py-4 px-4">

    {{-- ENCABEZADO --}}
    <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h2 class="h4 fw-black text-dark text-uppercase mb-1">
                <i class="fas fa-undo-alt text-orange me-2"></i> Historial de Reversos de Combustible
            </h2>
            <p class="text-muted small mb-0">Registro, auditoría y devoluciones de producto acreditadas a favor del cliente</p>
        </div>
        <div class="d-flex flex-wrap align-items-center justify-content-md-end gap-2">
            <a href="{{ route('combustibles.reversos_combustibles.show') }}" class="btn btn-sm btn-outline-dark fw-black text-uppercase shadow-sm px-3 d-inline-flex align-items-center" style="font-size: 12px; height: 32px;">
                <i class="fas fa-wallet text-orange me-1"></i> Ver Saldos Pendientes
            </a>
            <a href="{{ route('combustibles.reversos_combustibles.create') }}" class="btn btn-sm btn-warning fw-black text-uppercase shadow-sm px-3 d-inline-flex align-items-center" style="font-size: 12px; height: 32px; color: #000; background-color: #ff6600; border-color: #ff6600;">
                <i class="fas fa-plus-circle me-1"></i> Registrar Reverso
            </a>
        </div>
    </div>

    {{-- BLOQUE DE FILTROS AVANZADOS --}}
    <div class="card shadow-sm border-0 mb-4 p-3 bg-white">
        <form action="{{ url()->current() }}" method="GET" class="row g-2 align-items-end">
            
            <div class="col-md-3">
                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Sede</label>
                <select name="sede_id" class="form-select form-select-sm fw-bold text-dark" style="font-size: 13px;">
                    <option value="">TODAS LAS SEDES</option>
                    @foreach($sedes as $sede)
                        <option value="{{ $sede->id }}" {{ request('sede_id') == $sede->id ? 'selected' : '' }}>
                            {{ $sede->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Cliente</label>
                <select name="cliente_id" class="form-select form-select-sm fw-bold text-dark" style="font-size: 13px;">
                    <option value="">TODOS LOS CLIENTES</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}" {{ request('cliente_id') == $cliente->id ? 'selected' : '' }}>
                            {{ $cliente->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Fecha Desde</label>
                <input type="date" name="fecha_desde" class="form-control form-control-sm fw-bold text-dark" style="font-size: 13px;" value="{{ request('fecha_desde') }}">
            </div>

            <div class="col-md-2">
                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Fecha Hasta</label>
                <input type="date" name="fecha_hasta" class="form-control form-control-sm fw-bold text-dark" style="font-size: 13px;" value="{{ request('fecha_hasta') }}">
            </div>
            
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-dark w-100 fw-bold text-uppercase d-inline-flex align-items-center justify-content-center" style="font-size: 11px; height: 31px;">
                    <i class="fas fa-filter me-1"></i> Filtrar
                </button>
                
                @if(request()->filled('sede_id') || request()->filled('cliente_id') || request()->filled('fecha_desde') || request()->filled('fecha_hasta'))
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

    {{-- TABLA HISTÓRICA --}}
    <div class="card shadow-sm border-0 mb-4" style="border-left: 4px solid #ff6600;">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-black text-uppercase small text-dark">
                <i class="fas fa-list text-orange me-2"></i> Registro de Reversos Procesados
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="min-width: 1000px;">
                    <thead class="bg-light">
                        <tr class="text-uppercase text-muted" style="font-size: 12px; letter-spacing: 0.5px;">
                            <th class="ps-4"># ID / Fecha</th>
                            <th>Registrado Por</th>
                            <th>Sede</th>
                            <th>Cliente</th>
                            <th>Combustible</th>
                            <th>Motivo / Observación</th>
                            <th class="pe-4 text-end">Litros Reversados</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reversos as $reverso)
                            <tr>
                                <td class="ps-4 font-monospace small fw-bold text-dark">
                                    #{{ $reverso->id }}
                                    <span class="d-block text-muted" style="font-size: 11px;">
                                        {{ $reverso->created_at ? \Carbon\Carbon::parse($reverso->created_at)->format('d/m/Y h:i A') : '-' }}
                                    </span>
                                </td>

                                <td>
                                    <span class="fw-bold text-dark" style="font-size: 13px;">
                                        <i class="fas fa-user-circle text-muted me-1"></i> 
                                        {{ $reverso->user->name ?? 'Sistema' }}
                                    </span>
                                </td>

                                <td>
                                    <span class="badge bg-light text-secondary border text-uppercase fw-bold" style="font-size: 11px;">
                                        {{ $reverso->sede->nombre ?? 'SEDE CENTRAL' }}
                                    </span>
                                </td>

                                <td>
                                    <span class="fw-bold text-dark text-uppercase" style="font-size: 13px;">
                                        <i class="fas fa-building text-muted me-1"></i> {{ $reverso->cliente->nombre ?? 'N/A' }}
                                    </span>
                                </td>

                                <td>
                                    @if(($reverso->tipo_combustible_id) == 2)
                                        <span class="badge bg-warning text-dark fw-bold text-uppercase" style="font-size: 10px; background-color: #ffa500 !important;">DIESEL</span>
                                    @else
                                        <span class="badge bg-info text-white fw-bold text-uppercase" style="font-size: 10px; background-color: #00a8ff !important;">MGO</span>
                                    @endif
                                </td>

                                <td>
                                    <span class="text-muted small">
                                        {{ $reverso->motivo_reverso ?? 'Sin motivo especificado' }}
                                    </span>
                                </td>

                                <td class="pe-4 text-end fw-black text-orange" style="font-size: 15px;">
                                    +{{ number_format($reverso->cantidad_litros, 2, ',', '.') }} Lts
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted small fw-bold">
                                    <i class="fas fa-info-circle me-1 text-warning"></i> No se han encontrado registros de reversos para los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($reversos->hasPages())
            <div class="card-footer bg-white border-top py-3 px-4">
                {{ $reversos->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

<style>
    .text-orange { color: #ff6600 !important; }
    .fw-black { font-weight: 900; }
</style>
@endsection