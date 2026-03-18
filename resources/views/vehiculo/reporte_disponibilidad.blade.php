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
        <button onclick="window.print()" class="btn btn-primary shadow-sm px-4">
            <i class="bi bi-printer-fill me-2"></i>Exportar Reporte
        </button>
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
                <h5 class="fw-bold mb-3 border-start border-4 border-primary ps-2">A.- CHUTOS Y CISTERNAS OPERATIVAS</h5>
                <div class="row row-cols-1 row-cols-md-3 g-3">
                    @foreach($chutosOperativos as $c)
                    <div class="col">
                        <div class="p-2 border rounded bg-light d-flex align-items-center">
                            <div class="bg-primary text-white rounded p-2 me-3">
                                <i class="bi bi-truck"></i>
                            </div>
                            <div>
                                <div class="fw-bold">{{ $c->flota }}</div>
                                @if($c->tipo==3)
                                @if($c->acoplado_id)
                                <div class="small text-primary fw-bold">Acoplado: {{ $c->cisternaAcoplada->flota }}</div>
                                @else
                                <div class="small text-muted italic">Sin cisterna</div>
                                @endif
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-12">
                    <h5 class="fw-bold mb-3 border-start border-4 border-danger ps-2">UNIDADES FUERA DE SERVICIO (FALLAS)</h5>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card border-danger h-100">
                        <div class="card-header bg-danger text-white py-1">B.- Chutos en Falla</div>
                        <div class="card-body p-2">
                           @forelse($chutosFalla as $f)
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div>
                                    <strong class="d-block">{{ $f->flota }}</strong>
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
                <div class="col-md-6 mb-3">
                    <div class="card border-danger h-100">
                        <div class="card-header bg-danger text-white py-1">C.- Cisternas en Falla</div>
                        <div class="card-body p-2">
                            @forelse($cisternasFalla as $f)
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div>
                                    <strong class="d-block">{{ $f->flota }}</strong>
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

            <div class="row">
                <div class="col-md-6">
                    <h6 class="fw-bold text-muted mb-3">E.- CAMIONETAS OPERATIVAS</h6>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($camionetasOperativas as $c)
                        <span class="badge bg-outline-dark border text-dark p-2">{{ $c->flota }} - {{ $c->modelo }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-bold text-muted mb-3">G.- CAMIONES OPERATIVOS</h6>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($camionesOperativos as $c)
                        <span class="badge bg-secondary p-2">{{ $c->flota }} ({{ $c->placa }})</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-light p-3 text-center border-top">
            <p class="mb-0 small fw-bold text-muted text-uppercase">Este documento es una declaración oficial de disponibilidad de flota.</p>
        </div>
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
    const printableArea = document.querySelector('.printableArea');
    const sendTelegramButton = document.querySelector('#sendTelegramButton');
    const elementToCaptureSelector = '.printableArea';

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

    // 7. Asignar evento al nuevo botón
    if (sendTelegramButton) {
        sendTelegramButton.addEventListener('click', sendReportToTelegram);
    }
});
</script>
@endpush
@endsection