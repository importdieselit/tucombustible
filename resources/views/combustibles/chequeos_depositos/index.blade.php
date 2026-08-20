@extends('layouts.app')
@section('title', 'Historial de Varillajes')

@section('content')
<div class="container-fluid py-4 px-4">

    {{-- ENCABEZADO RESPONSIVO --}}
    <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h2 class="h4 fw-black text-dark text-uppercase mb-1">
                <i class="fas fa-history text-orange me-2"></i> Histórico de Aforos (Chequeos de Tanques)
            </h2>
            <p class="text-muted small mb-0">Cronología, control de turnos y trazabilidad de cubicación en sedes de ImporDiesel.</p>
        </div>
        <div>
            <a href="{{ route('combustibles.chequeos_depositos.create') }}" class="btn btn-sm btn-dark fw-bold text-uppercase shadow-sm py-2 px-3" style="font-size: 12px;">
                <i class="fas fa-plus-circle me-1"></i> Registrar Aforo
            </a>
        </div>
    </div>

    {{-- PANEL DE FILTROS AVANZADOS (SEDE + RANGO DE FECHAS) --}}
    <form action="{{ url()->current() }}" method="GET" class="row g-2 align-items-end mb-4">
        <div class="col-md-3">
            <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Ubicación / Sede</label>
            <select name="id_sede" class="form-select form-select-sm fw-bold" style="font-size: 13px;" onchange="this.form.submit()">
                <option value="">SELECCIONE UNA SEDE</option>
                @foreach($sedes as $sede)
                    <option value="{{ $sede->id }}" {{ request('id_sede') == $sede->id ? 'selected' : '' }}>
                        {{ $sede->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Fecha Inicio</label>
            <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}" class="form-control form-control-sm fw-bold" style="font-size: 13px; cursor: pointer;" onchange="this.form.submit()" onclick="this.showPicker()">
        </div>

        <div class="col-md-2">
            <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Fecha Fin</label>
            <input type="date" name="fecha_fin" value="{{ request('fecha_fin') }}" class="form-control form-control-sm fw-bold" style="font-size: 13px; cursor: pointer;" onchange="this.form.submit()" onclick="this.showPicker()">
        </div>
        
        @if(request('id_sede') || request('fecha_inicio') || request('fecha_fin'))
            <div class="col-md-1">
                <a href="{{ url()->current() }}" class="btn btn-outline-secondary btn-sm w-100 fw-bold text-uppercase" style="font-size: 12px; padding: 5px 0;">
                    Limpiar
                </a>
            </div>
        @endif
    </form>

    {{-- TABLA DE AUDITORÍAS DE VARILLAJE --}}
    <div class="card shadow-sm border-0" style="border-left: 4px solid #ff6600;">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-black text-uppercase small text-dark">
                <i class="fas fa-eye-dropper text-orange me-2"></i> Chequeos Realizados
            </h6>
        </div>
        <div class="card-body p-0">
            {{-- SEGURO DE SCROLL VERTICAL Y HORIZONTAL --}}
            <div class="table-responsive" style="max-height: 500px; overflow-y: auto; overflow-x: auto;">
                {{-- min-width previene el colapso de columnas en pantallas pequeñas --}}
                <table class="table table-hover align-middle mb-0" style="min-width: 1050px;">
                    <thead class="bg-light sticky-top" style="z-index: 10;">
                        <tr class="text-uppercase text-muted" style="font-size: 13px;">
                            <th class="ps-4" style="width: 130px;">Fecha</th>
                            <th style="width: 120px;">Hora Reg.</th>
                            <th style="width: 180px;">Sede</th>
                            <th class="text-center" style="width: 140px;">Turno</th>
                            <th style="width: 180px;">Auditor</th>
                            <th>Observaciones</th>
                            <th class="text-center" style="width: 100px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($chequeos as $chequeo)
                            <tr>
                                <td class="ps-4 fw-bold">{{ \Carbon\Carbon::parse($chequeo->fecha)->format('d/m/Y') }}</td>
                                <td class="text-muted fw-bold" style="font-size: 13px;">
                                    {{ \Carbon\Carbon::parse($chequeo->created_at)->format('h:i A') }}
                                </td>
                                <td class="text-muted fw-bold" style="font-size: 14px;">
                                    <i class="fas fa-map-marker-alt text-secondary me-1"></i> {{ $chequeo->sede_nombre }}
                                </td>
                                <td class="text-center">
                                    @if(Str::contains(Str::lower($chequeo->turno), 'matutino') || Str::contains(Str::lower($chequeo->turno), 'dia'))
                                        <span class="badge bg-warning text-dark text-uppercase fw-bold shadow-sm" style="font-size: 11px; letter-spacing: 0.5px; border: 1px solid #e0a800;">
                                            <i class="fas fa-sun me-1"></i> {{ $chequeo->turno }}
                                        </span>
                                    @elseif(Str::contains(Str::lower($chequeo->turno), 'nocturno') || Str::contains(Str::lower($chequeo->turno), 'noche'))
                                        <span class="badge bg-indigo text-white text-uppercase fw-bold shadow-sm" style="font-size: 11px; letter-spacing: 0.5px; background-color: #3b3f5c !important;">
                                            <i class="fas fa-moon me-1"></i> {{ $chequeo->turno }}
                                        </span>
                                    @else
                                        <span class="badge bg-dark text-white text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                                            <i class="fas fa-clock me-1"></i> {{ $chequeo->turno }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-muted small fw-bold">
                                    <i class="fas fa-user-circle me-1"></i> {{ $chequeo->usuario_nombre }}
                                </td>
                                {{-- Control de desborde de texto en observaciones --}}
                                <td class="text-muted small" style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $chequeo->observaciones }}">
                                    {{ $chequeo->observaciones ?? 'Sin observaciones.' }}
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <button type="button" 
                                                class="btn btn-sm btn-light border shadow-sm btn-ver-detalle" 
                                                title="Ver Cubicaciones Completas"
                                                data-id="{{ $chequeo->id }}"
                                                data-fecha="{{ \Carbon\Carbon::parse($chequeo->fecha)->format('d/m/Y') }}"
                                                data-sede="{{ $chequeo->sede_nombre }}"
                                                data-turno="{{ $chequeo->turno }}">
                                            <i class="fas fa-eye text-orange"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 fw-bold text-muted" style="font-size: 14px;">
                                    No hay registros de varillaje que coincidan con los filtros aplicados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- PAGINACIÓN --}}
        @if ($chequeos->hasPages())
            <div class="card-footer bg-white border-top py-3 px-4 d-flex justify-content-between align-items-center">
                <span class="small text-muted fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
                    Mostrando del {{ $chequeos->firstItem() }} al {{ $chequeos->lastItem() }} de un total de {{ $chequeos->total() }} registros
                </span>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        @if ($chequeos->onFirstPage())
                            <li class="page-item disabled"><span class="page-link fw-bold text-uppercase bg-light text-muted" style="font-size: 11px; padding: 6px 12px;">Anterior</span></li>
                        @else
                            <li class="page-item"><a class="page-link fw-bold text-uppercase text-dark border shadow-sm" href="{{ $chequeos->appends(request()->query())->previousPageUrl() }}" style="font-size: 11px; padding: 6px 12px;">Anterior</a></li>
                        @endif

                        @if ($chequeos->hasMorePages())
                            <li class="page-item"><a class="page-link fw-bold text-uppercase text-dark border shadow-sm" href="{{ $chequeos->appends(request()->query())->nextPageUrl() }}" style="font-size: 11px; padding: 6px 12px;">Siguiente</a></li>
                        @else
                            <li class="page-item disabled"><span class="page-link fw-bold text-uppercase bg-light text-muted" style="font-size: 11px; padding: 6px 12px;">Siguiente</span></li>
                        @endif
                    </ul>
                </nav>
            </div>
        @endif
    </div>
</div>

@include('combustibles.chequeos_depositos.partials.modal_detalle')

<style>
    .text-orange { color: #ff6600 !important; }
    .fw-black { font-weight: 900; }
</style>
@endsection