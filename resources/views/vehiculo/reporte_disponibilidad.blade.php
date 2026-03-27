@extends('layouts.app')
@push('styles')
<style>
.bg-chutos {
        background-color: #ff6600 !important;
    }
    .bg-camiones {
        background-color: #ffc107 !important;
    }
    .bg-cisternas {
        background-color: #198754 !important;
    }
    .bg-camionetas {
        background-color: #2c3e50 !important;
    }

    .border-chutos {
        border-color: #ff6600 !important;
    }
    .border-camiones {
        border-color: #ffc107 !important;
        }
    .border-cisternas {
        border-color: #198754 !important;
    }
    .border-camionetas {
        border-color: #2c3e50 !important
    }

@media print {
    /* Configuración de la página */
    @page {
        size: letter;
        margin: 1cm;
    }

    /* Evitar que el contenedor principal tenga sombras o fondos grises de la web */
    .printableArea {
        box-shadow: none !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* EVITAR CORTES EN BLOQUES CRÍTICOS */
    .card, .row, tr, .highcharts-container, .mt-3 {
        page-break-inside: avoid !important;
        break-inside: avoid !important;
    }

    /* FORZAR SALTO DE PÁGINA ANTES DE UN ELEMENTO SI ES NECESARIO */
    .page-break {
        page-break-before: always !important;
        break-before: always !important;
    }

    /* Ocultar elementos innecesarios como botones */
    .no-print, .btn, #statusMessage {
        display: none !important;
    }
    /* Este estilo solo afecta a la captura, no a la vista web normal */
    .is-capturing {
        width: 1000px !important; /* Forzamos el ancho para consistencia */
        margin: 0 !important;
        padding: 20px !important;
        background: #ffffff !important;
        border: none !important;
        box-shadow: none !important;
    }

    /* Aseguramos que las tablas no se corten visualmente en el canvas */
    .is-capturing .table {
        background: white !important;
    }

    /* Colores suaves para los iconos */
    .bg-primary-light { background-color: rgba(13, 110, 253, 0.1); }
    .bg-success-light { background-color: rgba(25, 135, 84, 0.1); }
    .bg-info-light    { background-color: rgba(13, 202, 240, 0.1); }
    .bg-warning-light { background-color: rgba(255, 193, 7, 0.1); }
    
    /* Estilo de los cuadros */
    .card {
        border-radius: 10px;
        transition: transform 0.2s;
    }
    .card:hover {
        transform: translateY(-3px);
    }
}
</style>
@endpush

@section('content')
<div class="container-fluid py-4" style="background-color: #f4f6f9; min-height: 100vh;">
    
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <h2 class="fw-bold text-navy">Dashboard Operativo</h2>
            <p class="text-muted">Estado de flota en tiempo real</p>
        </div>

        <button id="sendTelegramButton" class="btn btn-info shadow-sm">
            <i class="fa fa-telegram me-2"></i> Enviar a Telegram
        </button>
         <button id="captureButton" class="btn btn-primary shadow-sm">
            <i class="fa fa-camera me-2"></i> Capturar a portapapeles
        </button>
        <button id="exportButton" class="btn btn-primary shadow-sm px-4">
            <i class="fa fa-printer-fill me-2"></i>Exportar Reporte
        </button>
    </div>
    <div id="statusMessage" class="text-center p-3 rounded-lg bg-yellow-100 text-yellow-800 hidden mb-4">
            Procesando...
    </div>

    <div class="report-master-card shadow-lg bg-white mx-auto p-0 printableArea" style="max-width: 1000px; border-radius: 15px; overflow: hidden;">
        
        <div class="bg-dark p-4 text-white d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-0 fw-bold">TUCOMBUSTIBLE</h3>
            </div>
            <div>
                <h1 class="fw-bold">REPORTE DIARIO DE FLOTA</h1>
            </div>
            <div class="text-end">
                <div class="h4 mb-0">{{ $today->translatedFormat('d M, Y') }}</div>
                <div class="small opacity-75">Sincronizado: {{ $today->format('g:i A') }}</div>
            </div>
        </div>

        <div class="row g-0 border-bottom">
            <div class="col-md-4 p-4 text-center border-end">
                <div class="display-5 fw-bold text-primary">{{ $total }}</div>
                <div class="text-uppercase small fw-bold text-muted">Total Flota</div>
            </div>
            <div class="col-md-4 p-4 text-center border-end">
                <div class="display-5 fw-bold text-success">{{ $operativosCount }}</div>
                <div class="text-uppercase small fw-bold text-muted">Unidades Activas</div>
            </div>
            <div class="col-md-4 p-4 text-center">
                <div class="h2 mb-1 fw-bold {{ $porcentajeDisponibilidad > 80 ? 'text-success' : 'text-warning' }}">
                    {{ $porcentajeDisponibilidad }}%
                </div>
                <div class="progress mx-auto" style="height: 8px; width: 100px;">
                    <div class="progress-bar bg-success" style="width: {{ $porcentajeDisponibilidad }}%"></div>
                </div>
                <div class="text-uppercase small fw-bold text-muted mt-2">Disponibilidad</div>
            </div>
        </div>

        {{-- SECCIÓN GERENCIAL --}}

<div class="row mb-4 no-print">
    <div class="col-lg-5">
        <div class="card shadow-sm border-0" style="border-radius: 15px;">
            <div id="chart-disponibilidad" style="width:100%; height:300px;"></div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm border-0 h-100" style="border-radius: 15px;">
            <div class="card-body">
                <h6 class="fw-black text-uppercase small text-muted mb-4">Análisis de Flota por Segmento</h6>
                <div id="chart-segmentos" style="width:100%; height:250px;"></div>
                
        
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-body">

                <div class="row g-3 mb-4">
                    <h3 class="w-100 text-center"><strong>  UNIDADES OPERATIVAS</strong></h3>
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="card h-100 shadow-sm  border-chutos border-0 border-top border-4">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                                <span class="fw-bold small text-uppercase"><i class="fas fa-truck-pickup me-1 text-corporate"></i> Chutos</span>
                                <span class="badge bg-chutos rounded-pill">{{ $chutosOperativos->count() }} de {{ $totalChutos }}</span>
                            </div>
                            <div class="card-body p-2">
                                <div class="d-flex flex-wrap gap-1">
                                    @forelse($chutosOperativos as $v)
                                        <span class="badge border text-dark fw-normal bg-light" style="font-size: 0.7rem;">
                                            <i class="fa-solid fa-truck-pickup text-muted"></i> {{ $v->flota }} <span class="text-muted">|</span> {{ $v->placa }}
                                        </span>
                                    @empty
                                        <span class="text-muted x-small ps-1">Sin unidades operativas</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="card h-100 shadow-sm border-0 border-top border-4 border-camiones">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                                <span class="fw-bold small text-uppercase"><i class="fas fa-truck me-1 text-warning"></i> Camiones</span>
                                <span class="badge bg-camiones rounded-pill">{{ $camionesOperativos->count() }} de {{ $totalCamiones }}</span>
                            </div>
                            <div class="card-body p-2">
                                <div class="d-flex flex-wrap gap-1">
                                    @forelse($camionesOperativos as $v)
                                        <span class="badge border text-dark fw-normal bg-light" style="font-size: 0.7rem;">
                                            <i class="fas fa-truck text-muted"></i> {{ $v->flota }} <span class="font-weight-bold text-muted">|</span> {{ $v->placa }}
                                        </span>
                                    @empty
                                        <span class="text-muted x-small ps-1">Sin unidades operativas</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="card h-100 shadow-sm border-0 border-top border-4 border-cisternas">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                                <span class="fw-bold small text-uppercase"><i class="fas fa-trailer me-1 text-success"></i> Cisternas</span>
                                <span class="badge bg-cisternas rounded-pill">{{ $cisternasOperativas->count() }} de {{ $totalCisternas }}</span>
                            </div>
                            <div class="card-body p-2">
                                <div class="d-flex flex-wrap gap-1">
                                    @forelse($cisternasOperativas as $v)
                                        <span class="badge border text-dark fw-normal bg-light" style="font-size: 0.7rem;">
                                            <i class="fas fa-trailer text-muted"></i> {{ $v->nro_flota }} <span class="text-muted">|</span> {{ $v->placa }}
                                        </span>
                                    @empty
                                        <span class="text-muted x-small ps-1">Sin unidades operativas</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="card h-100 shadow-sm border-0 border-top border-4 border-camionetas">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                                <span class="fw-bold small text-uppercase"><i class="fas fa-car me-1 text-secondary"></i> Livianos</span>
                                <span class="badge bg-secondary rounded-pill">{{ $camionetasOperativas->count() }} de {{ $totalLivianos }}</span>
                            </div>
                            <div class="card-body p-2">
                                <div class="d-flex flex-wrap gap-1">
                                    @forelse($camionetasOperativas as $v)
                                        <span class="badge border text-dark fw-normal bg-light" style="font-size: 0.7rem;">
                                            <i class="fas fa-car text-muted"></i>{{ $v->nro_flota }} <span class="text-muted">|</span> {{ $v->placa }}
                                        </span>
                                    @empty
                                        <span class="text-muted x-small ps-1">Sin unidades operativas</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    <h3 class="w-100 text-center"><strong>  UNIDADES CON FALLA</strong></h3>
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="card h-100 shadow-sm border-0 border-top border-4 border-chutos">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                                <span class="fw-bold small text-uppercase"><i class="fas fa-truck-pickup me-1 text-corporate"></i> Chutos</span>
                                <span class="badge bg-chutos rounded-pill">{{ $chutosFalla->count() }}</span>
                            </div>
                            <div class="card-body p-2">
                                <div class="d-flex flex-wrap gap-1">
                                    @forelse($chutosFalla as $v)
                                        <span class="badge border text-dark fw-normal bg-light" style="font-size: 0.7rem;">
                                            <i class="fa-solid fa-truck-pickup text-muted"></i> {{ $v->flota }} <span class="text-muted">|</span> {{ $v->placa }}
                                        </span>
                                    @empty
                                        <span class="text-muted x-small ps-1">Sin unidades operativas</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="card h-100 shadow-sm border-0 border-top border-4 border-warning">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                                <span class="fw-bold small text-uppercase"><i class="fas fa-truck me-1 text-warning"></i> Camiones</span>
                                <span class="badge bg-warning rounded-pill">{{ $camionesFalla->count() }}</span>
                            </div>
                            <div class="card-body p-2">
                                <div class="d-flex flex-wrap gap-1">
                                    @forelse($camionesFalla as $v)
                                        <span class="badge border text-dark fw-normal bg-light" style="font-size: 0.7rem;">
                                            <i class="fas fa-truck text-muted"></i> {{ $v->flota }} <span class="font-weight-bold text-muted">|</span> {{ $v->placa }}
                                        </span>
                                    @empty
                                        <span class="text-muted x-small ps-1">Sin unidades operativas</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="card h-100 shadow-sm border-0 border-top border-4 border-success">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                                <span class="fw-bold small text-uppercase"><i class="fas fa-trailer me-1 text-success"></i> Cisternas</span>
                                <span class="badge bg-success rounded-pill">{{ $cisternasFalla->count() }}</span>
                            </div>
                            <div class="card-body p-2">
                                <div class="d-flex flex-wrap gap-1">
                                    @forelse($cisternasFalla as $v)
                                        <span class="badge border text-dark fw-normal bg-light" style="font-size: 0.7rem;">
                                            <i class="fas fa-trailer text-muted"></i> {{ $v->nro_flota }} <span class="text-muted">|</span> {{ $v->placa }}
                                        </span>
                                    @empty
                                        <span class="text-muted x-small ps-1">Sin unidades operativas</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="card h-100 shadow-sm border-0 border-top border-4 border-secondary">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                                <span class="fw-bold small text-uppercase"><i class="fas fa-car me-1 text-secondary"></i> Livianos</span>
                                <span class="badge bg-secondary text-dark rounded-pill">{{ $camionetasFalla->count() }}</span>
                            </div>
                            <div class="card-body p-2">
                                <div class="d-flex flex-wrap gap-1">
                                    @forelse($camionetasFalla as $v)
                                        <span class="badge border text-dark fw-normal bg-light" style="font-size: 0.7rem;">
                                            <i class="fas fa-car text-muted"></i>{{ $v->nro_flota }} <span class="text-muted">|</span> {{ $v->placa }}
                                        </span>
                                    @empty
                                        <span class="text-muted x-small ps-1">Sin unidades operativas</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>     
        </div>
    </div>
    <div class="col-12">
        <div class="card shadow-sm border-0 h-100" style="border-radius: 15px;">
            <div class="card-body">
        
        <div class="mt-3 bg-white rounded shadow-sm border-start border-4 border-orange overflow-hidden">
            {{-- Encabezado del Cuadro --}}
            <div class="p-3 bg-light d-flex justify-content-between align-items-center border-bottom">
                <div>
                    <span class="text-uppercase fw-bold mb-0" style="font-size: 11px; color: #666; letter-spacing: 1px;">Cargas y Despachos Programados (Hoy)</span>
                    <h4 class="fw-black mb-0 text-dark">{{ $despachosHoy->count() ?? 0 }} <small class="text-muted small" style="font-size: 14px;">Viajes Totales</small></h4>
                </div>
                <div class="text-end">
                    <span class="badge bg-dark text-orange fw-black px-3 py-2" style="border-radius: 20px;">
                        CAPACIDAD UTILIZADA: {{ $utilizacionFlota ?? 0 }}%
                    </span>
                </div>
            </div>

            {{-- Tabla de Movimientos --}}
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="py-2 px-3 text-uppercase small text-muted" style="width: 120px;">Tipo</th>
                            <th class="py-2 px-3 text-uppercase small text-muted" style="width: 240px;">Unidad</th>
                            <th class="py-2 px-3 text-uppercase small text-muted">Detalle de Operación</th>
                            <th class="py-2 px-3 text-uppercase small text-muted text-end" style="width: 150px;">Total Litros</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($despachosHoy as $viaje)
                            @php
                                $destinoRaw = $viaje->destino_ciudad;
                                $esFlete = str_contains(strtoupper($destinoRaw), 'FLETE');
                                
                                // Limpiamos la palabra "FLETE", las flechas "->" y espacios sobrantes
                                $destinoLimpio = trim(str_ireplace(['FLETE', ' ->'], ['', ''], $destinoRaw));
                                
                                // Si es flete, asignamos colores y estilos únicos (un tono Púrpura o Gris Oscuro)
                                if ($esFlete) {
                                    $badgeClass = 'bg-dark text-white'; // O un color que prefieras para fletes
                                    $lineColor = '#6f42c1'; // Púrpura para fletes
                                    $tipoEtiqueta = 'Flete';
                                    $iconClass = 'fa-truck-loading';
                                } else {
                                    $esDespacho = is_null($viaje->litros);
                                    $detallesDespacho = $esDespacho ? $viaje->despachos : null;
                                
                                    $totalLitros = $esDespacho 
                                        ? ($detallesDespacho->sum('litros') ?? 0) 
                                        : $viaje->litros;
                                    $lineColor = $esDespacho ? '#28a745' : '#17a2b8';
                                    $badgeClass = $esDespacho ? 'bg-success' : 'bg-info text-dark';
                                    $tipoEtiqueta = $esDespacho ? 'Despacho' : 'Carga';
                                    $iconClass = $esDespacho ? 'fa-arrow-up' : 'fa-arrow-down';
                                }
                            @endphp

                            <tr class="viaje-row" style="border-left: 5px solid {{ $lineColor }};">
                                <td class="align-middle px-3">
                                    <span class="badge {{ $badgeClass }} text-uppercase w-100" style="font-size: 9px; letter-spacing: 0.5px;">
                                        <i class="fas {{ $iconClass }} me-1"></i>
                                        {{ $tipoEtiqueta }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center align-middle">
                                        <div class="me-2">
                                            <i class="fas fa-truck text-muted"></i>
                                        </div>
                                        <div>
                                            <span class="fw-bold text-dark" style="font-size: 13px;">{{ $viaje->vehiculo ? '['.$viaje->vehiculo->flota.'] '.$viaje->vehiculo->placa :$viaje->otro_vehiculo}} 
                                                @if($viaje->cisternaAcoplada) 
                                                
                                                    @php($cisterna=$viaje->cisternaAcoplada)
                                                   
                                                    <br>
                                                    <i class="fas fa-link text-muted opacity-50" style="font-size"></i>
                                                    [{{ $cisterna->flota}}] <span class="text-success">{{ $cisterna->placa }}</span>
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="align-middle px-3">
                                    {{-- Si es Flete, mostramos el destino limpio --}}
                                    @if($esFlete)
                                        <div class="d-flex align-items-center">
                                            <div class="me-2">
                                                <i class="fas fa-map-marker-alt text-muted"></i>
                                            </div>
                                            <div>
                                                <span class="fw-bold text-dark" style="font-size: 13px;">{{ $destinoLimpio }}</span>
                                            </div>
                                        </div>
                                    @elseif($esDespacho && $detallesDespacho && $detallesDespacho->count() > 0)
                                        {{-- (Mantienes tu lógica anterior de desglose de clientes aquí) --}}
                                         <div>
                                            <span class="fw-black text-dark" style="font-size: 15px;">{{ $destinoLimpio }}</span>
                                        </div>
                                        <div class="py-1">
                                            @foreach($detallesDespacho as $d)
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="text-dark fw-bold" style="font-size: 13px;">{{ $d->cliente->alias?? $d->cliente->nombre ?? $d->otro_cliente }}</span>
                                                    <span class="badge bg-light text-dark border">{{ number_format($d->litros, 2) }} Lts</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        {{-- Caso Carga --}}
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="fw-black text-dark" style="font-size: 15px;">{{ $destinoLimpio }}</span>
                                            </div>
                                            <i class="fas fa-gas-pump text-muted opacity-50"></i>
                                        </div>
                                    @endif
                                </td>
                                
                                <td class="text-end align-middle px-3 fw-black text-dark" style="font-size: 16px;">
                                    {{ number_format($totalLitros, 2) }} Ltrs
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
            </div>
        </div>
    </div>
</div>

    </div>
     <!-- Área donde se mostrará el canvas generado (opcional, para debug/visualización) -->
        <div id="outputContainer" class="mt-8 pt-4 border-t border-gray-300">
        </div>
    
</div>

<style>
    .text-navy { color: #1a237e; }
    .bg-outline-dark { background: transparent; color: #333; }
    @media print {
        .no-print { display: none !important; }
        body { background: white !important; padding: 0 !important; }
        .report-master-card { box-shadow: none !important; border: 1px solid #eee !important; width: 100% !important; max-width: 100% !important; margin: 0 !important; }
    }
</style>
@push('scripts')

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" defer></script>
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Gráfica de Disponibilidad (Donut)
    Highcharts.chart('chart-disponibilidad', {
        chart: { type: 'pie', backgroundColor: 'transparent' },
        title: { text: 'Disponibilidad Real', align: 'left', style: { fontWeight: '900', fontSize: '15px', textTransform: 'uppercase', color: '#666' } },
        plotOptions: {
            pie: {
                innerSize: '50%',
                dataLabels: { enabled: true, format: '{point.name}: {point.y}' }
            }
        },
        series: [{
            name: 'Unidades',
            data: [
                { name: 'Operativos', y: {{ $operativosCount }}, color: '#2c3e50' },
                { name: 'En Ruta', y: {{ $enRuta }}, color: '#ff6600' },
                { name: 'Fuera de Servicio', y: {{ $fallaCount }}, color: '#e74c3c' }
            ]
        }],
        credits: { enabled: false }
    });

    // 2. Gráfica de Segmentos (Columnas con Variación)
    Highcharts.chart('chart-segmentos', {
    chart: { type: 'column', backgroundColor: 'transparent' },
    title: { text: null },
    xAxis: { 
        categories: [ 'Chutos', 'Camiones', 'Tanques', 'Livianos'], 
        crosshair: true,
        labels: { style: { fontWeight: 'bold', color: '#2c3e50' } }
    },
    yAxis: { min: 0, title: { text: 'Cantidad de Unidades' } },
    tooltip: { shared: true },
    plotOptions: {
        column: { 
            borderRadius: 5, 
            colorByPoint: true,
            // --- AJUSTE AQUÍ: ACTIVAR ETIQUETAS ---
            dataLabels: {
                enabled: true,
                // Mostramos cantidad y el nombre de la categoría
                format: '{point.y}',
                style: {
                    fontSize: '12px',
                    fontFamily: 'inherit',
                    textOutline: 'none', // Quita el borde blanco de la letra
                    textAlign: 'center'
                },
                y: -10 // Ajuste para que floten un poco sobre la barra
            }
        }
    },
    colors: ['#ff6600', '#ffc107','#198754','#2c3e50'],
    series: [{
        name: '',
        data: [
            { y: {{ $totalChutos }}, name: 'Chutos' },
            { y: {{ $totalCamiones }}, name: 'Camiones' },
            { y: {{ $totalCisternas }}, name: 'Tanques' }, 
            { y: {{ $totalLivianos }}, name: 'Livianos' } 
        ]
    }],
    credits: { enabled: false }
});
    
    const printableArea = $("div.printableArea")[0]; 
    const sendTelegramButton = document.querySelector('#sendTelegramButton');
    const elementToCaptureSelector = '.printableArea';
    const captureButton = document.getElementById('captureButton');
    const statusMessage = document.getElementById('statusMessage');
    const outputContainer = document.getElementById('outputContainer');
    const exportButton = document.getElementById('exportButton');

    if (!printableArea || !captureButton || !statusMessage) {
        console.error("Faltan elementos DOM críticos (printableArea, captureButton, statusMessage, o outputContainer).");
        return; // Salir si no se puede inicializar correctamente
    }

    statusMessage.textContent = 'Procesando...';

    async function sendReportToTelegram() {
        sendTelegramButton.disabled = true;
        try {
            // Buscamos el primer elemento con la clase .printableArea
            const element = printableArea;
            if (!element) {
                throw new Error(`Elemento con selector '${elementToCaptureSelector}' no encontrado. ¡Verifique la clase!`);
            }

            // 1. Capturar el elemento con html2canvas
            const canvas = await html2canvas(element, {
                allowTaint: true, 
                useCORS: true,
                // Mejor calidad para la imagen
                scale: 2, 
            });

            // 2. Obtener la imagen como un Blob (archivo binario)
            const imageBlob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
            
            // 3. Crear FormData para enviar el archivo al servidor (POST request)
            const formData = new FormData();
            formData.append('chart_image', imageBlob, 'reporte_disponibilidad.png');
            formData.append('caption', `*Reporte de Disponibilidad*\nGenerado el: ${new Date().toLocaleString('es-VE')}\nTotal Flota: {{ $total }}\nUnidades Activas: {{ $operativosCount }}\nDisponibilidad: {{ $porcentajeDisponibilidad }}%`);
            
            // 4. Enviar al endpoint de Laravel (ruta que debe existir: telegram.send.photo)
            const response = await fetch('{{ route('telegram.send.photo') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}' // Protección CSRF de Laravel
                },
                body: formData
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || `Error ${response.status}: Fallo en el servidor al enviar a Telegram.`);
        }

            // 5. Éxito
            

        } catch (error) {
            console.error('Error al enviar a Telegram:', error);
            // Mostrar mensaje amigable al usuario
        //     showStatus(`Error al enviar a Telegram: ${error.message}`, 'error');

        } finally {
            // 6. Reestablecer el botón
            sendTelegramButton.disabled = false;
        }
    }
    
    async function captureAndCopyToClipboard() {
        // 1. Mostrar estado de carga y deshabilitar botón
        statusMessage.textContent = 'Generando imagen...';
        statusMessage.classList.remove('hidden', 'bg-red-100', 'text-red-800', 'bg-green-100', 'text-green-800');
        statusMessage.classList.add('bg-yellow-100', 'text-yellow-800');
        captureButton.disabled = true;
        outputContainer.innerHTML = ''; // Limpiar previsualización anterior

        try {
            // 2. Generar el Canvas a partir del elemento DOM (ya corregido a 'printableArea[0]')
            const canvas = await html2canvas(printableArea, {
                scale: 2, // Aumenta la escala para mejor calidad de imagen
                logging: false, // Desactiva logs de html2canvas
                useCORS: true // Necesario si hay imágenes o recursos externos
            });

            // Opcional: Mostrar el canvas generado en el DOM
           // outputContainer.appendChild(canvas);

            // 3. Convertir el Canvas a un Blob (formato de datos binarios)
            const imageBlob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
            
            if (!imageBlob) {
                throw new Error('No se pudo generar el Blob de la imagen.');
            }

            // 4. Copiar la imagen (Blob) al portapapeles usando el Clipboard API
            const item = new ClipboardItem({ "image/png": imageBlob });
            await navigator.clipboard.write([item]);

            // 5. Éxito
            statusMessage.textContent = '¡Éxito! La imagen ha sido copiada al portapapeles. Ahora puedes pegarla (Ctrl+V).';
            statusMessage.classList.replace('bg-yellow-100', 'bg-green-100');
            statusMessage.classList.replace('text-yellow-800', 'text-green-800');

        } catch (error) {
            // 6. Manejo de Errores
            let errorMessage = 'Error desconocido al copiar.';

            if (error.name === 'NotAllowedError' || (error.message && error.message.includes('permission'))) {
                errorMessage = 'Permiso denegado: El navegador requiere que la página esté en un contexto seguro (HTTPS) o que el usuario interactúe primero para usar el Clipboard API.';
            } else {
                console.error('Error durante la captura o copia:', error);
                errorMessage = `Error al generar/copiar la imagen: ${error.message}`;
            }
            
            statusMessage.textContent = errorMessage;
            statusMessage.classList.replace('bg-yellow-100', 'bg-red-100');
            statusMessage.classList.replace('text-yellow-800', 'text-red-800');

        } finally {
            // 7. Reestablecer el botón
            captureButton.disabled = false;
        }
    }

    async function exportarEImprimir() {

        // 1. Estado visual
        statusMessage.textContent = 'Procesando reporte gerencial...';
        statusMessage.classList.remove('hidden', 'bg-red-100', 'bg-green-100');
        statusMessage.classList.add('bg-yellow-100', 'text-yellow-800');
        exportButton.disabled = true;

        printableArea.classList.add('is-capturing');

        try {
            // 2. Captura del área con escala 2 para alta definición
            const canvas = await html2canvas(printableArea, {
                scale: 2,
                useCORS: true,
                logging: false,
                backgroundColor: '#ffffff',
                windowWidth: 1000 // Mantenemos el estándar de ancho del Master Card
            });

            // 3. Convertir a URL de datos (Data URL)
            const image = canvas.toDataURL("image/png");

            // 4. Crear link de descarga dinámico
            const link = document.createElement('a');
            
            // Formateamos el nombre del archivo: reporte_disponibilidad_25_03_2026.png
            const fecha = new Date().toLocaleDateString().replace(/\//g, '_');
            link.download = `reporte_disponibilidad_${fecha}.png`;
            
            link.href = image;
            
            // 5. Disparar la descarga
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            // 6. Éxito
            statusMessage.textContent = '¡Reporte descargado con éxito!';
            statusMessage.classList.replace('bg-yellow-100', 'bg-green-100');
            statusMessage.classList.replace('text-yellow-800', 'text-green-800');

        } catch (error) {
            console.error('Error al descargar:', error);
            statusMessage.textContent = 'Error al generar la descarga: ' + error.message;
            statusMessage.classList.replace('bg-yellow-100', 'bg-red-100');
        } finally {
            // 7. Limpieza
            printableArea.classList.remove('is-capturing');
            downloadButton.disabled = false;
            setTimeout(() => statusMessage.classList.add('hidden'), 5000);
        }
    }

    // 8. Asignar el evento al botón
    captureButton.addEventListener('click', captureAndCopyToClipboard);
    exportButton.addEventListener('click', exportarEImprimir);


    // 7. Asignar evento al nuevo botón
    if (sendTelegramButton) {
        sendTelegramButton.addEventListener('click', sendReportToTelegram);
    }
});
</script>
@endpush
@endsection