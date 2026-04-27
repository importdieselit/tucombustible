@extends('layouts.app')
@push('styles')
<style>

    .badge {
  /* Alineación */
  display: inline-flex;
  align-items: center;
  gap: 3px; /* Espacio entre icono y texto */
  
  /* Estilo del fondo y texto */
  background-color: #007bff;
  color: white;
  padding: 5px 5px;
  border-radius: 20px;
  font-family: sans-serif;
  font-size: 12px;
}

.icon {
  display: flex;
  align-items: center;
  /* Si usas fuentes de iconos como FontAwesome, 
     esto asegura que no haya desfases */
}
/* Colores de Flota */
.bg-chutos { background-color: #ff6600 !important; color: white; }
.bg-camiones { background-color: #ffc107 !important; color: #212529; }
.bg-cisternas { background-color: #198754 !important; color: white; }
.bg-camionetas { background-color: #e7e7e7 !important; color: white; }

.border-chutos { border-color: #ff6600 !important; }
.border-camiones { border-color: #ffc107 !important; }
.border-cisternas { border-color: #198754 !important; }
.border-camionetas { border-color: #e7e7e7 !important; }

/* Utilidades de contraste */
.text-chutos { color: #ff6600; }
.bg-white-clean { background-color: #ffffff; }

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

    /* Estilo para el placeholder del div editable */
[contenteditable=true]:empty:before {
    content: attr(placeholder);
    display: block;
    color: #adb5bd;
}

/* En la captura, quitamos el borde punteado para que parezca un reporte oficial */
.is-capturing #observaciones_reporte {
    border: none !important;
    padding-left: 0 !important;
    color: #333 !important;
}

/* Color específico para el título de observaciones si prefieres el naranja de los Chutos */
.bg-observaciones {
    background-color: #2c3e50; /* Color camionetas */
    color: white;
}
}
</style>
@endpush

@section('content')
<div class="container-fluid py-4" style="background-color: #f4f6f9; min-height: 100vh;">
    
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <h2 class="fw-bold text-navy">Dashboard Comencial`</h2>
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
<div class="card shadow-sm mb-4 no-print border-0">
        <div class="card-body bg-white rounded-3">
            <form action="{{ url()->current() }}" method="GET" class="row align-items-end g-3">
                <div class="col-md-3">
                    <label for="fecha_inicio" class="form-label small fw-bold text-muted mb-1"><i class="fas fa-calendar-alt me-1"></i> Desde</label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" class="form-control form-control-sm" 
                           value="{{ request('fecha_inicio', \Carbon\Carbon::now()->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-3">
                    <label for="fecha_fin" class="form-label small fw-bold text-muted mb-1"><i class="fas fa-calendar-check me-1"></i> Hasta</label>
                    <input type="date" id="fecha_fin" name="fecha_fin" class="form-control form-control-sm" 
                           value="{{ request('fecha_fin', \Carbon\Carbon::now()->addDays(2)->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-dark btn-sm w-100 shadow-sm">
                        <i class="fas fa-search me-1"></i> Generar Reporte
                    </button>
                </div>
                <div class="col-md-3 text-end">
                    <a href="{{ url()->current() }}" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="fas fa-undo me-1"></i> Limpiar Filtro
                    </a>
                </div>
            </form>
        </div>
    </div>
    <div id="reporteOperaciones" class="report-master-card shadow-lg bg-white mx-auto p-0 printableArea" style="max-width: 1000px; border-radius: 15px; overflow: hidden;">
 
<div class="container-fluid py-4">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-corporate-blue d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i> RESUMEN DE OPERACIONES</h5>
            <span class="badge bg-white text-dark">{{ \Carbon\Carbon::parse($reporte['fecha'])->format('d/m/Y') }}</span>
        </div>
        <div class="row g-2 mb-4 mx-1">
    <div class="col-md-2 col-sm-6">
        <div class="card border-0 shadow-sm border-start border-4 border-camionetas">
            <div class="card-body p-2 text-center">
                <small class="text-muted fw-bold d-block text-uppercase" style="font-size: 0.65rem;">Disponibles</small>
                <h5 class="mb-0 fw-bold text-dark">{{ number_format($stats['disponibles'], 0, ',', '.') }} <span class="small">L</span></h5>
                <i class="fas fa-warehouse text-muted mt-1"></i>
            </div>
        </div>
    </div>

    <div class="col-md-2 col-sm-6">
        <div class="card border-0 shadow-sm border-start border-4 border-cisternas">
            <div class="card-body p-2 text-center">
                <small class="text-muted fw-bold d-block text-uppercase" style="font-size: 0.65rem;">Despachados</small>
                <h5 class="mb-0 fw-bold text-success">{{ number_format($stats['despachados'], 0, ',', '.') }} <span class="small">L</span></h5>
                <i class="fas fa-sign-out-alt text-success mt-1"></i>
            </div>
        </div>
    </div>

    <div class="col-md-2 col-sm-6">
        <div class="card border-0 shadow-sm border-start border-4 border-camiones">
            <div class="card-body p-2 text-center">
                <small class="text-muted fw-bold d-block text-uppercase" style="font-size: 0.65rem;">Cargas</small>
                <h5 class="mb-0 fw-bold text-warning">{{ number_format($stats['cargas'], 0, ',', '.') }} <span class="small">L</span></h5>
                <i class="fas fa-truck-loading text-warning mt-1"></i>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm border-start border-4 border-chutos">
            <div class="card-body p-2 text-center">
                <small class="text-muted fw-bold d-block text-uppercase" style="font-size: 0.65rem;">Despachos Programados</small>
                <h5 class="mb-0 fw-bold text-chutos">{{ number_format($stats['prog_desp'], 0, ',', '.') }} <span class="small">L</span></h5>
                <i class="fas fa-calendar-alt text-chutos mt-1"></i>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
         
        <div class="card border-0 shadow-sm border-start border-4 border-info">
            <div class="card-body p-2 text-center">
                <small class="text-muted fw-bold d-block text-uppercase" style="font-size: 0.65rem;">Carga Programada</small>
                <h5 class="mb-0 fw-bold text-info">{{ number_format($stats['prog_carg'], 0, ',', '.') }} <span class="small">L</span></h5>
                <i class="fas fa-clock text-info mt-1"></i>
            </div>
        </div>
    </div>
</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered mb-0 text-center align-middle">
                <thead class="bg-light small">
                    <tr>
                    <th rowspan="2" class="text-start ps-3 bg-white-clean">CATEGORÍA</th>
                        <th colspan="2" class="bg-camionetas border-camionetas">1. PROGRAMADOS</th>
                        <th colspan="2" class="bg-camiones border-camiones">2. EN RUTA</th>
                        <th colspan="2" class="bg-cisternas border-cisternas">3. COMPLETADOS</th>
                    </tr>
                    <tr class="bg-light x-small">
                        <th width="12%">DIESEL</th><th width="12%">MGO</th>
                        <th width="12%">DIESEL</th><th width="12%">MGO</th>
                        <th width="12%">DIESEL</th><th width="12%">MGO</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-start ps-3 fw-bold border-start border-4 border-camionetas" style="border-left-color: #ffc107 !important;">
                            <i class="fas fa-shipping-fast text-orange me-2"></i> DESPACHOS
                        </td>
                        <td class="fw-bold">{{ $reporte['despachos']['programados']['ind'] }}</td>
                        <td class="fw-bold">{{ $reporte['despachos']['programados']['mgo'] }}</td>
                        <td class="bg-warning bg-opacity-10 fw-bold">{{ $reporte['despachos']['en_ruta']['ind'] }}</td>
                        <td class="bg-warning bg-opacity-10 fw-bold">{{ $reporte['despachos']['en_ruta']['mgo'] }}</td>
                        <td class="bg-success bg-opacity-10 fw-bold text-success">{{ $reporte['despachos']['completados']['ind'] }}</td>
                        <td class="bg-success bg-opacity-10 fw-bold text-success">{{ $reporte['despachos']['completados']['mgo'] }}</td>
                    </tr>
                    <tr class="bg-white-clean">
                        <td class="text-start ps-3 fw-bold border-start border-4 border-camionetas" style="border-left-color: #ff6600 !important;">
                            <i class="fas fa-gas-pump text-chutos me-2"></i> CARGAS PLANTA
                        </td>
                        <td class="fw-bold">{{ $reporte['cargas']['programados']['ind'] }}</td>
                        <td class="fw-bold">{{ $reporte['cargas']['programados']['mgo'] }}</td>
                        <td class="bg-warning bg-opacity-10 fw-bold">{{ $reporte['cargas']['en_ruta']['ind'] }}</td>
                        <td class="bg-warning bg-opacity-10 fw-bold">{{ $reporte['cargas']['en_ruta']['mgo'] }}</td>
                        <td class="bg-success bg-opacity-10 fw-bold text-success">{{ $reporte['cargas']['completados']['ind'] }}</td>
                        <td class="bg-success bg-opacity-10 fw-bold text-success">{{ $reporte['cargas']['completados']['mgo'] }}</td>
                    </tr>
                    <tr class="bg-white-clean">
                        <td class="text-start ps-3 fw-bold border-start border-4 border-camionetas" style="border-left-color: #6f42c1 !important;">
                            <i class="fas fa-truck-loading text-purple me-2"></i> FLETES
                        </td>
                        <td class="fw-bold">{{ $reporte['fletes']['programados']['ind'] }}</td>
                        <td class="fw-bold">{{ $reporte['fletes']['programados']['mgo'] }}</td>
                        <td class="bg-warning bg-opacity-10 fw-bold">{{ $reporte['fletes']['en_ruta']['ind'] }}</td>
                        <td class="bg-warning bg-opacity-10 fw-bold">{{ $reporte['fletes']['en_ruta']['mgo'] }}</td>
                        <td class="bg-success bg-opacity-10 fw-bold text-success">{{ $reporte['fletes']['completados']['ind'] }}</td>
                        <td class="bg-success bg-opacity-10 fw-bold text-success">{{ $reporte['fletes']['completados']['mgo'] }}</td>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-list-ul me-2"></i> DETALLE DE PLANIFICACIÓN DIARIA</h5>
        </div>
        <div class="card-body p-0">
            {{-- <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light x-small text-uppercase">
                        <tr>
                            <th class="ps-3">Estatus</th>
                            <th>Fecha Salida</th>
                            <th>Tipo</th>
                            <th>Unidad</th>
                            <th>Chofer</th>
                            <th>Litros</th>
                            <th>Producto</th>
                            <th>Destino</th>
                        </tr>
                    </thead>
                    <tbody>
                       @foreach($viajesDelDia as $v)
                            @php
                                
                                if ($v->es_flete) {
                                    $tipoEtiqueta = 'Flete';
                                    $badgeColor = 'bg-dark text-white';
                                    $icon = 'fa-truck-loading';
                                    $textColor = 'text-purple'; // Definir este color en CSS: #6f42c1
                                } else {
                                    $tipoEtiqueta = $v->es_despacho ? 'Despacho' : 'Carga';
                                    $badgeColor = $v->es_despacho ? 'bg-primary' : 'bg-danger';
                                    $icon = $v->es_despacho ? 'fa-arrow-up' : 'fa-arrow-down';
                                    $textColor = $v->es_despacho ? 'text-primary' : 'text-danger';
                                }
                            @endphp
                            <tr style="cursor: default; font-size: 0.8rem;">
                                <td class="p-1">
                                    @php
                                        $statusConfig = match($v->status) {
                                            'Programado' => ['class' => 'bg-info', 'icon' => 'fa-clock'],
                                            'EN RUTA'    => ['class' => 'bg-camiones', 'icon' => 'fa-truck-moving'],
                                            'COMPLETADO' => ['class' => 'bg-cisternas', 'icon' => 'fa-check-double'],
                                            default      => ['class' => 'bg-secondary', 'icon' => 'fa-info-circle']
                                        };
                                    @endphp
                                    <span class="badge {{ $statusConfig['class'] }} p-1 px-2 shadow-sm border-0" style="min-width: 80px; font-size: 0.6rem;">
                                        <i class="fas {{ $statusConfig['icon'] }} me-1"></i> {{ $v->status }}
                                    </span>
                                </td>
                                <td>
                                    <div class=" text-small" style="font-size: 0.8rem;">{{ $v->fecha_salida ? \Carbon\Carbon::parse($v->fecha_salida)->format('d/m/Y') : 'N/A' }}</div>
                                    {{-- <div class="fw-bold text-small" style="font-size: 0.7rem;">{{ $v->fecha_salida ? \Carbon\Carbon::parse($v->fecha_salida)->format('H:i') : 'N/A' }}</div> --}}
                               {{--  </td>
                                <td class="small fw-bold {{ $textColor }}">
                                    <i class="fas {{ $icon }}"></i> {{ strtoupper($tipoEtiqueta) }}
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $v->vehiculo->flota ?? 'N/A' }} {{ $v->vehiculo->placa ?? 'N/A' }}</div>
                                    <div class="fw-bold small bt-1" style="font-size: 0.8rem; border-top:solid 1px #ced4da; width: fit-content;">{{ $v->cisternaAcoplada->flota ?? '' }} {{ $v->cisternaAcoplada->placa ?? '' }}</div>
                                </td>
                                <td class="small">{{ explode(' ', $v->chofer->persona->nombre ?? 'Sin asignar')[0] }}</td>
                                <td class="small fw-bold">{{ $v->litros_totales; }}</td>
                                <td><span class="badge border text-dark bg-white">{{ $v->producto->nombre ?? 'N/A' }}</span></td>
                                <td class="small text-wrap " style="max-width: 180px;" title="{{ $v->destino_limpio }}">
                                    <div class="fw-bold">{{ $v->destino_limpio }}</div>
                                    <div class="small bt-1" style="font-size: 0.7rem; border-top:solid 1px #ced4da; width: fit-content;">{{ $v->cliente_reporte }}</div>

                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div> --}}
            <div class="table-responsive">
                <div class=" p-3 mb-3 shadow-sm border-light">
                    <h6 class="text-muted mb-3"><i class="fa fa-info-circle me-2"></i> Leyenda de Estatus y Tipo de Servicio</h6>
                    <div class="d-flex flex-wrap gap-3 small">
                        <div class="d-flex align-items-center me-4">
                            <div class="rounded-circle bg-cisternas me-2" style="width: 15px; height: 15px; border: 1px solid #0002;"></div>
                            <span class="small">COMPLETADOS</span>
                        </div>
                        <div class="d-flex align-items-center me-4">
                            <div class="rounded-circle bg-camiones me-2" style="width: 15px; height: 15px; border: 1px solid #0002;"></div>
                            <span class="small">EN RUTA</span>
                        </div>
                        <div class="d-flex align-items-center me-4">
                            <div class="rounded-circle bg-info me-2" style="width: 15px; height: 15px; border: 1px solid #0002;"></div>
                            <span class="small">PROGRAMADO</span>
                        </div>
                        <div class="d-flex align-items-center me-4">
                            <i class="fa fa-arrow-down mx-1"></i>
                            <span class="small">DESPACHO</span>
                        </div>
                        <div class="d-flex align-items-center me-4">
                            <i class="fa fa-arrow-up mx-1"></i>
                            <span class="small">CARGA</span>
                        </div>
                        <div class="d-flex align-items-center me-4">
                             <i class="fas fa-truck-loading"></i>
                            <span class="small">FLETE</span>
                        </div>
                    </div>
                </div>
                <table class="table table-bordered align-top mb-0">
                    <thead class="table-light x-small text-uppercase">
                        <tr>
                            <th class="ps-3" style="width: 100px; background: #f8f9fa;">Unidad</th>
                            @foreach($rangoDias as $dia)
                                <th class="text-center" style="min-width: 200px;">
                                    {{ \Carbon\Carbon::parse($dia)->locale('es')->dayName }} {{ \Carbon\Carbon::parse($dia)->format('d/m') }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($viajesPorUnidad as $vehiculoId => $viajesUnidad)
                            @php $primerViaje = $viajesUnidad->first(); @endphp
                            <tr>
                                <td class="ps-3 bg-light border-end">
                                    <div class="fw-bold text-navy" style="font-size: 0.85rem;">
                                        {{ $primerViaje->vehiculo->flota ?? 'N/A' }}
                                    </div>
                                    <div class="small text-muted" style="font-size: 0.7rem;">
                                        {{ $primerViaje->vehiculo->placa ?? $primerViaje->otro_vehiculo ?? 'N/A' }}
                                    </div>
                                </td>

                                @foreach($rangoDias as $dia)
                                    @php
                                        $viajesEseDia = $viajesUnidad->filter(fn($v) => \Carbon\Carbon::parse($v->fecha_salida)->format('Y-m-d') === $dia);
                                    @endphp

                                    <td class="p-2" style="vertical-align: top; min-width: 450px;"> <div class="d-flex flex-wrap gap-2">
        @foreach($viajesEseDia as $v)
            @php
                if ($v->es_flete) {
                    $tipo = ['label' => 'FLETE', 'icon' => 'fa-truck-loading', 'color' => 'purple', 'border' => '#6f42c1'];
                } else {
                    $tipo = $v->es_despacho 
                        ? ['label' => 'DESPACHO', 'icon' => 'fa-arrow-up', 'color' => 'primary', 'border' => '#0d6efd']
                        : ['label' => 'CARGA', 'icon' => 'fa-arrow-down', 'color' => 'danger', 'border' => '#dc3545'];
                }

                $statusConfig = match($v->status) {
                    'Programado' => ['class' => 'bg-info', 'icon' => 'fa-clock'],
                    'EN RUTA'    => ['class' => 'bg-warning text-dark', 'icon' => 'fa-truck-moving'],
                    'COMPLETADO' => ['class' => 'bg-success text-white', 'icon' => 'fa-check-double'],
                    default      => ['class' => 'bg-secondary', 'icon' => 'fa-info-circle']
                };
            @endphp

            <div class="card shadow-sm border-0 border-start border-4 flex-grow-1" 
                 style="border-color: {{ $tipo['border'] }} !important; 
                        flex: 1 1 calc(50% - 0.5rem); /* Esto obliga a intentar ocupar el 50% menos el espacio del gap */
                        min-width: 200px; /* Ancho mínimo para que no se rompa el texto */
                        max-width: 100%;">
                
                <div class="p-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="badge {{ $statusConfig['class'] }} p-1 px-2" style="font-size: 0.6rem;">
                            <i class="fas {{ $tipo['icon'] }} me-1"></i>
                        </span>
                        <span class="x-small text-dark  text-truncate" style="font-size: 0.65rem; max-width: 120px;" title="{{ $v->producto->nombre ?? 'N/A' }} | {{ $v->cliente_reporte }}">
                            {{ $v->cliente_reporte }}
                        </span>
                        <span class="fw-bold text-muted" style="font-size: 0.75rem;">
                            {{ \Carbon\Carbon::parse($v->fecha_salida)->format('H:i') }}
                        </span>
                    </div>

                    <div class="fw-bold text-uppercase mb-1" style="font-size: 0.85rem; color: {{ $tipo['border'] }}; white-space: nowrap; overflow: hidden; text-wrap:auto;">
                         {{ $v->destino_limpio   ?? 'N/A' }}
                    </div>

                    <div class="row g-0 border-top pt-1 mt-1 align-items-center" style="font-size: 0.75rem;">
                        <div class="col-4 text-truncate">
                            <i class="fas fa-user text-secondary me-1"></i>
                            <strong>{{ explode(' ', $v->chofer->persona->nombre ?? $v->otro_chofer ?? 'N/A')[0] }}</strong>
                        </div>
                        <div class="col-4 x-small text-dark  text-truncate" style="font-size: 0.65rem; max-width: 100px;" title="{{ $v->producto->nombre ?? 'N/A' }} | {{ $v->cliente_reporte }}">
                                                {{ $v->producto->nombre ?? 'N/A' }}
                        </div>
                        <div class="col-4 text-end">
                            <span class="fw-bolder">{{ number_format($v->litros_totales, 0) }}</span> 
                            <small class="text-muted">L</small>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</td>
                                @endforeach
                                
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($rangoDias) + 1 }}" class="text-center p-4 text-muted">
                                    No hay planificaciones para los próximos 3 días.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card border-0 shadow-sm mt-4 mb-4 mx-3">
                <div class="card-header bg-camionetas text-white d-flex align-items-center">
                    <h6 class="mb-0 small fw-bold"><i class="fas fa-comment-alt me-2"></i> OBSERVACIONES / NOVEDADES DEL DÍA</h6>
                </div>
                <div class="card-body bg-white border border-top-0">
                    <div id="observaciones_reporte" 
                        contenteditable="true" 
                        class="p-3 text-muted" 
                        style="min-height: 100px; border: 1px dashed #ced4da; border-radius: 8px; font-size: 0.9rem; outline: none;"
                        placeholder="Haga clic aquí para escribir las novedades...">
                        Sin observaciones adicionales para la jornada.
                    </div>
                </div>
                <div class="card-footer bg-light p-1 text-center no-print">
                    <small class="text-muted" style="font-size: 0.7rem;">
                        <i class="fas fa-info-circle"></i> Este recuadro es editable. Lo que escribas aquí saldrá en la imagen capturada.
                    </small>
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
            formData.append('caption', `*Reporte de Disponibilidad*\nGenerado el: ${new Date().toLocaleString('es-VE')}`);
            
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
                useCORS: true, // Necesario si hay imágenes o recursos externos
                windowWidth: 1300 // Mantenemos el estándar de ancho del Master Card

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
                windowWidth: 1300 // Mantenemos el estándar de ancho del Master Card
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
