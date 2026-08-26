@extends('layouts.app')
@section('title', 'Histórico de Abastecimiento de Tanques')

@section('content')
<div class="container-fluid py-4 px-4">

    {{-- ENCABEZADO --}}
    <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h2 class="h4 fw-black text-dark text-uppercase mb-1">
                <i class="fas fa-dolly-flatbed text-orange me-2"></i> Abastecimiento de Tanques
            </h2>
            <p class="text-muted small mb-0">Registro de traspasos de combustible desde cisternas/vehículos hacia depósitos locales</p>
        </div>
        <div>
            <a href="{{ route('combustibles.abastecimientos_tanques.create') }}" class="btn btn-sm btn-warning fw-black text-uppercase shadow-sm px-3 d-inline-flex align-items-center" style="font-size: 12px; height: 32px; color: #000; background-color: #ff6600; border-color: #ff6600;">
                <i class="fas fa-plus-circle me-1"></i> Registrar Abastecimiento
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

    {{-- TARJETA CONTENEDORA DE TABLA --}}
    <div class="card shadow-sm border border-secondary-subtle overflow-hidden">
        <div class="table-responsive" style="max-height: 550px; overflow-y: auto;">
            <table class="table table-hover table-bordered table-striped align-middle mb-0 w-100 border-secondary-subtle">
                <thead class="bg-light sticky-top border-bottom" style="top: 0; z-index: 2;">
                    <tr class="text-uppercase text-muted" style="font-size: 11px; letter-spacing: 0.5px;">
                        <th class="ps-3 py-3 text-nowrap text-center" style="width: 140px;"># ID / Fecha</th>
                        <th class="py-3 text-nowrap" style="width: 140px;">Sede</th>
                        <th class="py-3 text-nowrap" style="width: 220px;">Origen (Vehículo / Compra)</th>
                        <th class="py-3 text-nowrap text-center" style="width: 130px;">Combustible</th>
                        <th class="py-3 text-end text-nowrap pe-3" style="width: 140px;">Cantidad</th>
                        <th class="py-3">Observaciones</th>
                        <th class="py-3 text-center text-nowrap" style="width: 140px;">Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($abastecimientos as $item)
                        <tr>
                            <td class="ps-3 text-center font-monospace small fw-bold text-dark text-nowrap">
                                #{{ $item->id }}
                                <span class="d-block text-muted fw-normal" style="font-size: 11px;">
                                    {{ $item->fecha_hora ? $item->fecha_hora->format('d/m/Y h:i A') : '-' }}
                                </span>
                            </td>

                            <td class="text-nowrap">
                                <span class="badge bg-light text-secondary border text-uppercase fw-bold" style="font-size: 11px;">
                                    {{ $item->sede->nombre ?? 'N/A' }}
                                </span>
                            </td>

                            <td class="text-nowrap">
                                @if($item->id_vehiculo || $item->precargaOrigen)
                                    <span class="fw-black text-dark text-uppercase" style="font-size: 13px;">
                                        <i class="fas fa-truck text-orange me-1"></i> {{ $item->vehiculo->placa ?? ($item->precargaOrigen->vehiculo->placa ?? 'SIN PLACA') }}
                                    </span>
                                @elseif($item->id_compra_combustible)
                                    <span class="fw-black text-dark text-uppercase" style="font-size: 13px;">
                                        <i class="fas fa-shopping-cart text-success me-1"></i> Compra #{{ $item->id_compra_combustible }}
                                    </span>
                                    @if($item->compraCombustible && ($item->compraCombustible->proveedor || $item->compraCombustible->otro_proveedor))
                                        <small class="text-muted d-block fw-normal" style="font-size: 10px;">
                                            Prov: {{ $item->compraCombustible->proveedor->nombre ?? $item->compraCombustible->otro_proveedor }}
                                        </small>
                                    @endif
                                @else
                                    <span class="text-muted small">N/A</span>
                                @endif
                            </td>

                            <td class="text-center text-nowrap">
                                @if(($item->id_tipo_combustible) == 2)
                                    <span class="badge bg-warning text-dark fw-bold text-uppercase" style="font-size: 10px; background-color: #ffa500 !important;">DIESEL</span>
                                @else
                                    <span class="badge bg-info text-white fw-bold text-uppercase" style="font-size: 10px; background-color: #00a8ff !important;">{{ $item->tipoCombustible->nombre ?? 'COMBUSTIBLE' }}</span>
                                @endif
                            </td>

                            <td class="text-end fw-black text-orange text-nowrap pe-3" style="font-size: 14px;">
                                {{ number_format($item->cantidad_litros, 2, ',', '.') }} Lts
                            </td>

                            <td>
                                <span class="text-muted small" style="font-size: 12px; line-height: 1.2; display: block;">
                                    {{ $item->observaciones ?: 'Sin observaciones' }}
                                </span>
                            </td>

                            <td class="text-center small fw-bold text-muted text-nowrap" style="font-size: 11px;">
                                <i class="fas fa-user-circle me-1"></i> {{ $item->usuario->name ?? 'Sistema' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted small fw-bold">
                                <i class="fas fa-info-circle me-1 text-warning"></i> No se han registrado abastecimientos de tanques.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .text-orange { color: #ff6600 !important; }
    .fw-black { font-weight: 900; }
    /* Ajustes visuales para bordes de celdas */
    .table-bordered th, 
    .table-bordered td {
        border-color: #dee2e6 !important;
    }
</style>
@endsection