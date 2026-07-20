@extends('layouts.app-nosidebar')

@push('styles')
    <style>
      <style>
        body { font-size: 13px; background-color: #f8fafc; color: #333; }
        .text-corporate { color: #0f2d59; }
        .bg-corporate { background-color: #0f2d59 !important; color: white; }
        .text-bold-custom { font-weight: 700; color: #0f2d59; }
        
        /* Badges de Roles */
        .rol-badge { font-size: 10px; font-weight: bold; text-transform: uppercase; padding: 2px 5px; border-radius: 4px; display: inline-block; letter-spacing: 0.5px; }
        .badge-cho { background-color: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .badge-ayu { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-mec { background-color: #f3e8ff; color: #6b21a8; border: 1px solid #e9d5ff; }

        /* Contenedor de impresión */
        .printableArea { background-color: #ffffff !important; padding: 25px; border-radius: 8px; }

        /* Estilos CSS Grid para el Calendario */
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 10px;
        }
        .calendar-header-day {
            text-align: center; font-weight: 700; color: #0f2d59; padding: 12px 0;
            background: #f1f5f9; border-radius: 6px; text-transform: uppercase; font-size: 0.85rem;
            border: 1px solid #e2e8f0;
        }
        .calendar-cell {
            background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px;
            min-height: 150px; display: flex; flex-direction: column; 
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        }
        .calendar-cell.today { border: 2px solid #f59e0b; background: #fffbeb; }
        
        .cell-date-header {
            padding: 8px 12px; border-bottom: 1px solid #f1f5f9; background: #fafafa;
            font-weight: 600; color: #475569; display: flex; justify-content: space-between; align-items: center;
            border-top-left-radius: 6px; border-top-right-radius: 6px;
        }
        .drop-container { flex-grow: 1; padding: 10px; display: flex; flex-direction: column; gap: 8px; }

        /* Items asignados en el calendario */
        .assigned-item {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 8px; border-radius: 6px;
            display: flex; flex-direction: column; gap: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; background-color: #fff; }
            .printableArea { padding: 0; border-radius: 0; border: none !important; box-shadow: none !important; }
            .calendar-cell { page-break-inside: avoid; }
        }
    </style>
@endpush

@section('content')

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
                 <h2 class="text-bold-custom mb-0"><img src="{{ asset('img/logo1.png') }}" alt="logo empresa" style="width: 250px"></h2>
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
        <div class="calendar-container">
            <!-- Cabeceras de días -->
            <div class="calendar-grid mb-2">
                @foreach($semanaDias as $dia)
                    <div class="calendar-header-day">
                        {{ $dia->isoFormat('dddd') }}
                    </div>
                @endforeach
            </div>

            <!-- Celdas de días -->
            <div class="calendar-grid">
                @foreach($semanaDias as $dia)
                    @php 
                        $fechaString = $dia->toDateString();
                        $guardiasDelDia = $guardias->get($fechaString) ?? collect();
                        $claseDia = $dia->isToday() ? 'today' : '';
                    @endphp

                    <div class="calendar-cell {{ $claseDia }}">
                        <!-- Fecha de la celda -->
                        <div class="cell-date-header">
                            <span class="fs-6 {{ $dia->dayOfWeek == 0 ? 'text-danger' : 'text-corporate' }}">{{ $dia->format('d') }}</span>
                            <span class="small text-muted text-uppercase">{{ $dia->translatedFormat('M') }}</span>
                        </div>
                        
                        <!-- Contenido / Personal Asignado -->
                        <div class="drop-container">
                            @forelse($guardiasDelDia as $g)
                                @php 
                                    $badgeClass = strtolower(substr($g->rol_guardia, 0, 3)); 
                                @endphp
                                <div class="assigned-item">
                                    <span class="rol-badge badge-{{ $badgeClass }} align-self-start">{{ $g->rol_guardia }}</span>
                                    <div class="fw-bold text-dark mt-1" style="font-size: 0.8rem; line-height: 1.2;">
                                        {{ $g->personal->persona->nombre ?? 'Sin nombre' }}
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted small mt-4 fw-medium" style="opacity: 0.6;">Sin guardias</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

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
@push('scripts')
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
@endpush
@endsection