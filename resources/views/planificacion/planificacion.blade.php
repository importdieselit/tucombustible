@extends('layouts.app')

@section('title', 'Planificación de Mantenimiento')

@push('styles')
    <!-- Asegúrate de tener cargado FullCalendar y Bootstrap Icons -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.css' rel='stylesheet' />
    <style>
        #calendar-mantenimiento {
            max-width: 1100px;
            margin: 40px auto;
            padding: 20px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }
        .fc-daygrid-event {
            font-size: 0.85rem;
            padding: 2px 4px;
            white-space: normal;
        }
        .custom-swal-popup {
            max-width: 600px !important;
            width: 90%;
        }
    </style>
@endpush

@section('content')
<div class="container-fluid mt-4">
    <h1 class="mb-4 text-primary text-center">
        <i class="bi bi-calendar-range me-3"></i> Planificación de Mantenimientos
    </h1>
    
    <!-- Contenedor del Calendario -->
    <div id='calendar-mantenimiento'>
        <!-- FullCalendar se renderizará aquí -->
    </div>
</div>

<!-- Modal para Planificar Mantenimiento -->
<div class="modal fade" id="planificarModal" tabindex="-1" aria-labelledby="planificarModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="planificarModalLabel">Programar Mantenimiento para <span id="modalDateDisplay"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="planificacionForm">
                <div class="modal-body">
                    @csrf
                    <input type="hidden" id="fecha_programada" name="fecha_programada">

                    <div class="mb-3">
                        <label for="vehiculo_id" class="form-label">Vehículo</label>
                        <select class="form-select" id="vehiculo_id" name="vehiculo_id" required>
                            <option value="" disabled selected>Seleccione la unidad</option>
                            @foreach($vehiculos as $vehiculo)
                                <option value="{{ $vehiculo->id }}">{{ $vehiculo->flota }} ({{ $vehiculo->placa }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="tipo_mantenimiento" class="form-label">Tipo de Mantenimiento</label>
                        <select class="form-select" id="tipo_mantenimiento" name="tipo_mantenimiento" required>
                            <option value="" disabled selected>Seleccione el servicio</option>
                            @foreach($tiposMantenimiento as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion_plan" class="form-label">Descripción / Tareas</label>
                        <textarea class="form-control" id="descripcion_plan" name="descripcion_plan" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardar">
                        <i class="bi bi-calendar-check me-1"></i> Guardar Planificación y Generar OT
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <!-- FullCalendar Core & Plugins -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/locales-all.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
    <!-- SweetAlert2 para notificaciones -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar-mantenimiento');
            var planificarModal = new bootstrap.Modal(document.getElementById('planificarModal'));
            var form = document.getElementById('planificacionForm');

            if (!calendarEl) {
                console.error("No se encontró el elemento del calendario.");
                return;
            }

            // Inicialización de FullCalendar
            var calendar = new FullCalendar.Calendar(calendarEl, {
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                locale: 'es',
                initialView: 'dayGridMonth',
                editable: true,
                dayMaxEvents: true, // permite el enlace "más" cuando hay demasiados eventos
                events: '{{ route('mantenimiento.planificacion.eventos') }}', // API para cargar eventos
                
                // Manejar clic en un día para abrir el modal de planificación
                dateClick: function(info) {
                    // Cargar la fecha seleccionada en el modal
                    document.getElementById('fecha_programada').value = info.dateStr;
                    document.getElementById('modalDateDisplay').textContent = info.dateStr;
                    planificarModal.show();
                },

                // Manejar clic en un evento existente
                eventClick: function(info) {
                    const props = info.event.extendedProps;
                    const statusText = props.estatus === 2 
                        ? '<span class=""><i class="bi bi-check-circle"></i> OT Generada (#<a href="/ordenes/' + props.orden_id + '" target="_blank">' + props.orden_id + '</a>)</span>'
                        : '<span class="badge bg-primary"><i class="bi bi-clock"></i> Programado</span>';

                    Swal.fire({
                        title: `Planificación de Mantenimiento`,
                        html: `
                            <p class="text-start mt-3">
                                <strong>Vehículo:</strong> ${info.event.title.split(']')[0].replace('[', '')}<br>
                                <strong>Placa:</strong> ${props.placa}<br>
                                <strong>Tipo:</strong> ${props.tipo}<br>
                                <strong>Fecha:</strong> ${info.event.start.toLocaleDateString()}<br>
                                <strong>Estado:</strong> ${statusText}<br>
                                <hr>
                                <strong>Tareas Planificadas:</strong> ${props.descripcion || 'Sin descripción.'}
                            </p>
                        `,
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: 'Ver Vehículo',
                        cancelButtonText: 'Cerrar',
                        focusConfirm: false,
                        customClass: {
                            popup: 'custom-swal-popup',
                        }
                    });
                }
            });

            calendar.render();

            // Manejar el submit del formulario (Crear Planificación y OT)
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const btnGuardar = document.getElementById('btnGuardar');
                btnGuardar.disabled = true;
                btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...';

                const formData = new FormData(form);

                fetch('{{ route('mantenimiento.planificacion.store') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: formData
                })
                .then(response => response.json().then(data => ({ status: response.status, body: data })))
                .then(({ status, body }) => {
                    if (status === 200 && body.success) {
                        planificarModal.hide();
                        calendar.refetchEvents(); // Recargar eventos del calendario
                        
                        Swal.fire({
                            title: '¡Éxito!',
                            html: `Mantenimiento Programado. <br>Se generó la Orden de Trabajo #<strong>${body.orden_id}</strong> (Estatus: Programada).`,
                            icon: 'success',
                            showConfirmButton: true,
                            confirmButtonText: 'Ver OT'
                        }).then((result) => {
                             if (result.isConfirmed) {
                                console.log('Ver OT:', body.orden_id);
                                //window.location.href = `/ordenes/${body.orden_id}`; // Asume que tienes una ruta para ver la orden
                            }
                        });
                        
                    } else if (status === 422) {
                         // Manejo de errores de validación de Laravel
                         let errorsHtml = '<ul>';
                         Object.values(body.errors).forEach(err => {
                             errorsHtml += `<li>${err[0]}</li>`;
                         });
                         errorsHtml += '</ul>';

                         Swal.fire('Error de Validación', errorsHtml, 'error');
                         
                    } else {
                        // Manejo de otros errores del servidor
                        Swal.fire('Error', body.message || 'No se pudo guardar la planificación.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error de Conexión', 'Ocurrió un error al intentar comunicarse con el servidor.', 'error');
                })
                .finally(() => {
                    btnGuardar.disabled = false;
                    btnGuardar.innerHTML = '<i class="bi bi-calendar-check me-1"></i> Guardar Planificación y Generar OT';
                });
            });

        });
    </script>
@endpush
@endsection
