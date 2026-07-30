@extends('layouts.app')
@section('title', 'Módulo de Combustibles - Dashboard')

@section('content')
<div class="container-fluid">

    {{-- 1. ENCABEZADO PRINCIPAL DE LA VISTA CON FILTRO DE SEDES --}}
    @php
        $sedeFiltro = request('id_sede', $sedeId ?? null);
    @endphp

    <div class="row page-titles mb-4">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center bg-white p-3 shadow-sm rounded border border-gray-300 gap-3">
            <div>
                <h3 class="text-orange mb-0 fw-bold text-uppercase italic">| Panel Central de Combustibles</h3>
                <div class="text-[11px] font-black text-gray-500 text-uppercase mt-1">Control de Inventario, Disponibilidad & Operaciones de Tanques</div>
            </div>

            {{-- FILTRO DE SEDE Y BOTÓN --}}
            <div class="d-flex align-items-center gap-2">
                <form action="{{ route('combustibles.dashboard') }}" method="GET" class="d-flex align-items-center gap-2 m-0" id="formFiltroSede">
                    <label for="id_sede" class="text-xs font-black text-uppercase text-muted mb-0 whitespace-nowrap">
                        <i class="fas fa-filter text-orange me-1"></i> Filtrar Sede:
                    </label>
                    <select name="id_sede" id="id_sede" class="form-select form-select-sm fw-bold border-gray-300 text-xs shadow-sm" onchange="this.form.submit();" style="min-width: 220px;">
                        <option value="">-- TODAS LAS SEDES (GLOBAL) --</option>
                        @foreach($sedes ?? [] as $s)
                            <option value="{{ $s->id }}" {{ (string)$sedeFiltro === (string)$s->id ? 'selected' : '' }}>
                                {{ $s->nombre }}
                            </option>
                        @endforeach
                    </select>
                </form>

                {{-- BOTÓN LIMPIAR FILTRO --}}
                @if(!empty($sedeFiltro))
                    <a href="{{ route('combustibles.dashboard') }}" class="btn btn-sm btn-outline-secondary text-uppercase fw-bold shadow-sm whitespace-nowrap" title="Limpiar filtro y ver todas las sedes">
                        <i class="fas fa-undo me-1 text-orange"></i> Limpiar Filtro
                    </a>
                @else
                    <button type="button" class="btn btn-sm btn-outline-secondary text-uppercase fw-bold shadow-sm whitespace-nowrap opacity-50" disabled>
                        <i class="fas fa-undo me-1"></i> Limpiar Filtro
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- 2. SECCIÓN DE DISPONIBILIDADES / RESUMEN DE INVENTARIO --}}
    <div class="card mb-4 border border-gray-300 shadow-sm">
        <div class="card-header bg-gray-800 py-2">
            <h6 class="text-white mb-0 text-xs font-black text-uppercase">
                <i class="fas fa-tachometer-alt text-orange me-2"></i> 
                {{ empty($sedeId) ? 'Estado Global de Disponibilidad en Tanques' : 'Disponibilidad Filtrada por Sede' }}
            </h6>
        </div>
        <div class="card-body bg-light">
            {{-- KPI CARDS RÁPIDOS --}}
            <div class="row g-3 mb-4">
                {{-- DIESEL --}}
                <div class="col-md-3">
                    <div class="bg-white p-3 rounded border border-gray-300 shadow-sm h-100 border-start border-4 border-primary">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-xs font-black text-gray-500 text-uppercase d-block">Disponibilidad DIESEL</span>
                                <div class="text-2xl font-black text-dark mt-1">
                                    {{ number_format($totalDisponibleDiesel ?? 0, 0, ',', '.') }} <small class="fs-6 text-muted">Lts</small>
                                </div>
                            </div>
                            <div class="rounded-circle bg-primary bg-opacity-10 p-3 text-primary">
                                <i class="fas fa-gas-pump fa-2x"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between text-xs font-bold text-muted mb-1">
                                <span>Capacidad Utilizada</span>
                                <span>{{ number_format($porcentajeDiesel ?? 0, 1) }}%</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: {{ min($porcentajeDiesel ?? 0, 100) }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- MGO (MARINO) --}}
                <div class="col-md-3">
                    <div class="bg-white p-3 rounded border border-gray-300 shadow-sm h-100 border-start border-4 border-warning" style="border-left-color: #ff6600 !important;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-xs font-black text-gray-500 text-uppercase d-block">Disponibilidad MGO</span>
                                <div class="text-2xl font-black text-dark mt-1">
                                    {{ number_format($totalDisponibleMgo ?? 0, 0, ',', '.') }} <small class="fs-6 text-muted">Lts</small>
                                </div>
                            </div>
                            <div class="rounded-circle bg-warning bg-opacity-10 p-3 text-orange">
                                <i class="fas fa-ship fa-2x"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between text-xs font-bold text-muted mb-1">
                                <span>Capacidad Utilizada</span>
                                <span>{{ number_format($porcentajeMgo ?? 0, 1) }}%</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-orange" role="progressbar" style="width: {{ min($porcentajeMgo ?? 0, 100) }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TOTAL COMPROMETIDO --}}
                <div class="col-md-3">
                    <div class="bg-white p-3 rounded border border-gray-300 shadow-sm h-100 border-start border-4 border-danger">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-xs font-black text-gray-500 text-uppercase d-block">Total Comprometido</span>
                                <div class="text-2xl font-black text-danger mt-1">
                                    {{ number_format($totalComprometido ?? (($totalComprometidoDiesel ?? 0) + ($totalComprometidoMgo ?? 0)), 0, ',', '.') }} <small class="fs-6 text-muted">Lts</small>
                                </div>
                            </div>
                            <div class="rounded-circle bg-danger bg-opacity-10 p-3 text-danger">
                                <i class="fas fa-lock fa-2x"></i>
                            </div>
                        </div>
                        <div class="mt-3 pt-1 text-xs font-bold text-muted d-flex justify-content-between">
                            <span><i class="fas fa-gas-pump text-primary me-1"></i> DIESEL: {{ number_format($totalComprometidoDiesel ?? 0, 0, ',', '.') }} L</span>
                            <span><i class="fas fa-ship text-orange me-1"></i> MGO: {{ number_format($totalComprometidoMgo ?? 0, 0, ',', '.') }} L</span>
                        </div>
                    </div>
                </div>

                {{-- INFRAESTRUCTURA DE TANQUES --}}
                <div class="col-md-3">
                    <div class="bg-white p-3 rounded border border-gray-300 shadow-sm h-100 border-start border-4 border-dark">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-xs font-black text-gray-500 text-uppercase d-block">Tanques Activos</span>
                                <div class="text-2xl font-black text-dark mt-1">
                                    {{ $tanquesActivos ?? 0 }} <small class="fs-6 text-muted">En Total</small>
                                </div>
                            </div>
                            <div class="rounded-circle bg-dark bg-opacity-10 p-3 text-dark">
                                <i class="fas fa-boxes fa-2x"></i>
                            </div>
                        </div>
                        <div class="mt-3 pt-2 text-xs font-bold text-muted">
                            <i class="fas fa-clock text-orange me-1"></i> Última medición: 
                            <span class="text-dark">{{ !empty($ultimaMedicion) ? \Carbon\Carbon::parse($ultimaMedicion)->format('d/m/Y h:i A') : 'Sin registros hoy' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- DETALLE DE DISPONIBILIDAD POR TANQUE / SEDE --}}
            <div class="card border border-gray-300 shadow-sm">
                <div class="card-header bg-white border-bottom py-2">
                    <h6 class="mb-0 fw-black text-uppercase small text-dark">
                        <i class="fas fa-list-ul text-orange me-2"></i> Desglose de Volúmenes por Tanque
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                        <table class="table table-hover table-sm align-middle mb-0 text-xs">
                            <thead class="bg-light font-black text-uppercase text-muted sticky-top shadow-sm" style="font-size: 11px; top: 0; z-index: 10;">
                                <tr>
                                    <th class="ps-3 py-2 bg-light">Sede</th>
                                    <th class="bg-light">Tanque</th>
                                    <th class="bg-light">Producto</th>
                                    <th class="bg-light">Capacidad Total</th>
                                    <th class="bg-light">Nivel Actual</th>
                                    <th class="bg-light">% Nivel</th>
                                    <th class="text-center bg-light">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($disponibilidades ?? [] as $tanque)
                                    @php
                                        // Mapeo correcto contra la tabla 'depositos'
                                        $capacidad = $tanque->capacidad_litros ?? 0;
                                        $volumenActual = $tanque->nivel_actual_litros ?? 0;
                                        $porcentaje = $capacidad > 0 ? ($volumenActual / $capacidad) * 100 : 0;
                                        
                                        // Color del badge según nivel
                                        $badgeColor = match(true) {
                                            $porcentaje < 20 => 'bg-danger text-white',
                                            $porcentaje < 50 => 'bg-warning text-dark',
                                            default          => 'bg-success text-white'
                                        };

                                        // Nombre de la sede (por relación Eloquent o alias de JOIN)
                                        $nombreSede = $tanque->sedes->nombre ?? $tanque->sede->nombre ?? $tanque->nombre_sede ?? 'Sin Sede';
                                        $producto = $tanque->producto ?? 'N/A';
                                        $isDiesel = str_contains(strtoupper($producto), 'DIESEL');
                                    @endphp
                                    <tr>
                                        <td class="ps-3 fw-bold text-dark text-uppercase">{{ $nombreSede }}</td>
                                        <td class="fw-black text-uppercase">{{ $tanque->serial ?? 'N/A' }}</td>
                                        @php
                                            // 1. Obtenemos el nombre del tipo desde la relación tipoCombustible
                                            $nombreProducto = $tanque->tipoCombustible->nombre 
                                                        ?? $tanque->tipoCombustible->descripcion 
                                                        ?? null;

                                            // Respaldamos por si la relación viene null
                                            $producto = $nombreProducto ?? 'Sin Tipo (' . $tanque->tipo_combustible_id . ')';

                                            // 2. Verificamos si es diésel o gasoil para alternar el color del badge
                                            $isDiesel = \Illuminate\Support\Str::contains(strtolower($producto), ['diesel', 'diésel', 'gasoil']);
                                        @endphp
                                        <td>
                                            <span class="badge {{ $isDiesel ? 'bg-primary' : 'bg-warning text-dark' }} text-uppercase">
                                                {{ $producto }}
                                            </span>
                                        </td>
                                        <td class="fw-bold">{{ number_format($capacidad, 2, ',', '.') }} L</td>
                                        <td class="fw-black text-dark">{{ number_format($volumenActual, 2, ',', '.') }} L</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="progress flex-grow-1" style="height: 6px; width: 60px;">
                                                    <div class="progress-bar {{ $porcentaje < 20 ? 'bg-danger' : ($porcentaje < 50 ? 'bg-warning' : 'bg-success') }}" style="width: {{ min($porcentaje, 100) }}%"></div>
                                                </div>
                                                <span class="fw-bold">{{ number_format($porcentaje, 1) }}%</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $badgeColor }} text-uppercase" style="font-size: 10px;">
                                                {{ $porcentaje < 20 ? 'CRÍTICO' : ($porcentaje < 50 ? 'MEDIO' : 'ÓPTIMO') }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted fw-bold">
                                            <i class="fas fa-info-circle text-orange fa-lg mb-2 d-block"></i>
                                            No hay depósitos/tanques registrados para mostrar en esta consulta.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- PAGINACIÓN --}}
                @if(isset($disponibilidades) && method_exists($disponibilidades, 'hasPages') && $disponibilidades->hasPages())
                    <div class="card-footer bg-white border-top py-2 d-flex justify-content-between align-items-center">
                        <div class="text-xs text-muted font-bold">
                            Mostrando {{ $disponibilidades->firstItem() }} - {{ $disponibilidades->lastItem() }} de {{ $disponibilidades->total() }} tanques
                        </div>
                        <div>
                            {{ $disponibilidades->withQueryString()->links() }}
                        </div>
                    </div>
                @endif
            </div>

    {{-- 3. MENÚ DE ÁREAS OPERATIVAS --}}
    <div class="card mb-4 border border-gray-300 shadow-sm">
        <div class="card-header bg-gray-800 py-2">
            <h6 class="text-white mb-0 text-xs font-black text-uppercase">
                <i class="fas fa-th-large text-orange me-2"></i> Módulos y Accesos Operativos
            </h6>
        </div>
        <div class="card-body bg-light p-4">
            <div class="row g-4">
                
                {{-- 1: TANQUES --}}
                <div class="col-xl-3 col-md-6">
                    <div class="card h-100 shadow-sm border border-gray-300 card-modulo transition-all">
                        <div class="card-body p-4 d-flex flex-column align-items-center text-center">
                            <div class="icon-shape bg-light rounded-circle p-3 mb-3 text-orange shadow-inner d-flex align-items-center justify-content-center">
                                <i class="fas fa-boxes fa-2x"></i>
                            </div>
                            <h5 class="fw-black text-uppercase text-dark mb-2 style-title">Gestión de Tanques</h5>
                            <p class="text-muted text-xs mb-4 flex-grow-1">
                                Configuración geométrica y registro técnico de tanques en sedes.
                            </p>
                            <a href="{{ route('combustibles.depositos.index') }}" class="btn btn-warning w-100 fw-black text-uppercase py-2 text-dark text-xs bg-orange border-0">
                                <i class="fas fa-sliders-h me-1"></i> Gestionar Tanques
                            </a>
                        </div>
                    </div>
                </div>

                {{-- 2: VARILLAJE --}}
                <div class="col-xl-3 col-md-6">
                    <div class="card h-100 shadow-sm border border-gray-300 card-modulo transition-all">
                        <div class="card-body p-4 d-flex flex-column align-items-center text-center">
                            <div class="icon-shape bg-light rounded-circle p-3 mb-3 text-orange shadow-inner d-flex align-items-center justify-content-center">
                                <i class="fas fa-ruler-vertical fa-2x"></i>
                            </div>
                            <h5 class="fw-black text-uppercase text-dark mb-2 style-title">Chequeo / Varillaje</h5>
                            <p class="text-muted text-xs mb-4 flex-grow-1">
                                Varillajes físicos periódicos para auditoría e inventario en tanques.
                            </p>
                            <a href="{{ route('combustibles.chequeos_depositos.create') }}" class="btn btn-warning w-100 fw-black text-uppercase py-2 text-dark text-xs bg-orange border-0">
                                <i class="fas fa-clipboard-check me-1"></i> Varillaje
                            </a>
                        </div>
                    </div>
                </div>

                {{-- 3: CUPOS PREPAGADOS --}}
                <div class="col-xl-3 col-md-6">
                    <div class="card h-100 shadow-sm border border-gray-300 card-modulo transition-all">
                        <div class="card-body p-4 d-flex flex-column align-items-center text-center">
                            <div class="icon-shape bg-light rounded-circle p-3 mb-3 text-orange shadow-inner d-flex align-items-center justify-content-center">
                                <i class="fas fa-file-invoice-dollar fa-2x"></i>
                            </div>
                            <h5 class="fw-black text-uppercase text-dark mb-2 style-title">Cupos Prepagados</h5>
                            <p class="text-muted text-xs mb-4 flex-grow-1">
                                Registro de llenados asociados a partidas y contratos prepagados.
                            </p>
                            <a href="{{ route('combustibles.llenados_prepagados.index') }}" class="btn btn-warning w-100 fw-black text-uppercase py-2 text-dark text-xs bg-orange border-0">
                                <i class="fas fa-gas-pump me-1"></i> Abrir Llenados
                            </a>
                        </div>
                    </div>
                </div>

                {{-- 4: TRANSACCIONES (LEDGER) --}}
                <div class="col-xl-3 col-md-6">
                    <div class="card h-100 shadow-sm border border-gray-300 card-modulo transition-all">
                        <div class="card-body p-4 d-flex flex-column align-items-center text-center">
                            <div class="icon-shape bg-light rounded-circle p-3 mb-3 text-orange shadow-inner d-flex align-items-center justify-content-center">
                                <i class="fas fa-exchange-alt fa-2x"></i>
                            </div>
                            <h5 class="fw-black text-uppercase text-dark mb-2 style-title">Transacciones</h5>
                            <p class="text-muted text-xs mb-4 flex-grow-1">
                                Libro contable y registro detallado de todas las operaciones.
                            </p>
                            <a href="{{ route('combustibles.transacciones.index') }}" class="btn btn-warning w-100 fw-black text-uppercase py-2 text-dark text-xs bg-orange border-0">
                                <i class="fas fa-list me-1"></i> Ver Libro
                            </a>
                        </div>
                    </div>
                </div>

                {{-- 5: CONSUMOS OPERATIVOS --}}
                <div class="col-xl-3 col-md-6">
                    <div class="card h-100 shadow-sm border border-gray-300 card-modulo transition-all">
                        <div class="card-body p-4 d-flex flex-column align-items-center text-center">
                            <div class="icon-shape bg-light rounded-circle p-3 mb-3 text-orange shadow-inner d-flex align-items-center justify-content-center">
                                <i class="fas fa-industry fa-2x"></i>
                            </div>
                            <h5 class="fw-black text-uppercase text-dark mb-2 style-title">Consumos Operativos</h5>
                            <p class="text-muted text-xs mb-4 flex-grow-1">
                                Salidas de combustible destinadas a equipos internos y flota propia.
                            </p>
                            <a href="{{ route('combustibles.consumos_operativos.index') }}" class="btn btn-warning w-100 fw-black text-uppercase py-2 text-dark text-xs bg-orange border-0">
                                <i class="fas fa-tachometer-alt me-1"></i> Ver Consumos
                            </a>
                        </div>
                    </div>
                </div>

                {{-- 6: TRASEGADOS --}}
                <div class="col-xl-3 col-md-6">
                    <div class="card h-100 shadow-sm border border-gray-300 card-modulo transition-all">
                        <div class="card-body p-4 d-flex flex-column align-items-center text-center">
                            <div class="icon-shape bg-light rounded-circle p-3 mb-3 text-orange shadow-inner d-flex align-items-center justify-content-center">
                                <i class="fas fa-random fa-2x"></i>
                            </div>
                            <h5 class="fw-black text-uppercase text-dark mb-2 style-title">Trasegados</h5>
                            <p class="text-muted text-xs mb-4 flex-grow-1">
                                Movimientos internos de transferencia de combustible entre tanques.
                            </p>
                            <a href="{{ route('combustibles.trasegados.index') }}" class="btn btn-warning w-100 fw-black text-uppercase py-2 text-dark text-xs bg-orange border-0">
                                <i class="fas fa-dolly me-1"></i> Ver Trasegados
                            </a>
                        </div>
                    </div>
                </div>

                {{-- 7: REVERSOS DE COMBUSTIBLE --}}
                <div class="col-xl-3 col-md-6">
                    <div class="card h-100 shadow-sm border border-gray-300 card-modulo transition-all">
                        <div class="card-body p-4 d-flex flex-column align-items-center text-center">
                            <div class="icon-shape bg-light rounded-circle p-3 mb-3 text-orange shadow-inner d-flex align-items-center justify-content-center">
                                <i class="fas fa-undo-alt fa-2x"></i>
                            </div>
                            <h5 class="fw-black text-uppercase text-dark mb-2 style-title">Reversos</h5>
                            <p class="text-muted text-xs mb-4 flex-grow-1">
                                Devoluciones o reintegraciones de combustible en despachos operativos.
                            </p>
                            <a href="{{ route('combustibles.reversos_combustibles.index') }}" class="btn btn-warning w-100 fw-black text-uppercase py-2 text-dark text-xs bg-orange border-0">
                                <i class="fas fa-history me-1"></i> Ver Reversos
                            </a>
                        </div>
                    </div>
                </div>

                {{-- 8: MERMAS --}}
                <div class="col-xl-3 col-md-6">
                    <div class="card h-100 shadow-sm border border-gray-300 card-modulo transition-all">
                        <div class="card-body p-4 d-flex flex-column align-items-center text-center">
                            <div class="icon-shape bg-light rounded-circle p-3 mb-3 text-orange shadow-inner d-flex align-items-center justify-content-center">
                                <i class="fas fa-chart-line fa-2x"></i>
                            </div>
                            <h5 class="fw-black text-uppercase text-dark mb-2 style-title">Mermas</h5>
                            <p class="text-muted text-xs mb-4 flex-grow-1">
                                Auditoría y ajuste de diferencias por evaporación o merma técnica.
                            </p>
                            <a href="{{ route('combustibles.mermas.index') }}" class="btn btn-warning w-100 fw-black text-uppercase py-2 text-dark text-xs bg-orange border-0">
                                <i class="fas fa-search-minus me-1"></i> Ver Mermas
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

<style>
    .fw-black { font-weight: 900; }
    .text-orange { color: #ff6600 !important; }
    .bg-orange { background-color: #ff6600 !important; color: #fff !important; }
    .bg-orange:hover { background-color: #e65c00 !important; color: #fff !important; }
    .style-title { font-size: 15px; letter-spacing: 0.5px; }
    .icon-shape { width: 65px; height: 65px; }
    .card-modulo {
        border-radius: 6px;
        background-color: #ffffff;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .card-modulo:hover {
        transform: translateY(-4px);
        box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,.08) !important;
    }
    .shadow-inner {
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
    }
</style>
@endsection