@extends('layouts.app')

@section('title', 'Planificación de Mantenimiento')

@push('styles')
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.css' rel='stylesheet' />
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0 text-uppercase">Planificación de Mantenimientos</h4>
            <small class="text-muted">Calendario operativo y programación de unidades</small>
        </div>
        <i class="fas fa-calendar-alt fa-2x text-light"></i>
    </div>
    
    <div id='calendar-mantenimiento' class="bg-white"></div>
</div>

{{-- Modal con Estilo Impordiesel --}}
<div class="modal fade" id="planificarModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-corporate text-white">
                <h6 class="modal-title fw-bold text-uppercase">Programar OT: <span id="modalDateDisplay"></span></h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="planificacionForm">
                <div class="modal-body p-4">
                    @csrf
                    <input type="hidden" id="fecha_programada" name="fecha_programada">

                    <div class="mb-3">
                        <label class="small fw-bold text-uppercase text-muted">Unidad de Flota</label>
                        <select class="form-select border-0 bg-light" id="vehiculo_id" name="vehiculo_id" required>
                            @foreach($vehiculos as $vehiculo)
                                <option value="{{ $vehiculo->id }}">{{ $vehiculo->flota }} ({{ $vehiculo->placa }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-uppercase text-muted">Servicio Requerido</label>
                        <select class="form-select border-0 bg-light" id="tipo_mantenimiento" name="tipo_mantenimiento" required>
                            @foreach($tiposMantenimiento as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-0">
                        <label class="small fw-bold text-uppercase text-muted">Instrucciones Técnicas</label>
                        <textarea class="form-control border-0 bg-light" id="descripcion_plan" name="descripcion_plan" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-orange px-4" id="btnGuardar" style="background-color: #f2A435; color: white;">
                        <i class="fas fa-check-circle me-1"></i> Generar Orden Trabajo
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
                        ? '<span class=""><i class="fa fa-check-circle"></i> OT Generada (#<a href="/ordenes/' + props.orden_id + '" target="_blank">' + props.nro_orden + '</a>)</span>'
                        : '<span class="badge bg-primary"><i class="fa fa-clock"></i> Programado</span>';

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