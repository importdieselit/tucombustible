@extends('layouts.app')

@push('styles')
<style>
    .kpi-card { background: #ffffff; border-left: 4px solid #0f2d59; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .table-header-custom { background-color: #0f2d59 !important; color: white; }
    .text-bold-custom { font-weight: 700; color: #0f2d59; }
    .text-bold-title { font-weight: 800; color: #0f2d59; font-size: 1.5rem; }
    .chart-container { background: white; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0; }
    /* Ajuste de fondo para que combine con el contenedor principal del layout */
    .bg-dashboard { background-color: #f1f5f9; } 
</style>
@endpush

@section('content')
<div class="container-fluid py-4 max-width-1200 bg-dashboard">
    
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded shadow-sm">
        
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
    <div id="statusMessage" class="text-center p-3 rounded-lg bg-yellow-100 text-yellow-800 hidden mb-4">
            Procesando...
    </div>
        <form action="{{ route('reporte.admon') }}" method="GET" class="d-flex align-items-center gap-2">
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
    <div id="reporteFinanzas" class="bg-white p-4 rounded shadow-sm printableArea">
        <div class="row col-12 g-12 justify-content-between align-items-center  bg-white p-3 rounded shadow-sm">
            <div class="col-3">
                <h2 class="text-bold-custom mb-0">IMPORDIESEL, C.A.</h2>
                <small class="text-muted">Control de Operaciones y Flujos Financieros</small>
            </div>
            <div class="col-7 justify-content-between align-items-center mb-4">
              <h1 class="text-bold-title text-navy">Dashboard Administracion</h1>
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
    <div id="outputContainer" class="mt-8 pt-4 border-t border-gray-300 width-full">
        </div>
</div>
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
                scale: 3, 
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
@endsection