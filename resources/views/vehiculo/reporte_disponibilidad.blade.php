@extends('layouts.app')

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
        <button onclick="window.print()" class="btn btn-primary shadow-sm px-4">
            <i class="bi bi-printer-fill me-2"></i>Exportar Reporte
        </button>
    </div>
    <div id="statusMessage" class="text-center p-3 rounded-lg bg-yellow-100 text-yellow-800 hidden mb-4">
            Procesando...
    </div>

    <div class="report-master-card shadow-lg bg-white mx-auto p-0 printableArea" style="max-width: 1000px; border-radius: 15px; overflow: hidden;">
        
        <div class="bg-dark p-4 text-white d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-0 fw-bold">TUCOMBUSTIBLE</h3>
                <span class="badge bg-primary">REPORTE DIARIO DE FLOTA</span>
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

        <div class="p-4">
            <div class="mb-5">
                <h5 class="fw-bold mb-3 border-start border-4 border-primary ps-2">A.- CAMIONES OPERATIVOS</h5>
                <div class="row row-cols-1 row-cols-md-4 row-cols-lg-3 g-4">
                    @foreach($chutosOperativos as $c)
                    <div class="col-3">
                        <div class="p-2 border rounded bg-light d-flex align-items-center">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="fa fa-truck"></i>
                            </div>
                            <div>
                                <div class="fw-bold">{{ $c->flota }} [{{ $c->placa }}]</div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                    @foreach($camionesOperativos as $c)
                        <div class="col">
                            <div class="p-2 border rounded bg-light d-flex align-items-center">
                                <div class="bg-primary text-white rounded p-2 me-3">
                                    <i class="fa fa-truck"></i>
                                </div>
                                <div>
                                    <div class="fw-bold">{{ $c->flota }} [{{ $c->placa }}]</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="mb-5">
                <h5 class="fw-bold mb-3 border-start border-4 border-primary ps-2">B.- TANQUES OPERATIVOS</h5>
                <div class="row row-cols-1 row-cols-md-3 g-3">
                    @foreach($cisternasOperativas as $c)
                    <div class="col">
                        <div class="p-2 border rounded bg-light d-flex align-items-center">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="fa fa-truck"></i>
                            </div>
                            <div>
                                <div class="fw-bold">{{ $c->flota }} [{{ $c->placa }}]</div>
                            </div>
                        </div>
                    </div>
                    @endforeach
            
                </div>
            </div>
            <div class="mb-5">
                <h5 class="fw-bold mb-3 border-start border-4 border-primary ps-2">C.- FLOTA LIVIANA OPERATIVA</h5>
                <div class="row row-cols-1 row-cols-md-3 g-3">
                    @foreach($camionetasOperativas as $c)
                        <div class="col">
                            <div class="p-2 border rounded bg-light d-flex align-items-center">
                                <div class="fw-bold text-white rounded p-2 me-3">
                                    <i class="bg-primary fa fa-truck"></i> {{ $c->flota }} [{{ $c->placa }}]
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="row mb-5">
                <div class="col-12">
                    <h5 class="fw-bold mb-3 border-start border-4 border-danger ps-2">D.- UNIDADES FUERA DE SERVICIO (FALLAS)</h5>
                </div>
                <div class="col-md-12 mb-3">
                    <div class="card border-danger h-100">
                        <div class="card-header bg-danger text-white py-1">B.- Chutos en Falla</div>
                        <div class="card-body p-2">
                           @forelse($chutosFalla as $f)
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div>
                                    <strong class="d-block">{{ $f->flota }} [{{ $c->placa }}]</strong>
                                    <span class="small text-muted">{{ $f->observacion ?? 'Sin diagnóstico' }}</span>
                                </div>
                                <div class="text-end">
                                    @if($f->ordenActiva)
                                        <span class="badge {{ $f->dias_fuera_servicio > 7 ? 'bg-danger' : 'bg-warning text-dark' }}" style="font-size: 0.7rem;">
                                            <i class="bi bi-clock-history"></i> {{ $f->dias_fuera_servicio }} DÍAS
                                        </span>
                                        <div style="font-size: 0.65rem;" class="text-muted">Desde: {{ $f->ordenActiva->created_at->format('d/m/Y') }}</div>
                                    @else
                                        <span class="badge bg-secondary" style="font-size: 0.7rem;">SIN ORDEN</span>
                                    @endif
                                </div>
                            </div>
                            @empty 
                            <div class="text-muted small">Sin novedades.</div> 
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-md-12 mb-3">
                    <div class="card border-danger h-100">
                        <div class="card-header bg-danger text-white py-1">E.- Cisternas en Falla</div>
                        <div class="card-body p-2">
                            @forelse($cisternasFalla as $f)
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div>
                                    <strong class="d-block">{{ $f->flota }} [{{ $c->placa }}]</strong>
                                    <span class="small text-muted">{{ $f->observacion ?? 'Sin diagnóstico' }}</span>
                                </div>
                                <div class="text-end">
                                    @if($f->ordenActiva)
                                        <span class="badge {{ $f->dias_fuera_servicio > 7 ? 'bg-danger' : 'bg-warning text-dark' }}" style="font-size: 0.7rem;">
                                            <i class="bi bi-clock-history"></i> {{ $f->dias_fuera_servicio }} DÍAS
                                        </span>
                                        <div style="font-size: 0.65rem;" class="text-muted">Desde: {{ $f->ordenActiva->created_at->format('d/m/Y') }}</div>
                                    @else
                                        <span class="badge bg-secondary" style="font-size: 0.7rem;">SIN ORDEN</span>
                                    @endif
                                </div>
                            </div>
                            @empty 
                            <div class="text-muted small">Sin novedades.</div> 
                             @endforelse
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="bg-light p-3 text-center border-top">
            <p class="mb-0 small fw-bold text-muted text-uppercase">Este documento es una declaración oficial de disponibilidad de flota.</p>
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

    // 8. Asignar el evento al botón
    captureButton.addEventListener('click', captureAndCopyToClipboard);

    // 7. Asignar evento al nuevo botón
    if (sendTelegramButton) {
        sendTelegramButton.addEventListener('click', sendReportToTelegram);
    }
});
</script>
@endpush
@endsection