@extends('layouts.app')

@section('title', 'Editar Orden de Trabajo #' . $item->nro_orden)

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        .border-orange { border-top: 3px solid #e67e22 !important; }
        .text-orange { color: #e67e22 !important; }
        .bg-corporate { background-color: #2c3e50 !important; color: white; }
        .btn-corporate { background-color: #e67e22; color: white; font-weight: bold; }
        #map { height: 300px; width: 100%; border-radius: 8px; border: 2px solid #ddd; }
        .select2-container--bootstrap-5 .select2-selection { border-radius: 0.375rem; }
    </style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 shadow-sm rounded">
        <div>
            <h3 class="fw-bold mb-0 text-uppercase">
                <i class="fas fa-file-signature text-orange me-2"></i>Editar Orden de Trabajo
            </h3>
            <p class="text-muted mb-0 small">Modificación de registros, diagnósticos y repuestos asignados.</p>
        </div>
        <div class="text-end">
            <span class="badge bg-corporate p-2 fs-6">ORDEN NRO: {{ $item->nro_orden }}</span>
        </div>
    </div>

    <form action="{{ route('ordenes.update', $item->id) }}" method="POST" id="orden-form" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        
        <input type="hidden" name="latitud" id="latitud" value="{{ $item->latitud }}">
        <input type="hidden" name="longitud" id="longitud" value="{{ $item->longitud }}">

        <div class="row g-4">
            {{-- SECCIÓN 1: DATOS DE LA UNIDAD --}}
            <div class="col-md-4">
                <div class="card h-100 shadow-sm border-orange">
                    <div class="card-header bg-white fw-bold"><i class="fas fa-truck me-2 text-orange"></i>Datos de la Unidad</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Vehículo / Equipo</label>
                            <select name="id_vehiculo" class="form-select select2-vehiculos" required>
                                <option value="{{ $item->id_vehiculo }}" selected>
                                    {{ $item->vehiculo()->flota }} - {{ $item->vehiculo()->placa }} ({{ $item->vehiculo()->marca()->marca }})
                                </option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Kilometraje al Ingreso</label>
                            <input type="number" name="kilometraje" class="form-control" value="{{ $item->kilometraje }}" required>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-bold">Prioridad</label>
                            <select name="prioridad" class="form-select">
                                <option value="Baja" {{ $item->prioridad == 'Baja' ? 'selected' : '' }}>Baja</option>
                                <option value="Media" {{ $item->prioridad == 'Media' ? 'selected' : '' }}>Media</option>
                                <option value="Alta" {{ $item->prioridad == 'Alta' ? 'selected' : '' }}>Alta</option>
                                <option value="Crítica" {{ $item->prioridad == 'Crítica' ? 'selected' : '' }}>Crítica</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SECCIÓN 2: UBICACIÓN --}}
            <div class="col-md-8">
                <div class="card h-100 shadow-sm border-orange">
                    <div class="card-header bg-white fw-bold"><i class="fas fa-map-marker-alt me-2 text-orange"></i>Ubicación del Servicio</div>
                    <div class="card-body">
                        <div class="input-group mb-3">
                            <input type="text" id="search-input" class="form-control" placeholder="Buscar dirección o lugar..." value="{{ $item->direccion }}">
                            <button class="btn btn-corporate" type="button" id="search-btn"><i class="fas fa-search"></i></button>
                        </div>
                        <div id="map"></div>
                        <input type="hidden" name="direccion" id="input-direccion" value="{{ $item->direccion }}">
                    </div>
                </div>
            </div>

            {{-- SECCIÓN 3: DETALLES --}}
            <div class="col-md-12">
                <div class="card shadow-sm border-orange">
                    <div class="card-header bg-white fw-bold"><i class="fas fa-tools me-2 text-orange"></i>Detalles Técnicos y Diagnóstico</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label small fw-bold">Tipo de Servicio</label>
                                <input type="text" name="tipo" class="form-control" value="{{ $item->tipo }}" placeholder="Ej: Preventivo, Correctivo...">
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label small fw-bold">Título / Resumen</label>
                                <input type="text" name="descripcion_1" class="form-control" value="{{ $item->descripcion_1 }}" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Observaciones / Informe Detallado</label>
                                <textarea name="descripcion" class="form-control" rows="4">{{ $item->descripcion }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

     {{-- SECCIÓN: TRABAJOS Y TAREAS REALIZADAS --}}
<div class="col-md-12">
    <div class="card shadow-sm border-orange">
        <div class="card-header bg-white fw-bold">
            <i class="fas fa-wrench me-2 text-orange"></i>Asignación de Trabajos y Personal
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="small fw-bold">Categoría de Servicio</label>
                    <select id="select-categoria" class="form-select form-select-sm select2">
                        <option value="">Seleccione...</option>
                        @foreach ($categorias_tempario as $cat)
                            <option value="{{ $cat->id_tempario_categoria }}" data-context="{{ $cat->id_tipo_req }}">
                                [{{ $cat->codigo }}] {{ $cat->categoria }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small fw-bold">Tarea / Trabajo Específico</label>
                    <select id="select-servicio" class="form-select form-select-sm select2" disabled>
                        <option value="">Seleccione categoría primero</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small fw-bold">Personal Asignado</label>
                    <select id="select-mecanicos" class="form-select form-select-sm select2" multiple>
                        @foreach ($personal as $p)
                            <option value="{{ $p->id_personal }}">{{ $p->persona->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 text-end">
                    <button type="button" class="btn btn-corporate btn-sm fw-bold px-4" id="btn-agregar-trabajo">
                        <i class="fas fa-plus me-1"></i> ASIGNAR TAREA
                    </button>
                </div>
            </div>

            <hr class="my-4">

            <div class="table-responsive">
                <table class="table table-bordered align-middle" id="tabla-trabajos-asignados">
                    <thead class="bg-light">
                        <tr class="small text-uppercase">
                            <th>Categoría / Tarea</th>
                            <th>Personal Asignado</th>
                            <th width="80" class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tasks-container">
                        {{-- Se llena con JS --}}
                    </tbody>
                </table>
                <div id="no-tasks-msg" class="text-muted text-center small mt-4 d-none"></div>
            </div>
        </div>
    </div>
</div>

            {{-- SECCIÓN 4: INSUMOS DINÁMICOS --}}
            <div class="col-md-12">
                <div class="card shadow-sm border-orange">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <span class="fw-bold"><i class="fas fa-boxes me-2 text-orange"></i>Repuestos e Insumos</span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-9">
                                <select id="supply-search" class="form-select select2-supplies">
                                    <option value="">Buscar repuesto por código o nombre...</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="button" id="add-supply-btn" class="btn btn-corporate w-100">
                                    <i class="fas fa-plus me-1"></i> Agregar
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="selected-supplies-table">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Código</th>
                                        <th>Descripción</th>
                                        <th width="120">Cantidad</th>
                                        <th width="150">P. Unitario</th>
                                        <th width="150">Subtotal</th>
                                        <th width="50"></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                                <tfoot>
                                    <tr class="table-dark">
                                        <td colspan="4" class="text-end fw-bold text-uppercase">Total Insumos:</td>
                                        <td colspan="2" class="fw-bold" id="total-amount">$ 0.00</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 text-end">
            <a href="{{ route('ordenes.index') }}" class="btn btn-outline-secondary px-4 me-2">Cancelar</a>
            <button type="submit" class="btn btn-corporate px-5 shadow-sm">
                <i class="fas fa-save me-2"></i> ACTUALIZAR ORDEN DE TRABAJO
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- LÓGICA DEL MAPA (IDENTICA AL CREATE) ---
        const initialLat = {{ $item->latitud ?? 10.6447 }};
        const initialLon = {{ $item->longitud ?? -71.6105 }};
        
        const map = L.map('map').setView([initialLat, initialLon], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        let marker = L.marker([initialLat, initialLon], { draggable: true }).addTo(map);

        function updateCoords(lat, lon) {
            document.getElementById('latitud').value = lat;
            document.getElementById('longitud').value = lon;
            marker.setLatLng([lat, lon]);
            map.panTo([lat, lon]);
        }

        marker.on('dragend', function(e) {
            const pos = e.target.getLatLng();
            updateCoords(pos.lat, pos.lng);
        });

        // --- BÚSQUEDA DE UBICACIÓN ---
        $('#search-btn').click(function() {
            const query = $('#search-input').val();
            if (query.length < 3) return;
            
            $.getJSON(`https://nominatim.openstreetmap.org/search?format=json&q=${query}`, function(data) {
                if (data && data.length > 0) {
                    const res = data[0];
                    updateCoords(res.lat, res.lon);
                    $('#input-direccion').val(res.display_name);
                }
            });
        });

        // --- GESTIÓN DE INSUMOS (CARGA INICIAL) ---
        let selectedSupplies = {};

        // Precargar insumos existentes
        @foreach($insumos as $insumo)
            selectedSupplies["{{ $insumo->id_inventario }}"] = {
                id: "{{ $insumo->id_inventario }}",
                codigo: "{{ $insumo->inventario->codigo }}",
                descripcion: "{{ $insumo->inventario->descripcion }}",
                cantidad: {{ $insumo->cantidad }},
                precio: {{ $insumo->precio_unitario ?? 0 }},
                subtotal: {{ $insumo->subtotal }}
            };
        @endforeach
        @foreach($requerimientos as $requerimiento)
            @foreach($requerimiento->detalles as $detalle)
                selectedSupplies["C-{{ $detalle->id }}"] = {
                id: "{{ $detalle->id     }}",
                codigo: "",
                descripcion: "{{ $detalle->descripcion }}",
                cantidad: {{ $detalle->cantidad_aprobada ?? $detalle->cantidad_solicitada }},
                precio: {{ $detalle->costo_unitario_aprobado ?? 0 }},
                subtotal: {{ ($detalle->cantidad_aprobada ?? $detalle->cantidad_solicitada) * ($detalle->costo_unitario_aprobado)}}
                };
            @endforeach
        @endforeach
                

        function renderSuppliesTable() {
            console.log(selectedSupplies);
            console.log(Object.values(selectedSupplies));
            const tbody = document.querySelector('#selected-supplies-table tbody');
            tbody.innerHTML = '';
            let total = 0;

            Object.values(selectedSupplies).forEach(item => {
                total += item.subtotal;
                tbody.innerHTML += `
                    <tr>
                        <td class="small fw-bold text-orange">${item.codigo}</td>
                        <td class="small">${item.descripcion}</td>
                        <td>
                            <input type="number" class="form-control form-control-sm qty-input" 
                                   data-id="${item.id}" value="${item.cantidad}" min="1">
                        </td>
                        <td><input type="number" step="0.01" class="form-control form-control-sm price-input" 
                                   data-id="${item.id}" value="${item.precio}"></td>
                        <td class="fw-bold">$ ${item.subtotal.toFixed(2)}</td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-supply" data-id="${item.id}">
                                <i class="fas fa-times"></i>
                            </button>
                            <input type="hidden" name="supplies[${item.id}][id]" value="${item.id}">
                            <input type="hidden" name="supplies[${item.id}][cantidad]" value="${item.cantidad}">
                            <input type="hidden" name="supplies[${item.id}][precio]" value="${item.precio}">
                        </td>
                    </tr>
                `;
            });
            document.getElementById('total-amount').innerText = `$ ${total.toFixed(2)}`;
        }

        renderSuppliesTable();

        // (Resto de la lógica de agregar/quitar insumos idéntica a tu create...)
        $('#add-supply-btn').click(function() {
            const data = $('#supply-search').select2('data')[0];
            if (!data) return;
            
            const id = data.id;
            if (selectedSupplies[id]) {
                selectedSupplies[id].cantidad++;
            } else {
                selectedSupplies[id] = {
                    id: id,
                    codigo: data.codigo,
                    descripcion: data.text,
                    cantidad: 1,
                    precio: data.precio || 0,
                    subtotal: data.precio || 0
                };
            }
            selectedSupplies[id].subtotal = selectedSupplies[id].cantidad * selectedSupplies[id].precio;
            renderSuppliesTable();
        });

        $(document).on('click', '.remove-supply', function() {
            delete selectedSupplies[$(this).data('id')];
            renderSuppliesTable();
        });

        // --- GESTIÓN DE TRABAJOS REALIZADOS ---
        let performedTasks = [];

        // 1. Precargar trabajos existentes de la base de datos
        @if(isset($trabajos) && $trabajos->count() > 0)
            @foreach($trabajos as $trabajo)
                performedTasks.push("{{ $trabajo->descripcion }}");
            @endforeach
        @endif

        function renderTasksTable() {
            const container = document.getElementById('tasks-container');
            const noTasksMsg = document.getElementById('no-tasks-msg');
            const inHTML = '';

            if (performedTasks.length === 0) {
                noTasksMsg.classList.remove('d-none');
            } else {
                noTasksMsg.classList.add('d-none');
                performedTasks.forEach((task, index) => {
                    inHTML += `
                        <tr>
                            <td class="text-center fw-bold text-muted small">${index + 1}</td>
                            <td>
                                <span class="text-dark">${task}</span>
                                <input type="hidden" name="trabajos[]" value="${task}">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="removeTask(${index})">
                                    <i class="fas fa-trash-can"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
            }
            container.innerHTML = inHTML;
        }

        // Inicializar tabla al cargar
        renderTasksTable();

        // Agregar nueva tarea
        $('#add-task-btn').click(function() {
            const taskInput = $('#task-input');
            const taskValue = taskInput.val().trim();

            if (taskValue === "") {
                taskInput.addClass('is-invalid').focus();
                return;
            }

            taskInput.removeClass('is-invalid');
            performedTasks.push(taskValue);
            taskInput.val(''); // Limpiar input
            renderTasksTable();
        });

        // Función global para eliminar tarea
        window.removeTask = function(index) {
            performedTasks.splice(index, 1);
            renderTasksTable();
        };

        // Permitir agregar con la tecla Enter
        $('#task-input').keypress(function(e) {
            if(e.which == 13) {
                e.preventDefault();
                $('#add-task-btn').click();
            }
        });

           // 1. CARGA DINÁMICA DE TEMPARIO
        $('#select-categoria').on('change', function() {
            const catId = $(this).val();
            if (!catId) return;

            $.post('{{ route("get.tempario_servicios") }}', { catemp: catId }, function(data) {
                $('#select-servicio').html(data);
            });
        });
    });
</script>
@endpush