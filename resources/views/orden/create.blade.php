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
            <input type="hidden" name="guardar_modo" id="guardar-modo" value="orden_normal">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="id_vehiculo" class="form-label">Vehículo</label>
                    <select class="form-select" id="id_vehiculo" name="id_vehiculo" required>
                        <option value="">Seleccione un vehículo</option>
                        {{-- Ejemplo de carga de vehículos (debes cargarlos desde el controller) --}}
                        {{-- @foreach($vehiculos as $vehiculo)
                            <option value="{{ $vehiculo->id }}">{{ $vehiculo->flota }} - {{ $vehiculo->placa }}</option>
                        @endforeach --}}
                         <option value="1">Flota 1 - ABC-123</option>
                         <option value="2">Flota 2 - XYZ-456</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="tipo" class="form-label">Tipo de Orden</label>
                    <select class="form-select" id="tipo" name="tipo" required>
                        <option value="Mantenimiento">Mantenimiento</option>
                        <option value="Reparación">Reparación</option>
                    </select>
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
                    <label for="km_actual" class="form-label">Kilometraje Actual</label>
                    <input type="number" class="form-control" id="km_actual" name="km_actual" value="" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="descripcion_1" class="form-label">Resumen del Problema / Servicio</label>
                <input type="text" class="form-control" id="descripcion_1" name="descripcion_1" required>
            </div>
            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción Detallada (Opcional)</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
            </div>
            
             <h5 class="card-title mt-4 mb-3">Suministros y Materiales</h5>
             <div class="alert alert-info">
                 <i class="bi bi-info-circle-fill me-2"></i>
                 Los suministros agregados se guardarán como **USO de Inventario** si hay existencias, o como **SOLICITUD de Compra** si no hay existencia o si se agregan manualmente.
             </div>

            <div class="d-flex justify-content-end mb-3">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#searchSupplyModal">
                    <i class="bi bi-search me-1"></i> Buscar Suministro
                </button>
                 <button type="button" class="btn btn-outline-secondary ms-2" data-bs-toggle="modal" data-bs-target="#manualSupplyModal">
                    <i class="bi bi-plus-circle me-1"></i> Agregar Suministro Manual
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="selectedSuppliesTable">
                    <thead class="table-light">
                        <tr>
                            <th>Código / Tipo</th>
                            <th>Descripción</th>
                            <th>Cantidad</th>
                            <th>Estado</th> {{-- Muestra si es USO o COMPRA --}}
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Filas de suministros seleccionados se insertarán aquí por JS --}}
                    </tbody>
                </table>
            </div>
            
            {{-- Input oculto para enviar los suministros al controlador --}}
            <input type="hidden" name="suministros_data" id="suministros-data">
            
            <hr class="mt-4">

            {{-- Botones de acción --}}
            <div class="d-flex justify-content-between">
                <a href="{{ route('ordenes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                
                {{-- Botón principal para crear la orden y usar/solicitar suministros --}}
                <button type="submit" class="btn btn-primary" id="btn-crear-orden">
                    <i class="bi bi-save me-1"></i> Crear Orden y Guardar Suministros
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL PARA BUSCAR SUMINISTROS EN INVENTARIO --}}
@include('ordenes.partials.search_supply_modal') {{-- Asume que tienes un partial para buscar --}}

{{-- MODAL PARA AGREGAR SUMINISTRO MANUAL --}}
<div class="modal fade" id="manualSupplyModal" tabindex="-1" aria-labelledby="manualSupplyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title" id="manualSupplyModalLabel">Agregar Suministro Manual (Solicitar Compra)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>El suministro manual se registra como una **Solicitud de Compra** (OC) para que el administrador la apruebe. No se descontará del inventario.</p>
                <div class="mb-3">
                    <label for="manual-descripcion" class="form-label">Descripción del Ítem</label>
                    <input type="text" class="form-control" id="manual-descripcion" required>
                </div>
                <div class="mb-3">
                    <label for="manual-cantidad" class="form-label">Cantidad Requerida</label>
                    <input type="number" class="form-control" id="manual-cantidad" value="1" min="1" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="add-manual-supply-btn">
                    <i class="bi bi-plus me-1"></i> Añadir Solicitud
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Objeto global para almacenar los suministros seleccionados.
        // La clave es el ID o un ID temporal para manuales.
        // Se añade una nueva propiedad: 'tipo' (USO, COMPRA)
        let selectedSupplies = {};
        let manualSupplyCounter = 0;
        const selectedSuppliesTableBody = document.querySelector('#selectedSuppliesTable tbody');
        const suministrosDataInput = document.getElementById('suministros-data');

        // Función para renderizar la tabla principal
        function renderSuppliesTable() {
            let html = '';
            let hasSupplies = false;

            for (const id in selectedSupplies) {
                if (selectedSupplies.hasOwnProperty(id)) {
                    hasSupplies = true;
                    const item = selectedSupplies[id];
                    
                    // Determinar el tipo y el color para la fila
                    const isManual = id.startsWith('MANUAL_');
                    const isLowStock = !isManual && (item.cantidad > item.existencia);
                    
                    // Si es manual o sin existencia, es SOLICITUD DE COMPRA (COMPRA)
                    const tipo = isManual || isLowStock ? 'COMPRA' : 'USO';
                    const badgeClass = tipo === 'USO' ? 'badge bg-success' : 'badge bg-warning text-dark';
                    const codigoDisplay = isManual ? 'N/A (Manual)' : item.codigo;
                    const existenciaDisplay = isManual ? 'N/A' : item.existencia;

                    // Asignar el tipo de forma persistente
                    item.tipo = tipo; 

                    html += `
                        <tr>
                            <td>${codigoDisplay}</td>
                            <td>${item.descripcion}</td>
                            <td>${item.cantidad}</td>
                            <td><span class="${badgeClass}">${tipo}</span></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-danger remove-supply" data-item-id="${id}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                }
            }
            
            if (!hasSupplies) {
                html = '<tr><td colspan="5" class="text-center text-muted">Aún no se han añadido suministros.</td></tr>';
            }
            
            selectedSuppliesTableBody.innerHTML = html;
            
            // Actualizar el campo oculto para enviar al servidor
            suministrosDataInput.value = JSON.stringify(Object.values(selectedSupplies));
        }

        // --- MANEJO DE SUMINISTROS DE INVENTARIO (BÚSQUEDA) ---
        // La lógica de búsqueda y adición se mantiene igual, 
        // pero ahora la renderización interna determina si es USO o COMPRA.
        
        // Manejar la adición de un suministro desde el modal de búsqueda
        $('#searchSupplyTableBody').on('click', '.add-supply', function(e) {
            const itemId = e.target.dataset.itemId;
            const row = e.target.closest('tr');
            const quantityInput = row.querySelector('.search-quantity');
            const quantity = parseInt(quantityInput.value, 10);
            
            const itemData = {
                id: itemId,
                codigo: row.cells[0].textContent,
                descripcion: row.cells[1].textContent,
                existencia: parseInt(row.cells[2].textContent, 10),
                cantidad: quantity
            };

            // Si ya existe, se actualiza, si no, se añade
            selectedSupplies[itemId] = itemData;
            
            renderSuppliesTable();
            
            // Cerrar el modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('searchSupplyModal'));
            modal.hide();
        });


        // --- MANEJO DE SUMINISTROS MANUALES (SOLICITUD DE COMPRA) ---
        document.getElementById('add-manual-supply-btn').addEventListener('click', function() {
            const descripcion = document.getElementById('manual-descripcion').value.trim();
            const cantidad = parseInt(document.getElementById('manual-cantidad').value, 10);
            
            if (!descripcion || cantidad <= 0) {
                 Swal.fire('Error', 'La descripción y la cantidad requerida son obligatorias.', 'error');
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

            // Limpiar el formulario manual
            document.getElementById('manual-descripcion').value = '';
            document.getElementById('manual-cantidad').value = 1;

            // Cerrar el modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('manualSupplyModal'));
            if (modal) {
                modal.hide();
            }
        });


        // --- LÓGICA DE ELIMINACIÓN ---
        selectedSuppliesTableBody.addEventListener('click', function(e) {
            if (e.target.closest('.remove-supply')) {
                const itemId = e.target.closest('.remove-supply').dataset.itemId;
                delete selectedSupplies[itemId];
                renderSuppliesTable();
            }
        });

        // Inicializar la tabla vacía
        renderSuppliesTable();
    });
</script>
@endpush
