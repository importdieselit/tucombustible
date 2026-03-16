@extends('layouts.app')

@section('title', 'Crear Nueva Orden de Trabajo')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 shadow-sm rounded">
        <div>
            <h3 class="fw-bold mb-0 text-uppercase"><i class="fas fa-file-signature text-orange me-2"></i>Crear Orden de Trabajo</h3>
            <p class="text-muted mb-0 small">Registro dinámico de reparaciones, mantenimientos y servicios generales.</p>
        </div>
        <div class="text-end">
            <span class="badge bg-corporate p-2 fs-6">ORDEN NRO: {{$nro_orden}}</span>
        </div>
    </div>

    <form action="{{ route('ordenes.store') }}" method="POST" id="orden-form" enctype="multipart/form-data">
        @csrf
        {{-- Campos ocultos de control --}}
        <input type="hidden" name="estatus" value="2">
        <input type="hidden" name="fecha_in" value="{{ date('Y-m-d') }}">
        <input type="hidden" name="nro_orden" value="{{$nro_orden}}">
        <input type="hidden" name="supplies_json" id="supplies_json">
        <input type="hidden" name="trabajos_json" id="trabajos_json">

        {{-- 1. SELECTOR MAESTRO DE TIPO DE ORDEN --}}
        <div class="card card-step border-orange shadow-sm mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-orange"><i class="fas fa-list-ul me-2"></i>Tipo de Requerimiento</label>
                        <select name="id_tipo_req" id="id_tipo_req" class="form-select form-select-lg fw-bold border-orange">
                           @foreach($tipo_req as $tipo)
                                <option value="{{$tipo->id}}" @if($tipo->id==1) selected @endif>{{$tipo->tipo}}</option>
                           @endforeach
                          </select>
                    </div>
                    <div class="col-md-6" id="group-sede" >
                        <label class="form-label fw-bold">Sede / Ubicación Administrativa</label>
                        <select name="id_sede" class="form-select border-2">
                            <option value="1" selected>Principal - Boleita</option>
                            @foreach($sedes ?? [] as $sede)
                                <option value="{{ $sede->id }}">{{ $sede->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            {{-- COLUMNA IZQUIERDA: DATOS DE CABECERA --}}
            <div class="col-lg-6">
                <div class="card card-step border-orange shadow-sm mb-4">
                    <div class="card-header bg-white fw-bold py-3">
                        <i class="fas fa-info-circle text-orange me-2"></i>Detalles del Servicio
                    </div>
                    <div class="card-body">
                        
                        {{-- BLOQUE VEHÍCULO (Condicional) --}}
                        <div class="section-dinamica mb-3" id="group-vehiculo">
                            <label for="id_vehiculo" class="form-label fw-bold">Vehículo / Unidad</label>
                            <select class="form-select select2 h-100" style="height: 100px" id="id_vehiculo" name="id_vehiculo">
                                <option value="">Buscar unidad...</option>
                                @foreach ($vehiculos as $v)
                                    <option value="{{ $v->id }}" data-km="{{ $v->kilometraje }}">[{{ $v->flota }}] {{ $v->placa }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- BLOQUE UBICACIÓN / TALLER (Condicional) --}}
                        <div class="section-dinamica mb-3" id="group-ubicacion-externa" style="display:none;">
                            <label class="form-label fw-bold" id="label-ubicacion">Ubicación del Trabajo</label>
                            <div class="input-group mb-2">
                                <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                <input type="text" name="descripcion_2" class="form-control" placeholder="Ej: Carretera Nacional / Planta Cliente">
                            </div>
                            
                            <div id="subgroup-taller-ext" style="display:none;">
                                <label class="form-label small fw-bold mt-2">Taller Externo Responsable</label>
                                <div class="input-group">
                                    <select name="id_taller_externo" class="form-select">
                                        <option value="">Seleccione Taller...</option>
                                        @foreach ($talleres as $t)
                                            <option value="{{ $t->id }}">{{ $t->nombre }}</option>
                                                                                    
                                        @endforeach
                                    </select>
                                    <button type="button" class="btn btn-outline-dark"><i class="fas fa-plus"></i></button>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="tipo" class="form-label fw-bold">Sub-Tipo</label>
                                <select class="form-select" id="tipo" name="tipo" required>
                                    <option value="Preventivo">Preventivo</option>
                                    <option value="Correctivo" selected>Correctivo</option>
                                    <option value="Mantenimiento">Mantenimiento</option>
                                    <option value="Otro">Otro / General</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3" id="group-km">
                                <label for="kilometraje" class="form-label fw-bold">Kilometraje</label>
                                <input type="number" class="form-control fw-bold border-orange" id="kilometraje" name="kilometraje" placeholder="0">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="descripcion_1" class="form-label fw-bold">Título del Requerimiento</label>
                            <input type="text" class="form-control" id="descripcion_1" name="descripcion_1" placeholder="Ej: Falla en frenos / Reparación de aire acondicionado" required>
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label fw-bold">Descripción / Alcance</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3" placeholder="Detalles específicos del servicio..." required></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="responsable" class="form-label fw-bold">Solicitante / Responsable</label>
                                <input type="text" class="form-control" id="responsable" name="responsable" value="{{ Auth::user()->name }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="fecha_prometida" class="form-label fw-bold">Fecha Estimada Final</label>
                                <input type="date" class="form-control" id="fecha_prometida" name="fecha_prometida" value="{{ date('Y-m-d', strtotime('+2 days')) }}">
                            </div>
                        </div>

                        <div class="bg-light p-3 rounded border-dashed mt-2">
                            <label class="form-label fw-bold small"><i class="fas fa-camera me-1"></i> Fotos Iniciales</label>
                            <input type="file" name="fotos_orden[]" class="form-control form-control-sm" accept="image/*" multiple>
                        </div>
                    </div>
                </div>
            </div>

            {{-- COLUMNA DERECHA: TRABAJOS E INSUMOS --}}
            <div class="col-lg-6">
                {{-- SECCIÓN TRABAJOS --}}
                <div class="card card-step border-orange shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <h5 class="m-0 fw-bold text-uppercase small"><i class="fas fa-tools text-orange me-2"></i>Planificación de Tareas</h5>
                        <button type="button" class="btn btn-sm btn-outline-danger border-0" id="btn-limpiar-trabajos"><i class="fas fa-trash"></i></button>
                    </div>
                    <div class="card-body bg-light border-bottom">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="small fw-bold">Categoría de Servicio</label>
                                <select id="select-categoria" class="form-select form-select-sm select2">
                                    <option value="">Seleccione...</option>
                                    @foreach ($categorias_tempario as $cat)
                                        {{-- El backend debe proveer un 'contexto' para filtrar: vehiculo, infraestructura, etc --}}
                                        <option value="{{ $cat->id_tempario_categoria }}" data-context="{{ $cat->id_tipo_req }}">
                                            [{{ $cat->codigo }}] {{ $cat->categoria }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="small fw-bold">Tarea / Trabajo Específico</label>
                                <select id="select-servicio" class="form-select form-select-sm select2">
                                    <option value="">Seleccione categoría primero</option>
                                </select>
                            </div>
                            <div class="col-12 mt-2">
                                <label class="small fw-bold">Personal Asignado</label>
                                <select id="select-mecanicos" class="form-select form-select-sm" multiple>
                                    @foreach ($personal as $p)
                                        <option value="{{ $p->id_personal }}">{{ $p->persona->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 text-end mt-2">
                                <button type="button" class="btn btn-orange btn-sm fw-bold px-4" id="btn-agregar-trabajo">
                                    <i class="fas fa-plus me-1"></i> ASIGNAR TAREA
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height: 200px;">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="bg-corporate text-white x-small">
                                <tr>
                                    <th class="ps-3">CONCEPTO</th>
                                    <th>RESPONSABLES</th>
                                    <th class="text-center"></th>
                                </tr>
                            </thead>
                            <tbody id="tabla-trabajos-body">
                                <tr><td colspan="3" class="text-center text-muted py-4 small">No hay tareas asignadas</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- SECCIÓN INSUMOS --}}
                <div class="card card-step shadow-sm border-orange">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <h5 class="m-0 fw-bold text-uppercase small"><i class="fas fa-box-open text-orange me-2"></i>Repuestos e Insumos</h5>
                        <div class="btn-group">
                            <button type="button" class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#searchSupplyModal"><i class="fas fa-search"></i></button>
                            <button type="button" class="btn btn-outline-dark btn-sm" data-bs-toggle="modal" data-bs-target="#manualSupplyModal"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="min-height: 250px;">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead>
                                    <tr class="bg-light text-muted">
                                        <th class="ps-3">CÓDIGO</th>
                                        <th>DESCRIPCIÓN</th>
                                        <th class="text-end">CANT.</th>
                                        <th class="text-center"></th>
                                    </tr>
                                </thead>
                                <tbody id="selectedSuppliesTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 mt-4 mb-5">
                <button type="submit" class="btn btn-orange btn-lg w-100 shadow fw-bold py-3 text-uppercase">
                    <i class="fas fa-save me-2"></i> Procesar Orden de Trabajo
                </button>
            </div>
        </div>
    </form>
</div>
<

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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $(document).ready(function() {

        var checkSelect2 = setInterval(function() {
        if ($.isFunction($.fn.select2)) {
            clearInterval(checkSelect2); // Detener la espera
            
            // AQUÍ inicializas todo
            $('.select2').select2({
                theme: 'bootstrap-5',
                
            });
            console.log("Select2 cargado y listo");
        }else{                                                                                                                                                                                                                          
            console.log("Esperando a que Select2 esté disponible...");
        }
    }, 100); //

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


// --- 1. LÓGICA DEL ORQUESTADOR DINÁMICO ---
    const $tipoMaestro = $('#id_tipo_req');
    
    function adaptarFormulario() {
        const tipo = $tipoMaestro.val();
       
        // Reset inicial
        $('.section-dinamica, #subgroup-taller-ext').hide();
        $('#group-km').fadeIn(); // Mostrar por defecto, ocultar solo en infraestructura
        
        switch(tipo) {
            case '1':
                console.log('Tipo 1 seleccionado');
                $('#group-vehiculo').fadeIn();
                break;
                
            case '2':
                console.log('Tipo 2 seleccionado');
                $('#group-vehiculo, #group-ubicacion-externa').fadeIn();
                $('#label-ubicacion').text('Ubicación del Auxilio (Punto de Falla)');
                break;
                
            case '3':
                console.log('Tipo 3 seleccionado');
                $('#group-vehiculo, #group-ubicacion-externa, #subgroup-taller-ext').fadeIn();
                $('#label-ubicacion').text('Datos del Taller Externo');
                break;
                
            case '4':
                console.log('Tipo 4 seleccionado');
                $('#group-ubicacion-externa, #group-km').toggle(); // Ocultamos KM en infra
                $('#group-ubicacion-externa').fadeIn();
                $('#label-ubicacion').text('Área / Oficina específica');
                $('#group-km').hide();
                break;
                
            case '5':
                console.log('Tipo 5 seleccionado');
                $('#group-ubicacion-externa').fadeIn();
                $('#label-ubicacion').text('Ubicación / Cliente Final');
                $('#group-km').hide();
                break;
        }

        filterTempario(tipo);
    }

    // Filtrado de categorías basado en el contexto
    function filterTempario(tipoMaestro) {
        const $catSelect = $('#select-categoria');
        $catSelect.val(null).trigger('change');
        console.log('tipo maestro: '+ tipoMaestro);
        // Mapeo simple: vehiculo, auxilio y taller externo usan contexto 'vehiculo'
        // servicios_generales usa 'infraestructura', etc.
        const contextoBuscado = (tipoMaestro === 4) ? 2 : 1;

        $('#select-categoria option').each(function() {
            const optContext = $(this).data('context');
            console.log('context: '+ optContext);
            // if (!optContext || optContext === contextoBuscado) {
            //     $(this).prop('disabled', false);
            // } else {
            //     $(this).prop('disabled', true);
            // }
        });
        if (typeof $.fn.select2 !== 'undefined') {
            $catSelect.select2(); 
        }
        
    }

    $tipoMaestro.on('change', adaptarFormulario);
    adaptarFormulario(); // Inicializar



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