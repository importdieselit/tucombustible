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
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.11/locales/es.global.min.js'></script> <!-- Soporte para español -->
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
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
            // Verificar si el ayudante es un objeto Chofer (con persona.nombre) o un string simple
            const ayudanteNombre = viajeCompleto.ayudante_chofer 
                                   ? (viajeCompleto.ayudante_chofer.persona?.nombre || 'N/A')
                                   : (viajeCompleto.ayudante || 'N/A');
            
            let totalLitros = 0;
            let despachosHtml = '';

            // Generar filas de despachos
            viajeCompleto.despachos.forEach(despacho => {
                const clienteNombre = despacho.cliente ? (despacho.cliente.nombre || despacho.otro_cliente) : (despacho.otro_cliente || 'Cliente Null');
                const litros = despacho.litros || 0;
                totalLitros += litros;

                despachosHtml += `
                    <tr style="font-size: 15px; font-weight: 500;">
                        <td class="px-2">${clienteNombre}</td>
                        <td class="px-2">${new Intl.NumberFormat('es-ES').format(litros)} Lts</td>
                    </tr>
                `;
            });

            // Usamos un formato compacto similar a tu vista Blade
            return `
                <div class="table-responsive mt-3" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm text-start" style="font-size: 0.8rem; width: 100%;">
                        <thead class="bg-primary text-white">
                            <tr style="font-weight: 700">
                                <th class="py-1 px-2" style="width: 50%;">Destino / Cliente</th>
                                <th class="py-1 px-2" style="width: 25%;">Litros</th>
                                <th class="py-1 px-2 text-center" style="background-color: #6c757d; width: 25%;">Unidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="border-bottom: 1px solid #01050a; background-color:#f8f9fa;">
                                <td colspan="2" class="px-2">
                                    Despacho ${new Date(viajeCompleto.fecha_salida).toLocaleDateString('es-ES')}<br>
                                    <strong class="text-primary">Destino: [${viajeCompleto.destino_ciudad}]</strong>
                                    <p class="mb-0 mt-1">
                                        <span class="fw-bold">Chofer:</span> ${viajeCompleto.chofer.persona.nombre || 'PENDIENTE'}<br>
                                        ${viajeCompleto.ayudante ? `<span class="fw-bold">Ayudante:</span> ${ayudanteNombre}` : ''}
                                    </p>
                                </td>
                                <td rowspan="${(viajeCompleto.despachos.length) + 2}" style="vertical-align: middle; text-align:center; background-color:#e9ecef;">
                                    <span class="text-black fw-bold" style="font-size: 20px" >${viajeCompleto.vehiculo.flota || 'N/A'}</span><br>
                                    ${viajeCompleto.vehiculo.placa || 'PENDIENTE'}
                                </td>
                            </tr>
                            ${despachosHtml}
                        </tbody>
                        <tfoot style="border-top: 2px solid #01050a; background-color: #d1ecf1; font-weight: 700; font-size:16px;">
                            <tr>
                                <td class="py-1 px-2">TOTAL LITROS</td>
                                <td class="py-1 px-2">${new Intl.NumberFormat('es-ES').format(totalLitros)} Lts</td>
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
                title: `[${viaje.status}] ${viaje.cliente || 'Viaje'} a ${viaje.destino_ciudad}`,
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
                    width: '85%', // Ancho adaptado para la tabla
                    showCancelButton: true,
                    confirmButtonText: '<i class="bi bi-pencil-square me-2"></i> Ver / Editar Viaje',
                    cancelButtonText: 'Cerrar',
                    focusConfirm: false,
                    showCloseButton: true,
                    customClass: {
                        container: 'custom-swal-container',
                        popup: 'custom-swal-popup',
                        title: 'custom-swal-title'
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
