@extends('layouts.app')

@section('title', 'Requerimientos de Suministros')

@section('content')
@php
    $total=0;
@endphp
<!-- Cargar librerías necesarias para la impresión/captura -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Uso jQuery.print.js para simular PrintArea.js (se abre el diálogo de impresión/PDF) -->
<script src="{{asset('js/jquery.PrintArea.js')}}" defer></script>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-primary"><i class="fa fa-cash me-2"></i> Solicitud de suministros</h1>
        
        <!-- Botón para Capturar la Imagen/Imprimir -->
        <button id="print" class="btn btn-primary shadow-sm">
            <i class="fa fa-camera me-2"></i> Capturar y Descargar Reporte
        </button>
         <button id="captureButton" class="btn btn-primary shadow-sm">
            <i class="fa fa-camera me-2"></i> Capturar a portapapeles
        </button>
         <!-- NUEVO BOTÓN: Enviar a Telegram -->
        <button id="sendTelegramButton" class="btn btn-info shadow-sm">
            <i class="fa fa-telegram me-2"></i> Enviar a Telegram
        </button>
    </div>

    <!-- Contenedor del Reporte (El área que será capturada, simplificada) -->
    <div style="width: 800px; justify-self: anchor-center;">
        <div id="statusMessage" class="text-center p-3 rounded-lg bg-yellow-100 text-yellow-800 hidden mb-4">
        </div>
    <div id="reporte-area" class="card shadow-sm p-3 bg-white border border-primary printableArea" >
        <div class="row">
            <div class="col col-6 text-left">
                <img src="{{ asset('img/logo1.png') }}" alt="logo empresa" style="width: 250px">
            </div>
            <div class="col col-6 text-right content-right" style="text-align: right;">
                <p class="text-rigth small mb-1">Fecha de Emisión: {{ now()->format('d/m/Y H:i') }}</p>
            </div>
        
        <!-- Encabezado del Reporte Simplificado -->
            <div class="col-12 text-center mb-3">
                <h5 class="text-primary fw-bold mb-0">Requerimiento de Suministros</h5>
            </div>
            <div class="col-12">OT: OT-{{ $orden->nro_orden }}</div>
            <div class="col-12">Requerido por {{ $orden->resposable }}</div>

            @if(!is_null($vehiculo))
                <div class="col-12">
                    Para: Unidad {{$vehiculo->flota}} [{{$vehiculo->placa}}]
                </div>
            @endif
        </div>
        <!-- Tabla de Detalle Simplificada -->
        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                <meta name="csrf-token" content="{{ csrf_token() }}">

            <table class="table table-sm" style="font-size: 0.75rem;">  
                <thead class="bg-primary text-white">
                    <tr>
                        <th class="py-1" style="background-color: navajowhite; text-align: center;    vertical-align: middle;" width="50%">Descripcion</th>
                        <th class="py-1" style="background-color: navajowhite; text-align: center;    vertical-align: middle;">Cantidad</th>
                        <th class="py-1" style="background-color: navajowhite; text-align: center;    vertical-align: middle;" width="20%">Precio Unitario</th>
                        <th class="py-1" style="background-color: navajowhite; text-align: left;    vertical-align: middle;"width="20%">Total</th>
                    </tr>
                </thead>
                <tbody>
                     @forelse($purchaseDetail as $detail)
                     @php
                        $costo=$detail->costo_unitario_aprobado??0;
                        $sub=$detail->cantidad_solicitada*$costo;
                        $total+=$sub;
                    @endphp
                      <tr style="border-bottom: 1px solid #01050a; background-color:white; text-align: center;  vertical-align: middle;"   >
                        <td>{{$detail->descripcion}}   </td>
                        <td>{{$detail->cantidad_solicitada}} <input type="number"  class="cantidad" style="display:none" name="cantidad[]" value="{{$detail->cantidad_solicitada}}" step="0.01"></td>
                        <td>@if($admin && $purchaseOrder->estatus==1)  <input type="number" class="form-control precio" data-id="{{$detail->id}}" name="precio_unitario[]" value="{{$costo}}" step="0.01"> @else {{$costo}} @endif</td>
                        <td><input type="text" value="{{$sub}}" class="form-control subtotal" name="subtotal[] border-0" style="border: 0;" readonly></td>
                      </tr>
                    @empty 
                    @endforelse 
                    <tr style="font-weight: 700; text-align: center; font-size:19px; border-top: 2px solid #01050a; background-color: #d1ecf1;">
                        <td class="py-1"></td>
                        <td class="py-1"></td>
                        <td class="py-1">TOTAL</td>
                        <td class="py-1" style="text-align: left">

                             <input type="text" id="total_general" class="form-control" style="border: 0" value="{{$total}}" readonly>

                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
    </div>
                <div class="mt-4 flex gap-3">

    {{-- ESTATUS: 1 = SOLICITADA --}}
            @if ($purchaseOrder->estatus == 1)
                {{-- Solo usuarios administradores pueden aprobar o rechazar --}}
                @if($admin)
                    <button class="px-4 py-2 btn btn-info text-white rounded shadow"
                            onclick="actualizarEstatus({{ $purchaseOrder->id }}, 2)">
                        Aprobar
                    </button>

                    <button class="px-4 py-2 btn btn-success text-white rounded shadow"
                            onclick="actualizarEstatus({{ $purchaseOrder->id }}, 3)">
                        Rechazar
                    </button>
                @endif
            @endif


            {{-- ESTATUS: 2 = APROBADA --}}
            @if ($purchaseOrder->estatus == 2)
                {{-- Mecánico marca como recibido --}}
                @if($user->id_perfil == 5) 
                    <button class="px-4 py-2 bg-blue-600 text-white rounded shadow"
                            onclick="actualizarEstatus({{ $purchaseOrder->id }}, 4)">
                        Marcar como Recibido
                    </button>
                @endif
            @endif


            {{-- ESTATUS: 3 = RECHAZADA --}}
            @if ($purchaseOrder->estatus == 3)
                <span class="text-red-600 font-semibold">Orden Rechazada</span>
            @endif


            {{-- ESTATUS: 4 = RECIBIDO --}}
            @if ($purchaseOrder->estatus == 4)
                <span class="text-green-700 font-semibold">Orden Finalizada y Recibida</span>
            @endif

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
    const sendTelegramButton = document.getElementById('sendTelegramButton');
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
        document.querySelectorAll('input').forEach(input => {
            input.style='boder:0;';
        });
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


    
    async function sendReportToTelegram() {
        sendTelegramButton.disabled = true;
        document.querySelectorAll('.precio').forEach(input => {
           input.style.border = '0';          // Quitar borde
            input.style.outline = 'none';      // Evitar borde azul de focus
            input.style.background = 'transparent'; 
        });
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
            formData.append('chart_image', imageBlob, 'requerimiento_de_compra.png');
            formData.append('caption', `*Requerimiento de Compra*\nGenerado el: ${new Date().toLocaleString('es-VE')}`);
            
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
    

   
    // Detectar cambios en cantidad y precio
    document.querySelectorAll('.cantidad, .precio').forEach(input => {
            input.addEventListener('input', function () {
                const row = this.closest('tr');
                calcularSubtotal(row);
                calcularTotal();
            });
        });


    document.querySelectorAll('.precio').forEach(input => {
        input.addEventListener('change', function () {
            let idDetalle = this.dataset.id;
            let precio    = this.value;
            console.log('precio:'+precio);
            fetch("{{ route('compras.actualizar_precio') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    id: idDetalle,
                    precio: precio
                })
            })
            .then(r => r.json())
            .then(data => {
                console.log('recibe');
                if (!data.ok) {
                    alert("Error al guardar: " + data.msg);
                }
            })
            .catch(err => console.log(err));
        });
    });    
});

 function calcularSubtotal(row) {
        const cantidad = parseFloat(row.querySelector('.cantidad').value) || 0;
        const precio  = parseFloat(row.querySelector('.precio').value) || 0;
        const subtotal = cantidad * precio;

        row.querySelector('.subtotal').value = subtotal.toFixed(2);
    }

    function calcularTotal() {
        let total = 0;

        document.querySelectorAll('.subtotal').forEach(sub => {
            total += parseFloat(sub.value) || 0;
        });

        document.getElementById('total_general').value = total.toFixed(2);
    }

     function actualizarEstatus(id, estatus) {

        if(!confirm("¿Confirmar esta acción?")) return;

        fetch("{{ route('compras.cambiar_estatus') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                id: id,
                estatus: estatus
            })
        })
        .then(r => r.json())
        .then(data => {

            if (data.ok) {
                alert(data.msg);
                location.reload();
            } else {
                alert("Error: " + data.msg);
            }
        })
        .catch(e => alert("Error de conexión: " + e));
    }

</script>
@endpush
@endsection
