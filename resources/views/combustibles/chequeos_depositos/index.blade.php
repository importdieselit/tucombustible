@extends('layouts.app')
@section('title', 'Historial de Varillajes')

@section('content')
<div class="container-fluid py-4 px-4">

    {{-- ENCABEZADO --}}
    <div class="mb-4 d-flex justify-content-between align-items-end">
        <div>
            <h2 class="h4 fw-black text-dark text-uppercase mb-1">
                <i class="fas fa-history text-orange me-2"></i> Historial de Varillajes
            </h2>
            <p class="text-muted small mb-0">Cronología, control de turnos y trazabilidad de cubicación en sedes de ImporDiesel.</p>
        </div>
        <div>
            <a href="{{ route('combustibles.chequeos_depositos.create') }}" class="btn btn-sm btn-dark fw-bold text-uppercase shadow-sm py-2 px-3" style="font-size: 12px;">
                <i class="fas fa-plus-circle me-1"></i> Registrar Varillaje
            </a>
        </div>
    </div>

    {{-- FILTROS POR SEDE --}}
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
        
        @if(request('id_sede'))
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
                <i class="fas fa-eye-dropper text-orange me-2"></i> Auditorías de Varillaje Registradas
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light sticky-top" style="z-index: 10;">
                        <tr class="text-uppercase text-muted" style="font-size: 13px;">
                            <th class="ps-4">Fecha</th>
                            <th>Hora Reg.</th>
                            <th>Sede</th>
                            <th class="text-center">Turno</th>
                            <th>Auditor / Usuario</th>
                            <th>Observaciones</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($chequeos as $chequeo)
                            <tr>
                                {{-- Fecha del Chequeo --}}
                                <td class="ps-4 fw-black text-dark" style="font-size: 15px;">
                                    {{ \Carbon\Carbon::parse($chequeo->fecha)->format('d/m/Y') }}
                                </td>
                                
                                {{-- Hora exacta del registro --}}
                                <td class="text-muted fw-bold" style="font-size: 13px;">
                                    {{ \Carbon\Carbon::parse($chequeo->created_at)->format('h:i A') }}
                                </td>
                                
                                {{-- Sede --}}
                                <td class="text-muted fw-bold" style="font-size: 14px;">
                                    <i class="fas fa-map-marker-alt text-secondary me-1"></i> {{ $chequeo->sede_nombre }}
                                </td>
                                
                                {{-- Turno --}}
                                <td class="text-center">
                                    <span class="badge bg-dark text-white text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
                                        {{ $chequeo->turno }}
                                    </span>
                                </td>
                                
                                {{-- Nombre del Auditor --}}
                                <td class="text-muted small fw-bold">
                                    <i class="fas fa-user-circle me-1"></i> {{ $chequeo->usuario_nombre }}
                                </td>
                                
                                {{-- Observaciones --}}
                                <td class="text-muted small" style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $chequeo->observaciones }}">
                                    {{ $chequeo->observaciones ?? 'Sin observaciones.' }}
                                </td>
                                
                                {{-- Acciones --}}
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <button type="button" class="btn btn-sm btn-light border shadow-sm" title="Ver Cubicaciones Completas" onclick="alert('Detalle de cubicación en desarrollo. ID: {{ $chequeo->id }}')">
                                            <i class="fas fa-eye text-secondary"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 fw-bold text-muted" style="font-size: 14px;">
                                    No hay registros de varillaje cargados en el sistema
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- COMPONENTE DE PAGINACIÓN SIMÉTRICO Y MANUAL (Evita errores de Vendor/Views) --}}
        @if ($chequeos->hasPages())
            <div class="card-footer bg-white border-top py-3 px-4 d-flex justify-content-between align-items-center">
                <span class="small text-muted fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
                    Mostrando del {{ $chequeos->firstItem() }} al {{ $chequeos->lastItem() }} de un total de {{ $chequeos->total() }} registros
                </span>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        {{-- Botón Anterior --}}
                        @if ($chequeos->onFirstPage())
                            <li class="page-item disabled">
                                <span class="page-link fw-bold text-uppercase bg-light text-muted" style="font-size: 11px; padding: 6px 12px;">Anterior</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link fw-bold text-uppercase text-dark border shadow-sm" href="{{ $chequeos->appends(request()->query())->previousPageUrl() }}" style="font-size: 11px; padding: 6px 12px;">Anterior</a>
                            </li>
                        @endif

                        {{-- Botón Siguiente --}}
                        @if ($chequeos->hasMorePages())
                            <li class="page-item">
                                <a class="page-link fw-bold text-uppercase text-dark border shadow-sm" href="{{ $chequeos->appends(request()->query())->nextPageUrl() }}" style="font-size: 11px; padding: 6px 12px;">Siguiente</a>
                            </li>
                        @else
                            <li class="page-item disabled">
                                <span class="page-link fw-bold text-uppercase bg-light text-muted" style="font-size: 11px; padding: 6px 12px;">Siguiente</span>
                            </li>
                        @endif
                    </ul>
                </nav>
            </div>
        @endif
    </div>
</div>

<style>
    .text-orange { color: #ff6600 !important; }
    .fw-black { font-weight: 900; }
</style>
@endsection