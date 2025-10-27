@extends('layouts.app')

@section('title', 'Crear Nueva Orden de Trabajo')
@push('scripts')
<!-- jQuery para manejar las llamadas AJAX -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap JS para el modal -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="mb-2">Crear Orden de Trabajo</h1>
        <p class="text-muted">Registra una nueva orden de reparación o mantenimiento.</p>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="card-title m-0">Datos de la Orden</h5>
    </div>
    <div class="card-body">
        {{-- Se añade un campo oculto para el modo de guardado --}}
        <form action="{{ route('ordenes.store') }}" method="POST" id="orden-form" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="estatus" value="2"> {{-- Estatus "Abierta" --}}
            <input type="hidden" name="fecha_in" value="{{ date('Y-m-d') }}">
            <input type="hidden" name="usuario_id" value="{{ Auth::id() }}">

            {{-- Sección de Datos Principales (Ejemplo, usa tus campos reales aquí) --}}
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="id_vehiculo" class="form-label">Vehículo</label>
                    <select class="form-select" id="id_vehiculo" name="id_vehiculo" required>
                        <option value="">Seleccione un vehículo</option>
                        {{-- @foreach ($vehiculos as $vehiculo)
                            <option value="{{ $vehiculo->id }}">{{ $vehiculo->flota }} - {{ $vehiculo->placa }}</option>
                        @endforeach --}}
                         <option value="1">Flota 1 - ABC-123</option>
                         <option value="2">Flota 2 - DEF-456</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="tipo" class="form-label">Tipo de Orden</label>
                    <select class="form-select" id="tipo" name="tipo" required>
                        <option value="Mantenimiento">Mantenimiento</option>
                        <option value="Reparación">Reparación</option>
                    </select>
                </div>
                <div class="col-12 mb-3">
                    <label for="descripcion_1" class="form-label">Resumen del Trabajo</label>
                    <input type="text" class="form-control" id="descripcion_1" name="descripcion_1" required placeholder="Eje: Cambio de aceite y filtros">
                </div>
                <div class="col-12 mb-3">
                    <label for="descripcion" class="form-label">Detalles de la Falla/Trabajo</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required></textarea>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="prioridad" class="form-label">Prioridad</label>
                    <select class="form-select" id="prioridad" name="prioridad" required>
                        <option value="Baja">Baja</option>
                        <option value="Media" selected>Media</option>
                        <option value="Alta">Alta</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="kilom" class="form-label">Kilometraje Actual</label>
                    <input type="number" class="form-control" id="kilom" name="kilom" placeholder="Ej: 150000" required>
                </div>
            </div>

            {{-- SECCIÓN DE SUMINISTROS --}}
            <h5 class="mt-4 mb-3 border-bottom pb-2">Suministros / Repuestos Necesarios</h5>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title m-0">Lista de Repuestos Solicitados</h5>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#searchSupplyModal">
                        <i class="bi bi-plus-circle me-1"></i> Buscar/Añadir Suministro
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 10%">Código</th>
                                    <th style="width: 40%">Descripción</th>
                                    <th style="width: 15%">En Existencia</th>
                                    <th style="width: 15%">Cantidad a Usar</th>
                                    <th style="width: 10%">Solicitar Compra</th>
                                    <th style="width: 10%">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="selected-supplies-table-body">
                                <!-- Los suministros seleccionados se renderizarán aquí por JS -->
                                <tr><td colspan="6" class="text-center text-muted">Aún no se han añadido suministros.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Input oculto OBLIGATORIO para enviar los datos de los suministros al controlador -->
                    <input type="hidden" name="supplies_json" id="supplies-json-input">
                </div>
            </div>
            
            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary me-2"><i class="bi bi-save me-1"></i> Guardar Orden</button>
                <a href="{{ route('ordenes.index') }}" class="btn btn-secondary"><i class="bi bi-x-circle me-1"></i> Cancelar</a>
            </div>
        </form>
    </div>
</div>

{{-- ================================================================= --}}
{{-- MODAL PARA BUSCAR Y AÑADIR SUMINISTROS (Reemplazo del partial) --}}
{{-- ================================================================= --}}
<div class="modal fade" id="searchSupplyModal" tabindex="-1" aria-labelledby="searchSupplyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="searchSupplyModalLabel"><i class="bi bi-search me-2"></i> Buscar Suministro en Inventario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-8">
                        <input type="text" class="form-control" id="supply-search-input" placeholder="Buscar por código o descripción...">
                    </div>
                    <div class="col-md-4 d-grid">
                        <button type="button" class="btn btn-secondary" id="search-supply-btn"><i class="bi bi-search me-1"></i> Buscar</button>
                    </div>
                </div>
                
                <h6 class="mt-4 mb-3 border-bottom pb-2">Resultados de la Búsqueda</h6>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-striped table-hover table-sm">
                        <thead class="sticky-top bg-light">
                            <tr>
                                <th style="width: 15%">Código</th>
                                <th style="width: 35%">Descripción</th>
                                <th style="width: 15%">Existencia</th>
                                <th style="width: 20%">Cantidad a Usar</th>
                                <th style="width: 15%">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="supply-search-results-body">
                            <tr><td colspan="5" class="text-center text-muted">Escribe y presiona "Buscar" para encontrar suministros.</td></tr>
                        </tbody>
                    </table>
                </div>

                {{-- Separador para la opción manual --}}
                <div class="d-flex align-items-center my-4">
                    <div style="flex-grow: 1; height: 1px; background-color: #ccc;"></div>
                    <span class="mx-3 text-muted">O</span>
                    <div style="flex-grow: 1; height: 1px; background-color: #ccc;"></div>
                </div>

                <h6 class="mt-4 mb-3 border-bottom pb-2">Añadir Suministro Manual (No Inventariado)</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="manual-descripcion" class="form-label">Descripción del Suministro</label>
                        <input type="text" class="form-control" id="manual-descripcion" placeholder="Ej: Póliza de Seguro, Servicio Externo, etc.">
                    </div>
                    <div class="col-md-3">
                        <label for="manual-cantidad" class="form-label">Cantidad Requerida</label>
                        <input type="number" class="form-control" id="manual-cantidad" value="1" min="1">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-warning w-100" id="add-manual-supply-btn"><i class="bi bi-file-earmark-plus me-1"></i> Añadir Manual</button>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>


@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Objeto para almacenar los suministros seleccionados
        let selectedSupplies = {};
        let manualSupplyCounter = 0; // Contador para IDs únicos de suministros manuales

        const suppliesJsonInput = document.getElementById('supplies-json-input');
        const selectedSuppliesTableBody = document.getElementById('selected-supplies-table-body');
        const searchInput = document.getElementById('supply-search-input');
        const searchResultsBody = document.getElementById('supply-search-results-body');
        const searchButton = document.getElementById('search-supply-btn');
        const manualAddButton = document.getElementById('add-manual-supply-btn');
        const modal = document.getElementById('searchSupplyModal');

        // --- FUNCIONES DE RENDERIZADO Y LÓGICA DE SUMINISTROS ---

        /**
         * Renders the table of selected supplies and updates the hidden input.
         */
        function renderSuppliesTable() {
            let html = '';
            const suppliesArray = Object.values(selectedSupplies);

            if (suppliesArray.length === 0) {
                html = '<tr><td colspan="6" class="text-center text-muted">Aún no se han añadido suministros.</td></tr>';
            } else {
                suppliesArray.forEach(item => {
                    const isManual = item.id.startsWith('MANUAL_');
                    const requiredPurchase = item.existencia < item.cantidad;
                    
                    html += `
                        <tr class="${requiredPurchase ? 'table-danger' : ''}">
                            <td>${item.codigo}</td>
                            <td>${item.descripcion}</td>
                            <td>${isManual ? 'N/A' : item.existencia}</td>
                            <td>
                                <input type="number" 
                                       class="form-control form-control-sm supply-quantity-input" 
                                       data-item-id="${item.id}" 
                                       value="${item.cantidad}" 
                                       min="1" 
                                       style="width: 80px;">
                            </td>
                            <td>
                                ${requiredPurchase ? '<span class="badge bg-danger">Sí</span>' : '<span class="badge bg-success">No</span>'}
                            </td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm remove-supply" data-item-id="${item.id}">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
            }

            selectedSuppliesTableBody.innerHTML = html;
            
            // Actualizar el input JSON oculto
            suppliesJsonInput.value = JSON.stringify(suppliesArray);

            // Re-asignar eventos de cambio de cantidad
            document.querySelectorAll('.supply-quantity-input').forEach(input => {
                input.addEventListener('change', updateSupplyQuantity);
            });
        }

        /**
         * Maneja la actualización de la cantidad en la tabla principal.
         */
        function updateSupplyQuantity(e) {
            const itemId = e.target.dataset.itemId;
            const newQuantity = parseInt(e.target.value, 10);
            
            if (newQuantity <= 0 || isNaN(newQuantity)) {
                e.target.value = selectedSupplies[itemId].cantidad; // Mantener valor anterior
                console.error("La cantidad debe ser un número positivo.");
                return;
            }

            if (selectedSupplies[itemId]) {
                selectedSupplies[itemId].cantidad = newQuantity;
                renderSuppliesTable(); // Re-renderizar para actualizar el estado de "Solicitar Compra"
            }
        }

        /**
         * Realiza la búsqueda de suministros por AJAX.
         */
        function searchSupplies() {
            const query = searchInput.value.trim();
            if (query.length < 3) {
                searchResultsBody.innerHTML = '<tr><td colspan="5" class="text-center text-warning">Ingresa al menos 3 caracteres para buscar.</td></tr>';
                return;
            }

            searchResultsBody.innerHTML = '<tr><td colspan="5" class="text-center"><i class="bi bi-arrow-clockwise spin"></i> Buscando...</td></tr>';
            
            // Reemplaza con tu ruta real de API
            const searchUrl = '{{ route('ordenes.search-supplies') }}'; 

            $.ajax({
                url: searchUrl,
                method: 'GET',
                data: { query: query },
                success: function(response) {
                    let resultsHtml = '';
                    if (response.length === 0) {
                        resultsHtml = '<tr><td colspan="5" class="text-center text-info">No se encontraron resultados en el inventario.</td></tr>';
                    } else {
                        response.forEach(item => {
                            // Determina la cantidad inicial. Si ya está en la lista, usa la cantidad seleccionada.
                            const initialQuantity = selectedSupplies[item.id] ? selectedSupplies[item.id].cantidad : 1;

                            resultsHtml += `
                                <tr>
                                    <td>${item.codigo}</td>
                                    <td>${item.descripcion}</td>
                                    <td>${item.existencia}</td>
                                    <td>
                                        <input type="number" 
                                               class="form-control form-control-sm search-quantity" 
                                               data-item-id="${item.id}" 
                                               value="${initialQuantity}" 
                                               min="1" 
                                               style="width: 80px;">
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-success btn-sm add-supply" data-item-id="${item.id}">
                                            <i class="bi bi-check-lg"></i> Añadir
                                        </button>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                    searchResultsBody.innerHTML = resultsHtml;
                },
                error: function(xhr, status, error) {
                    console.error("Error en la búsqueda:", error);
                    searchResultsBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error al cargar los resultados. Intente de nuevo.</td></tr>';
                }
            });
        }

        // --- MANEJADORES DE EVENTOS ---

        // 1. Búsqueda por botón
        searchButton.addEventListener('click', searchSupplies);

        // 2. Búsqueda por tecla Enter en el input
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault(); // Previene el submit del formulario si aplica
                searchSupplies();
            }
        });


        // 3. Añadir suministro desde los resultados de búsqueda
        searchResultsBody.addEventListener('click', function(e) {
            if (e.target.classList.contains('add-supply')) {
                const itemId = e.target.dataset.itemId;
                const row = e.target.closest('tr');
                // Se obtiene el input de cantidad dentro de la fila (row)
                const quantityInput = row.querySelector('.search-quantity');
                const quantity = parseInt(quantityInput.value, 10);
                
                // Validación básica
                if (quantity <= 0 || isNaN(quantity)) {
                    alert('La cantidad a usar debe ser un número positivo.');
                    return;
                }

                const itemData = {
                    id: itemId,
                    codigo: row.cells[0].textContent,
                    descripcion: row.cells[1].textContent,
                    // Se asume que la existencia está en la tercera celda (índice 2)
                    existencia: parseInt(row.cells[2].textContent, 10), 
                    cantidad: quantity
                };

                // Si ya existe, se actualiza, si no, se añade
                selectedSupplies[itemId] = itemData;
                
                renderSuppliesTable();
                
                // Opcional: Cerrar el modal después de añadir
                const bootstrapModal = bootstrap.Modal.getInstance(modal);
                if (bootstrapModal) {
                    bootstrapModal.hide();
                }
            }
        });

        // 4. Añadir suministro Manual
        manualAddButton.addEventListener('click', function() {
            const descripcion = document.getElementById('manual-descripcion').value.trim();
            const cantidad = parseInt(document.getElementById('manual-cantidad').value, 10);

            if (!descripcion || cantidad <= 0 || isNaN(cantidad)) {
                 alert('Por favor, ingrese una descripción y una cantidad válida (> 0).');
                 return;
            }

            // Generar un ID temporal único para este suministro manual
            manualSupplyCounter++;
            const manualId = 'MANUAL_' + manualSupplyCounter;

            const itemData = {
                id: manualId,
                codigo: 'N/A', // No tiene código de inventario
                descripcion: descripcion,
                existencia: 0, // Fuerza la solicitud de compra
                cantidad: cantidad
            };

            selectedSupplies[manualId] = itemData;
            renderSuppliesTable();

            // Limpiar y cerrar
            document.getElementById('manual-descripcion').value = '';
            document.getElementById('manual-cantidad').value = 1;

            const bootstrapModal = bootstrap.Modal.getInstance(modal);
            if (bootstrapModal) {
                bootstrapModal.hide();
            }
        });


        // 5. Eliminar un suministro de la tabla principal
        selectedSuppliesTableBody.addEventListener('click', function(e) {
            if (e.target.closest('.remove-supply')) {
                const itemId = e.target.closest('.remove-supply').dataset.itemId;
                delete selectedSupplies[itemId];
                renderSuppliesTable();
            }
        });
        
        // 6. Listener para la serialización final antes del submit
        document.getElementById('orden-form').addEventListener('submit', function(e) {
            // Asegúrate de que el input oculto tenga el valor correcto antes de enviar.
            // Esto es redundante si renderSuppliesTable() se llama después de cada cambio, 
            // pero es una buena práctica de seguridad.
            suppliesJsonInput.value = JSON.stringify(Object.values(selectedSupplies));
            
            // Aquí puedes añadir validaciones finales si es necesario, por ejemplo:
            // if (Object.values(selectedSupplies).length === 0) {
            //     alert("Debe añadir al menos un suministro.");
            //     e.preventDefault();
            // }
        });


        // Inicializar la tabla vacía al cargar la página
        renderSuppliesTable();
    });
</script>
@endpush
