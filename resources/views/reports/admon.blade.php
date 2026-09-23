@extends('layouts.app')

@push('styles')
<style>
    .kpi-card { background: #ffffff; border-left: 4px solid #0f2d59; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .table-header-custom { background-color: #0f2d59 !important; color: white; }
    .text-bold-custom { font-weight: 700; color: #0f2d59; }
    .text-bold-title { font-weight: 800; color: #0f2d59; font-size: 1.7rem; }
    .chart-container { background: white; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0; }
    .bg-dashboard { background-color: #f1f5f9; } 
    .kpi-card-financial { 
        background: #f8fafc; 
        border: 1px solid #e2e8f0; 
        border-radius: 6px; 
    }
    /* Estilo para el indicador visual de turno */
    .badge-turno {
        font-size: 0.9rem;
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 600;
    }
    .badge-matutino { background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    .badge-vespertino { background-color: #fef3c7; color: #b45309; border: 1px solid #f0fd8a; }
    .table-responsive-custom { overflow-y: auto; }
    .table-responsive-custom thead th { position: sticky; top: 0; z-index: 1; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4 max-width-1200 bg-dashboard">
    
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded shadow-sm">
        <div class="d-flex gap-2">
            <button id="captureButton" class="btn btn-primary shadow-sm">
                <i class="fa fa-camera me-2"></i> Capturar a portapapeles
            </button>
            <button id="exportButton" class="btn btn-success shadow-sm">
                <i class="fa fa-download me-2"></i> Exportar Reporte
            </button>
            <button id="sendWhatsappButton" class="btn btn-info shadow-sm">
                <i class="fa fa-paper-plane me-2"></i> Enviar a WhatsApp
            </button>
        </div>
        
        {{-- Filtros de Búsqueda Integrando Fecha y Turno simultáneos --}}
        <form action="{{ route('reporte.admon') }}" method="GET" class="d-flex align-items-center gap-3 m-0">
            <div class="d-flex align-items-center gap-2">
                <label for="date" class="fw-bold text-secondary mb-0 text-nowrap">Fecha:</label>
                <select name="date" id="date" class="form-select" onchange="this.form.submit()">
                    {{-- Cambiamos $availableDates por $availableFiles agrupados por fecha para evitar duplicados en el select --}}
                    @foreach($availableFiles->unique('report_date') as $file)
                        <option value="{{ $file->report_date }}" {{ $file->report_date == $selectedDate ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::parse($file->report_date)->format('d/m/Y') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="d-flex align-items-center gap-2">
                <label for="turno" class="fw-bold text-secondary mb-0 text-nowrap">Turno:</label>
                <select name="turno" id="turno" class="form-select" onchange="this.form.submit()">
                    <option value="Matutino" {{ $selectedTurno == 'Matutino' ? 'selected' : '' }}>🌅 Matutino</option>
                    <option value="Vespertino" {{ $selectedTurno == 'Vespertino' ? 'selected' : '' }}>🌆 Vespertino</option>
                </select>
            </div>
        </form>
    </div>

    <div id="statusMessage" class="text-center p-3 rounded bg-warning text-dark d-none mb-4 fw-bold">
        Procesando...
    </div>

    <div id="reporteFinanzas" class="bg-white p-4 rounded shadow-sm printableArea">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
            <div>
                <h2 class="text-bold-custom mb-0"><img src="{{ asset('img/logo1.png') }}" alt="logo empresa" style="width: 250px"></h2>
                <small class="text-muted">Control de Operaciones y Flujos Financieros</small>
            </div>
            <div class="text-end">
                <h1 class="text-bold-title mb-1">REPORTE FINANCIERO DIARIO: {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</h1>
                {{-- Badge dinámico para identificar visualmente el turno en la captura de pantalla --}}
                <span class="badge-turno {{ $selectedTurno == 'Matutino' ? 'badge-matutino' : 'badge-vespertino' }}">
                    {{ $selectedTurno == 'Matutino' ? '🌅 CORTE MATUTINO (CIERRE DÍA ANTERIOR)' : '🌆 CORTE VESPERTINO (CIERRE OPERATIVO)' }}
                </span>
            </div>
        </div>

    @if($opexRecords->isEmpty() && $bancosRecords->isEmpty())
        <div class="alert alert-warning text-center">No hay datos registrados para el turno y fecha seleccionados.</div>
    @else
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="kpi-card p-3">
                    <span class="text-muted text-uppercase d-block small fw-bold">Litros Vendidos</span> 
                    <span class="fs-4 fw-bold text-dark">{{ number_format($ventasLitros, 2, ',', '.') }} L</span> 
                </div>
            </div>
            <div class="col-md-2">
                <div class="kpi-card p-3" style="border-left-color: #b45309;">
                    <span class="text-muted text-uppercase d-block small fw-bold">Litros Devueltos</span> 
                    <span class="fs-4 fw-bold" style="color: #b45309;">{{ number_format($ventasDesglose->get('LITROS DEVUELTOS')?->monto ?? 0 , 2, ',', '.') }} L</span> 
                </div>
            </div>
            <div class="col-md-2">
                <div class="kpi-card p-3" style="border-left-color: #2563eb;">
                    <span class="text-muted text-uppercase d-block small fw-bold">Litros Facturados</span> 
                    <span class="fs-4 fw-bold text-primary">{{ number_format($ventasDesglose->get('LITROS FACTURADOS')?->monto ?? 0, 2, ',', '.') }} L</span>
                </div>
            </div>
            <div class="col-md-2">
                <div class="kpi-card p-3" style="border-left-color: #16a34a;">
                    <span class="text-muted text-uppercase d-block small fw-bold">Litros Notas de Entrega</span> 
                    <span class="fs-4 fw-bold text-success">{{ number_format($ventasDesglose->get('LITROS NOTAS DE ENTREGA')?->monto ?? 0, 2, ',', '.') }} L</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kpi-card p-3" style="border-left-color: #dc3545;">
                    <span class="text-muted text-uppercase d-block small fw-bold">Ventas (USD)</span> 
                    <span class="fs-4 fw-bold text-danger">$ {{ number_format($ventasUsd, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="kpi-card p-3">
                     <span class="text-muted text-uppercase d-block small fw-bold">Cobros Realizados</span> 
                    <span class="fs-4 fw-bold text-dark">$ {{ number_format($totalCobros, 2, ',', '.') }}</span> 
                </div>
            </div>
            <div class="col-md-2">
                <div class="kpi-card p-3" style="border-left-color: #b45309;">
                    <span class="text-muted text-uppercase d-block small fw-bold">Total Ventas (CxP)</span> 
                    <span class="fs-4 fw-bold" style="color: #b45309;">$ {{ number_format($totalCxP, 2, ',', '.') }}</span> 
                </div>
            </div>
            <div class="col-md-2">
                <div class="kpi-card p-3" style="border-left-color: #2563eb;">
                    <span class="text-muted text-uppercase d-block small fw-bold">Total Bancos</span> 
                    <span class="fs-4 fw-bold text-primary" >$ {{ number_format($totalBancos, 2, ',', '.') }}</span> 
                </div>
            </div>
            <div class="col-md-2">
                <div class="kpi-card p-3" style="border-left-color: #16a34a;">
                    <span class="text-muted text-uppercase d-block small fw-bold">Disponible Caja</span> 
                    <span class="fs-4 fw-bold text-success">$ {{ number_format($totalCajas, 2, ',', '.') }}</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kpi-card p-3" style="border-left-color: #dc3545;">
                    <span class="text-muted text-uppercase d-block small fw-bold">Total Gastos (OPEX)</span> 
                    <span class="fs-4 fw-bold text-danger">$ {{ number_format($totalOpex, 2, ',', '.') }}</span>
                </div>
            </div>
            
            
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="text-bold-custom border-bottom pb-2 mb-3">Control de Cartera: Proporción vs Ventas Realizadas</h5>
                        <div class="row align-items-center">
                            
                            <div class="col-md-6 mb-3 mb-md-0 border-end">
                                <div class="d-flex justify-content-between small text-secondary mb-1">
                                    <span>Impacto de Cuentas por Cobrar (CxC)</span> 
                                    <strong>{{ number_format($pctCxC_Ventas, 1) }}%</strong> 
                                </div>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-primary progress-bar-striped" role="progressbar" style="width: {{ min($pctCxC_Ventas, 100) }}%"></div>
                                </div>
                                <small class="text-muted mt-2 d-block">
                                    CxC Pendiente: <strong>$ {{ number_format($totalCxC, 2, ',', '.') }}</strong> sobre los $ {{ number_format($ventasUsd, 2, ',', '.') }} facturados.
                                </small>
                            </div>

                            <div class="col-md-6">
                                <div class="d-flex justify-content-between small text-secondary mb-1">
                                    <span>Impacto de Cuentas por Pagar (CxP)</span> 
                                    <strong>{{ number_format($pctCxP_Ventas, 1) }}%</strong> 
                                </div>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-danger progress-bar-striped" role="progressbar" style="width: {{ min($pctCxP_Ventas, 100) }}%"></div>
                                </div>
                                <small class="text-muted mt-2 d-block">
                                    CxP Pendiente: <strong>$ {{ number_format($totalCxP, 2, ',', '.') }}</strong>.
                                </small>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 mb-4 h-100">
                    <div class="card-body">
                        <h5 class="text-bold-custom border-bottom pb-2 mb-3">Distribución de Gastos Operacionales</h5>  
                        <div class="chart-container mb-4">
                            @foreach($opexRecords->sortByDesc('monto')->take(5) as $gasto) 
                                @php 
                                    $maxMonto = $opexRecords->max('monto') ?: 1; 
                                    $porcentajeBarra = ($gasto->monto / $maxMonto) * 100; 
                                @endphp 
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between small text-secondary">
                                        <span>{{ $gasto->cuenta }}</span> 
                                        <strong>$ {{ number_format($gasto->monto, 2, ',', '.') }}</strong> 
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $porcentajeBarra }}%"></div>
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
                                <tr class="table-light text-bold-custom border-top border-dark"><td>TOTAL OPEX:</td><td class="text-end">$ {{ number_format($totalOpex, 2, ',', '.') }}</td></tr>
                            </tbody>
                        </table>
                         <!-- Desglose de Inventario -->
                        <h4 class="text-bold-custom border-bottom pb-2 mb-3 mt-4">Estado de Inventario</h4>
                        <div class="table-responsive-custom">
                            <table class="table table-sm table-hover align-middle">
                                <thead class="table-header-custom">
                                    <tr><th>Producto</th><th class="text-end">Cantidad</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($inventarioDesglose->sortByDesc('monto') as $inv)
                                        @if($inv->monto != 0)
                                        <tr>
                                            <td class="small">{{ $inv->cuenta }}</td>
                                            <td class="text-end small {{ $inv->monto < 0 ? 'text-danger' : 'fw-bold' }}">
                                                {{ number_format($inv->monto, 2, ',', '.') }}
                                            </td>
                                        </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>  
                
            </div>

            <div class="col-lg-6">
                <div class="card shadow-sm border-0 mb-4 h-100">
                    <div class="card-body">
                        <h5 class="text-bold-custom border-bottom pb-2 mb-3">Disponibilidad de Liquidez Consolidada: <span class="text-danger">$ {{ number_format($totalLiquidez, 2, ',', '.') }}</span></h5>

                        <div class="chart-container d-flex align-items-center justify-content-around mb-4">
                            <div class="w-50">
                                <h6 class="text-uppercase fw-bold text-muted mb-2">Segmentación</h6>
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-1"><span class="badge bg-success me-1">&nbsp;</span> Bancos: <strong>{{ number_format($pctBancos, 1) }}%</strong></li>
                                    <li><span class="badge bg-warning me-1">&nbsp;</span> Cajas: <strong>{{ number_format($pctCajas, 1) }}%</strong></li>
                                </ul>
                            </div>
                            <div style="width: 150px; height: 150px;">
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
                                <tr class="table-light text-bold-custom border-top border-dark"><td>TOTAL EN BANCOS:</td><td class="text-end">$ {{ number_format($totalBancos, 2, ',', '.') }}</td></tr>
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
                         <!-- Desglose de Ventas -->
                        <h6 class="text-bold-custom border-bottom pb-2 mb-3">Composición de Ventas</h6>
                            <table class="table table-sm table-hover align-middle">
                                <thead class="table-header-custom">
                                    <tr><th>Concepto</th><th class="text-end">Monto</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($ventasDesglose as $venta)
                                            <tr>
                                                <td class="small">{{ $venta->cuenta }}</td>
                                                <td class="text-end small {{ $venta->monto < 0 ? 'text-danger fw-bold' : '' }}">
                                                    @if(str_contains($venta->tipo, 'USD'))
                                                        $ {{ number_format($venta->monto, 2, ',', '.') }}
                                                    @else
                                                        {{ number_format($venta->monto, 2, ',', '.') }} L
                                                    @endif
                                                </td>
                                            </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <!-- Desglose de Cuentas por Cobrar (CxC) -->
                        <h5 class="text-bold-custom border-bottom pb-2 mb-3">Detalle Cuentas por Cobrar</h5>
                        <table class="table table-sm table-hover align-middle">
                                <thead class="table-header-custom">
                                    <tr><th>Cliente / Cuenta</th><th class="text-end">Monto (USD)</th></tr>
                                </thead>
                                <tbody>
                                   
                                    @foreach($cxcDesglose as $cxc)
                                        @if($cxc->monto != 0)
                                        <tr>
                                            <td class="small">{{ $cxc->cuenta }}</td>
                                            <td class="text-end small fw-bold">$ {{ number_format($cxc->monto, 2, ',', '.') }}</td>
                                        </tr>
                                        @endif
                                    @endforeach
                                    <tr class="table-light text-bold-custom border-top border-dark">
                                        <td class="small">TOTAL CxC:</td>
                                        <td class="text-end small">$ {{ number_format($totalCxC, 2, ',', '.') }}</td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Desglose de Cobros Realizados a Clientes -->
                        <h5 class="text-bold-custom border-bottom pb-2 mb-3 mt-4">Desglose de Cobros a Clientes</h5>
                        <table class="table table-sm table-hover align-middle">
                            <thead class="table-header-custom">
                                <tr>
                                    <th>Origen / Tipo</th>
                                    <th>Cuenta</th>
                                    <th class="text-end">Monto (USD)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cobrosDesglose as $cobro)
                                    @if($cobro->monto != 0)
                                    <tr>
                                        <td class="small text-muted">{{ $cobro->tipo }}</td>
                                        <td class="small fw-bold">{{ $cobro->cuenta }}</td>
                                        <td class="text-end small text-success fw-bold">$ {{ number_format($cobro->monto, 2, ',', '.') }}</td>
                                    </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center small text-muted">No se registraron cobros en este turno.</td>
                                    </tr>
                                @endforelse
                                <tr class="table-light text-bold-custom border-top border-dark">
                                    <td colspan="2" class="small">TOTAL COBROS CLIENTES:</td>
                                    <td class="text-end small text-success">$ {{ number_format($totalCobros, 2, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>

        </div>
        
    
        <!-- INDICADORES FINALES ACTUALIZADOS CON INVENTARIO -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="kpi-card p-3" style="border-left-color: #6366f1;">
                    <span class="text-muted text-uppercase d-block small fw-bold">Margen Bruto Estimado</span> 
                    <span class="fs-4 fw-bold" style="color: #6366f1;">{{ number_format($margenBruto, 1, ',', '.') }}%</span>
                </div>
            </div>
            <div class="col-md-2">
                 <div class="kpi-card p-3" style="border-left-color: #0ea5e9;">
                    <span class="text-muted text-uppercase d-block small fw-bold">Inventario MGO</span> 
                    <span class="fs-4 fw-bold" style="color: #0ea5e9;">{{ number_format($invMGO, 0, ',', '.') }} L</span>
                </div>
            </div>
            <div class="col-md-2">
                <div class="kpi-card p-3" style="border-left-color: #b45309;">
                    <span class="text-muted text-uppercase d-block small fw-bold">Inventario Diésel</span> 
                    <span class="fs-4 fw-bold" style="color: #b45309;">{{ number_format($invDiesel, 0, ',', '.') }} L</span>
                </div>
            </div>
            <div class="col-md-2">
                <div class="kpi-card p-3" style="border-left-color: #f43f5e;">
                    <span class="text-muted text-uppercase d-block small fw-bold">CXP Combustible</span> 
                    <span class="fs-4 fw-bold text-danger">$ {{ number_format($cxpComb, 2, ',', '.') }}</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kpi-card p-3">
                    <span class="text-muted text-uppercase d-block small fw-bold">Cuentas por Cobrar (CxC)</span> 
                    <span class="fs-4 fw-bold text-dark">$ {{ number_format($totalCxC, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>

        @if($alertas->isNotEmpty())
            <div class="row mt-2">
                <div class="col-12">
                    <div class="card shadow-sm border-0" style="border-left: 4px solid #f59e0b;">
                        <div class="card-body bg-light rounded">
                            <h6 class="text-bold-custom mb-3 d-flex align-items-center" style="color: #b45309;">
                                <i class="fas fa-lightbulb me-2 fs-5"></i> Insights y Alertas de la Operación
                            </h6>
                            <ul class="mb-0 text-dark" style="line-height: 1.8;">
                                @foreach($alertas as $alerta)
                                    <li><strong>Observación:</strong> {{ $alerta }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
    </div>
        
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    const printableArea = document.querySelector('.printableArea');
    const captureButton = document.getElementById('captureButton');
    const statusMessage = document.getElementById('statusMessage');
    const exportButton = document.getElementById('exportButton');
    const sendWhatsappButton = document.getElementById('sendWhatsappButton');

    if (!printableArea) {
        console.error("Falta la clase .printableArea en el DOM.");
        return;
    }

    const setStatus = (msg, bgClass, textClass) => {
        statusMessage.textContent = msg;
        statusMessage.className = `text-center p-3 rounded fw-bold mb-4 d-block ${bgClass} ${textClass}`;
    };

    async function sendReportToWhatsapp() {
        setStatus('Generando imagen para enviar a WhatsApp...', 'bg-warning', 'text-dark');
        sendWhatsappButton.disabled = true;

        try {
            const canvas = await html2canvas(printableArea, { scale: 2, useCORS: true });
            const imageBlob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
            
            const formData = new FormData();
            formData.append('image', imageBlob, 'reporte_whatsapp.png');
            // Añadimos el turno explícito en el pie del mensaje de WhatsApp para mayor claridad del grupo directivo
            formData.append('caption', '📊 *Reporte Gerencial KPI ({{ $selectedTurno }})* - ' + '{{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}');
            
            const response = await fetch('{{ route('whatsapp.send') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                body: formData
            });

            if (!response.ok) throw new Error('Error de conexión con la API.');

            setStatus('¡Reporte enviado exitosamente a WhatsApp!', 'bg-success', 'text-white');
        } catch (error) {
            setStatus('Error: ' + error.message, 'bg-danger', 'text-white');
        } finally {
            sendWhatsappButton.disabled = false;
            setTimeout(() => statusMessage.classList.add('d-none'), 5000);
        }
    }
    
    async function captureAndCopyToClipboard() {
        setStatus('Generando imagen en portapapeles...', 'bg-warning', 'text-dark');
        captureButton.disabled = true;

        try {
            const canvas = await html2canvas(printableArea, {
                scale: 3, 
                logging: false, 
                useCORS: true, 
                windowWidth: 1500
            });

            const imageBlob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
            if (!imageBlob) throw new Error('Blob no generado.');

            const item = new ClipboardItem({ "image/png": imageBlob });
            await navigator.clipboard.write([item]);

            setStatus('¡Imagen copiada al portapapeles (Ctrl+V)!', 'bg-success', 'text-white');
        } catch (error) {
            setStatus('Error de permisos o renderizado: ' + error.message, 'bg-danger', 'text-white');
        } finally {
            captureButton.disabled = false;
            setTimeout(() => statusMessage.classList.add('d-none'), 5000);
        }
    }

    async function exportarEImprimir() {
        setStatus('Procesando descarga en alta definición...', 'bg-warning', 'text-dark');
        exportButton.disabled = true;

        try {
            const canvas = await html2canvas(printableArea, {
                scale: 3,
                useCORS: true,
                logging: false,
                backgroundColor: '#ffffff',
                windowWidth: 1500
            });

            const image = canvas.toDataURL("image/png");
            const link = document.createElement('a');
            
            const fecha = '{{ $selectedDate }}';
            const turno = '{{ $selectedTurno }}';
            link.download = `Reporte_Gerencial_${fecha}_${turno}.png`;
            link.href = image;
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            setStatus('¡Reporte descargado con éxito!', 'bg-success', 'text-white');
        } catch (error) {
            setStatus('Error al generar la descarga: ' + error.message, 'bg-danger', 'text-white');
        } finally {
            exportButton.disabled = false;
            setTimeout(() => statusMessage.classList.add('d-none'), 5000);
        }
    }

    captureButton?.addEventListener('click', captureAndCopyToClipboard);
    exportButton?.addEventListener('click', exportarEImprimir);
    sendWhatsappButton?.addEventListener('click', sendReportToWhatsapp);
});
</script>
@endpush
@endsection