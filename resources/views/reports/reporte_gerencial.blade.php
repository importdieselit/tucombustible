    @extends('layouts.app')

  @push('styles')
<style>
    /* Estándar Visual TuCombustible - Versión High-Visibility */
    .bg-chutos { background-color: #ff6600 !important; color: white; }
    .bg-camiones { background-color: #ffc107 !important; color: #212529; }
    .bg-cisternas { background-color: #198754 !important; color: white; }
    .bg-camionetas { background-color: #2c3e50 !important; color: white; }
    
    .badge-std {
        display: inline-flex;
        align-items: center;
        padding: 8px 16px; /* Más grande */
        border-radius: 20px;
        font-size: 14px; /* Aumentado */
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .card-master {
        border-radius: 20px; /* Más redondeado */
        box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        border: none;
        margin-bottom: 1.5rem;
    }

    /* Tipografía de Lectura Rápida */
    .mega-text {
        font-size: 3.5rem !important; /* Impacto total */
        font-weight: 900 !important;
        line-height: 1;
        margin: 10px 0;
    }

    .kpi-label {
        font-size: 1.1rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .sub-kpi {
        font-size: 1.2rem;
        font-weight: 600;
    }

    .table-text {
        font-size: 1.1rem !important;
    }
</style>
@endpush

    @section('content')
<div class="container-fluid p-4" id="printable-area" style="background-color: #f8f9fa;">
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
    
    <div id="reporteOperaciones" class="report-master-card shadow-lg bg-white mx-auto p-0 printableArea" style="max-width: 1500px; border-radius: 15px; overflow: hidden;">
 
        <div class="container-fluid py-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-corporate-blue d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><i class="fas fa-chart-pie me-2"></i>REPORTE MAESTRO GERENCIAL</h3>
                    <span class="badge bg-white text-dark">{{ \Carbon\Carbon::parse($reporte['fecha'])->format('d/m/Y') }}</span>
                </div>
                <div class="row g-2 mb-4 mx-1">
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm border-start border-4 border-primary border-camionetas">
                            <div class="card-body p-2 text-center">
                                <small class="text-muted fw-bold d-block text-uppercase" style="font-size: 0.65rem;">DISPONIBILIDAD FLOTA</small>
                                <h2 class="text-primary font-bold">{{ round(($statsFlota['operativos'] / $statsFlota['total']) * 100) }}%</h2>
                                <div class="progress mb-2" style="height: 12px; border-radius: 10px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                        style="width: {{ ($statsFlota['operativos'] / $statsFlota['total']) * 100 }}%"></div>
                                </div>
                                <span class=" text-dark">{{ $statsFlota['operativos'] }} / {{ $statsFlota['total'] }} UNIDADES</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm border-start border-4 border-info">
                            <div class="card-body p-2 text-center">
                                <small class="text-muted fw-bold d-block text-uppercase" style="font-size: 0.65rem;">CAPACIDAD OPERATIVA</small>
                                <div class="d-flex justify-content-around align-items-center">
                                    <div class="text-center">
                                        <span class="text-orange fs-5 d-block">{{ $flota->whereIn('tipo', [1,3,4,5])->where('estatus', 1)->count() }}</span>
                                        <small class="fw-bold">CHUTOS</small>
                                    </div>
                                    <div class="text-center">
                                        <span class="text-cisternas fs-5 d-block">{{ $flota->where('tipo', 2)->where('estatus', 1)->count() }}</span>
                                        <small class="fw-bold">CISTERNAS</small>
                                    </div>
                                    <div class="text-center">
                                        <span class="text-camionetas fs-5 d-block">{{ $flota->where('tipo', 6)->where('estatus', 1)->count() }}</span>
                                        <small class="fw-bold">LIVIANO</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm border-start border-4 border-danger">
                            <div class="card-body p-2 text-center">
                                <small class="text-muted fw-bold d-block text-uppercase" style="font-size: 0.65rem;">ÓRDENES CRÍTICAS</small>
                                <h2 class="text-danger font-bold p-2">{{ $ordenesAbiertas->where('dias_abierta', '>', 3)->count() }}</h2>
                                <span class="font-bold text-danger fs-6 pulse-animation">MÁS DE 3 DÍAS</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm border-start border-4 border-camionetas">
                            <div class="card-body p-2 text-center">
                                <small class="text-muted fw-bold d-block text-uppercase" style="font-size: 0.80rem;">Disponibilidad de Combustible</small>
                                <h5 class="mb-0 fw-bold text-dark">{{ number_format($stats['disponibles'], 0, ',', '.') }} <span class="small">L</span></h5>
                                <i class="fas fa-warehouse text-muted mt-1"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm border-start border-4 border-primary">
                            <div class="card-body p-2 text-center">
                                <small class="text-muted fw-bold d-block text-uppercase" style="font-size: 0.65rem;">Despachados</small>
                                <h5 class="mb-0 fw-bold text-success">{{ number_format($stats['despachados'], 0, ',', '.') }} <span class="small">L</span></h5>
                                <i class="fas fa-sign-out-alt text-success mt-1"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm border-start border-4 border-info">
                            <div class="card-body p-2 text-center">
                                <small class="text-muted fw-bold d-block text-uppercase" style="font-size: 0.65rem;">Cargas</small>
                                <h5 class="mb-0 fw-bold text-warning">{{ number_format($stats['cargas'], 0, ',', '.') }} <span class="small">L</span></h5>
                                <i class="fas fa-truck-loading text-warning mt-1"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm border-start border-4 border-danger">
                            <div class="card-body p-2 text-center">
                                <small class="text-muted fw-bold d-block text-uppercase" style="font-size: 0.65rem;">Despachos Programados</small>
                                <h5 class="mb-0 fw-bold text-chutos">{{ number_format($stats['prog_desp'], 0, ',', '.') }} <span class="small">L</span></h5>
                                <i class="fas fa-calendar-alt text-chutos mt-1"></i>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <div class="card border-0 shadow-sm border-start border-4 border-camioneta">
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
                    
                    <div class="table-responsive">
                        <div class=" p-3 mb-3 shadow-sm border-light">
                            <h6 class="text-muted mb-3"><i class="fa fa-info-circle me-2"></i> Leyenda de Estatus y Tipo de Servicio</h6>
                            <div class="d-flex flex-wrap gap-3 small">
                                
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
        <div class="card card-master border-top border-4 border-info">
    <div class="card-header bg-white fw-bold py-2">
        <i class="fas fa-route me-2 text-info"></i> SEGUIMIENTO EN RUTA Y PROGRAMADOS
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-borderless align-middle mb-0">
            <thead class="table-light" style="font-size: 0.75rem;">
                <tr>
                    <th style="width: 20%;" class="ps-3">UNIDAD</th>
                    <th style="width: 80%;">ACTIVIDAD CRONOLÓGICA</th>
                </tr>
            </thead>
            <tbody>
                @forelse($viajesPorUnidad as $vehiculoId => $viajesUnidad)
                    @php 
                        // Filtramos y ordenamos chronológicamente
                        $viajesActivos = $viajesUnidad->whereIn('status', ['Programado', 'EN RUTA'])->sortBy('fecha_salida');
                        if($viajesActivos->isEmpty()) continue; 
                        $primerViaje = $viajesUnidad->first(); 
                    @endphp
                    
                    <tr class="border-bottom">
                        <td class="ps-3 py-2 border-end">
                            <div class="fw-bolder text-dark" style="font-size: 0.95rem;">
                                {{ $primerViaje->vehiculo->flota ?? 'N/A' }}
                            </div>
                            <div class="text-muted" style="font-size: 0.7rem;">
                                {{ $primerViaje->vehiculo->placa ?? '' }}
                            </div>
                        </td>

                        <td class="py-2">
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($viajesActivos as $v)
                                    @php
                                        $isEnRuta = $v->status === 'EN RUTA';
                                        $color = $isEnRuta ? 'warning' : 'info';
                                        $text = $isEnRuta ? 'text-dark' : 'text-white';
                                        
                                        // Formato de fecha inteligente (HOY vs Otro día)
                                        $fecha = \Carbon\Carbon::parse($v->fecha_salida);
                                        $diaHora = $fecha->isToday() ? 'HOY ' . $fecha->format('H:i') : $fecha->format('d/m H:i');
                                    @endphp

                                    <div class="border border-{{ $color }} rounded shadow-sm bg-white d-inline-flex align-items-center" style="font-size: 0.8rem;">
                                        <span class="badge bg-{{ $color }} {{ $text }} rounded-start px-2 py-2" style="border-radius: 0;">
                                            <i class="fas {{ $isEnRuta ? 'fa-truck-moving' : 'fa-clock' }} me-1"></i> {{ $diaHora }}
                                        </span>
                                        <span class="fw-bold text-dark px-2 text-truncate" style="max-width: 180px;" title="{{ $v->destino_limpio }}">
                                            {{ $v->destino_limpio }}
                                        </span>
                                        <span class="badge bg-light text-primary border-start px-2 py-2" style="border-radius: 0;">
                                            {{ number_format($v->litros_totales, 0) }} L
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="text-center text-muted py-3 fw-bold" style="font-size: 0.85rem;">
                            No hay unidades en ruta ni viajes programados en este momento.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
                    </div>
                    <div class="card border-0 shadow-sm mt-4 mb-4 mx-3">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="table-text fw-bold text-uppercase">
                                    <th style="width: 20%">Unidad</th>
                                    <th style="width: 40%">Motivo / Observación</th>
                                    <th style="width: 20%">Ingreso</th>
                                    <th style="width: 20%">Días Parada</th>
                                </tr>
                            </thead>
                            <tbody class="table-text">
                                @foreach($ordenesAbiertas as $orden)
                                <tr>
                                    <td>
                                        <span class="text {{ $orden->vehiculoBelong->tipo == 2 ? 'text-cisternas' : 'text-chutos' }}">
                                            {{ $orden->vehiculoBelong->flota }} - {{ $orden->vehiculoBelong->placa }}
                                        </span>
                                    </td>
                                    <td class="fw-bold">{{ $orden->tipo }}</td>
                                    <td>{{ \Carbon\Carbon::parse($orden->fecha_in)->format('d/m/Y') }}</td>
                                    <td>
                                        <span class="text-danger fs-5 px-3">
                                            {{ $orden->dias_abierta }}
                                        </span>
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

     <div id="outputContainer" class="mt-8 pt-4 border-t border-gray-300 width-full">
        </div>
</div>
    @endsection
    

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

    const sendWhatsappButton = document.getElementById('sendWhatsappButton');

    async function sendReportToWhatsapp() {
        statusMessage.textContent = 'Enviando imagen a WhatsApp...';
        statusMessage.classList.remove('hidden', 'bg-red-100', 'bg-green-100');
        statusMessage.classList.add('bg-yellow-100', 'text-yellow-800');
        sendWhatsappButton.disabled = true;

        try {
            // Capturar
            const canvas = await html2canvas(printableArea, { scale: 2, useCORS: true });
            const imageBlob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
            
            // Crear FormData
            const formData = new FormData();
            formData.append('image', imageBlob, 'reporte_whatsapp.png');
            formData.append('caption', '📊 *Reporte Gerencial KPI* - ' + new Date().toLocaleDateString());
            
            // Enviar a tu ruta de Laravel
            const response = await fetch('{{ route('whatsapp.send.report') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken },
                body: formData
            });

            if (!response.ok) throw new Error('Error al enviar el reporte.');

            statusMessage.textContent = '¡Reporte enviado exitosamente a WhatsApp!';
            statusMessage.classList.replace('bg-yellow-100', 'bg-green-100');
        } catch (error) {
            statusMessage.textContent = 'Error: ' + error.message;
            statusMessage.classList.replace('bg-yellow-100', 'bg-red-100');
        } finally {
            sendWhatsappButton.disabled = false;
        }
    }

    sendWhatsappButton.addEventListener('click', sendReportToWhatsapp);
        
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
                    scale: 3, // Aumenta la escala para mejor calidad de imagen
                    logging: false, // Desactiva logs de html2canvas
                    useCORS: true, // Necesario si hay imágenes o recursos externos
                    windowWidth: 2000, // Mantenemos el estándar de ancho del Master Card
                    windowHeight: 1500, // Mantenemos el estándar de alto del Master Card
                    constraints: {
                        width: 2000,
                        height: 1500
                    }

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
                scale: 3,
                useCORS: true,
                logging: false,
                backgroundColor: '#ffffff',
                windowWidth: 1500 // Mantenemos el estándar de ancho del Master Card
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