<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Guardias - IMPORDIESEL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-size: 13px; background-color: #fff; color: #333; }
        .text-corporate { color: #0f2d59; }
        .bg-corporate { background-color: #0f2d59 !important; color: white; }
        .table-custom { border: 2px solid #0f2d59; }
        .table-custom th { background-color: #0f2d59; color: white; border: 1px solid #dee2e6; text-align: center; }
        .table-custom td { border: 1px solid #dee2e6; vertical-align: top; height: 120px; }
        .rol-badge { font-size: 10px; font-weight: bold; text-transform: uppercase; padding: 2px 4px; border-radius: 4px; display: inline-block; }
        .badge-cho { background-color: #dbeafe; color: #1e40af; }
        .badge-ayu { background-color: #fef3c7; color: #92400e; }
        .badge-mec { background-color: #f3e8ff; color: #6b21a8; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body class="p-4">

    <div class="d-flex justify-content-between align-items-center mb-4 no-print border-bottom pb-3">
        <span class="text-muted">Vista previa del Reporte de Guardias Semanales</span>
        <button onclick="window.print()" class="btn btn-primary bg-corporate"><i class="fa fa-print"></i> Confirmar Impresión / Guardar PDF</button>
        <button id="captureButton" class="btn btn-dark shadow-sm">
            <i class="fa-regular fa-copy me-2"></i> Capturar Imagen
        </button>
        <button id="sendWhatsappButton" class="btn btn-info shadow-sm">
            <i class="fa fa-paper-plane me-2"></i> Enviar a WhatsApp
        </button>
    </div>
    <div id="statusMessage" class="alert no-print mb-4 d-none" role="alert"></div>
    <div class="printableArea">
        <!-- Encabezado del Reporte -->
        <div class="row align-items-center border-bottom pb-3 mb-4">
            <div class="col-8">
                <h2 class="text-corporate fw-bold mb-1">IMPORDIESEL, C.A.</h2>
                <h5 class="text-muted mb-0">Cronograma de Operación de Guardias</h5>
            </div>
            <div class="col-4 text-end">
                <h6 class="fw-bold mb-1">Semana Programada</h6>
                <span class="badge bg-corporate fs-6">{{ $startOfWeek->format('d/m/Y') }} al {{ $startOfWeek->copy()->addDays(6)->format('d/m/Y') }}</span>
            </div>
        </div>

        <!-- Tabla Semanal de Guardias -->
        <table class="table table-bordered table-custom">
            <thead>
                <tr>
                    @foreach($semanaDias as $dia)
                        <th style="width: 14.28%;">
                            {{ $dia->isoFormat('dddd') }}<br>
                            <span class="fs-5">{{ $dia->format('d/m') }}</span>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    @foreach($semanaDias as $dia)
                        @php 
                            $fechaString = $dia->toDateString();
                            $guardiasDelDia = $guardias->get($fechaString) ?? collect();
                        @endphp
                        <td>
                            @forelse($guardiasDelDia as $g)
                                <div class="mb-2 p-2 rounded border" style="background-color: #f8fafc;">
                                    @php 
                                        $badgeClass = strtolower(substr($g->rol_guardia, 0, 3)); 
                                    @endphp
                                    <span class="rol-badge badge-{{ $badgeClass }}">{{ $g->rol_guardia }}</span>
                                    <div class="fw-bold mt-1" style="font-size: 11px;">{{ $g->personal->persona->nombre }}</div>
                                </div>
                            @empty
                                <div class="text-center text-muted small mt-4">Sin guardias</div>
                            @endforelse
                        </td>
                    @endforeach
                </tr>
            </tbody>
        </table>

        <!-- Firmas / Control Interno -->
        <div class="row mt-5 pt-4 text-center">
            <div class="col-4 offset-1 border-top pt-2">
                <strong>Elaborado por:</strong><br>
                <span class="text-muted">Operaciones y Logística</span>
            </div>
            <div class="col-4 offset-2 border-top pt-2">
                <strong>Aprobado por:</strong><br>
                <span class="text-muted">Dirección General</span>
            </div>
        </div>
    </div>
    <div id="outputContainer" class="mt-4 pt-4 border-top"></div>
    <!-- Librería encargada de la renderización del HTML a Canvas -->
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Selectores Nativos (Anti-errores, elimina dependencia obligatoria de jQuery)
        const printableArea = document.querySelector(".printableArea"); 
        const captureButton = document.getElementById('captureButton');
        const statusMessage = document.getElementById('statusMessage');
        const sendWhatsappButton = document.getElementById('sendWhatsappButton');

        if (!printableArea || !captureButton || !statusMessage) {
            console.error("Faltan elementos críticos en el DOM para la ejecución de capturas.");
            return;
        }

        // Helper para el manejo visual de estados
        function showStatus(message, type = 'info') {
            statusMessage.className = `alert no-print mb-4 d-block alert-${type}`;
            statusMessage.textContent = message;
        }

        function hideStatus() {
            statusMessage.className = 'd-none';
        }

        /**
         * 1. Capturar área y copiar al portapapeles (Para pegar directo en WhatsApp con Ctrl+V)
         */
        async function captureAndCopyToClipboard() {
            showStatus('Generando imagen de alta resolución...', 'warning');
            captureButton.disabled = true;

            try {
                const canvas = await html2canvas(printableArea, {
                    scale: 2, // Mantiene nitidez en pantallas de alta densidad
                    logging: false,
                    useCORS: true,
                    backgroundColor: '#ffffff' // Garantiza que no existan transparencias
                });

                const imageBlob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
                
                if (!imageBlob) throw new Error('Error al procesar los datos binarios de la imagen.');

                const item = new ClipboardItem({ "image/png": imageBlob });
                await navigator.clipboard.write([item]);

                showStatus('¡Éxito! El cronograma se copió al portapapeles. Ya puedes pegarlo en WhatsApp (Ctrl + V).', 'success');
                setTimeout(hideStatus, 6000);

            } catch (error) {
                console.error(error);
                if (error.name === 'NotAllowedError') {
                    showStatus('Error de permisos: El navegador bloqueó el acceso al portapapeles. Asegúrate de estar usando HTTPS.', 'danger');
                } else {
                    showStatus(`Fallo al generar imagen: ${error.message}`, 'danger');
                }
            } finally {
                captureButton.disabled = false;
            }
        }

         async function sendReportToWhatsapp() {
            setStatus('Generando imagen para enviar a WhatsApp...', 'bg-warning', 'text-dark');
            sendWhatsappButton.disabled = true;

            try {
                const canvas = await html2canvas(printableArea, { scale: 2, useCORS: true });
                const imageBlob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
                
                const formData = new FormData();
                formData.append('image', imageBlob, 'reporte_guardias.png');
                // Añadimos el turno explícito en el pie del mensaje de WhatsApp para mayor claridad del grupo directivo
                formData.append('caption', '📊 *Reporte Guardias - ' + '{{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}');
                
                const response = await fetch('{{ route('guardia.send') }}', {
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

        // Asignación de Escuchas de Eventos
        captureButton.addEventListener('click', captureAndCopyToClipboard);
        sendWhatsappButton?.addEventListener('click', sendReportToWhatsapp);
    });
    </script>

</body>
</html>