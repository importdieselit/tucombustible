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

    <div class="report-master-card shadow-lg bg-white mx-auto p-0 printableArea" style="max-width: 1000px; border-radius: 15px; overflow: hidden;">
 
<div class="container-fluid py-4">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-corporate-blue d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i> RESUMEN DE OPERACIONES</h5>
            <span class="badge bg-white text-dark">{{ \Carbon\Carbon::parse($reporte['fecha'])->format('d/m/Y') }}</span>
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
                        <td class="text-start ps-3 fw-bold"><i class="fas fa-shipping-fast text-primary me-2"></i> DESPACHOS</td>
                        <td class="fw-bold">{{ $reporte['despachos']['programados']['ind'] }}</td>
                        <td class="fw-bold">{{ $reporte['despachos']['programados']['mgo'] }}</td>
                        <td class="bg-warning bg-opacity-10 fw-bold">{{ $reporte['despachos']['en_ruta']['ind'] }}</td>
                        <td class="bg-warning bg-opacity-10 fw-bold">{{ $reporte['despachos']['en_ruta']['mgo'] }}</td>
                        <td class="bg-success bg-opacity-10 fw-bold text-success">{{ $reporte['despachos']['completados']['ind'] }}</td>
                        <td class="bg-success bg-opacity-10 fw-bold text-success">{{ $reporte['despachos']['completados']['mgo'] }}</td>
                    </tr>
                    <tr>
                        <td class="text-start ps-3 fw-bold"><i class="fas fa-filling-station text-danger me-2"></i> CARGAS PLANTA</td>
                        <td class="fw-bold">{{ $reporte['cargas']['programadas']['ind'] }}</td>
                        <td class="fw-bold">{{ $reporte['cargas']['programadas']['mgo'] }}</td>
                        <td class="bg-warning bg-opacity-10 fw-bold">{{ $reporte['cargas']['en_ruta']['ind'] }}</td>
                        <td class="bg-warning bg-opacity-10 fw-bold">{{ $reporte['cargas']['en_ruta']['mgo'] }}</td>
                        <td class="bg-success bg-opacity-10 fw-bold text-success">{{ $reporte['cargas']['completadas']['ind'] }}</td>
                        <td class="bg-success bg-opacity-10 fw-bold text-success">{{ $reporte['cargas']['completadas']['mgo'] }}</td>
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
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light x-small text-uppercase">
                        <tr>
                            <th class="ps-3">Estatus</th>
                            <th>Tipo</th>
                            <th>Unidad</th>
                            <th>Chofer</th>
                            <th>Producto</th>
                            <th>Destino</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($viajesDelDia as $v)
                        <tr>
                            <td class="ps-3">
                                @php
                                    $statusMap = [
                                        'Programado' => ['class' => 'bg-primary', 'icon' => 'fa-clock'],
                                        'EN RUTA'    => ['class' => 'bg-warning text-dark', 'icon' => 'fa-truck-moving'],
                                        'COMPLETADO' => ['class' => 'bg-success', 'icon' => 'fa-check-circle']
                                    ];
                                    $current = $statusMap[$v->status] ?? ['class' => 'bg-secondary', 'icon' => 'fa-info'];
                                @endphp
                                <span class="badge rounded-pill {{ $current['class'] }} p-2 px-3 shadow-sm" style="min-width: 110px;">
                                    <i class="fas {{ $current['icon'] }} me-1"></i> {{ $v->status }}
                                </span>
                            </td>
                            <td class="small fw-bold {{ $v->litros ? 'text-danger' : 'text-primary' }}">
                                {{ $v->litros ? 'CARGA' : 'DESPACHO' }}
                            </td>
                            <td>
                                <div class="fw-bold">{{ $v->vehiculo->flota ?? 'N/A' }} {{ $v->vehiculo->placa ?? 'N/A' }}</div>
                                <div class="text-muted small" style="font-size: 0.7rem;">{{ $v->cisternaAcoplada->flota ?? '' }} {{ $v->cisternaAcoplada->placa ?? '' }}</div>
                            </td>
                            <td class="small">{{ explode(' ', $v->chofer->nombre ?? 'Sin asignar')[0] }}</td>
                            <td><span class="badge border text-dark bg-white">{{ $v->producto->nombre ?? 'N/A' }}</span></td>
                            <td class="small text-truncate" style="max-width: 180px;">{{ $v->destino_ciudad }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
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
