@extends('layouts.app')
@section('title', 'Planificación de Viajes - Calendario')

@push('styles')
    <style>
        /* Estilos generales para el contenedor y la fuente */
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f4f6f8;
        }

        /* Estilo para el contenedor principal del calendario */
        #calendar {
            max-width: 1100px;
            margin: 40px auto;
            padding: 20px;
            background: #ffffff;
            color: #070707 !important;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }

        /* Colores de los eventos basados en el status (simulados) */
        .fc-event-PENDIENTE_ASIGNACION {
            background-color: #fca5a5 !important; /* Rojo claro */
            border-color: #ef4444 !important;
            color: #450a0a !important;
        }
        .fc-event-EN_CURSO {
            background-color: #6ee7b7 !important; /* Verde claro */
            border-color: #10b981 !important;
            color: #064e3b !important;
        }
        .fc-event-COMPLETADO {
            background-color: #bfdbfe !important; /* Azul claro */
            border-color: #0d0d0e !important;
            color: #070707 !important;
            opacity: 0.7; /* Menor opacidad para terminados */
        }
        
        /* Estilos para que los eventos se vean bien */
        .fc-event {
            padding: 5px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .fc-event-main {
            color: #070707 !important;
        }
    </style>
@endpush

@push('scripts')
    <!-- 1. Carga de las librerías de FullCalendar (Core + Plugins) -->
    <!-- Se recomienda usar la versión más reciente con los plugins necesarios -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script> 
    <script>
        @once('fullcalendar')
            const printableArea = document.querySelector('.printableArea');
            const statusMessage = document.getElementById('statusMessage');
            const outputContainer = document.getElementById('outputContainer');

        document.addEventListener('DOMContentLoaded', function() {

             async function CopyToClipboard() {
        // 1. Mostrar estado de carga y deshabilitar botón
            statusMessage.textContent = 'Generando imagen...';
            statusMessage.classList.remove('hidden', 'bg-red-100', 'text-red-800', 'bg-green-100', 'text-green-800');
            statusMessage.classList.add('bg-yellow-100', 'text-yellow-800');
            $(this).disabled = true;
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
                $(this).disabled = false;
            }
        }
            
            const viajesDataJsonString = '{!! $viajesDataJson !!}';

            console.log('Cargando datos de viajes para el calendario...');
            console.log('JSON de Viajes:', viajesDataJsonString);
            
            // Deserializar el JSON pasado desde el controlador
    try {
        const viajesData = JSON.parse(viajesDataJsonString);
        console.log('Datos de Viajes (Objeto JS):', viajesData);

        // =====================================================================
        // 1. FUNCIÓN AUXILIAR: GENERAR TABLA HTML PARA SWEETALERT
        // =====================================================================

        function generateSummaryHtml(viajeCompleto) {
        
        // Extracción segura de datos
        const choferNombre = viajeCompleto.chofer || 'PENDIENTE';
        const ayudante = viajeCompleto.ayudante || null; 
        const flota = viajeCompleto.vehiculo || 'PENDIENTE';
        const placa = viajeCompleto.placa || 'N/A';
        
        // *** CORRECCIÓN CRÍTICA: Asegurar que despachos es un array, si no, usar [] ***
        const despachos = Array.isArray(viajeCompleto.despachos) ? viajeCompleto.despachos : [];
        
        let totalLitros = 0;
        let despachosHtml = '';
        
        // Generar filas de despachos
        if (despachos.length > 0) {
            despachos.forEach(despacho => {
                const clienteNombre = despacho.cliente ? despacho.cliente :'Cliente Desconocido';
                const litros = parseFloat(despacho.litros) || 0; 
                totalLitros += litros; 
                despachosHtml += `
                    <tr style="font-size: 15px; font-weight: 500;">
                        <td class="px-2" style="border-right: 1px solid #dee2e6; color:#495057;">${clienteNombre}</td>
                        <td class="px-2 fw-bold text-end">${new Intl.NumberFormat('es-ES').format(litros)} Lts</td>
                    </tr>
                `;
            });
        } else {
             // Mostrar una fila si no hay despachos
             despachosHtml = `
                <tr style="font-size: 15px; font-weight: 500; background-color: #f8d7da;">
                    <td colspan="2" class="px-2 text-center text-danger">No hay despachos asignados a este viaje.</td>
                </tr>
             `;
        }

        const choferRowSpan = despachos.length > 0 ? despachos.length + 1 : 2; 

        // Construcción del HTML de la tabla
        return `
        <div class="d-flex justify-content-rigth gap-2 mt-4 flex-wrap">
                <button id="print" class="btn btn-info shadow-sm">
                    <i class="fa fa-print me-2"></i>
                </button>
                <button id="copyToClipboardBtn" class="btn btn-secondary shadow-sm">
                    <i class="fa fa-clipboard me-2"></i>
                </button>
            </div>
            <div id="statusMessage" class="text-center p-3 rounded-lg bg-yellow-100 text-yellow-800 hidden mb-4">
                Procesando...
            </div>
            <div class="table-responsive mt-3 printableArea" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-sm text-start table-borderless" style="font-size: 0.8rem; width: 100%;     border: 2px solid #000; background-color: #FFF !important;">
                    <thead class="bg-primary text-white">
                        <tr style="font-weight: 700">
                            <th class="py-1 px-2" style="width: 50%;">Despacho / Cliente</th>
                            <th class="py-1 px-2 text-end" style="width: 25%;">Litros</th>
                            <th class="py-1 px-2 text-center" style="background-color: #34495e; color: #fff; width: 25%;">Unidad / Personal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom: 1px solid #01050a; background-color:white;">
                            <td colspan="2" class="px-2" style="border-right: 1px solid #dee2e6;">
                                <small class="text-muted">Fecha Salida: ${new Date(viajeCompleto.fecha_salida).toLocaleDateString('es-ES')}</small><br>
                                <strong class="text-info">Destino: [${viajeCompleto.destino}]</strong>
                            </td>
                            
                            <!-- Celda unificada para Chofer/Unidad -->
                            <td rowspan="${choferRowSpan + 1}" style="vertical-align: middle; text-align:center; background-color: navajowhite;">
                                <span class="text-black fw-bold d-block" style="font-size: 18px" >${flota}</span>
                                <small class="d-block text-muted mb-2">${placa}</small>
                                <hr class="my-1 border-secondary">
                                <span class="fw-bold d-block text-success" style="font-size: 14px;">Chofer: ${choferNombre}</span>
                                ${ayudante ? `<span class="d-block text-dark" style="font-size: 14px;">Ayudante: ${ayudante}</span>` : ''}
                            </td>
                        </tr>
                        ${despachosHtml}
                    </tbody>
                    <tfoot style="border-top: 2px solid #01050a; background-color: #FFF; font-weight: 700; font-size:16px;">
                        <tr>
                            <td class="py-1 px-2" style="border-right: 1px solid #01050a;">TOTAL LITROS</td>
                            <td class="py-1 px-2 text-end">${new Intl.NumberFormat('es-ES').format(totalLitros)} Lts</td>
                            <td class="py-1 px-2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
                        
        `;
    }


        // =====================================================================
        // 2. CONVERSIÓN DE DATA AL FORMATO DE FULLCALENDAR
        // =====================================================================

        const events = viajesData.map(viaje => {
            // ... (Lógica de fechas idéntica)
            const endDate = new Date(viaje.fecha_salida);
            endDate.setDate(endDate.getDate() + (viaje.duracion_dias || 1)); // Asegura al menos 1 día
            const endFormatted = endDate.toISOString().split('T')[0];

            return {
                id: viaje.id,
                title: `${viaje.cliente || 'Viaje'} a ${viaje.destino}`,
                start: viaje.fecha_salida,
                end: endFormatted,
                allDay: true,
                classNames: ['fc-event', `fc-event-${viaje.status}`,'text-black'],
                // Guardamos el objeto COMPLETO del viaje en extendedProps
                extendedProps: {
                    data: viaje // Aquí guardamos toda la información (vehiculo, despachos, etc.)
                }
            };
        });

        // =====================================================================
        // 3. INICIALIZACIÓN DEL CALENDARIO
        // =====================================================================
        const calendarEl = document.getElementById('calendar');

        const calendar = new FullCalendar.Calendar(calendarEl, {
            // ... (Configuración básica)
            locale: 'es', 
            initialView: 'dayGridMonth', 
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            
            // Eventos
            events: events,

            // Interacción del usuario (Manejo de clics en eventos)
            eventClick: function(info) {
                console.log('Evento clickeado:', info.event);
                const viajeCompleto = info.event.extendedProps.data;
                console.log('Viaje completo seleccionado:', viajeCompleto);
                const htmlContent = generateSummaryHtml(viajeCompleto);

                Swal.fire({
                    title: `<h5 class="text-primary">${viajeCompleto.cliente || 'Viaje Programado'}</h5>`,
                    html: htmlContent,
                    icon: 'info',
                    width: '50%', // Ancho adaptado para la tabla
                    showCancelButton: true,
                    confirmButtonText: '<i class="bi bi-pencil-square me-2"></i> Ver',
                    cancelButtonText: 'Cerrar',
                    focusConfirm: false,
                    showCloseButton: true,
                    customClass: {
                        container: 'custom-swal-container',
                        popup: 'custom-swal-popup',
                        title: 'custom-swal-title'
                    },
                    didOpen: (modal) => {
                                const downloadBtn = modal.querySelector('#downloadReportBtn');
                                const copyBtn = modal.querySelector('#copyToClipboardBtn');
                                
                                const summaryId = downloadBtn.getAttribute('data-summary-id');
                                
                                // Listener para Descargar
                                if (downloadBtn) {
                                    downloadBtn.addEventListener('click', async () => {
                                        // Usamos un botón de acción de SweetAlert para el cierre automático
                                        
                                        const imageBlob = await captureElement(summaryId);
                                        
                                        if (imageBlob) {
                                            const url = URL.createObjectURL(imageBlob);
                                            const a = document.createElement('a');
                                            a.href = url;
                                            a.download = `Reporte_Viaje_${viajeCompleto.id}_${new Date().toISOString().slice(0, 10)}.png`;
                                            document.body.appendChild(a);
                                            a.click();
                                            document.body.removeChild(a);
                                            URL.revokeObjectURL(url);
                                            // Se muestra un éxito de forma inmediata
                                            Swal.close(); 
                                            Swal.fire('Descarga Exitosa', 'El reporte PNG ha sido descargado.', 'success');
                                        }
                                    });
                                }

                                // Listener para Copiar al Portapapeles
                                if (copyBtn) {
                                    copyBtn.addEventListener('click', async () => {
                                        
                                        const imageBlob = await captureElement(summaryId);
                                        
                                        if (imageBlob) {
                                            try {
                                                // La API de Clipboard es moderna, pero podría fallar en algunos iframes (Canvas)
                                                // Usamos la API asíncrona (ClipboardItem)
                                                const item = new ClipboardItem({ [imageBlob.type]: imageBlob });
                                                await navigator.clipboard.write([item]);
                                                Swal.close(); 
                                                Swal.fire('Copiado', 'Reporte copiado al portapapeles (Ctrl+V para pegar).', 'success');
                                            } catch (err) {
                                                console.error('Error al copiar al portapapeles. Posiblemente por restricciones de seguridad:', err);
                                                Swal.close(); 
                                                // Fallback si falla la copia de imagen
                                                Swal.fire('Atención', 'No se pudo copiar la imagen directamente. Intenta usar la opción de "Descargar Reporte".', 'warning');
                                            }
                                        }
                                    });
                                }
                            }
                            
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Redirigir a la ruta de edición de viaje.
                        window.location.href = `/viajes/${info.event.id}/edit`;
                    }
                });
            },
            // ... (Otros callbacks)
        });

        calendar.render();

    } catch (e) {
        console.error('Error al inicializar el calendario o parsear JSON:', e);
    }
});
@endonce
    </script>
@endpush

@section('content')
<div class="container-fluid mt-4">
    <h1 class="mb-4 text-primary text-center">
        <i class="bi bi-calendar-event me-3"></i> Planificación (Calendario)
    </h1>
    
    <div id='calendar'>
        <!-- FullCalendar se renderizará aquí -->
    </div>
</div>
@endsection
