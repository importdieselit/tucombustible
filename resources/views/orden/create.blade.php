@extends('layouts.app')

@section('title', 'Crear Nueva Orden de Trabajo')

@push('scripts')
<!-- jQuery para manejar las llamadas AJAX -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap JS para el modal -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="id_vehiculo" class="form-label">Vehículo</label>
                    <select class="form-select" id="id_vehiculo" name="id_vehiculo" required>
                        <option value="">Seleccione Vehículo</option>
                        {{-- Ejemplo de opciones (reemplazar con datos reales de Laravel) --}}
                        <option value="1">Camión A (Placa: AAA-111)</option>
                        <option value="2">Remolque B (Placa: BBB-222)</option>
                        {{-- @foreach ($vehiculos as $vehiculo)
                            <option value="{{ $vehiculo->id }}">{{ $vehiculo->flota }} (Placa: {{ $vehiculo->placa }})</option>
                        @endforeach --}}
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="tipo" class="form-label">Tipo de Orden</label>
                    <select class="form-select" id="tipo" name="tipo" required>
                        <option value="Reparación">Reparación</option>
                        <option value="Mantenimiento">Mantenimiento</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="descripcion_1" class="form-label">Falla Principal/Título</label>
                <input type="text" class="form-control" id="descripcion_1" name="descripcion_1" placeholder="Ej: Ruido en motor o Mantenimiento Preventivo" required>
            </div>
            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción Detallada</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required></textarea>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="prioridad" class="form-label">Prioridad</label>
                    <select class="form-select" id="prioridad" name="prioridad" required>
                        <option value="Baja">Baja</option>
                        <option value="Media" selected>Media</option>
                        <option value="Alta">Alta</option>
                        <option value="Crítica">Crítica</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="fecha_prometida" class="form-label">Fecha de Cierre Prometida</label>
                    <input type="date" class="form-control" id="fecha_prometida" name="fecha_prometida" value="{{ date('Y-m-d', strtotime('+3 days')) }}" required>
                </div>
            </div>
            
            <hr class="my-4">

            {{-- ---------------------------------------------------- --}}
            {{-- BLOQUE DE INSUMOS (Inventario y Manuales) --}}
            {{-- ---------------------------------------------------- --}}
            
            <h5 class="card-title m-0 mb-3">Insumos y Repuestos Requeridos</h5>
            
            <div class="d-flex gap-2 mb-3">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#searchSupplyModal">
                    <i class="bi bi-search me-1"></i> Buscar en Inventario
                </button>
                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#manualSupplyModal">
                    <i class="bi bi-plus-circle me-1"></i> Agregar Suministro Manual
                </button>
            </div>

            {{-- Tabla de Insumos Seleccionados (Previsualización) --}}
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th class="text-end">Existencia</th>
                            <th class="text-end">Cant. Requerida</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="selectedSuppliesTableBody">
                        {{-- Filas generadas por JS --}}
                        <tr><td colspan="5" class="text-center text-muted">Aún no se han agregado insumos.</td></tr>
                    </tbody>
                </table>
            </div>

            {{-- Input Oculto para enviar los datos serializados al controlador --}}
            <input type="hidden" name="supplies_json" id="supplies_json">
            
            <hr class="my-4">

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-success btn-lg shadow-sm">
                    <i class="bi bi-save me-2"></i> Guardar Orden de Trabajo
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

{{-- ---------------------------------------------------- --}}
{{-- MODALES --}}
{{-- ---------------------------------------------------- --}}

{{-- Modal para Búsqueda en Inventario (Permite múltiples adiciones) --}}
<div class="modal fade" id="searchSupplyModal" tabindex="-1" aria-labelledby="searchSupplyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="searchSupplyModalLabel"><i class="bi bi-search me-1"></i> Buscar Repuestos en Inventario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="supplySearchInput" class="form-label">Buscar por Código o Descripción:</label>
                    <input type="text" class="form-control" id="supplySearchInput" placeholder="Escriba aquí para buscar en tiempo real...">
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Descripción</th>
                                <th class="text-end">Existencia</th>
                                <th class="text-center" style="width: 120px;">Cantidad</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="searchResultsTableBody">
                            <tr><td colspan="5" class="text-center text-muted">Escriba en el campo de búsqueda para empezar.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar y Volver</button>
                {{-- No hay botón de guardar aquí, la adición es por fila --}}
            </div>
        </div>
    </div>
</div>

{{-- Modal para Suministro Manual (Permite múltiples adiciones) --}}
<div class="modal fade" id="manualSupplyModal" tabindex="-1" aria-labelledby="manualSupplyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title" id="manualSupplyModalLabel"><i class="bi bi-plus-circle me-1"></i> Agregar Suministro Manual</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Utilice esta opción para artículos que no están en inventario o que se comprarán aparte.</p>
                <div class="mb-3">
                    <label for="manual-descripcion" class="form-label">Descripción del Artículo:</label>
                    <input type="text" class="form-control" id="manual-descripcion" required placeholder="Ej: Aceite 20w50 (Comprado en Ferretería)">
                </div>
                <div class="mb-3">
                    <label for="manual-cantidad" class="form-label">Cantidad Requerida:</label>
                    <input type="number" class="form-control text-end" id="manual-cantidad" value="1" min="1" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Terminar y Cerrar</button>
                <button type="button" class="btn btn-success" id="addManualSupplyBtn">
                    <i class="bi bi-check-lg me-1"></i> Agregar a la Orden
                </button>
            </div>
        </div>
    </div>
</div>


@push('scripts')
<script>
    $(document).ready(function() {
        // Objeto global para almacenar los suministros seleccionados (Inventario y Manuales)
        // Key: id del inventario (numérico) o 'MANUAL_X'
        let selectedSupplies = {};
        let manualSupplyCounter = 0; // Contador para generar IDs únicos para items manuales

        // Referencias a elementos del DOM
        const selectedSuppliesTableBody = document.getElementById('selectedSuppliesTableBody');
        const searchInput = document.getElementById('supplySearchInput');
        const searchResultsBody = document.getElementById('searchResultsTableBody');
        const suppliesJsonInput = document.getElementById('supplies_json');
        const addManualSupplyBtn = document.getElementById('addManualSupplyBtn');

        // Función de utilidad para debouncing (limita la frecuencia de ejecución)
        function debounce(func, timeout = 300) {
            let timer;
            return (...args) => {
                clearTimeout(timer);
                timer = setTimeout(() => {
                    func.apply(this, args);
                }, timeout);
            };
        }

        // --- LÓGICA DE BÚSQUEDA EN INVENTARIO (AJAX) ---
        function renderSearchResults(data) {
            let html = '';
            if (data.length === 0) {
                html = '<tr><td colspan="5" class="text-center text-warning">No se encontraron resultados.</td></tr>';
            } else {
                data.forEach(item => {
                    // Determinar si el item ya está seleccionado para mostrar el estado
                    const isSelected = selectedSupplies.hasOwnProperty(item.id);
                    const selectedQty = isSelected ? selectedSupplies[item.id].cantidad : 1;
                    const stockClass = item.existencia < selectedQty ? 'text-danger fw-bold' : '';

                    html += `
                        <tr class="${isSelected ? 'table-info' : ''}">
                            <td>${item.codigo}</td>
                            <td>${item.descripcion}</td>
                            <td class="text-end ${stockClass}">${item.existencia}</td>
                            <td class="text-center">
                                <input type="number" class="form-control form-control-sm text-end search-quantity" 
                                       data-item-id="${item.id}"
                                       value="${selectedQty}" 
                                       min="1" style="width: 100px; display: inline-block;">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-success add-supply" data-item-id="${item.id}"
                                    title="Agregar/Actualizar a la Orden">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
            }
            searchResultsBody.innerHTML = html;
        }

        // Ejecuta la búsqueda de inventario
        function performSupplySearch() {
            const query = searchInput.value.trim();
            if (query.length < 3) {
                searchResultsBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Escriba al menos 3 caracteres.</td></tr>';
                return;
            }

            searchResultsBody.innerHTML = '<tr><td colspan="5" class="text-center text-primary"><div class="spinner-border spinner-border-sm me-2" role="status"></div> Buscando...</td></tr>';

            // Realizar la llamada AJAX (Asumimos una ruta 'supplies.search' que devuelve JSON)
            $.ajax({
                url: '{{ route("supplies.search") }}', // RUTA DE EJEMPLO: DEBES CREAR ESTA RUTA EN LARAVEL
                method: 'GET',
                data: { query: query },
                success: function(response) {
                    renderSearchResults(response.data); // Asume que el controlador devuelve { data: [...] }
                },
                error: function(xhr) {
                    searchResultsBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error al cargar el inventario.</td></tr>';
                    console.error("Error en la búsqueda:", xhr.responseText);
                }
            });
        }

        // Listener con debounce para la búsqueda en tiempo real
        searchInput.addEventListener('input', debounce(performSupplySearch, 300));


        // --- LÓGICA DE TABLA PRINCIPAL Y PREVISUALIZACIÓN ---

        // Dibuja la tabla de suministros seleccionados y actualiza el input JSON oculto
        function renderSuppliesTable() {
            let html = '';
            const suppliesArray = Object.values(selectedSupplies);

            if (suppliesArray.length === 0) {
                html = '<tr><td colspan="5" class="text-center text-muted">Aún no se han agregado insumos.</td></tr>';
            } else {
                suppliesArray.forEach(item => {
                    // Clase de estilo si el item es de inventario y la cantidad excede la existencia
                    let rowClass = '';
                    let existenceText = item.existencia;
                    if (!item.id.startsWith('MANUAL_') && item.cantidad > item.existencia) {
                         // Item de inventario y requerimiento > existencia
                        rowClass = 'table-warning';
                    }
                    if (item.id.startsWith('MANUAL_')) {
                        // Item Manual
                        existenceText = 'N/A (Manual)';
                        rowClass = 'table-light';
                    }

                    html += `
                        <tr class="${rowClass}">
                            <td>${item.codigo}</td>
                            <td>${item.descripcion}</td>
                            <td class="text-end">${existenceText}</td>
                            <td class="text-end">${item.cantidad}</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-danger remove-supply" data-item-id="${item.id}" title="Quitar de la Orden">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
            }
            selectedSuppliesTableBody.innerHTML = html;
            
            // Actualizar el input oculto que se enviará con el formulario
            suppliesJsonInput.value = JSON.stringify(suppliesArray);
        }

        // --- MANEJO DE EVENTOS ---

        // 1. Añadir/Actualizar Suministro de Inventario (dentro del modal de búsqueda)
        searchResultsBody.addEventListener('click', function(e) {
            const addButton = e.target.closest('.add-supply');
            if (addButton) {
                const itemId = addButton.dataset.itemId;
                const row = addButton.closest('tr');
                // Buscamos el input de cantidad dentro de la fila
                const quantityInput = row.querySelector('.search-quantity');
                const quantity = parseInt(quantityInput.value, 10);
                
                if (quantity <= 0 || isNaN(quantity)) {
                    Swal.fire('Error', 'La cantidad debe ser un número positivo.', 'error');
                    return;
                }
                
                // Los datos de la existencia y descripción se toman del HTML para evitar otra llamada
                const itemData = {
                    id: itemId,
                    codigo: row.cells[0].textContent,
                    descripcion: row.cells[1].textContent,
                    // Parseamos la existencia, manejando el caso de errores de lectura si ocurre
                    existencia: parseInt(row.cells[2].textContent, 10) || 0, 
                    cantidad: quantity
                };

                // Si ya existe, se actualiza, si no, se añade
                selectedSupplies[itemId] = itemData;
                
                renderSuppliesTable();
                
                // Retroalimentación visual
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `Agregado: ${itemData.descripcion} (x${quantity})`,
                    showConfirmButton: false,
                    timer: 2000
                });
                
                // Vuelve a ejecutar la búsqueda para refrescar el color de la fila agregada/actualizada
                // (Opcional: solo refrescar la fila, pero la búsqueda completa es más simple)
                performSupplySearch(); 
            }
        });
        
        // 2. Añadir Suministro Manual (del modal manual)
        addManualSupplyBtn.addEventListener('click', function(e) {
            const descripcionInput = document.getElementById('manual-descripcion');
            const cantidadInput = document.getElementById('manual-cantidad');
            
            const descripcion = descripcionInput.value.trim();
            const cantidad = parseInt(cantidadInput.value, 10);

            if (!descripcion || cantidad <= 0 || isNaN(cantidad)) {
                 Swal.fire('Error', 'Debe ingresar una descripción y una cantidad válida.', 'error');
                 return;
            }

            // Generar un ID temporal único
            manualSupplyCounter++;
            const manualId = 'MANUAL_' + manualSupplyCounter;

            const itemData = {
                id: manualId,
                codigo: 'N/A', // No tiene código de inventario
                descripcion: descripcion,
                existencia: 0, // 0 existencia fuerza la compra (si aplica tu lógica de negocio)
                cantidad: cantidad
            };

            selectedSupplies[manualId] = itemData;
            renderSuppliesTable();

            // Limpiar el formulario manual para permitir una nueva adición
            descripcionInput.value = '';
            cantidadInput.value = 1;

            // Retroalimentación visual
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: `Agregado Manual: ${itemData.descripcion} (x${cantidad})`,
                showConfirmButton: false,
                timer: 2000
            });
            
            // NOTA: El modal manual permanece abierto para seguir agregando si el usuario lo desea.
        });


        // 3. Eliminar un suministro de la tabla principal
        selectedSuppliesTableBody.addEventListener('click', function(e) {
            if (e.target.closest('.remove-supply')) {
                const itemId = e.target.closest('.remove-supply').dataset.itemId;
                delete selectedSupplies[itemId];
                renderSuppliesTable();
                 Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'info',
                    title: 'Suministro Eliminado',
                    showConfirmButton: false,
                    timer: 1500
                });
                // Si el modal de búsqueda está abierto, refresca los resultados
                if ($('#searchSupplyModal').hasClass('show')) {
                     performSupplySearch(); 
                }
            }
        });
        
        // 4. Listener para la serialización final antes del submit
        document.getElementById('orden-form').addEventListener('submit', function(e) {
            // Asegúrate de que el input oculto tenga el valor correcto antes de enviar.
            suppliesJsonInput.value = JSON.stringify(Object.values(selectedSupplies));
            
            // Puedes añadir una validación para asegurar que se agregaron items
            if (Object.values(selectedSupplies).length === 0) {
                 // Puedes emitir una advertencia, pero es mejor permitir órdenes sin insumos si aplica tu caso
                 // Swal.fire('Atención', 'No se ha añadido ningún suministro a la orden.', 'warning');
                 // e.preventDefault();
            }
        });

        // Inicializar la tabla vacía al cargar la página
        renderSuppliesTable();
    });
</script>
@endpush
