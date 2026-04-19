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
            <h2 class="fw-bold text-navy">Dashboard Mantenimiento</h2>
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
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('ordenes.reporte_gerencial') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="small fw-bold text-muted mb-1"><i class="fas fa-calendar-alt me-1"></i> FECHA INICIO</label>
                    <input type="date" 
                        name="fecha_inicio" 
                        class="form-control form-control-sm border-0 bg-light" 
                        value="{{ $reporte['periodo']['inicio'] }}">
                </div>
                
                <div class="col-md-4">
                    <label class="small fw-bold text-muted mb-1"><i class="fas fa-calendar-check me-1"></i> FECHA FIN</label>
                    <input type="date" 
                        name="fecha_fin" 
                        class="form-control form-control-sm border-0 bg-light" 
                        value="{{ $reporte['periodo']['fin'] }}">
                </div>

                <div class="col-md-4">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                        <button type="submit" class="btn btn-chutos btn-sm px-4 shadow-sm">
                            <i class="fas fa-filter me-1"></i> Consultar
                        </button>
                        <a href="{{ route('ordenes.reporte_gerencial') }}" class="btn btn-light btn-sm px-3 text-muted">
                            <i class="fas fa-undo me-1"></i> Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div id="reporte-container" class="report-master-card shadow-lg bg-white mx-auto p-0 printableArea" style="max-width: 1000px; border-radius: 15px; overflow: hidden;">
        <div class="container-fluid px-4 py-3" id="printableArea">
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
            <h5 class="fw-bold text-camionetas"><i class="fas fa-tools me-2"></i> REPORTE GERENCIAL DE MANTENIMIENTO</h5>
            <span class="badge bg-light text-dark border">
                Período: {{ \Carbon\Carbon::parse($reporte['periodo']['inicio'])->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($reporte['periodo']['fin'])->format('d/m/Y') }}
            </span>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm border-start border-4 border-danger">
                    <div class="card-body p-3">
                        <small class="text-muted fw-bold d-block text-uppercase">Total Abiertas</small>
                        <h3 class="mb-0 fw-bold text-danger">{{ $reporte['kpis']['abiertas_hoy'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm border-start border-4 border-warning">
                    <div class="card-body p-3">
                        <small class="text-muted fw-bold d-block text-uppercase">Activas / En Taller</small>
                        <h3 class="mb-0 fw-bold text-warning">{{ $reporte['kpis']['activas_totales'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm border-start border-4 border-success">
                    <div class="card-body p-3">
                        <small class="text-muted fw-bold d-block text-uppercase">Total Cerradas</small>
                        <h3 class="mb-0 fw-bold text-success">{{ $reporte['kpis']['cerradas_mes'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm border-start border-4 border-info bg-info bg-opacity-10">
                    <div class="card-body p-3">
                        <small class="text-dark fw-bold d-block text-uppercase">Falla Más Recurrente</small>
                        <h6 class="mb-0 fw-bold mt-1 text-truncate" title="{{ $reporte['operativo']['falla_top'] }}">
                            <i class="fas fa-exclamation-triangle text-danger me-1"></i> {{ $reporte['operativo']['falla_top'] }}
                        </h6>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-camionetas text-white py-2">
                <h6 class="mb-0 small fw-bold">RESUMEN DEL PERÍODO</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3 border-end">
                        <span class="text-muted small d-block">Suministros / Repuestos</span>
                        <h5 class="fw-bold text-dark mt-1">${{ number_format($reporte['financiero']['suministros'], 2) }}</h5>
                    </div>
                    <div class="col-md-3 border-end">
                        <span class="text-muted small d-block">Mano de Obra Externa</span>
                        <h5 class="fw-bold text-dark mt-1">${{ number_format($reporte['financiero']['externos'], 2) }}</h5>
                    </div>
                    <div class="col-md-3 border-end">
                        <span class="text-muted small d-block">Trabajos Realizados</span>
                        <h5 class="fw-bold text-orange mt-1">{{ $reporte['operativo']['internos_qty'] }} Internos</h5>
                        <h5 class="fw-bold text-primary mt-1">{{ $reporte['operativo']['externos_qty'] }} Externos</h5>
                    </div>
                    <div class="col-md-3">
                        <span class="text-muted small d-block fw-bold">COSTO TOTAL MANTENIMIENTO</span>
                        <h4 class="fw-bold text-danger mt-1">${{ number_format($reporte['financiero']['total'], 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold text-dark mb-0">
                    <i class="fas fa-chart-line me-2 text-primary"></i> FLUJO DIARIO: ÓRDENES ABIERTAS VS. CERRADAS
                </h6>
            </div>
            <div class="card-body">
                <div id="container-timeline-mantenimiento" style="width:100%; height:350px;"></div>
            </div>
        </div>
        <div class="row g-3 mb-4">
            
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-2 border-bottom">
                        <h6 class="mb-0 small fw-bold text-dark"><i class="fas fa-chart-bar me-1"></i> DISTRIBUCIÓN POR CATEGORÍA (Top 5)</h6>
                    </div>
                    <div class="card-body p-3">
                        @php $maxCat = $reporte['operativo']['por_categoria']->first() ?: 1; @endphp
                        @foreach($reporte['operativo']['por_categoria'] as $categoria => $cantidad)
                            @php $porcentaje = ($cantidad / $maxCat) * 100; @endphp
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span>{{ $categoria ?: 'Sin Categoría' }}</span>
                                    <span class="fw-bold">{{ $cantidad }} OTs</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-chutos" role="progressbar" style="width: {{ $porcentaje }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="row g-3 h-100">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-3">
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="fas fa-tools me-2 text-primary"></i> DISTRIBUCIÓN POR TIPO DE ORDEN
                                </h6>
                            </div>
                            <div class="card-body">
                                {{-- <div class="d-flex justify-content-center flex-wrap gap-4 mb-4 border-bottom pb-4">
                                    @foreach($reporte['operativo']['por_tipo'] as $tipo => $cantidad)
                                        @php
                                            // Mapeo flexible: Si es mantenimiento o preventivo va en verde, etc.
                                            $colorClass = match(strtoupper($tipo)) {
                                                'PREVENTIVO', 'MANTENIMIENTO' => 'text-success',
                                                'CORRECTIVO', 'FALLA'        => 'text-danger',
                                                'IMPREVISTO', 'OTRO'        => 'text-warning',
                                                default                     => 'text-secondary'
                                            };
                                            $borderClass = str_replace('text', 'border', $colorClass);
                                        @endphp
                                        <div class="text-center px-4 py-2 border-start border-4 {{ $borderClass }} bg-light bg-opacity-50 rounded-end">
                                            <h3 class="{{ $colorClass }} fw-bold mb-0">{{ $cantidad }}</h3>
                                            <small class="text-muted fw-bold text-uppercase" style="font-size: 0.7rem;">{{ $tipo }}</small>
                                        </div>
                                    @endforeach
                                </div> --}}

                                <div id="container-highcharts-tipo" style="width:100%; height:500px;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="card border-0 shadow-sm border-danger border-2">
                            <div class="card-body p-3 d-flex align-items-center">
                                <div class="rounded-circle bg-danger bg-opacity-10 p-3 me-3">
                                    <i class="fas fa-truck text-danger fa-2x"></i>
                                </div>
                                <div>
                                    <small class="text-danger fw-bold d-block text-uppercase mb-1"><i class="fas fa-exclamation-circle"></i> UNIDAD CON MÁS FALLAS</small>
                                    @if($reporte['operativo']['unidad_top'])
                                        <h5 class="mb-0 fw-bold text-dark">Unidad {{ $reporte['operativo']['unidad_top']['vehiculo'] }} <span class="small text-muted">({{ $reporte['operativo']['unidad_top']['placa'] }})</span></h5>
                                        <span class="badge bg-danger mt-1">{{ $reporte['operativo']['unidad_top']['cantidad'] }} Órdenes Abiertas</span>
                                    @else
                                        <span class="text-muted">No hay unidades en taller actualmente.</span>
                                    @endif
                                </div>
                            </div>
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

    const timelineData = @json($reporte['timeline']);

    Highcharts.chart('container-timeline-mantenimiento', {
        chart: {
            type: 'areaspline', // Curvas suaves con relleno
            backgroundColor: 'transparent'
        },
        title: { text: null },
        xAxis: {
            categories: timelineData.labels,
            gridLineWidth: 1,
            gridLineDashStyle: 'Dot'
        },
        yAxis: {
            title: { text: 'Cantidad de Órdenes' },
            min: 0,
            allowDecimals: false
        },
        tooltip: {
            shared: true,
            crosshairs: true
        },
        plotOptions: {
            areaspline: {
                fillOpacity: 0.1,
                marker: {
                    radius: 4,
                    symbol: 'circle'
                },
                lineWidth: 3
            }
        },
        series: [{
            name: 'Órdenes Abiertas',
            data: timelineData.abiertas,
            color: '#ff6600', // Tu color de Chutos/Naranja
        }, {
            name: 'Órdenes Cerradas',
            data: timelineData.cerradas,
            color: '#198754', // Verde Cisternas/Éxito
        }],
        credits: { enabled: false },
        responsive: {
            rules: [{
                condition: { maxWidth: 500 },
                chartOptions: {
                    legend: { layout: 'horizontal', align: 'center', verticalAlign: 'bottom' }
                }
            }]
        }
    });
    
    // 1. Obtenemos la data real que mostraste en el dd()
    const dataFromLaravel = @json($reporte['operativo']['por_tipo']);
    
    // 2. Definimos la paleta de colores lógica
    const palette = {
        'MANTENIMIENTO': '#198754', // Verde
        'PREVENTIVO':    '#198754', // Verde
        'CORRECTIVO':    '#dc3545', // Rojo
        'OTRO':          '#ffc107', // Amarillo
        'IMPREVISTO':    '#fd7e14', // Naranja
    };

    // 3. Transformamos la data para Highcharts
    const seriesData = Object.keys(dataFromLaravel).map(name => {
        return {
            name: name,
            y: dataFromLaravel[name],
            color: palette[name.toUpperCase()] || '#6c757d' // Color por defecto si no está en la paleta
        };
    });

    // 4. Renderizado
    Highcharts.chart('container-highcharts-tipo', {
        chart: {
            type: 'column',
            backgroundColor: 'transparent'
        },
        title: { text: null },
        xAxis: {
            type: 'category',
            labels: { style: { fontWeight: 'bold' } }
        },
        yAxis: {
            title: { text: 'Cantidad de Órdenes' },
            gridLineDashStyle: 'Dash'
        },
        legend: { enabled: false },
        plotOptions: {
            column: {
                borderRadius: 7,
                dataLabels: { enabled: true, format: '{point.y}' }
            }
        },
        tooltip: {
            pointFormat: 'Total: <b>{point.y}</b> órdenes'
        },
        series: [{
            name: 'Órdenes',
            colorByPoint: true,
            data: seriesData
        }],
        credits: { enabled: false },
        exporting: { enabled: true }
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
            formData.append('chart_image', imageBlob, 'reporte_mantenimiento.png');
            
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
