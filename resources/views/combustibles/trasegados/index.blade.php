@extends('layouts.app')
@section('title', 'Historial de Trasegados')

@section('content')
<div class="container-fluid py-4 px-4">

    {{-- ENCABEZADO --}}
    <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h2 class="h4 fw-black text-dark text-uppercase mb-1">
                <i class="fas fa-history text-orange me-2"></i> Historial de Trasegados
            </h2>
            <p class="text-muted small mb-0">Registro y trazabilidad cronológica de movimientos de combustible entre tanques</p>
        </div>
        <div class="d-flex flex-wrap align-items-center justify-content-md-end gap-2">
            <div class="dropdown">
                <button class="btn btn-sm btn-warning dropdown-toggle fw-black text-uppercase shadow-sm px-3 d-inline-flex align-items-center" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 12px; height: 32px; color: #000; background-color: #ff6600; border-color: #ff6600;">
                    <i class="fas fa-plus-circle me-1"></i> Registrar Trasegado
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="dropdownMenuButton1" style="font-size: 13px;">
                    <li>
                        <a class="dropdown-item py-2 fw-bold text-dark" href="{{ route('combustibles.trasegados.create_interno') }}">
                            <i class="fas fa-exchange-alt text-orange me-2"></i> Trasegado Interno (Misma Sede)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2 fw-bold text-dark" href="{{ route('combustibles.trasegados.create_inter_sedes') }}">
                            <i class="fas fa-truck-moving text-orange me-2"></i> Trasegado Inter-Sedes
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2 fw-bold text-dark" href="{{ route('combustibles.trasegados.create_externo') }}">
                            <i class="fas fa-external-link-alt text-muted me-2"></i> Trasegado Externo (Prestamos o Donaciones)
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    {{-- BLOQUE DE FILTROS AVANZADOS --}}
    <div class="card shadow-sm border-0 mb-4 p-3 bg-white">
        <form action="{{ url()->current() }}" method="GET" class="row g-2 align-items-end">
            
            {{-- FILTRO POR SEDE ORIGEN --}}
            <div class="col-md-3">
                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Sede de Origen</label>
                <select name="id_sede_origen" class="form-select form-select-sm fw-bold text-dark" style="font-size: 13px;">
                    <option value="">TODAS LAS SEDES (ORIGEN)</option>
                    @foreach($sedes as $sede)
                        <option value="{{ $sede->id }}" {{ request('id_sede_origen') == $sede->id ? 'selected' : '' }}>
                            {{ $sede->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- FILTRO POR SEDE DESTINO --}}
            <div class="col-md-3">
                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Sede de Destino</label>
                <select name="id_sede_destino" class="form-select form-select-sm fw-bold text-dark" style="font-size: 13px;">
                    <option value="">TODAS LAS SEDES (DESTINO)</option>
                    @foreach($sedes as $sede)
                        <option value="{{ $sede->id }}" {{ request('id_sede_destino') == $sede->id ? 'selected' : '' }}>
                            {{ $sede->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- RANGO DE FECHAS: DESDE --}}
            <div class="col-md-2">
                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Fecha Desde</label>
                <input type="date" name="fecha_desde" class="form-control form-control-sm fw-bold text-dark" style="font-size: 13px;" value="{{ request('fecha_desde') }}">
            </div>

            {{-- RANGO DE FECHAS: HASTA --}}
            <div class="col-md-2">
                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Fecha Hasta</label>
                <input type="date" name="fecha_hasta" class="form-control form-control-sm fw-bold text-dark" style="font-size: 13px;" value="{{ request('fecha_hasta') }}">
            </div>
            
            {{-- ACCIONES DEL FILTRO --}}
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-dark w-100 fw-bold text-uppercase d-inline-flex align-items-center justify-content-center" style="font-size: 11px; height: 31px;">
                    <i class="fas fa-filter me-1"></i> Filtrar
                </button>
                
                @if(request()->filled('id_sede_origen') || request()->filled('id_sede_destino') || request()->filled('fecha_desde') || request()->filled('fecha_hasta'))
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
                <i class="fas fa-list text-orange me-2"></i> Auditoría de Movimientos de Combustible
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 600px; overflow-y: auto; overflow-x: auto;">
                <table class="table table-hover align-middle mb-0" style="min-width: 1450px;">
                    <thead class="bg-light sticky-top" style="z-index: 1;">
                        <tr class="text-uppercase text-muted" style="font-size: 12px; letter-spacing: 0.5px;">
                            <th class="ps-4">Fecha y Hora</th>
                            <th>Registrado Por</th>
                            <th>Sede Origen</th>
                            <th>Tanque Emisor</th>
                            <th>Sede Destino / Aliado</th>
                            <th>Tanque Receptor</th>
                            <th>Combustible</th>
                            <th style="min-width: 220px; max-width: 320px;">Observaciones</th>
                            <th class="pe-4 text-end">Litros Trasegados</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($trasegados as $trasegado)
                            <tr>
                                {{-- Fecha y Hora --}}
                                <td class="ps-4 font-monospace small fw-bold text-dark">
                                    {{ $trasegado->created_at ? \Carbon\Carbon::parse($trasegado->created_at)->format('d/m/Y h:i A') : 'EXTERNO' }}
                                </td>

                                {{-- Usuario que registró --}}
                                <td>
                                    <span class="fw-bold text-dark" style="font-size: 13px;">
                                        <i class="fas fa-user-circle text-muted me-1"></i> 
                                        {{ $trasegado->usuario->name ?? $trasegado->user->name ?? 'Sistema' }}
                                    </span>
                                </td>

                                {{-- Sede Origen --}}
                                <td>
                                    <span class="badge bg-light text-secondary border text-uppercase fw-bold" style="font-size: 11px;">
                                        {{ $trasegado->sedeOrigen->nombre ?? 'EXTERNO' }}
                                    </span>
                                </td>

                                {{-- Tanque Emisor --}}
                                <td>
                                    <span class="fw-bold text-dark" style="font-size: 13px;">
                                        <i class="fas fa-database text-muted me-1"></i> {{ $trasegado->depositoOrigen->serial ?? 'Externo' }}
                                    </span>
                                </td>

                                {{-- Destino o Aliado Comercial --}}
                                <td>
                                    @if($trasegado->aliado)
                                        <span class="badge text-uppercase fw-black px-2 py-1" style="font-size: 11px; background-color: #e3f2fd; color: #0d47a1; border: 1px solid #bbdefb;">
                                            <i class="fas fa-handshake me-1"></i> {{ $trasegado->aliado->nombre ?? $trasegado->aliado->razon_social ?? 'Aliado' }}
                                        </span>
                                    @else
                                        <span class="badge bg-light text-secondary border text-uppercase fw-bold" style="font-size: 11px;">
                                            {{ $trasegado->sedeDestino->nombre ?? 'EXTERNO' }}
                                        </span>
                                    @endif
                                </td>

                                {{-- Tanque Receptor --}}
                                <td>
                                    @if($trasegado->depositoDestino)
                                        <span class="fw-bold text-dark" style="font-size: 13px;">
                                            <i class="fas fa-database text-muted me-1"></i> {{ $trasegado->depositoDestino->serial }}
                                        </span>
                                    @else
                                        <span class="text-muted small">Externo</span>
                                    @endif
                                </td>

                                {{-- Tipo de Combustible --}}
                                <td>
                                    @if(($trasegado->tipo_combustible_id ?? $trasegado->depositoOrigen->tipo_combustible_id ?? null) == 2)
                                        <span class="badge bg-warning text-dark fw-bold text-uppercase" style="font-size: 10px; background-color: #ffa500 !important;">DIESEL</span>
                                    @else
                                        <span class="badge bg-info text-white fw-bold text-uppercase" style="font-size: 10px; background-color: #00a8ff !important;">MGO</span>
                                    @endif
                                </td>

                                {{-- Observaciones (Multilínea adaptable) --}}
                                <td style="min-width: 220px; max-width: 320px; white-space: normal;">
                                    @if(!empty($trasegado->observaciones))
                                        <div class="small text-secondary lh-sm">
                                            <i class="fas fa-comment-alt text-muted me-1"></i>{{ $trasegado->observaciones }}
                                        </div>
                                    @else
                                        <span class="text-muted small opacity-50">Sin observaciones</span>
                                    @endif
                                </td>

                                {{-- Volumen Neto --}}
                                <td class="pe-4 text-end fw-black text-orange" style="font-size: 15px;">
                                    {{ number_format($trasegado->litros, 2, ',', '.') }} Lts
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted small fw-bold">
                                    <i class="fas fa-info-circle me-1 text-warning"></i> No se han registrado movimientos de trasegado para los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($trasegados->hasPages())
            <div class="card-footer bg-white border-top py-3 px-4">
                {{ $trasegados->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

</div>

<style>
    .text-orange { color: #ff6600 !important; }
    .fw-black { font-weight: 900; }
</style>
@endsection