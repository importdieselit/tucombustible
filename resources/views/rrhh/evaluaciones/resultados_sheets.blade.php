@extends('layouts.app')

@push('styles')
<style>
    /* Estándar Visual TuCombustible - Adaptación para Evaluaciones */
    .bg-evaluacion { background-color: #004b93 !important; color: white; } /* Azul corporativo para el header */
    .bg-score-high { background-color: #198754 !important; color: white; }
    .bg-score-mid { background-color: #ffc107 !important; color: #212529; }
    .bg-score-low { background-color: #dc3545 !important; color: white; }
    
    .badge-std {
        display: inline-flex;
        align-items: center;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .card-master {
        border-radius: 20px;
        box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        border: none;
        margin-bottom: 1.5rem;
    }

    .is-capturing {
        width: 1200px !important;
        background: white !important;
        padding: 40px !important;
    }

    .table-eval thead th {
        background-color: #f8f9fa;
        color: #334155;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 12px;
        border-top: none;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="font-weight-bold text-dark">Consolidado de Evaluaciones</h2>
            <p class="text-muted">Personal evaluado vía Google Forms - Sincronización en Tiempo Real</p>
        </div>
        <div class="col-md-4 text-right">
            <button id="btnCapture" class="btn btn-dark shadow-sm rounded-pill px-4">
                <i class="fas fa-camera mr-2"></i> Copiar Imagen
            </button>
            <button id="btnExport" class="btn btn-primary shadow-sm rounded-pill px-4">
                <i class="fas fa-file-download mr-2"></i> Descargar Reporte
            </button>
        </div>
    </div>

        <div id="statusMessage" class="hidden noPrint p-3 mb-3 rounded-lg font-weight-bold text-center"></div>
    <div id="printableArea">

        <div class="card card-master">
            <div class="card-header bg-evaluacion py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="m-0 font-weight-bold italic">REPORTE GERENCIAL DE DESEMPEÑO</h5>
                    <span class="badge badge-light px-3 py-2 rounded-pill">Total: {{ $reporteResultados->count() }} Evaluados</span>
                </div>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                   <table class="table table-eval table-hover m-0">
                        <thead>
                            <tr>
                                <th class="pl-4">Personal</th>
                                <th>Cargo</th>
                                <th class="text-center">Puntuación</th>
                                <th>Áreas de Mejora (Alertas)</th>
                                <th class="text-center">Fecha Evaluación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reporteResultados as $item)
                                <tr>
                                    <td class="pl-4 font-weight-bold text-dark align-middle" style="font-size: 15px;">
                                        {{ $item['nombre'] }}
                                    </td>
                                    
                                    <td class="align-middle">
                                        <span class="badge-std bg-light border text-muted">
                                            {{ $item['cargo'] }}
                                        </span>
                                    </td>
                                    
                                    <td class="text-center align-middle">
                                        @php
                                            $scoreParts = explode('/', $item['puntuacion']);
                                            $val = isset($scoreParts[0]) ? (int)$scoreParts[0] : 0;
                                            $class = $val >= 8 ? 'bg-score-high' : ($val >= 5 ? 'bg-score-mid' : 'bg-score-low');
                                        @endphp
                                        <span class="badge-std {{ $class }}">
                                            {{ $item['puntuacion'] }}
                                        </span>
                                    </td>

                                    <td class="align-middle">
                                        @if(count($item['negativas']) > 0)
                                            <div class="d-flex flex-column gap-2">
                                                @foreach($item['negativas'] as $falla)
                                                    <div style="font-size: 12px; line-height: 1.2; border-left: 2px solid #dc3545; padding-left: 6px;">
                                                        <strong class="text-dark">{{ $falla['indicador'] }}</strong><br>
                                                        <span class="text-danger font-weight-bold" style="font-size: 11px;">
                                                            <i class="fas fa-times-circle mr-1"></i>{{ $falla['respuesta'] }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="badge badge-success px-2 py-1" style="font-size: 11px;">
                                                <i class="fas fa-check mr-1"></i> Sin Novedades
                                            </span>
                                        @endif
                                    </td>
                                    
                                    <td class="text-center text-secondary align-middle">
                                        {{ $item['fecha_evaluacion'] }}
                                        <br>
                                        <small class="text-muted font-italic" style="font-size: 10px;">{{ $item['marca_temporal'] }}</small>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        No se encontraron datos en las hojas de Google Sheets.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white text-muted text-center py-3">
                <small>Este reporte extrae datos de {{ count($sheetsList ?? []) }} pestañas de cargos configuradas en el sistema.</small>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
<script>
    const captureButton = document.getElementById('btnCapture');
    const exportButton = document.getElementById('btnExport');
    const printableArea = document.getElementById('printableArea');
    const statusMessage = document.getElementById('statusMessage');

    async function captureAndAction(mode = 'download') {
        try {
            captureButton.disabled = true;
            exportButton.disabled = true;
            
            statusMessage.textContent = 'Procesando reporte...';
            statusMessage.className = 'p-3 mb-3 rounded-lg font-weight-bold text-center bg-warning text-dark';
            statusMessage.classList.remove('hidden');

            printableArea.classList.add('is-capturing');

            const canvas = await html2canvas(printableArea, {
                scale: 2,
                useCORS: true,
                backgroundColor: '#ffffff'
            });

            if (mode === 'clipboard') {
                canvas.toBlob(async (blob) => {
                    const data = [new ClipboardItem({ [blob.type]: blob })];
                    await navigator.clipboard.write(data);
                    statusMessage.textContent = '¡Imagen copiada al portapapeles!';
                    statusMessage.className = 'p-3 mb-3 rounded-lg font-weight-bold text-center bg-success text-white';
                });
            } else {
                const link = document.createElement('a');
                link.download = `reporte_evaluaciones_${new Date().getTime()}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
                statusMessage.textContent = '¡Reporte descargado!';
                statusMessage.className = 'p-3 mb-3 rounded-lg font-weight-bold text-center bg-success text-white';
            }

        } catch (error) {
            console.error(error);
            statusMessage.textContent = 'Error: ' + error.message;
            statusMessage.className = 'p-3 mb-3 rounded-lg font-weight-bold text-center bg-danger text-white';
        } finally {
            printableArea.classList.remove('is-capturing');
            captureButton.disabled = false;
            exportButton.disabled = false;
            setTimeout(() => statusMessage.classList.add('hidden'), 4000);
        }
    }

    captureButton.addEventListener('click', () => captureAndAction('clipboard'));
    exportButton.addEventListener('click', () => captureAndAction('download'));
</script>
@endpush