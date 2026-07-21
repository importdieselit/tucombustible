@extends('layouts.app')
@section('title', 'Ledger de Transacciones de Combustible')

@section('content')
<div class="container-fluid py-4 px-4">

    {{-- ENCABEZADO --}}
    <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h2 class="h4 fw-black text-dark text-uppercase mb-1">
                <i class="fas fa-book text-orange me-2"></i> Historial de Transacciones de Combustibles
            </h2>
            <p class="text-muted small mb-0">Libro mayor de auditoría y movimientos históricos de inventario</p>
        </div>
    </div>

    {{-- BLOQUE DE FILTROS AVANZADOS --}}
    <div class="card shadow-sm border-0 mb-4 p-3 bg-white">
        <form action="{{ url()->current() }}" method="GET" class="row g-2 align-items-end">
            
            {{-- FILTRO POR TIPO DE MOVIMIENTO --}}
            <div class="col-md-2">
                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Movimiento</label>
                <select name="tipo_movimiento" class="form-select form-select-sm fw-bold text-dark" style="font-size: 13px;">
                    <option value="">TODOS LOS MOVIMIENTOS</option>
                    
                    {{-- Entradas y Salidas --}}
                    <option value="compra" {{ request('tipo_movimiento') == 'compra' ? 'selected' : '' }}>COMPRAS (ENTRADAS)</option>
                    <option value="despacho" {{ request('tipo_movimiento') == 'despacho' ? 'selected' : '' }}>DESPACHOS (SALIDAS)</option>
                    <option value="despacho_prepagado" {{ request('tipo_movimiento') == 'despacho_prepagado' ? 'selected' : '' }}>PREPAGADOS (SALIDAS)</option>
                    <option value="consumo_operativo" {{ request('tipo_movimiento') == 'consumo_operativo' ? 'selected' : '' }}>CONSUMOS OPERATIVOS</option>
                    
                    {{-- Trasegados --}}
                    <option value="trasegado_interno" {{ request('tipo_movimiento') == 'trasegado_interno' ? 'selected' : '' }}>TRASEGADO INTERNO</option>
                    <option value="trasegado_inter-sede" {{ request('tipo_movimiento') == 'trasegado_inter-sede' ? 'selected' : '' }}>TRASEGADO INTER-SEDE</option>
                    <option value="trasegado_externo" {{ request('tipo_movimiento') == 'trasegado_externo' ? 'selected' : '' }}>TRASEGADO EXTERNO</option>
                    
                    {{-- Ajustes y Reversos --}}
                    <option value="ajuste_positivo" {{ request('tipo_movimiento') == 'ajuste_positivo' ? 'selected' : '' }}>AJUSTES (+)</option>
                    <option value="ajuste_negativo" {{ request('tipo_movimiento') == 'ajuste_negativo' ? 'selected' : '' }}>AJUSTES (-)</option>
                    <option value="reverso" {{ request('tipo_movimiento') == 'reverso' ? 'selected' : '' }}>REVERSOS</option>
                </select>
            </div>

            {{-- FILTRO POR SEDE --}}
            <div class="col-md-2">
                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Sede</label>
                <select name="sede_id" class="form-select form-select-sm fw-bold text-dark" style="font-size: 13px;">
                    <option value="">TODAS</option>
                    @foreach($sedes as $sede)
                        <option value="{{ $sede->id }}" {{ request('sede_id') == $sede->id ? 'selected' : '' }}>
                            {{ strtoupper($sede->nombre) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- FILTRO POR TIPO DE COMBUSTIBLE --}}
            <div class="col-md-2">
                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Combustible</label>
                <select name="tipo_combustible_id" class="form-select form-select-sm fw-bold text-dark" style="font-size: 13px;">
                    <option value="">TODOS</option>
                    @foreach($tiposCombustible as $tipo)
                        <option value="{{ $tipo->id }}" {{ request('tipo_combustible_id') == $tipo->id ? 'selected' : '' }}>
                            {{ strtoupper($tipo->nombre) }}
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
                
                @if(request()->filled('tipo_movimiento') || request()->filled('sede_id') || request()->filled('deposito_id') || request()->filled('tipo_combustible_id') || request()->filled('fecha_desde') || request()->filled('fecha_hasta'))
                    <a href="{{ url()->current() }}" class="btn btn-outline-secondary btn-sm w-100 fw-bold text-uppercase d-inline-flex align-items-center justify-content-center" style="font-size: 11px; height: 31px;">
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- TABLA LEDGER HISTÓRICA --}}
    <div class="card shadow-sm border-0 mb-4" style="border-left: 4px solid #ff6600;">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-black text-uppercase small text-dark">
                <i class="fas fa-list text-orange me-2"></i> Asientos del Libro de Combustible
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="min-width: 1200px;">
                    <thead class="bg-light">
                        <tr class="text-uppercase text-muted" style="font-size: 12px; letter-spacing: 0.5px;">
                            <th class="ps-4">Fecha y Hora</th>
                            <th>Sede / Ubicación</th>
                            <th>Depósito / Tanque</th>
                            <th>Tipo Movimiento</th>
                            <th>Combustible</th>
                            <th>Cliente / Aliado</th>
                            <th>Operador (Usuario)</th>
                            <th class="text-end">Litros</th>
                            <th class="pe-4 text-end" style="width: 80px;">Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transacciones as $transaccion)
                            @php
                                // Configuración visual según la naturaleza contable del movimiento
                                $badgeClass = 'bg-secondary text-white';
                                $tipoLabel = str_replace('_', ' ', $transaccion->tipo_movimiento);
                                $signo = '';
                                $litrosColor = 'text-dark';

                                switch($transaccion->tipo_movimiento) {
                                    case 'compra':
                                    case 'ingreso':
                                        $badgeClass = 'bg-success text-white';
                                        $signo = '+';
                                        $litrosColor = 'text-success';
                                        break;
                                    case 'despacho':
                                        $badgeClass = 'bg-primary text-white';
                                        $signo = '-';
                                        $litrosColor = 'text-danger';
                                        break;
                                    case 'despacho_prepagado':
                                        $badgeClass = 'bg-warning text-dark';
                                        $signo = '-';
                                        $litrosColor = 'text-danger';
                                        break;
                                    case 'ajuste_positivo':
                                        $badgeClass = 'bg-info text-dark';
                                        $signo = '+';
                                        $litrosColor = 'text-success';
                                        break;
                                    case 'ajuste_negativo':
                                        $badgeClass = 'bg-danger text-white';
                                        $signo = '-';
                                        $litrosColor = 'text-danger';
                                        break;
                                }
                            @endphp
                            <tr>
                                {{-- Fecha y Hora --}}
                                <td class="ps-4 font-monospace small fw-bold text-dark">
                                    {{ $transaccion->created_at->format('d/m/Y h:i A') }}
                                </td>

                                {{-- Sede --}}
                                <td>
                                    <span class="badge bg-light text-secondary border text-uppercase fw-bold" style="font-size: 11px;">
                                        {{ $transaccion->sede->nombre ?? 'N/A' }}
                                    </span>
                                </td>

                                {{-- Depósito --}}
                                <td>
                                    <span class="fw-bold text-secondary" style="font-size: 13px;">
                                        <i class="fas fa-database text-muted me-1"></i> {{ $transaccion->deposito->serial ?? 'N/A' }}
                                    </span>
                                </td>

                                {{-- Tipo de Movimiento Badge --}}
                                <td>
                                    <span class="badge {{ $badgeClass }} text-uppercase fw-bold" style="font-size: 10px; letter-spacing: 0.3px; padding: 5px 8px;">
                                        {{ $tipoLabel }}
                                    </span>
                                </td>

                                {{-- Combustible --}}
                                <td>
                                    @if(($transaccion->tipoCombustible->id ?? null) == 2 || ($transaccion->tipo_combustible_id ?? null) == 2)
                                        <span class="badge bg-warning text-dark fw-bold text-uppercase" style="font-size: 10px; background-color: #ffa500 !important;">DIESEL</span>
                                    @else
                                        <span class="badge bg-info text-white fw-bold text-uppercase" style="font-size: 10px; background-color: #00a8ff !important;">MGO</span>
                                    @endif
                                </td>

                                {{-- Tercero Asociado (Cliente o Aliado Comercial) --}}
                                <td>
                                    @if($transaccion->cliente)
                                        <span class="fw-black text-dark d-block" style="font-size: 13px;">
                                            {{ $transaccion->cliente->nombre }}
                                        </span>
                                        <small class="text-muted font-monospace" style="font-size: 11px;">
                                            {{ $transaccion->cliente->rif ?? 'S/N' }}
                                        </small>
                                    @else
                                        <span class="text-muted italic small" style="font-size: 12px;">Uso Interno / Ajuste</span>
                                    @endif
                                </td>

                                {{-- Operador / Usuario --}}
                                <td>
                                    <span class="text-dark fw-bold" style="font-size: 12px;">
                                        <i class="fas fa-user-cog text-muted me-1"></i> {{ $transaccion->user->name ?? 'Sistema' }}
                                    </span>
                                </td>

                                {{-- Litros Afectados --}}
                                <td class="text-end fw-black {{ $litrosColor }}" style="font-size: 15px;">
                                    {{ $signo }} {{ number_format($transaccion->cantidad_litros, 2, ',', '.') }} Lts
                                </td>

                                {{-- Botón de Detalle --}}
                                <td class="pe-4 text-end">
                                    <a href="{{ route('combustibles.transacciones.show', $transaccion->id) }}" class="btn btn-sm btn-outline-dark p-0 d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 28px; height: 28px; border-radius: 4px;" title="Ver auditoría">
                                        <i class="fas fa-eye" style="font-size: 11px;"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted small fw-bold">
                                    <i class="fas fa-info-circle me-1 text-warning"></i> No se han registrado movimientos en el Ledger para los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        {{-- Paginación --}}
        @if($transacciones->hasPages())
            <div class="card-footer bg-white border-top py-3 px-4">
                {{ $transacciones->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

<style>
    .text-orange { color: #ff6600 !important; }
    .fw-black { font-weight: 900; }
</style>
@endsection