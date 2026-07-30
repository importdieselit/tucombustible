@extends('layouts.app')
@section('title', 'Historial de Consumos Operativos')

@section('content')
<div class="container-fluid py-4 px-4">

    {{-- ENCABEZADO UNIFICADO --}}
    <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h2 class="h4 fw-black text-dark text-uppercase mb-1">
                <i class="fas fa-tools text-orange me-2"></i> Consumos Operativos
            </h2>
            <p class="text-muted small mb-0">Historial de combustible despachado para flota de vehículos y maquinaria interna</p>
        </div>
        <div class="d-flex flex-wrap align-items-center justify-content-md-end gap-2">
            <a href="{{ route('combustibles.consumos_operativos.create') }}" class="btn btn-sm btn-warning fw-black text-uppercase shadow-sm px-3 d-inline-flex align-items-center" style="font-size: 12px; height: 32px; color: #000; background-color: #ff6600; border-color: #ff6600;">
                <i class="fas fa-plus-circle me-1"></i> Registrar Consumo
            </a>
        </div>
    </div>

    {{-- BLOQUE DE FILTROS AVANZADOS (Symmetric 12-col Grid) --}}
    <div class="card shadow-sm border-0 mb-4 p-3 bg-white">
        <form action="{{ url()->current() }}" method="GET" class="row g-2 align-items-end">
            
            <div class="col-md-4">
                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Filtrar por Sede</label>
                <select name="sede_id" class="form-select form-select-sm fw-bold text-dark" style="font-size: 13px;">
                    <option value="">Seleccione una sede</option>
                    @foreach($sedes as $sede)
                        <option value="{{ $sede->id }}" {{ request('sede_id') == $sede->id ? 'selected' : '' }}>
                            {{ $sede->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- RANGO DE FECHAS: DESDE --}}
            <div class="col-md-3">
                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Fecha Desde</label>
                <input type="date" name="fecha_desde" class="form-control form-control-sm fw-bold text-dark" style="font-size: 13px;" value="{{ request('fecha_desde') }}">
            </div>

            {{-- RANGO DE FECHAS: HASTA --}}
            <div class="col-md-3">
                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Fecha Hasta</label>
                <input type="date" name="fecha_hasta" class="form-control form-control-sm fw-bold text-dark" style="font-size: 13px;" value="{{ request('fecha_hasta') }}">
            </div>
            
            {{-- ACCIONES DEL FILTRO --}}
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-dark w-100 fw-bold text-uppercase d-inline-flex align-items-center justify-content-center" style="font-size: 11px; height: 31px;">
                    <i class="fas fa-filter me-1"></i> Filtrar
                </button>
                
                @if(request()->filled('sede_id') || request()->filled('fecha_desde') || request()->filled('fecha_hasta'))
                    <a href="{{ url()->current() }}" class="btn btn-outline-secondary btn-sm w-100 fw-bold text-uppercase d-inline-flex align-items-center justify-content-center" style="font-size: 11px; height: 31px;">
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- MENSAJES DE ÉXITO O ERROR --}}
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
                <i class="fas fa-list text-orange me-2"></i> Registro de Consumos Realizados
            </h6>
        </div>
        
        <div class="card-body p-0">
            {{-- Contenedor con Scroll Doble Personalizado --}}
            <div class="table-responsive custom-table-scroll" style="max-height: 520px; overflow-y: auto; overflow-x: auto;">
                <table class="table table-hover table-bordered-custom align-middle mb-0" style="min-width: 1350px; border-collapse: separate; border-spacing: 0;">
                    {{-- Cabecera Sticky (Fija al hacer scroll vertical) --}}
                    <thead style="position: sticky; top: 0; z-index: 10; background-color: #f8f9fa;">
                        <tr class="text-uppercase text-muted" style="font-size: 12px; letter-spacing: 0.5px;">
                            <th class="ps-4 py-3 text-nowrap" style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; min-width: 160px;">Fecha y Hora</th>
                            <th class="py-3 text-nowrap" style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; min-width: 170px;">Vehículo / Equipo</th>
                            <th class="py-3 text-nowrap" style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; min-width: 130px;">Sede</th>
                            <th class="py-3 text-nowrap" style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; min-width: 140px;">Tanque Emisor</th>
                            <th class="py-3 text-nowrap" style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; min-width: 120px;">Combustible</th>
                            {{-- AJUSTE: Cabecera con ancho mínimo garantizado --}}
                            <th class="py-3 text-nowrap" style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; min-width: 200px;">Responsable</th>
                            <th class="py-3 text-nowrap" style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; min-width: 280px;">Observaciones</th>
                            <th class="pe-4 py-3 text-end text-nowrap" style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; min-width: 120px;">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($consumos as $consumo)
                            <tr>
                                <td class="ps-4 font-monospace small fw-bold text-dark text-nowrap" style="min-width: 160px;">
                                    {{ $consumo->created_at->format('d/m/Y h:i A') }}
                                </td>
                                <td class="text-nowrap" style="min-width: 170px;">
                                    @if($consumo->vehiculo_id)
                                        <span class="badge bg-dark text-white font-monospace text-uppercase p-2" style="font-size: 11px; letter-spacing: 0.5px;">
                                            <i class="fas fa-truck text-warning me-1"></i> Placa: {{ $consumo->vehiculo->placa ?? 'S/P' }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary text-white font-monospace text-uppercase p-2" style="font-size: 11px; letter-spacing: 0.5px;">
                                            <i class="fas fa-industry text-info me-1"></i> {{ $consumo->equipo_maquinaria }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-nowrap" style="min-width: 130px;">
                                    <span class="badge bg-light text-secondary border text-uppercase fw-bold" style="font-size: 11px;">
                                        {{ $consumo->sede->nombre }}
                                    </span>
                                </td>
                                <td class="text-nowrap" style="min-width: 140px;">
                                    <span class="fw-bold text-secondary font-monospace" style="font-size: 13px;">
                                        <i class="fas fa-database text-muted me-1"></i> {{ $consumo->deposito->serial ?? 'N/A' }}
                                    </span>
                                </td>
                                <td class="text-nowrap" style="min-width: 120px;">
                                    @if($consumo->tipo_combustible_id == 2)
                                        <span class="badge bg-warning text-dark fw-bold text-uppercase" style="font-size: 10px; background-color: #ffa500 !important;">DIESEL</span>
                                    @else
                                        <span class="badge bg-info text-white fw-bold text-uppercase" style="font-size: 10px; background-color: #00a8ff !important;">MGO</span>
                                    @endif
                                </td>
                                {{-- AJUSTE: Celda con espacio optimizado, texto nítido y sin cortes --}}
                                <td class="text-nowrap" style="min-width: 200px;">
                                    <span class="text-dark fw-bold" style="font-size: 13px;">
                                        <i class="fas fa-user-shield text-muted me-1"></i> {{ $consumo->user->name ?? 'Sistema' }}
                                    </span>
                                </td>
                                {{-- Observaciones legibles sin cortar abruptamente la fila --}}
                                <td class="text-muted small" style="max-width: 320px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $consumo->observaciones }}">
                                    {{ $consumo->observaciones ?? 'Sin observaciones' }}
                                </td>
                                <td class="pe-4 text-end fw-black text-dark font-monospace text-nowrap" style="font-size: 15px; min-width: 120px;">
                                    {{ number_format($consumo->cantidad_litros, 2, ',', '.') }} Lts
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted small fw-bold">
                                    <i class="fas fa-info-circle me-1 text-warning"></i> No se han registrado consumos operativos.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        @if($consumos->hasPages())
            <div class="card-footer bg-white border-top py-3 px-4">
                {{ $consumos->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

<style>
    .text-orange { color: #ff6600 !important; }
    .fw-black { font-weight: 900; }

    /* Personalización fina de las barras de desplazamiento (Scrollbars) */
    .custom-table-scroll::-webkit-scrollbar {
        width: 8px;  /* Scroll vertical */
        height: 8px; /* Scroll horizontal */
    }
    .custom-table-scroll::-webkit-scrollbar-track {
        background: #f8f9fa;
        border-radius: 4px;
    }
    .custom-table-scroll::-webkit-scrollbar-thumb {
        background: #ced4da;
        border-radius: 4px;
    }
    .custom-table-scroll::-webkit-scrollbar-thumb:hover {
        background: #ff6600; /* Color naranja corporativo */
    }

    /* Líneas divisorias verticales entre columnas */
    .table-bordered-custom th,
    .table-bordered-custom td {
        border-right: 1px solid #e9ecef !important; /* Líneas verticales sutiles */
    }

    /* Quita la línea vertical en el extremo derecho de la tabla para que se vea limpia */
    .table-bordered-custom th:last-child,
    .table-bordered-custom td:last-child {
        border-right: none !important;
    }
</style>
@endsection