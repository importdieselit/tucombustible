@extends('layouts.app')
@section('title', 'Logística y Planificación')

@section('content')
<div class="container-fluid py-4 px-4">

    {{-- ENCABEZADO --}}
    <div class="mb-4">
        <h2 class="h4 fw-black text-dark text-uppercase mb-1">
            <i class="fas fa-route text-orange me-2"></i> Centro de Logística
        </h2>
        <p class="text-muted small">Gestión de Planificación de Despachos, Fletes y Reabastecimiento de ImporDiesel.</p>
    </div>

    {{-- ACCESOS DIRECTOS (LOS 4 TIPOS DE PLANIFICACIÓN) --}}
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <a href="{{ route('logistica.create', 'diesel') }}" class="text-decoration-none">
                <div class="card shadow-sm border-0 h-100 hover-lift text-center py-4 border-bottom-orange">
                    <div class="card-body p-0">
                        <div class="icon-circle bg-orange-light text-orange mx-auto mb-3">
                            <i class="fas fa-gas-pump fa-2x"></i>
                        </div>
                        <h6 class="fw-black text-dark text-uppercase mb-1">Despacho de Diesel</h6>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="{{ route('logistica.create', 'mgo') }}" class="text-decoration-none">
                <div class="card shadow-sm border-0 h-100 hover-lift text-center py-4 border-bottom-dark">
                    <div class="card-body p-0">
                        <div class="icon-circle bg-dark-light text-dark mx-auto mb-3">
                            <i class="fas fa-ship fa-2x"></i>
                        </div>
                        <h6 class="fw-black text-dark text-uppercase mb-1">Despacho de MGO</h6>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="{{ route('logistica.create', 'flete') }}" class="text-decoration-none">
                <div class="card shadow-sm border-0 h-100 hover-lift text-center py-4 border-bottom-orange">
                    <div class="card-body p-0">
                        <div class="icon-circle bg-orange-light text-orange mx-auto mb-3">
                            <i class="fas fa-truck-moving fa-2x"></i>
                        </div>
                        <h6 class="fw-black text-dark text-uppercase mb-1">Servicio Fletes</h6>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="{{ route('logistica.create', 'compra') }}" class="text-decoration-none">
                <div class="card shadow-sm border-0 h-100 hover-lift text-center py-4 border-bottom-dark">
                    <div class="card-body p-0">
                        <div class="icon-circle bg-dark-light text-dark mx-auto mb-3">
                            <i class="fas fa-file-invoice-dollar fa-2x"></i>
                        </div>
                        <h6 class="fw-black text-dark text-uppercase mb-1">Compra de Combustible</h6>
                    </div>
                </div>
            </a>
        </div>
    </div>

    {{-- BARRA DE FILTROS --}}
    <div class="card shadow-sm border-orange mb-4">
        <div class="card-body bg-light py-2">
            <form action="{{ route('logistica.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="small fw-bold text-uppercase text-muted mb-1">Tipo de Planificación</label>
                    <select name="tipo" class="form-select form-select-sm fw-bold">
                        <option value="">TODAS</option>
                        <option value="1" {{ request('tipo') == '1' ? 'selected' : '' }}>Despacho Diesel</option>
                        <option value="2" {{ request('tipo') == '2' ? 'selected' : '' }}>Despacho MGO</option>
                        <option value="3" {{ request('tipo') == '3' ? 'selected' : '' }}>Fletes</option>
                        <option value="4" {{ request('tipo') == '4' ? 'selected' : '' }}>Compras</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="small fw-bold text-uppercase text-muted mb-1">Estatus</label>
                    <select name="estado" class="form-select form-select-sm fw-bold">
                        <option value="">TODOS</option>
                        <option value="PROGRAMADO" {{ request('estado') == 'PROGRAMADO' ? 'selected' : '' }}>Programado</option>
                        <option value="EN RUTA" {{ request('estado') == 'EN RUTA' ? 'selected' : '' }}>En Ruta</option>
                        <option value="COMPLETADO" {{ request('estado') == 'COMPLETADO' ? 'selected' : '' }}>Completado</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="small fw-bold text-uppercase text-muted mb-1">Fecha</label>
                    <input type="date" name="fecha" value="{{ request('fecha') }}" class="form-control form-control-sm fw-bold">
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark btn-sm fw-bold text-uppercase w-100">
                        <i class="fas fa-search me-1"></i> Buscar
                    </button>
                    <a href="{{ route('logistica.index') }}" class="btn btn-outline-secondary btn-sm w-100 fw-bold">
                        Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- TABLA DE VIAJES / PLANIFICACIONES --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-black text-uppercase small"><i class="fas fa-list me-2"></i>Historial de Planificaciones</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="text-uppercase text-muted" style="font-size: 11px;">
                            <th class="ps-3">ID / Fecha</th>
                            <th>Tipo Planificación</th>
                            <th>Transporte</th>
                            <th>Origen / Destino</th>
                            <th>Volumen (L)</th>
                            <th>Estatus</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($viajes as $viaje)
                            <tr>
                                <td class="ps-3">
                                    <span class="fw-black text-dark d-block">V-{{ str_pad($viaje->id, 5, '0', STR_PAD_LEFT) }}</span>
                                    <small class="text-muted fw-bold">{{ \Carbon\Carbon::parse($viaje->fecha_salida)->format('d/m/Y') }}</small>
                                </td>
                                <td>
                                    @php
                                        $tipoData = match($viaje->tipo_planificacion) {
                                            1 => ['text' => 'DIESEL', 'color' => 'bg-orange'],
                                            2 => ['text' => 'MGO', 'color' => 'bg-dark'],
                                            3 => ['text' => 'FLETE', 'color' => 'bg-secondary'],
                                            4 => ['text' => 'COMPRA', 'color' => 'bg-info'],
                                            default => ['text' => 'OTRO', 'color' => 'bg-light text-dark']
                                        };
                                    @endphp
                                    <span class="badge {{ $tipoData['color'] }} text-white text-uppercase" style="font-size: 10px;">
                                        {{ $tipoData['text'] }}
                                    </span>
                                </td>
                                <td>
                                    @if($viaje->es_transporte_externo)
                                        <span class="text-muted small fw-bold d-block"><i class="fas fa-truck me-1"></i> Externo</span>
                                        <span class="fw-bold" style="font-size: 11px;">{{ $viaje->vehiculo_externo }}</span>
                                    @else
                                        <span class="text-success small fw-bold d-block"><i class="fas fa-truck-moving me-1"></i> ImporDiesel</span>
                                        <span class="fw-bold" style="font-size: 11px;">{{ $viaje->vehiculo->placa ?? 'S/A' }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($viaje->tipo_planificacion == 4) {{-- Compra --}}
                                        <small class="d-block fw-bold text-muted">A: {{ $viaje->sede->nombre ?? 'Planta' }}</small>
                                        <small class="d-block text-info">SAP: {{ $viaje->codigo_sap ?? 'N/A' }}</small>
                                    @elseif($viaje->tipo_planificacion == 3) {{-- Flete --}}
                                        <small class="d-block fw-bold">De: {{ Str::limit($viaje->punto_salida, 15) }}</small>
                                        <small class="d-block text-muted">A: {{ Str::limit($viaje->punto_llegada, 15) }}</small>
                                    @else {{-- Diesel o MGO --}}
                                        <small class="d-block fw-bold">De: {{ $viaje->sede->nombre ?? 'Planta' }}</small>
                                        <small class="d-block text-muted">Destinos: {{ $viaje->detalles->count() }}</small>
                                    @endif
                                </td>
                                <td class="fw-black">{{ number_format($viaje->litros_totales ?? $viaje->litros, 0) }} L</td>
                                <td>
                                    @php
                                        $statusColor = match($viaje->status) {
                                            'PROGRAMADO' => 'warning',
                                            'EN RUTA' => 'primary',
                                            'COMPLETADO' => 'success',
                                            'CANCELADO' => 'danger',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusColor }} text-uppercase" style="font-size: 9px;">{{ $viaje->status }}</span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-light border shadow-sm" title="Ver Detalles">
                                        <i class="fas fa-eye text-primary"></i>
                                    </button>
                                    <button class="btn btn-sm btn-light border shadow-sm" title="Imprimir Guía">
                                        <i class="fas fa-file-pdf text-danger"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <img src="https://cdn-icons-png.flaticon.com/512/2362/2362252.png" width="60" class="opacity-25 mb-3">
                                    <p class="text-muted fw-bold">No hay operaciones registradas con estos filtros.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-light border-top">
            {{ $viajes->links() }}
        </div>
    </div>
</div>

<style>
    .hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; cursor: pointer; }
    .hover-lift:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
    .border-bottom-orange { border-bottom: 4px solid #ff6600 !important; }
    .border-bottom-dark { border-bottom: 4px solid #212529 !important; }
    .icon-circle { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
    .bg-orange-light { background-color: rgba(255, 102, 0, 0.1); }
    .bg-dark-light { background-color: rgba(33, 37, 41, 0.1); }
    .text-orange { color: #ff6600 !important; }
    .bg-orange { background-color: #ff6600 !important; }
    .fw-black { font-weight: 900; }
</style>
@endsection