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
            border-color: #3b82f6 !important;
            color: #1e3a8a !important;
            opacity: 0.7; /* Menor opacidad para terminados */
        }
        
        /* Estilos para que los eventos se vean bien */
        .fc-event {
            padding: 5px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
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
            // =====================================================================
            // 1. DATA SIMULADA DE VIAJES
            // ESTA DATA DEBE SER REEMPLAZADA POR LA CONSULTA REAL A TU CONTROLADOR
            // =====================================================================
            
            // Usamos la estructura de tu modelo Viaje: destino_ciudad, fecha_salida, status.
            const viajesData = JSON.parse('{{$viajesDataJson}}');

            // =====================================================================
            // 2. CONVERSIÓN DE DATA AL FORMATO DE FULLCALENDAR
            // =====================================================================
            
            const events = viajesData.map(viaje => {
                // Función auxiliar para calcular la fecha de fin (FullCalendar usa 'end' no inclusive)
                const endDate = new Date(viaje.fecha_salida);
                endDate.setDate(endDate.getDate() + viaje.duracion_dias);

                // Formateamos la fecha de fin para FullCalendar (YYYY-MM-DD)
                const endFormatted = endDate.toISOString().split('T')[0];

                return {
                    id: viaje.id,
                    title: `[${viaje.status}] ${viaje.destino} (${viaje.chofer})`,
                    start: viaje.fecha_salida,
                    end: endFormatted, // La fecha de fin es exclusiva
                    allDay: true, // Asumimos que la planificación es por días completos
                    classNames: ['fc-event', `fc-event-${viaje.status}`], // Clases CSS para colorear
                    extendedProps: {
                        status: viaje.status
                    }
                };
            });


            // =====================================================================
            // 3. INICIALIZACIÓN DEL CALENDARIO
            // =====================================================================
            const calendarEl = document.getElementById('calendar');

            const calendar = new FullCalendar.Calendar(calendarEl, {
                // Configuración básica
                locale: 'es', // Usar idioma español
                initialView: 'dayGridMonth', // Vista inicial: Mes
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay' // Opciones de vista
                },
                
                // Eventos
                events: events, // Carga los eventos generados arriba

                // Interacción del usuario (Manejo de clics en eventos)
                eventClick: function(info) {
                    const statusText = info.event.extendedProps.status;
                    
                    Swal.fire({
                        title: info.event.title,
                        html: `
                            <p><strong>ID Viaje:</strong> #${info.event.id}</p>
                            <p><strong>Fecha Inicio:</strong> ${info.event.start.toLocaleDateString()}</p>
                            <p><strong>Estatus:</strong> <span class="badge" style="background-color: ${info.event.classNames.includes('fc-event-EN_CURSO') ? '#10b981' : (info.event.classNames.includes('fc-event-PENDIENTE_ASIGNACION') ? '#ef4444' : '#3b82f6')}; color: white;">${statusText}</span></p>
                            <p class="mt-3">¿Deseas ver los detalles o editar la planificación?</p>
                        `,
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: 'Ver/Editar Viaje',
                        cancelButtonText: 'Cerrar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // En un entorno real, esto redirigiría a la ruta de edición de viaje.
                            window.location.href = `/viajes/${info.event.id}/edit`;
                        }
                    });
                },
                
                // Opcional: Permitir arrastrar y redimensionar (si tu usuario tiene permisos de planificación)
                editable: true, 
                eventDrop: function(info) {
                    // Muestra una notificación si el usuario mueve un viaje
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'warning',
                        title: `Viaje movido. Enviar nueva fecha (${info.event.startStr}) al servidor.`,
                        showConfirmButton: false,
                        timer: 3000
                    });
                    // En un sistema real, aquí harías una llamada AJAX para actualizar la fecha en la base de datos.
                }
            });

            // Renderizar el calendario
            calendar.render();
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
