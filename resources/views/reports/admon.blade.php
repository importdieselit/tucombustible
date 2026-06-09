@extends('layouts.app')

@push('styles')
<style>
    .kpi-card { background: #ffffff; border-left: 4px solid #0f2d59; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .table-header-custom { background-color: #0f2d59 !important; color: white; }
    .text-bold-custom { font-weight: 700; color: #0f2d59; }
    .chart-container { background: white; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0; }
    /* Ajuste de fondo para que combine con el contenedor principal del layout */
    .bg-dashboard { background-color: #f1f5f9; } 
</style>
@endpush

@section('content')
<div class="container-fluid py-4 max-width-1200 bg-dashboard">
    
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded shadow-sm">
        <div>
            <h2 class="text-bold-custom mb-0">IMPORDIESEL, C.A.</h2>
            <small class="text-muted">Control de Operaciones y Flujos Financieros</small>
        </div>
        <form action="{{ route('reporte.gerencial') }}" method="GET" class="d-flex align-items-center gap-2">
            <label for="date" class="fw-bold text-secondary mb-0 text-nowrap">Fecha de Reporte:</label>
            <select name="date" id="date" class="form-select" onchange="this.form.submit()">
                @foreach($availableDates as $date)
                    <option value="{{ $date }}" {{ $date == $selectedDate ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    @if($opexRecords->isEmpty() && $bancosRecords->isEmpty())
        <div class="alert alert-warning text-center">No hay datos registrados para la fecha seleccionada.</div>
    @else
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="kpi-card p-3">
                    <span class="text-muted text-uppercase d-block small fw-bold">Litros Vendidos</span> 
                    <span class="fs-4 fw-bold text-dark">{{ number_format($ventasLitros->where('cuenta', 'LITROS VENDIDOS')->first()->monto ?? 0, 2, ',', '.') }} L</span> 
                </div>
            </div>
            <div class="col-md-3">
                <div class="kpi-card p-3" style="border-left-color: #b45309;">
                    <span class="text-muted text-uppercase d-block small fw-bold">Ventas Realizadas</span> 
                    <span class="fs-4 fw-bold" style="color: #b45309;">$ {{ number_format($ventasUsd, 2, ',', '.') }}</span> 
                </div>
            </div>
            <div class="col-md-3">
                <div class="kpi-card p-3" style="border-left-color: #16a34a;">
                    <span class="text-muted text-uppercase d-block small fw-bold">Total Gastos (OPEX)</span> 
                    <span class="fs-4 fw-bold text-success">$ {{ number_format($totalOpex, 2, ',', '.') }}</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kpi-card p-3" style="border-left-color: #dc2626;">
                    <span class="text-muted text-uppercase d-block small fw-bold">Liquidez Consolidada</span>
                    <span class="fs-4 fw-bold text-danger">$ {{ number_format($totalLiquidez, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <h5 class="text-bold-custom border-bottom pb-2 mb-3">Distribución de Gastos Operacionales</h5>
                        
                        <div class="chart-container mb-4">
                            @foreach($opexRecords->sortByDesc('monto')->take(5) as $gasto) 
                                @php $maxMonto = $opexRecords->max('monto') ?: 1; $porcentajeBarra = ($gasto->monto / $maxMonto) * 100; @endphp 
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between small text-secondary">
                                        <span>{{ $gasto->cuenta }}</span> 
                                        <strong>$ {{ number_format($gasto->monto, 2, ',', '.') }}</strong> 
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $porcentajeBarra }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <table class="table table-sm table-hover align-middle">
                            <thead class="table-header-custom">
                                <tr><th>Cuenta Gasto</th><th class="text-end">Monto (USD)</th></tr>
                            </thead>
                            <tbody>
                                @foreach($opexRecords as $gasto) 
                                    <tr><td>{{ $gasto->cuenta }}</td><td class="text-end fw-bold">$ {{ number_format($gasto->monto, 2, ',', '.') }}</td></tr> 
                                @endforeach
                                <tr class="table-light text-bold-custom"><td>TOTAL OPEX:</td><td class="text-end">$ {{ number_format($totalOpex, 2, ',', '.') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <h5 class="text-bold-custom border-bottom pb-2 mb-3">Disponibilidad de Liquidez</h5>

                        <div class="chart-container d-flex align-items-center justify-content-around mb-4">
                            <div class="w-50">
                                <h6 class="small text-uppercase fw-bold text-muted mb-2">Segmentación Total</h6>
                                <ul class="list-unstyled small mb-0">
                                    <li class="mb-1"><span class="badge bg-success me-1">&nbsp;</span> Bancos: <strong>{{ number_format($pctBancos, 1) }}%</strong></li>
                                    <li><span class="badge bg-warning me-1">&nbsp;</span> Cajas: <strong>{{ number_format($pctCajas, 1) }}%</strong></li>
                                </ul>
                            </div>
                            <div style="width: 100px; height: 100px;">
                                <svg viewBox="0 0 32 32" style="transform: rotate(-90deg); border-radius: 50%;">
                                    <circle r="16" cx="16" cy="16" fill="#ffc107"></circle>
                                    <circle r="16" cx="16" cy="16" fill="transparent" stroke="#198754" stroke-width="32" stroke-dasharray="{{ $pctBancos }} 100"></circle>
                                </svg>
                            </div>
                        </div>

                        <h6 class="text-bold-custom mb-2">Cuentas Bancarias</h6>
                        <table class="table table-sm table-hover mb-4">
                            <thead class="table-dark">
                                <tr><th>Entidad Bancaria</th><th class="text-end">Monto (USD)</th></tr>
                            </thead>
                            <tbody>
                                @foreach($bancosRecords as $banco) 
                                    @if($banco->monto != 0) 
                                        <tr>
                                            <td>{{ $banco->cuenta }}</td> 
                                            <td class="text-end {{ $banco->monto < 0 ? 'text-danger fw-bold' : '' }}">$ {{ number_format($banco->monto, 2, ',', '.') }}</td> 
                                        </tr>
                                    @endif
                                @endforeach
                                <tr class="table-light text-bold-custom"><td>TOTAL EN BANCOS:</td><td class="text-end">$ {{ number_format($totalBancos, 2, ',', '.') }}</td></tr>
                            </tbody>
                        </table>

                        <h6 class="text-bold-custom mb-2">Disponibilidad en Cajas</h6>
                        <table class="table table-sm table-hover">
                            <thead class="table-dark">
                                <tr><th>Caja</th><th class="text-end">Monto (USD)</th></tr>
                            </thead>
                            <tbody>
                                @foreach($cajasRecords as $caja) 
                                    @if($caja->monto != 0) 
                                        <tr><td>{{ $caja->cuenta }}</td><td class="text-end">$ {{ number_format($caja->monto, 2, ',', '.') }}</td></tr> 
                                    @endif
                                @endforeach
                                <tr class="table-light text-bold-custom"><td>TOTAL EN CAJAS:</td><td class="text-end">$ {{ number_format($totalCajas, 2, ',', '.') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection