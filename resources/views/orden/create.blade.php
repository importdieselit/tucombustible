@extends('layouts.app')

@section('title', 'Crear Nueva Orden de Trabajo')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 shadow-sm rounded">
        <div>
            <h3 class="fw-bold mb-0 text-uppercase"><i class="fas fa-file-signature text-orange me-2"></i>Crear Orden de Trabajo</h3>
            <p class="text-muted mb-0 small">Registro de reparación o mantenimiento para la flota.</p>
        </div>
        <div class="text-end">
            <span class="badge bg-corporate p-2 fs-6">ORDEN NRO: {{$nro_orden}}</span>
        </div>
    </div>

    <form action="{{ route('ordenes.store') }}" method="POST" id="orden-form" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="estatus" value="2">
        <input type="hidden" name="fecha_in" value="{{ date('Y-m-d') }}">
        <input type="hidden" name="nro_orden" value="{{$nro_orden}}">
        <input type="hidden" name="supplies_json" id="supplies_json">
        <input type="hidden" name="trabajos_json" id="trabajos_json">

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card card-step border-orange shadow-sm mb-4">
                    <div class="card-header bg-white fw-bold py-3">
                        <i class="fas fa-truck text-orange me-2"></i>Detalles del Vehículo y Servicio
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="id_vehiculo" class="form-label">Vehículo</label>
                            @if(is_null($vehiculo))
                                <select class="form-select border-2" id="id_vehiculo" name="id_vehiculo">
                                    <option value="">Seleccione Vehículo</option>
                                    @foreach ($vehiculos as $v)
                                        <option value="{{ $v->id }}">{{ $v->flota }} (Placa: {{ $v->placa }})</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="hidden" name="vehiculo_id" value="{{$vehiculo->vehiculo_id}}">
                                <div class="p-2 bg-light border rounded fw-bold text-dark">
                                    {{$vehiculo->flota}} - {{$vehiculo->placa}}
                                </div>
                            @endif
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tipo" class="form-label">Tipo de Orden</label>
                                <select class="form-select" id="tipo" name="tipo" required>
                                    <option value="Preventivo">Preventivo</option>
                                    <option value="Revision">Revision</option>
                                    <option value="Correctivo">Correctivo</option>
                                    <option value="Mantenimiento">Mantenimiento</option>
                                    <option value="Otro">Otros</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="prioridad" class="form-label">Prioridad</label>
                                <select class="form-select" id="prioridad" name="prioridad" required>
                                    <option value="Baja">Baja</option>
                                    <option value="Media" selected>Media</option>
                                    <option value="Alta">Alta</option>
                                    <option value="Crítica">Crítica</option>
                                </select>
                            </div>
                       </div>

                        <div class="mb-3">
                            <label for="descripcion_1" class="form-label">Falla Principal / Título</label>
                            <input type="text" class="form-control" id="descripcion_1" name="descripcion_1" placeholder="Ej: Falla en frenos traseros" required>
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción Detallada</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3" placeholder="Detalle técnico de lo observado..." required></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="responsable" class="form-label">Responsable / Mecánico</label>
                                <input type="text" class="form-control" id="responsable" name="responsable">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="fecha_prometida" class="form-label">Fecha Prometida Entrega</label>
                                <input type="date" class="form-control" id="fecha_prometida" name="fecha_prometida" value="{{ date('Y-m-d', strtotime('+3 days')) }}" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-step shadow-sm border-0">
                    <div class="card-body py-3 bg-light rounded">
                        <label class="form-label mb-2"><i class="fas fa-camera text-orange me-1"></i> Evidencia Fotográfica</label>
                        <input type="file" name="fotos_orden[]" class="form-control border-dashed" accept="image/*" capture="environment" multiple>
                        <small class="text-muted mt-1 d-block">Nota: Puede subir varias fotos simultáneamente.</small>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card card-step border-orange shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <h5 class="m-0 fw-bold text-uppercase small"><i class="fas fa-tools text-orange me-2"></i>Planificación de Trabajos</h5>
                        <button type="button" class="btn btn-sm btn-danger" id="btn-limpiar-trabajos" title="Limpiar lista">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                    <div class="card-body bg-light border-bottom">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="small fw-bold">Categoría</label>
                              
                                <select id="select-categoria" name="id_categoria" class="form-select form-select-sm select2 s2">
                                    <option value="">Seleccione...</option>
                                    @foreach ($categorias_tempario as $cat)

                                        
                                        <option value="{{ $cat->id_tempario_categoria }}">[{{ $cat->codigo }}] {{ $cat->categoria }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="small fw-bold">Servicio / Trabajo</label>
                                <select id="select-servicio" name="id_servicio" class="form-select form-select-sm select2 s2">
                                    <option value="">Seleccione categoría primero</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="small fw-bold">Mecánico(s)</label>
                                <select id="select-mecanicos" name="mecanicos[]" class="form-select form-select-sm s2" multiple>
                                    @foreach ($personal as $p)
                                        <option value="{{ $p->id_personal }}">{{ $p->persona->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 text-end mt-2">
                                <button type="button" class="btn btn-orange btn-sm px-4 fw-bold" id="btn-agregar-trabajo">
                                    <i class="fas fa-plus me-1"></i> AGREGAR TRABAJO
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height: 250px;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-corporate text-white small">
                                <tr>
                                    <th class="ps-3">CONCEPTO</th>
                                    <th>MECÁNICOS ASIGNADOS</th>
                                    <th class="text-center">ACCIÓN</th>
                                </tr>
                            </thead>
                            <tbody id="tabla-trabajos-body">
                                <tr><td colspan="3" class="text-center text-muted py-4 small">No hay trabajos asignados</td></tr>
                            </tbody>
                        </table>
                    </div>
                

                <div class="card card-step shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <h5 class="m-0 fw-bold text-uppercase small" style="letter-spacing: 1px;"><i class="fas fa-box-open text-orange me-2"></i>Lista de Repuestos e Insumos</h5>
                        <div class="btn-group shadow-sm">
                            <button type="button" class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#searchSupplyModal">
                                <i class="fas fa-search me-1"></i> Inventario
                            </button>
                            <button type="button" class="btn btn-outline-dark btn-sm" data-bs-toggle="modal" data-bs-target="#manualSupplyModal">
                                <i class="fas fa-plus me-1"></i> Manual
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="min-height: 300px;">
                            <table class="table table-hover align-middle mb-0 table-supply">
                                <thead>
                                    <tr>
                                        <th class="ps-3 border-0">CÓDIGO</th>
                                        <th class="border-0">DESCRIPCIÓN</th>
                                        <th class="text-end border-0">STOCK</th>
                                        <th class="text-end border-0">CANT.</th>
                                        <th class="text-center border-0">ACCIONES</th>
                                    </tr>
                                </thead>
                                <tbody id="selectedSuppliesTableBody">
                                    {{-- Renderizado dinámico vía JS --}}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="observations-container" class="card-footer bg-white border-top-0" style="display: none;">
                        <label for="supplies_observations" class="form-label small">Observaciones de Repuestos:</label>
                        <textarea class="form-control form-control-sm bg-light" id="supplies_observations" name="supplies_observations" rows="2"></textarea>
                    </div>
                </div>
            </div>
            </div>

            <div class="col-12 mt-4 mb-5">
                <button type="submit" class="btn btn-orange btn-lg w-100 shadow-lg fw-bold py-3 text-uppercase" style="letter-spacing: 2px;">
                    <i class="fas fa-save me-2 text-white"></i> Procesar y Guardar Orden
                </button>
            </div>
        </div>
    </form>
</div>

<div class="modal fade" id="searchSupplyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-corporate text-white">
                <h5 class="modal-title fw-bold">CATÁLOGO DE INVENTARIO</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" id="supplySearchInput" placeholder="Escriba código o nombre del producto...">
                </div>
                <div class="table-responsive" style="max-height: 400px;">
                    <table class="table table-sm table-hover border">
                        <thead class="bg-light sticky-top">
                            <tr class="small text-muted">
                                <th>CÓDIGO</th>
                                <th>DESCRIPCIÓN</th>
                                <th class="text-center">EXISTENCIA</th>
                                <th class="text-center" style="width: 100px;">CANT.</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="searchResultsTableBody">
                            {{-- Resultados de búsqueda AJAX --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="manualSupplyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title fw-bold">SUMINISTRO NO CATALOGADO</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="mb-3">
                    <label class="form-label">Descripción del Repuesto</label>
                    <input type="text" class="form-control" id="manual-descripcion" placeholder="Ej: Tornillo grado 8 1/2 pulgada">
                </div>
                <div class="mb-3">
                    <label class="form-label">Cantidad Requerida</label>
                    <input type="number" class="form-control text-end fw-bold" id="manual-cantidad" value="1" min="1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success w-100 fw-bold" id="addManualSupplyBtn">AÑADIR A LA LISTA</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $(document).ready(function() {
        // --- TUS VARIABLES ORIGINALES ---
        let selectedSupplies = {};
        let manualSupplyCounter = 0;
        let trabajosAsignados = [];

        const selectedSuppliesTableBody = document.getElementById('selectedSuppliesTableBody');
        const searchInput = document.getElementById('supplySearchInput');
        const searchResultsBody = document.getElementById('searchResultsTableBody');
        const suppliesJsonInput = document.getElementById('supplies_json');
        const addManualSupplyBtn = document.getElementById('addManualSupplyBtn');

        // --- TU LÓGICA AJAX ---
        function debounce(func, timeout = 300) {
            let timer;
            return (...args) => {
                clearTimeout(timer);
                timer = setTimeout(() => { func.apply(this, args); }, timeout);
            };
        }

        function performSupplySearch() {
            const query = searchInput.value.trim();
            if (query.length < 3) {
                searchResultsBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted p-4">Ingrese al menos 3 caracteres para buscar.</td></tr>';
                return;
            }
            $.ajax({
                url: '{{ route("ordenes.search-supplies") }}',
                method: 'GET',
                data: { query: query },
                success: function(response) { renderSearchResults(response); }
            });
        }

        searchInput.addEventListener('input', debounce(performSupplySearch, 300));

        // --- TUS FUNCIONES DE RENDERIZADO ---
        function renderSearchResults(data) {
            let html = '';
            if(data.length === 0) {
                html = '<tr><td colspan="5" class="text-center text-danger p-4">No se encontraron productos.</td></tr>';
            } else {
                data.forEach(item => {
                    html += `
                        <tr class="align-middle">
                            <td class="fw-bold">${item.codigo}</td>
                            <td>${item.descripcion}</td>
                            <td class="text-center"><span class="badge ${item.existencia > 0 ? 'bg-success' : 'bg-danger'}">${item.existencia}</span></td>
                            <td><input type="number" class="form-control form-control-sm search-quantity text-center fw-bold" data-item-id="${item.id}" value="1" min="1"></td>
                            <td class="text-end"><button type="button" class="btn btn-sm btn-orange add-supply" data-item-id="${item.id}"><i class="fas fa-plus"></i></button></td>
                        </tr>`;
                });
            }
            searchResultsBody.innerHTML = html;
        }

        function renderSuppliesTable() {
            let html = '';
            const suppliesArray = Object.values(selectedSupplies);
            const obsContainer = document.getElementById('observations-container');

            if (suppliesArray.length === 0) {
                html = '<tr><td colspan="5" class="text-center text-muted py-5">No se han añadido repuestos a esta orden.</td></tr>';
                if(obsContainer) obsContainer.style.display = 'none';
            } else {
                if(obsContainer) obsContainer.style.display = 'block';
                suppliesArray.forEach(item => {
                    html += `
                        <tr class="align-middle">
                            <td class="ps-3 fw-bold text-muted">${item.codigo}</td>
                            <td class="small fw-bold">${item.descripcion}</td>
                            <td class="text-end"><span class="text-muted small">${item.id.toString().startsWith('MANUAL') ? '-' : item.existencia}</span></td>
                            <td class="text-end fw-bold text-orange">${item.cantidad}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-link btn-sm text-danger remove-supply" data-item-id="${item.id}"><i class="fas fa-times-circle"></i></button>
                            </td>
                        </tr>`;
                });
            }
            selectedSuppliesTableBody.innerHTML = html;
            suppliesJsonInput.value = JSON.stringify(suppliesArray);
        }

        // --- MANEJO DE EVENTOS ---
        $(document).on('click', '.add-supply', function() {
            const itemId = $(this).data('item-id');
            const row = $(this).closest('tr');
            const quantity = parseInt(row.find('.search-quantity').val());

            selectedSupplies[itemId] = {
                id: itemId,
                codigo: row.find('td:eq(0)').text(),
                descripcion: row.find('td:eq(1)').text(),
                existencia: parseInt(row.find('td:eq(2)').text()),
                cantidad: quantity
            };
            renderSuppliesTable();
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Agregado al listado', showConfirmButton: false, timer: 1500 });
        });

        addManualSupplyBtn.addEventListener('click', function() {
            const desc = $('#manual-descripcion').val();
            const cant = parseInt($('#manual-cantidad').val());
            if(!desc || cant <= 0) {
                Swal.fire('Atención', 'Debe ingresar descripción y cantidad válida', 'warning');
                return;
            }

            manualSupplyCounter++;
            const id = 'MANUAL_' + manualSupplyCounter;
            selectedSupplies[id] = { id: id, codigo: 'MANUAL', descripcion: desc, cantidad: cant, existencia: 0 };
            renderSuppliesTable();
            $('#manual-descripcion').val('');
            $('#manualSupplyModal').modal('hide');
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Agregado Manual', showConfirmButton: false, timer: 1500 });
        });

        $(document).on('click', '.remove-supply', function() {
            const id = $(this).data('item-id');
            delete selectedSupplies[id];
            renderSuppliesTable();
        });

        // Asegurar que el JSON se envíe al procesar
        $('#orden-form').on('submit', function() {
            suppliesJsonInput.value = JSON.stringify(Object.values(selectedSupplies));
            return true;
        });

        renderSuppliesTable();


            // 1. CARGA DINÁMICA DE TEMPARIO
        $('#select-categoria').on('change', function() {
            const catId = $(this).val();
            if (!catId) return;

            $.post('{{ route("get.tempario_servicios") }}', { catemp: catId }, function(data) {
                $('#select-servicio').html(data);
            });
        });

        // 2. AGREGAR TRABAJO A LA LISTA
        $('#btn-agregar-trabajo').on('click', function() {
            const servicioId = $('#select-servicio').val();
            const categoriaId = $('#select-categoria').val();
            const servicioTexto = $('#select-servicio option:selected').text();
            const mecanicosIds = $('#select-mecanicos').val();
            const mecanicosNombres = $('#select-mecanicos option:selected').map(function(){ return $(this).text(); }).get();

            if (!servicioId || mecanicosIds.length === 0) {
                Swal.fire('Error', 'Debe seleccionar un servicio y al menos un mecánico', 'error');
                return;
            }

            const nuevoTrabajo = {
                id_tempario: servicioId,
                id_categoria: categoriaId,
                concepto: servicioTexto,
                mecanicos: mecanicosIds,
                mecanicos_nombres: mecanicosNombres.join(', ')
            };

            trabajosAsignados.push(nuevoTrabajo);
            renderTrabajos();
            
            // Limpiar selectores
            $('#select-mecanicos').val(null).trigger('change');
        });

        function renderTrabajos() {
            let html = '';
            if (trabajosAsignados.length === 0) {
                html = '<tr><td colspan="3" class="text-center text-muted py-4 small">No hay trabajos asignados</td></tr>';
            } else {
                trabajosAsignados.forEach((t, index) => {
                    html += `
                    <tr class="small">
                        <td class="ps-3 fw-bold">${t.concepto}</td>
                        <td><span class="text-muted">${t.mecanicos_nombres}</span></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-link btn-sm text-danger btn-remove-trabajo" data-index="${index}">
                                <i class="fas fa-times-circle"></i>
                            </button>
                        </td>
                    </tr>`;
                });
            }
            $('#tabla-trabajos-body').html(html);
            $('#trabajos_json').val(JSON.stringify(trabajosAsignados));
        }

        $(document).on('click', '.btn-remove-trabajo', function() {
            const index = $(this).data('index');
            trabajosAsignados.splice(index, 1);
            renderTrabajos();
        });

        $('#btn-limpiar-trabajos').on('click', function() {
            if(confirm('¿Desea limpiar todos los trabajos?')) {
                trabajosAsignados = [];
                renderTrabajos();
            }
        });
    });
</script>
@endpush    