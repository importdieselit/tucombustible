@extends('layouts.app')

@push('styles')
<style>
    .bg-corporate-primary { background-color: #0f2d59 !important; color: white; }
    
    /* Panel lateral de personal */
    .draggable-list { max-height: 70vh; overflow-y: auto; }
    .draggable-badge {
        cursor: grab;
        background-color: #f8fafc;
        border: 1px solid #cbd5e1;
        transition: all 0.2s;
    }
    .draggable-badge:active { cursor: grabbing; opacity: 0.6; }
    .draggable-badge:hover { background-color: #e2e8f0; border-color: #0f2d59; transform: translateY(-1px); }

    /* CSS Grid para el Calendario de 3 Semanas */
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 8px;
    }
    .calendar-header-day {
        text-align: center; font-weight: 700; color: #0f2d59; padding: 10px 0;
        background: #f1f5f9; border-radius: 6px; text-transform: uppercase; font-size: 0.85rem;
    }
    .calendar-cell {
        background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px;
        min-height: 120px; display: flex; flex-direction: column; transition: all 0.2s;
    }
    .calendar-cell.other-week { background: #f8fafc; opacity: 0.8; }
    .calendar-cell.current-week { border-color: #cbd5e1; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
    .calendar-cell.today { border: 2px solid #f59e0b; background: #fffbeb; }
    .calendar-cell.drag-over { background-color: #f0fdf4 !important; border: 2px dashed #16a34a; }
    
    .cell-date-header {
        padding: 6px 10px; border-bottom: 1px solid #f1f5f9;
        font-weight: 600; color: #475569; display: flex; justify-content: space-between; align-items: center;
    }
    .drop-container { flex-grow: 1; padding: 8px; display: flex; flex-direction: column; gap: 6px; }

    /* Items asignados en el calendario */
    .assigned-item {
        background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;
        font-size: 0.75rem; padding: 5px 8px; border-radius: 4px;
        display: flex; justify-content: space-between; align-items: center;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .btn-remove-guardia {
        background: none; border: none; color: #ef4444; font-weight: bold;
        cursor: pointer; font-size: 0.9rem; padding: 0 0 0 5px; line-height: 1;
    }
    .btn-remove-guardia:hover { color: #b91c1c; transform: scale(1.1); }
</style>
@endpush

@section('content')
<div class="container-fluid py-4 bg-light">
    
    <!-- Filtros Superiores (Mantenemos tu diseño) -->
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded shadow-sm">
        <div class="d-flex gap-2">
            <!-- CORRECCIÓN 1: Usar $inicioSemanaActual en lugar de $startOfWeek -->
            <a href="{{ route('guardias.reporte', ['start_date' => $inicioSemanaActual->toDateString()]) }}" target="_blank" class="btn btn-success shadow-sm">
                <i class="fa fa-print me-2"></i> Emitir Reporte PDF
            </a>
        </div>
        
        <form action="{{ route('guardias.index') }}" method="GET" class="d-flex align-items-center gap-3 m-0">
            <div class="d-flex align-items-center gap-2">
                <label for="date" class="fw-bold text-secondary mb-0 text-nowrap">Fecha:</label>
                <!-- CORRECCIÓN 2: Usar $fechaBase -->
                <span class="badge bg-dark text-white fw-bold fs-6">{{ $fechaBase->format('d/m/Y') }}</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label for="rol" class="fw-bold text-secondary mb-0 text-nowrap">Rol Personal:</label>
                <select name="rol" id="rol" class="form-select" onchange="this.form.submit()">
                    <option value="Chofer" {{ $rolSeleccionado == 'Chofer' ? 'selected' : '' }}>🚛 Choferes</option>
                    <option value="Ayudante de Chofer" {{ $rolSeleccionado == 'Ayudante de Chofer' ? 'selected' : '' }}>🤝 Ayudantes</option>
                    <option value="Mecanico" {{ $rolSeleccionado == 'Mecanico' ? 'selected' : '' }}>🔧 Mecánicos</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Navegación -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <!-- CORRECCIÓN 3: Usar $inicioSemanaActual y $finSemanaActual -->
        <h4 class="mb-0 fw-bold" style="color: #0f2d59;">
            Planificación: {{ $inicioSemanaActual->format('d/m/Y') }} - {{ $finSemanaActual->format('d/m/Y') }}
        </h4>
        <div class="btn-group">
            <a href="{{ route('guardias.index', ['date' => $inicioSemanaActual->copy()->subWeek()->toDateString(), 'rol' => $rolSeleccionado]) }}" class="btn btn-sm btn-outline-secondary">« Semana Ant.</a>
            <a href="{{ route('guardias.index', ['rol' => $rolSeleccionado]) }}" class="btn btn-sm btn-outline-secondary">Semana Actual</a>
            <a href="{{ route('guardias.index', ['date' => $inicioSemanaActual->copy()->addWeek()->toDateString(), 'rol' => $rolSeleccionado]) }}" class="btn btn-sm btn-outline-secondary">Semana Sig. »</a>
        </div>
    </div>

    <div class="row">
        <!-- Sidebar: Personal Disponible -->
        <div class="col-lg-2 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-corporate-primary text-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="fa fa-users me-2"></i> Disponibles</h6>
                </div>
                <div class="card-body p-2">
                    <div class="draggable-list d-flex flex-column gap-2">
                        @forelse($personal as $pers)
                            <!-- Nota: Validamos que exista la relación persona antes de acceder al nombre -->
                            <div class="draggable-badge p-2 rounded shadow-sm d-flex align-items-center gap-2" 
                                 draggable="true" 
                                 ondragstart="drag(event)" 
                                 data-id="{{ $pers->id_personal }}"
                                 data-nombre="{{ $pers->persona ? $pers->persona->nombre : 'Sin Nombre' }}">
                                <i class="fa fa-grip-vertical text-muted" style="cursor: grab;"></i>
                                <span class="small fw-bold text-dark">{{ $pers->persona ? $pers->persona->nombre : 'Sin Nombre' }}</span>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4 small">Sin personal.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendario Grid (3 Semanas) -->
        <div class="col-lg-10">
            <div class="card shadow-sm border-0">
                <div class="card-body p-3 bg-white rounded">
                    <!-- Cabeceras de días -->
                    <div class="calendar-grid mb-2">
                        <div class="calendar-header-day">Lunes</div>
                        <div class="calendar-header-day">Martes</div>
                        <div class="calendar-header-day">Miércoles</div>
                        <div class="calendar-header-day">Jueves</div>
                        <div class="calendar-header-day">Viernes</div>
                        <div class="calendar-header-day">Sábado</div>
                        <div class="calendar-header-day text-danger">Domingo</div>
                    </div>

                    <!-- Celdas de días (21 días) -->
                    <div class="calendar-grid">
                        <!-- CORRECCIÓN 4: Usar $diasCalendario del nuevo controlador -->
                        @foreach($diasCalendario as $dia)
                            @php 
                                $fechaString = $dia->toDateString();
                                $guardiasDelDia = $guardias->get($fechaString) ?? collect();
                                $esSemanaActual = $dia->between($inicioSemanaActual, $finSemanaActual);
                                
                                $claseDia = 'other-week';
                                if ($esSemanaActual) $claseDia = 'current-week';
                                if ($dia->isToday()) $claseDia = 'today';
                            @endphp

                            <div class="calendar-cell {{ $claseDia }}" 
                                 data-date="{{ $fechaString }}"
                                 ondragover="allowDrop(event)" 
                                 ondragleave="dragLeave(event)"
                                 ondrop="drop(event)">
                                 
                                <div class="cell-date-header">
                                    <span class="fs-6 {{ $dia->dayOfWeek == 0 ? 'text-danger' : '' }}">{{ $dia->format('d') }}</span>
                                    <span class="small text-muted">{{ $dia->format('M') }}</span>
                                </div>
                                
                                <div class="drop-container" id="container-{{ $fechaString }}">
                                    @foreach($guardiasDelDia as $guardia)
                                        <div class="assigned-item" id="guardia-{{ $guardia->id }}">
                                            <!-- Verificamos si existe la relación persona antes de mostrar -->
                                            <div class="text-truncate" title="{{ $guardia->personal && $guardia->personal->persona ? $guardia->personal->persona->nombre : 'Desconocido' }}">
                                                {{ $guardia->personal && $guardia->personal->persona ? $guardia->personal->persona->nombre : 'Desconocido' }}
                                            </div>
                                            <button class="btn-remove-guardia" onclick="removeGuardia({{ $guardia->id }})" title="Eliminar">&times;</button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const csrfToken = '{{ csrf_token() }}';
    const activeRol = '{{ $rolSeleccionado }}';

    // Drag & Drop
    function drag(ev) {
        ev.dataTransfer.setData("text/plain", JSON.stringify({
            id: ev.currentTarget.getAttribute('data-id'),
            nombre: ev.currentTarget.getAttribute('data-nombre')
        }));
    }

    function allowDrop(ev) {
        ev.preventDefault();
        const dropZone = ev.currentTarget;
        dropZone.classList.add('drag-over');
    }

    function dragLeave(ev) {
        const dropZone = ev.currentTarget;
        dropZone.classList.remove('drag-over');
    }

    async function drop(ev) {
        ev.preventDefault();
        const dropZone = ev.currentTarget;
        dropZone.classList.remove('drag-over');

        const date = dropZone.getAttribute('data-date');
        const rawData = ev.dataTransfer.getData("text/plain");
        if (!rawData) return;

        const personal = JSON.parse(rawData);
        const container = document.getElementById(`container-${date}`);

        // Evitar duplicados visuales antes de consultar
        if (container.innerHTML.includes(personal.nombre)) {
            alert('El trabajador ya está asignado a este día.');
            return;
        }

        try {
            const response = await fetch('{{ route('guardias.storeAjax') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    personal_id: personal.id, // Enviar personal_id al controlador
                    fecha: date,
                    rol_guardia: activeRol
                })
            });

            const data = await response.json();
            if (data.success) {
                // Crear badge visualmente
                const div = document.createElement('div');
                div.className = 'assigned-item';
                div.id = `guardia-${data.guardia_id}`;
                div.innerHTML = `
                    <div class="text-truncate" title="${data.nombre}">${data.nombre}</div>
                    <button class="btn-remove-guardia" onclick="removeGuardia(${data.guardia_id})" title="Eliminar">&times;</button>
                `;
                container.appendChild(div);
            } else {
                alert(data.message || 'Error guardando guardia.');
            }
        } catch (error) {
            console.error(error);
            alert('Error de conexión.');
        }
    }

    // Remover Guardia con Confirmación
    async function removeGuardia(id) {
        if (!confirm('¿Seguro que deseas remover a este trabajador de la guardia?')) return;

        try {
            const response = await fetch(`/guardias/delete-ajax/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            const data = await response.json();
            if (data.success) {
                document.getElementById(`guardia-${id}`).remove();
            } else {
                alert('No se pudo borrar el registro.');
            }
        } catch (error) {
            console.error(error);
            alert('Error de red al borrar.');
        }
    }
</script>
@endpush
@endsection