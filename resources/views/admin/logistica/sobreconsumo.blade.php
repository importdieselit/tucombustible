@extends('layouts.app')
@section('title', 'Clientes con Sobreconsumo')

@section('content')
<div class="container-fluid py-4 px-4">

    {{-- ENCABEZADO --}}
    <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h2 class="h4 fw-black text-dark text-uppercase mb-1">
                <i class="fas fa-exclamation-triangle text-orange me-2"></i> Clientes con Sobreconsumo
            </h2>
            <p class="text-muted small mb-0">Auditoría de clientes que excedieron su Cupo Gasco</p>
        </div>
    </div>

    {{-- BLOQUE DE FILTROS Y RESUMEN KPI --}}
    <div class="row g-3 mb-4">
        {{-- Formulario de Filtros --}}
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 p-3 bg-white h-100">
                <form action="{{ route('logistica.sobreconsumo') }}" method="GET" class="row g-2 align-items-end">
                    
                    {{-- FILTRO POR MES --}}
                    <div class="col-md-5">
                        <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Mes de Evaluación</label>
                        <select name="mes" class="form-select form-select-sm fw-bold text-dark" style="font-size: 13px;">
                            @php
                                $meses = [
                                    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                                    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                                    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                                ];
                            @endphp
                            @foreach($meses as $num => $nombre)
                                <option value="{{ $num }}" {{ $mesSeleccionado == $num ? 'selected' : '' }}>
                                    {{ $nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- FILTRO POR AÑO --}}
                    <div class="col-md-4">
                        <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Año</label>
                        <select name="anio" class="form-select form-select-sm fw-bold text-dark" style="font-size: 13px;">
                            @foreach($aniosDisponibles as $anio)
                                <option value="{{ $anio }}" {{ $anioSeleccionado == $anio ? 'selected' : '' }}>
                                    {{ $anio }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- ACCIÓN FILTRAR --}}
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-sm btn-dark w-100 fw-bold text-uppercase d-inline-flex align-items-center justify-content-center" style="font-size: 11px; height: 31px;">
                            <i class="fas fa-filter me-1"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Resumen Rápido / KPIs --}}
        <div class="col-lg-4">
            <div class="row g-2 h-100">
                <div class="col-6">
                    <div class="card shadow-sm border-0 p-3 bg-white h-100 border-start border-4 border-danger">
                        <span class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Clientes Excedidos</span>
                        <h4 class="fw-black text-danger mb-0">{{ $totales->total_clientes ?? 0 }}</h4>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card shadow-sm border-0 p-3 bg-white h-100 border-start border-4 border-warning" style="border-left-color: #ff6600 !important;">
                        <span class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Litros Excedidos</span>
                        <h4 class="fw-black text-orange mb-0" style="font-size: 1.15rem;">
                            {{ number_format($totales->total_litros_excedidos ?? 0, 2, ',', '.') }} <span style="font-size: 11px;">Lts</span>
                        </h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ALERTAS DE ÉXITO --}}
    @if(Session::has('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm fw-bold small" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ Session::get('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- TABLA DE CLIENTES CON SOBRECONSUMO --}}
    <div class="card shadow-sm border-0 mb-4" style="border-left: 4px solid #ff6600;">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-black text-uppercase small text-dark">
                <i class="fas fa-list text-orange me-2"></i> Relación de Excesos de Cupo — {{ $meses[$mesSeleccionado] ?? '' }} {{ $anioSeleccionado }}
            </h6>
            @if($esMesActual)
                <span class="badge bg-success text-uppercase fw-black px-2 py-1" style="font-size: 10px;">
                    <i class="fas fa-bolt me-1"></i> En Vivo (Mes en curso)
                </span>
            @else
                <span class="badge bg-secondary text-uppercase fw-bold px-2 py-1" style="font-size: 10px;">
                    <i class="fas fa-history me-1"></i> Histórico Cerrado
                </span>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="min-width: 1200px;">
                    <thead class="bg-light">
                        <tr class="text-uppercase text-muted" style="font-size: 12px; letter-spacing: 0.5px;">
                            <th class="ps-4">Cliente / Razón Social</th>
                            <th>RIF</th>
                            <th>Contacto / Ubicación</th>
                            <th class="text-end">Cupo Aprobado</th>
                            <th class="text-end">Litros Consumidos</th>
                            <th class="text-end">Sobreconsumo</th>
                            <th class="pe-4 text-center">% Exceso</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sobreconsumos as $item)
                            <tr>
                                {{-- Cliente --}}
                                <td class="ps-4">
                                    <div class="fw-bold text-dark" style="font-size: 13px;">{{ $item->cliente_nombre }}</div>
                                    <small class="text-muted font-monospace" style="font-size: 11px;">ID: #{{ $item->cliente_id }}</small>
                                </td>

                                {{-- RIF --}}
                                <td>
                                    <span class="badge bg-light text-secondary border text-uppercase fw-bold" style="font-size: 11px;">
                                        {{ $item->rif ?? 'N/A' }}
                                    </span>
                                </td>

                                {{-- Contacto / Ubicación --}}
                                <td>
                                    <div class="fw-bold text-dark" style="font-size: 13px;">
                                        <i class="fas fa-user-circle text-muted me-1"></i> {{ $item->contacto ?? 'Sin contacto' }}
                                    </div>
                                    <small class="text-muted" style="font-size: 11px;">
                                        {{ $item->telefono ?? $item->email ?? $item->estado_nombre ?? 'S/D' }}
                                    </small>
                                </td>

                                {{-- Cupo Autorizado --}}
                                <td class="text-end fw-bold text-dark font-monospace" style="font-size: 13px;">
                                    {{ number_format($item->litros_autorizados, 2, ',', '.') }} Lts
                                </td>

                                {{-- Litros Consumidos --}}
                                <td class="text-end fw-bold text-dark font-monospace" style="font-size: 13px;">
                                    {{ number_format($item->litros_consumidos, 2, ',', '.') }} Lts
                                </td>

                                {{-- Sobreconsumo --}}
                                <td class="text-end fw-black text-danger font-monospace" style="font-size: 14px;">
                                    +{{ number_format($item->litros_excedidos, 2, ',', '.') }} Lts
                                </td>

                                {{-- Porcentaje de Exceso --}}
                                <td class="pe-4 text-center">
                                    <span class="badge bg-danger fw-bold text-uppercase px-2 py-1" style="font-size: 10px;">
                                        +{{ number_format($item->porcentaje_exceso, 1) }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted small fw-bold">
                                    <i class="fas fa-check-circle me-1 text-success"></i> No se registraron clientes con sobreconsumo para {{ $meses[$mesSeleccionado] ?? '' }} {{ $anioSeleccionado }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($sobreconsumos->hasPages())
            <div class="card-footer bg-white border-top py-3 px-4">
                {{ $sobreconsumos->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

<style>
    .text-orange { color: #ff6600 !important; }
    .fw-black { font-weight: 900; }
</style>
@endsection