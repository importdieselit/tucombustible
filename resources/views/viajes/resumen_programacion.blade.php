@extends('layouts.app')

@section('title', 'Resumen de Viajes Programados/En Curso')

@section('content')

<!-- Cargar librerías necesarias para la impresión/captura -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Uso jQuery.print.js para simular PrintArea.js (se abre el diálogo de impresión/PDF) -->
<script src="{{asset('js/jquery.PrintArea.js')}}" defer></script>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-primary"><i class="bi bi-calendar-check me-2"></i> Programación de Viajes Pendientes</h1>
        
        <!-- Botón para Capturar la Imagen/Imprimir -->
        <button id="print" class="btn btn-primary shadow-sm">
            <i class="bi bi-camera me-2"></i> Capturar y Descargar Reporte
        </button>
         <button id="captureButton" class="btn btn-primary shadow-sm">
            <i class="bi bi-camera me-2"></i> Capturar a portapapeles
        </button>
    </div>

    <!-- Contenedor del Reporte (El área que será capturada, simplificada) -->
    <div style="width: 50%">
        <div id="statusMessage" class="text-center p-3 rounded-lg bg-yellow-100 text-yellow-800 hidden mb-4">
            Procesando...
        </div>
    <div id="reporte-area" class="card shadow-sm p-3 bg-white border border-primary printableArea" >
        
        <!-- Encabezado del Reporte Simplificado -->
        <div class="text-center mb-3">
            <h5 class="text-primary fw-bold mb-0">REPORTE DE PROGRAMACIÓN DE VIAJES</h5>
            <p class="text-muted small mb-1">Fecha de Emisión: {{ now()->format('d/m/Y H:i') }}</p>
            {{-- <div class="bg-light p-2 rounded d-inline-block border border-danger">
                <span class="fw-light small me-2">Presupuesto Total Estimado:</span> 
                <span class="text-danger fw-bold">$ {{ number_format($totalViaticosPresupuestados, 2) }}</span>
            </div> --}}
        </div>

        <!-- Tabla de Detalle Simplificada -->
        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
            <table class="table table-sm" style="font-size: 0.75rem;">
                <thead class="bg-primary text-white">
                    <tr style="font-weight: 700">
                        <th class="py-1"><img src="{{ asset('img/logo1.png') }}" alt="logo empresa" style="width: 250px"></th>
                        <th class="py-1" width="30%">Litros</th>
                        <th class="py-1" style="background-color: navajowhite; text-align: center;    vertical-align: middle;">CHOFER / AYUDANTE</th>
                        <th class="py-1" style="background-color: navajowhite; text-align: center;    vertical-align: middle;">UNIDAD</th>
                    </tr>
                </thead>
                <tbody>
                    @php($TotalLitros=0)
                    @forelse($viajes as $viaje)
                    

                      <tr style="border-bottom: 1px solid #01050a; background-color:white"   >
                        <td colspan="2">Despacho {{ \Carbon\Carbon::parse($viaje->fecha_salida)->format('d/m/Y') }}<br>
                            <strong>[{{ $viaje->destino_ciudad }}]</strong>
                        </td>
                        <td rowspan="{{$viaje->despachos->count()+1}}" style="vertical-align: middle; text-align:center; font-size: 18px;">
                            <span class="fw-bold">{{ $viaje->chofer->persona->nombre ?? 'PENDIENTE' }}</span><br>
                            @if($viaje->ayudante)
                                <span class="d-block">Ayudante: {{ $viaje->ayudante_chofer->persona->nombre ?? 'N/A' }}</span>
                            @endif
                        </td>
                        <td rowspan="{{$viaje->despachos->count()+1}}" style="vertical-align: middle; text-align:center">
                            <span class="text-black fw-bold" style="font-size: 40px" >{{  $viaje->vehiculo->flota }}</span><br>
                                {{ $viaje->vehiculo->placa ?? 'PENDIENTE' }}
                            
                        </td>
                      </tr>

                      @foreach($viaje->despachos as $index => $despacho)
                      @php($TotalLitros += $despacho->litros ?? 0)
                        <tr style="font-size: 15px; font-weight: 500;">
                            <td>{{ $despacho->cliente->nombre ?? $despacho->otro_cliente ?? 'Cliente Null' }}</td>
                            <td>{{ number_format($despacho->litros, 0)}} Lts</td>
                        </tr>
                      @endforeach

                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">No hay viajes programados o en curso.</td>
                    </tr>
                    @endforelse
                    <tr style="font-weight: 700; font-size:19px; border-top: 2px solid #01050a; background-color: #d1ecf1;">
                        <td class="py-1">TOTAL LITROS</td>
                        <td class="py-1">{{ $TotalLitros }}</td>
                        <td class="py-1"></td>
                        <td class="py-1"></td>
                </tbody>
            </table>
        </div>
        
        <!-- Pie de página simple para el reporte -->
        <div class="mt-3 border-top pt-2 text-end small text-muted">
            Generado por el Sistema de Viajes.
        </div>
    </div>
    </div>
    <!-- Área donde se mostrará el canvas generado (opcional, para debug/visualización) -->
        <div id="outputContainer" class="mt-8 pt-4 border-t border-gray-300">
        </div>
</div>
@push('scripts')
    

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" defer></script>
<script>
   document.addEventListener('DOMContentLoaded', function() {
    // IMPORTANTE: Se asume que jQuery ($) está cargado antes de este script.

    // 1. Obtención de Elementos DOM/jQuery
    // Obtenemos el elemento DOM nativo de la colección jQuery con [0]
    const printableArea = $("div.printableArea")[0]; 
    
    // Obtenemos los demás elementos nativos
    const captureButton = document.getElementById('captureButton');
    const statusMessage = document.getElementById('statusMessage');
    const outputContainer = document.getElementById('outputContainer');

    // Validación inicial para asegurar que los elementos críticos existan
    if (!printableArea || !captureButton || !statusMessage || !outputContainer) {
        console.error("Faltan elementos DOM críticos (printableArea, captureButton, statusMessage, o outputContainer).");
        return; // Salir si no se puede inicializar correctamente
    }

    // Mensaje inicial de bienvenida/instrucción
    // Se ejecuta tan pronto como el DOM esté listo


    /**
     * Función principal para capturar el área HTML y copiarla al portapapeles.
     */
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
});
</script>
@endpush
@endsection
