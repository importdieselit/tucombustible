<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Token CSRF indispensable para peticiones AJAX en Laravel -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Reporte de Guardias - IMPORDIESEL</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { font-size: 13px; background-color: #f8fafc; color: #333; }
        .text-corporate { color: #0f2d59; }
        .bg-corporate { background-color: #0f2d59 !important; color: white; }
        .table-custom { border: 2px solid #0f2d59; }
        .table-custom th { background-color: #0f2d59; color: white; border: 1px solid #dee2e6; text-align: center; }
        .table-custom td { border: 1px solid #dee2e6; vertical-align: top; height: 120px; background-color: #fff; }
        .rol-badge { font-size: 10px; font-weight: bold; text-transform: uppercase; padding: 2px 4px; border-radius: 4px; display: inline-block; }
        .badge-cho { background-color: #dbeafe; color: #1e40af; }
        .badge-ayu { background-color: #fef3c7; color: #92400e; }
        .badge-mec { background-color: #f3e8ff; color: #6b21a8; }
        
        .printableArea { background-color: #ffffff !important; padding: 20px; border-radius: 8px; }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; background-color: #fff; }
            .printableArea { padding: 0; border-radius: 0; }
        }
    </style>
</head>
<body class="p-4">

    <!-- Barra de Acciones Superior -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print border-bottom pb-3">
        <span class="text-muted fw-bold">Vista previa del Reporte de Guardias Semanales</span>
        <div class="d-flex gap-2">
            <button id="captureButton" class="btn btn-dark shadow-sm">
                <i class="fa-regular fa-copy me-2"></i> Capturar Imagen
            </button>
            <button id="sendWhatsappButton" class="btn btn-info text-white shadow-sm">
                <i class="fa-brands fa-whatsapp me-2"></i> Enviar a WhatsApp
            </button>
            <button onclick="window.print()" class="btn btn-primary bg-corporate shadow-sm">
                <i class="fa fa-print me-2"></i> Imprimir / Guardar PDF
            </button>
        </div>
    </div>

    <!-- Mensaje dinámico de estado -->
    <div id="statusMessage" class="alert no-print mb-4 d-none" role="alert"></div>

    <!-- Área imprimible y capturable -->
    <div class="printableArea border shadow-sm">
        <!-- Encabezado del Reporte -->
        <div class="row align-items-center border-bottom pb-3 mb-4">
            <div class="col-8">
                <h2 class="text-corporate fw-bold mb-1">IMPORDIESEL, C.A.</h2>
                <h5 class="text-muted mb-0">Cronograma de Operación de Guardias</h5>
            </div>
            <div class="col-4 text-end">
                <h6 class="fw-bold mb-1">Semana Programada</h6>
                <span class="badge bg-corporate fs-6">
                    {{ $startOfWeek->format('d/m/Y') }} al {{ $startOfWeek->copy()->addDays(6)->format('d/m/Y') }}
                </span>
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
                                    <div class="fw-bold mt-1" style="font-size: 11px;">
                                        {{ $g->personal->persona->nombre ?? 'Sin nombre' }}
                                    </div>
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

    <!-- Librería html2canvas -->
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const printableArea = document.querySelector(".printableArea"); 
        const captureButton = document.getElementById('captureButton');
        const sendWhatsappButton = document.getElementById('sendWhatsappButton');
        const statusMessage = document.getElementById('statusMessage');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        if (!printableArea || !captureButton || !statusMessage || !sendWhatsappButton) {
            console.error("Faltan elementos necesarios en el DOM.");
            return;
        }

        function setStatus(message, bgClass, textClass) {
            statusMessage.className = `alert no-print mb-4 d-block ${bgClass} ${textClass}`;
            statusMessage.textContent = message;
        }

        function hideStatus() {
            statusMessage.className = 'd-none';
        }

        /**
         * 1. Capturar al Portapapeles
         */
        async function captureAndCopyToClipboard() {
            setStatus('Generando imagen de alta resolución...', 'alert-warning', 'text-dark');
            captureButton.disabled = true;

            try {
                const canvas = await html2canvas(printableArea, {
                    scale: 2,
                    logging: false,
                    useCORS: true,
                    backgroundColor: '#ffffff'
                });

                const imageBlob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
                if (!imageBlob) throw new Error('Error al procesar los datos de la imagen.');

                const item = new ClipboardItem({ "image/png": imageBlob });
                await navigator.clipboard.write([item]);

                setStatus('¡Éxito! El reporte se copió al portapapeles. Ya puedes pegarlo (Ctrl + V).', 'alert-success', 'text-white');
                setTimeout(hideStatus, 5000);

            } catch (error) {
                console.error(error);
                setStatus(`Fallo al generar imagen: ${error.message}`, 'alert-danger', 'text-white');
            } finally {
                captureButton.disabled = false;
            }
        }

        /**
         * 2. Enviar a WhatsApp mediante la API
         */
        async function sendReportToWhatsapp() {
            setStatus('Generando imagen y enviando a WhatsApp...', 'alert-warning', 'text-dark');
            sendWhatsappButton.disabled = true;

            try {
                const canvas = await html2canvas(printableArea, { 
                    scale: 2, 
                    useCORS: true,
                    backgroundColor: '#ffffff' 
                });
                
                const imageBlob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
                
                const formData = new FormData();
                formData.append('_token', csrfToken); // Garantiza que no falle CSRF
                formData.append('image', imageBlob, 'reporte_guardias.png');
                formData.append('caption', '📊 *Cronograma de Guardias Semanales* ({{ $startOfWeek->format("d/m/Y") }} - {{ $startOfWeek->copy()->addDays(6)->format("d/m/Y") }})');

                const response = await fetch('{{ route("guardia.send") }}', {
                    method: 'POST',
                    headers: { 
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.error || data.message || 'Error al procesar el envío en el servidor.');
                }

                setStatus('¡Reporte enviado exitosamente a WhatsApp!', 'alert-success', 'text-white');
                setTimeout(hideStatus, 5000);

            } catch (error) {
                console.error(error);
                setStatus('Error: ' + error.message, 'alert-danger', 'text-white');
            } finally {
                sendWhatsappButton.disabled = false;
            }
        }

        // Asignar Eventos
        captureButton.addEventListener('click', captureAndCopyToClipboard);
        sendWhatsappButton.addEventListener('click', sendReportToWhatsapp);
    });
    </script>

</body>
</html>