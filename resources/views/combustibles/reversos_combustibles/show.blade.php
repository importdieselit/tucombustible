@extends('layouts.app')
@section('title', 'Saldos Pendientes de Clientes')

@section('content')
<div class="container-fluid py-4 px-4">

    {{-- ENCABEZADO ORIGINAL --}}
    <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h2 class="h4 fw-black text-dark text-uppercase mb-1">
                <i class="fas fa-wallet text-orange me-2"></i> Saldos Pendientes de Clientes
            </h2>
            <p class="text-muted small mb-0">Consolidado de volumen a favor acumulado y consumido por concepto de reversos</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('combustibles.reversos_combustibles.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase shadow-sm px-3" style="font-size: 12px; height: 32px;">
                <i class="fas fa-history me-1"></i> Historial de Reversos
            </a>
            <a href="{{ route('combustibles.reversos_combustibles.create') }}" class="btn btn-sm btn-warning fw-black text-uppercase shadow-sm px-3 d-inline-flex align-items-center" style="font-size: 12px; height: 32px; color: #000; background-color: #ff6600; border-color: #ff6600;">
                <i class="fas fa-plus-circle me-1"></i> Nuevo Reverso
            </a>
        </div>
    </div>

    {{-- FILTRO POR CLIENTE ORIGINAL --}}
    <div class="card shadow-sm border-0 mb-4 p-3 bg-white">
        <form action="{{ url()->current() }}" method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Filtrar por Cliente</label>
                <select name="cliente_id" class="form-select form-select-sm fw-bold text-dark" style="font-size: 13px;">
                    <option value="">TODOS LOS CLIENTES</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}" {{ request('cliente_id') == $cliente->id ? 'selected' : '' }}>
                            {{ $cliente->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-dark w-100 fw-bold text-uppercase d-inline-flex align-items-center justify-content-center" style="font-size: 11px; height: 31px;">
                    <i class="fas fa-filter me-1"></i> Filtrar
                </button>
                @if(request()->filled('cliente_id'))
                    <a href="{{ url()->current() }}" class="btn btn-outline-secondary btn-sm w-100 fw-bold text-uppercase d-inline-flex align-items-center justify-content-center" style="font-size: 11px; height: 31px;">
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- TABLA 1: SALDOS ACTUALES DISPONIBLES POR CLIENTE (LO QUE HACÍA FALTA) --}}
    <div class="card shadow-sm border-0 mb-4" style="border-left: 4px solid #ff6600;">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-black text-uppercase small text-dark">
                <i class="fas fa-gas-pump text-orange me-2"></i> Saldos Actuales Disponibles (Litros A Favor de los Clientes)
            </h6>
            <span class="badge bg-light text-dark border fw-bold text-uppercase" style="font-size: 10px;">
                Resumen de Saldos Liquidos
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="min-width: 800px;">
                    <thead class="bg-light">
                        <tr class="text-uppercase text-muted" style="font-size: 12px; letter-spacing: 0.5px;">
                            <th class="ps-4">Cliente</th>
                            <th>Combustible</th>
                            <th class="text-end">Total Acumulado</th>
                            <th class="text-end">Total Consumido</th>
                            <th class="pe-4 text-end">Saldo Neto Disponible</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($saldosConsolidados as $item)
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold text-dark text-uppercase" style="font-size: 13px;">
                                        <i class="fas fa-building text-muted me-1"></i> {{ $item->cliente->nombre ?? 'N/A' }}
                                    </span>
                                </td>

                                <td>
                                    @if(($item->tipo_combustible_id) == 2)
                                        <span class="badge bg-warning text-dark fw-bold text-uppercase" style="font-size: 10px; background-color: #ffa500 !important;">DIESEL</span>
                                    @else
                                        <span class="badge bg-info text-white fw-bold text-uppercase" style="font-size: 10px; background-color: #00a8ff !important;">MGO</span>
                                    @endif
                                </td>

                                <td class="text-end fw-bold text-success" style="font-size: 13px;">
                                    +{{ number_format($item->total_acumulado, 2, ',', '.') }} Lts
                                </td>

                                <td class="text-end fw-bold text-danger" style="font-size: 13px;">
                                    -{{ number_format($item->total_consumido, 2, ',', '.') }} Lts
                                </td>

                                <td class="pe-4 text-end">
                                    <span class="badge bg-success text-white fw-black fs-6 px-3 py-1">
                                        {{ number_format($item->saldo_neto, 2, ',', '.') }} LTS
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted small fw-bold">
                                    <i class="fas fa-info-circle me-1 text-warning"></i> No hay clientes con saldo activo disponible a favor.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($saldosConsolidados->hasPages())
            <div class="card-footer bg-white border-top py-2 px-4">
                {{ $saldosConsolidados->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

    {{-- TABLA 2: TABLA ORIGINAL DE MOVIMIENTOS (AUDITORÍA COMPLETA) --}}
    <div class="card shadow-sm border-0 mb-4" style="border-left: 4px solid #ff6600;">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-black text-uppercase small text-dark">
                <i class="fas fa-coins text-orange me-2"></i> Movimientos de Saldos Pendientes (Histórico)
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="min-width: 900px;">
                    <thead class="bg-light">
                        <tr class="text-uppercase text-muted" style="font-size: 12px; letter-spacing: 0.5px;">
                            <th class="ps-4">Fecha</th>
                            <th>Cliente</th>
                            <th>Combustible</th>
                            <th>Tipo de Acción</th>
                            <th>Registrado Por</th>
                            <th class="pe-4 text-end">Volumen (Litros)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($saldos as $saldo)
                            <tr>
                                <td class="ps-4 font-monospace small fw-bold text-dark">
                                    {{ $saldo->created_at ? \Carbon\Carbon::parse($saldo->created_at)->format('d/m/Y h:i A') : '-' }}
                                </td>

                                <td>
                                    <span class="fw-bold text-dark text-uppercase" style="font-size: 13px;">
                                        <i class="fas fa-building text-muted me-1"></i> {{ $saldo->cliente->nombre ?? 'N/A' }}
                                    </span>
                                </td>

                                <td>
                                    @if(($saldo->tipo_combustible_id) == 2)
                                        <span class="badge bg-warning text-dark fw-bold text-uppercase" style="font-size: 10px; background-color: #ffa500 !important;">DIESEL</span>
                                    @else
                                        <span class="badge bg-info text-white fw-bold text-uppercase" style="font-size: 10px; background-color: #00a8ff !important;">MGO</span>
                                    @endif
                                </td>

                                <td>
                                    @if($saldo->tipo_accion === 'acumulado')
                                        <span class="badge bg-success text-white text-uppercase fw-bold" style="font-size: 10px;">
                                            <i class="fas fa-plus-circle me-1"></i> Acumulado
                                        </span>
                                    @else
                                        <span class="badge bg-secondary text-white text-uppercase fw-bold" style="font-size: 10px;">
                                            <i class="fas fa-minus-circle me-1"></i> Consumido
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <span class="fw-bold text-dark" style="font-size: 13px;">
                                        <i class="fas fa-user-circle text-muted me-1"></i> {{ $saldo->user->name ?? 'Sistema' }}
                                    </span>
                                </td>

                                <td class="pe-4 text-end fw-black {{ $saldo->tipo_accion === 'acumulado' ? 'text-success' : 'text-danger' }}" style="font-size: 15px;">
                                    {{ $saldo->tipo_accion === 'acumulado' ? '+' : '-' }}{{ number_format($saldo->cantidad_litros, 2, ',', '.') }} Lts
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted small fw-bold">
                                    <i class="fas fa-info-circle me-1 text-warning"></i> No hay registros de saldos pendientes para mostrar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($saldos->hasPages())
            <div class="card-footer bg-white border-top py-3 px-4">
                {{ $saldos->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

<style>
    .text-orange { color: #ff6600 !important; }
    .fw-black { font-weight: 900; }
</style>
@endsection