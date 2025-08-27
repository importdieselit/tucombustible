@extends('layouts.app')

@section('title', 'Crear Nueva Orden de Trabajo')

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
        <form action="{{ route('ordenes.store') }}" method="POST" id="orden-form">
            @csrf
            {{-- Datos de la Orden --}}
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="vehiculo_id" class="form-label">Vehículo</label>
                    <select class="form-select" id="vehiculo_id" name="id_vehiculo" required>
                        <option value="">Seleccione un vehículo</option>
                        @foreach ($vehiculos as $vehiculo)
                            <option value="{{ $vehiculo->id }}">{{ $vehiculo->placa }} - {{ $vehiculo->marca()->marca }} {{ $vehiculo->modelo()->modelo }}</option>
                        @endforeach 
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="nro_orden" class="form-label">Número de Orden</label>
                    <input type="text" class="form-control" disabled id="nro_orden" name="nro_orden" value="{{ $nro_orden }}" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="id_tipo_orden" class="form-label">Tipo de Orden</label>
                    <select class="form-select" id="id_tipo_orden" name="id_tipo_orden" required>
                        <option value="">Seleccione el tipo</option>
                        @foreach ($tipos as $tipo)
                            <option value="{{ $tipo->id_tipo_orden }}">{{ $tipo->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="kilometraje" class="form-label">Kilometraje</label>
                    <input type="number" class="form-control" id="kilometraje" name="kilometraje" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="responsable" class="form-label">Responsable Asignado</label>
                    <select class="form-select" id="responsable" name="responsable" required>
                        <option value="">Seleccione un responsable</option>
                        @foreach ($personal as $persona)
                            <option value="{{ $persona->id_personal }}">{{ $persona->nombre }} - {{ $persona->cargo }}</option>
                        @endforeach 
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="descripcion_1" class="form-label">Descripción del Problema/Tarea</label>
                <textarea class="form-control" id="descripcion_1" name="descripcion_1" rows="4" required></textarea>
            </div>

            <hr class="my-4">

            {{-- Sección de Suministros --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="m-0">Suministros Solicitados</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#searchSupplyModal">
                    <i class="bi bi-box-seam me-1"></i> Agregar Suministro
                </button>
            </div>

            <div class="table-responsive mb-4">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Cantidad</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="supplies-table-body">
                        {{-- Las filas de suministros se agregarán aquí con JavaScript --}}
                    </tbody>
                </table>
            </div>

            {{-- Campos ocultos para enviar los suministros --}}
            <div id="supplies-hidden-inputs"></div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('ordenes.list') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar Orden</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal para buscar y seleccionar suministros --}}
<div class="modal fade" id="searchSupplyModal" tabindex="-1" aria-labelledby="searchSupplyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="searchSupplyModalLabel">Buscar Suministros de Almacén</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" class="form-control" id="supply-search-input" placeholder="Buscar por código o descripción...">
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Descripción</th>
                                <th>Existencia</th>
                                <th>Cantidad a Solicitar</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="supply-search-results">
                            {{-- Los resultados de la búsqueda se agregarán aquí dinámicamente --}}
                            {{-- Ejemplo de una fila de resultado --}}
                            {{--
                            <tr>
                                <td>COD001</td>
                                <td>Aceite de Motor 10W-30</td>
                                <td>50</td>
                                <td>
                                    <input type="number" class="form-control form-control-sm" value="1" min="1" max="50">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-success btn-sm">Agregar</button>
                                </td>
                            </tr>
                            --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Objeto para almacenar los suministros seleccionados temporalmente
        let selectedSupplies = {};
        
        const suppliesTableBody = document.getElementById('supplies-table-body');
        const suppliesHiddenInputs = document.getElementById('supplies-hidden-inputs');
        const searchInput = document.getElementById('supply-search-input');
        const searchResultsTable = document.getElementById('supply-search-results');
        
        // Función para renderizar la tabla de suministros solicitados
        function renderSuppliesTable() {
            let html = '';
            let hiddenInputsHtml = '';
            for (const id in selectedSupplies) {
                const supply = selectedSupplies[id];
                html += `
                    <tr data-supply-id="${supply.id}">
                        <td>${supply.codigo}</td>
                        <td>${supply.descripcion}</td>
                        <td>
                            <input type="number" class="form-control form-control-sm supply-quantity" 
                                value="${supply.cantidad}" min="1" max="${supply.existencia}">
                        </td>
                        <td><span class="badge bg-warning">Solicitado</span></td>
                        <td>
                            <button type="button" class="btn btn-danger btn-sm remove-supply">Eliminar</button>
                        </td>
                    </tr>
                `;
                hiddenInputsHtml += `
                    <input type="hidden" name="supplies[${supply.id}][item_id]" value="${supply.id}">
                    <input type="hidden" name="supplies[${supply.id}][cantidad]" value="${supply.cantidad}" class="supply-quantity-hidden">
                `;
            }
            suppliesTableBody.innerHTML = html;
            suppliesHiddenInputs.innerHTML = hiddenInputsHtml;
        }

        // Delegación de eventos para los botones de eliminar y los campos de cantidad
        suppliesTableBody.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-supply')) {
                const row = e.target.closest('tr');
                const supplyId = row.dataset.supplyId;
                delete selectedSupplies[supplyId];
                renderSuppliesTable();
            }
        });

        suppliesTableBody.addEventListener('input', function(e) {
            if (e.target.classList.contains('supply-quantity')) {
                const row = e.target.closest('tr');
                const supplyId = row.dataset.supplyId;
                const newQuantity = parseInt(e.target.value, 10);
                if (newQuantity > 0) {
                    selectedSupplies[supplyId].cantidad = newQuantity;
                    // Actualizar el campo oculto
                    document.querySelector(`input[name="supplies[${supplyId}][cantidad]"]`).value = newQuantity;
                }
            }
        });

        // Simulación de búsqueda (debería ser una llamada AJAX a tu backend)
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            // Lógica de AJAX para buscar en la base de datos de inventario
            // Por ahora, usamos datos de ejemplo
            const allItems = [
                { id: 1, codigo: 'FIL001', descripcion: 'Filtro de Aceite', existencia: 25 },
                { id: 2, codigo: 'ACE002', descripcion: 'Aceite Sintético 5W-30', existencia: 50 },
                { id: 3, codigo: 'FRE003', descripcion: 'Pastillas de Freno', existencia: 10 },
                { id: 4, codigo: 'BUJ004', descripcion: 'Bujía de Encendido', existencia: 80 },
            ];
            const filteredItems = allItems.filter(item => 
                item.codigo.toLowerCase().includes(query) || item.descripcion.toLowerCase().includes(query)
            );
            renderSearchResults(filteredItems);
        });

        // Renderizar los resultados de la búsqueda en el modal
        function renderSearchResults(items) {
            let html = '';
            if (items.length > 0) {
                items.forEach(item => {
                    html += `
                        <tr>
                            <td>${item.codigo}</td>
                            <td>${item.descripcion}</td>
                            <td>${item.existencia}</td>
                            <td>
                                <input type="number" class="form-control form-control-sm search-quantity" value="1" min="1" max="${item.existencia}">
                            </td>
                            <td>
                                <button type="button" class="btn btn-success btn-sm add-supply" data-item-id="${item.id}">
                                    Agregar
                                </button>
                            </td>
                        </tr>
                    `;
                });
            } else {
                html = `<tr><td colspan="5" class="text-center">No se encontraron resultados.</td></tr>`;
            }
            searchResultsTable.innerHTML = html;
        }

        // Manejar el evento de agregar un suministro desde el modal
        searchResultsTable.addEventListener('click', function(e) {
            if (e.target.classList.contains('add-supply')) {
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
            }
        });
    });
</script>
@endpush
