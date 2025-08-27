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
                    <i class="bi bi-plus-circle me-1"></i> Añadir Suministro
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="selectedSuppliesTable">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Cantidad</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Los suministros seleccionados se renderizarán aquí con JS --}}
                    </tbody>
                </table>
            </div>

            {{-- Aquí se añadirán inputs ocultos para los suministros seleccionados --}}
            <div id="hidden-inputs-container"></div>
            
            <div class="mt-4 d-flex justify-content-between">
                <a href="{{ route('ordenes.list') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar Orden</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal para buscar suministros --}}
<div class="modal fade" id="searchSupplyModal" tabindex="-1" aria-labelledby="searchSupplyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="searchSupplyModalLabel">Buscar Suministro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" class="form-control" id="supplySearchInput" placeholder="Buscar por código o descripción...">
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Descripción</th>
                                <th>Existencia</th>
                                <th>Cantidad</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="searchResultsTable">
                            {{-- Los resultados de la búsqueda se mostrarán aquí --}}
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Objeto para mantener los suministros seleccionados
        const selectedSupplies = {};

        // Elementos del DOM
        const supplySearchInput = document.getElementById('supplySearchInput');
        const searchResultsTable = document.getElementById('searchResultsTable');
        const selectedSuppliesTable = document.getElementById('selectedSuppliesTable').querySelector('tbody');
        const hiddenInputsContainer = document.getElementById('hidden-inputs-container');

        // Función para buscar suministros
        const searchSupplies = (query) => {
            if (query.length < 2) {
                searchResultsTable.innerHTML = '';
                return;
            }
            fetch(`{{ route('ordenes.search-supplies') }}?query=${query}`)
                .then(response => response.json())
                .then(data => {
                    renderSearchResults(data);
                });
        };

        // Renderiza los resultados de la búsqueda
        const renderSearchResults = (data) => {
            let html = '';
            if (data.length > 0) {
                data.forEach(item => {
                    html += `
                        <tr>
                            <td>${item.codigo}</td>
                            <td>${item.descripcion}</td>
                            <td>${item.existencia}</td>
                            <td><input type="number" class="form-control search-quantity" value="1" min="1" max="${item.existencia}"></td>
                            <td>
                                <button type="button" class="btn btn-success btn-sm add-supply" data-item-id="${item.id}">
                                    Añadir
                                </button>
                            </td>
                        </tr>
                    `;
                });
            } else {
                html = `<tr><td colspan="5" class="text-center">No se encontraron resultados.</td></tr>`;
            }
            searchResultsTable.innerHTML = html;
        };

        // Renderiza los suministros seleccionados en la tabla principal
        const renderSuppliesTable = () => {
            selectedSuppliesTable.innerHTML = '';
            hiddenInputsContainer.innerHTML = '';
            for (const id in selectedSupplies) {
                const item = selectedSupplies[id];
                const row = `
                    <tr>
                        <td>${item.codigo}</td>
                        <td>${item.descripcion}</td>
                        <td>${item.cantidad}</td>
                        <td>
                            <button type="button" class="btn btn-danger btn-sm remove-supply" data-item-id="${item.id}">
                                Eliminar
                            </button>
                        </td>
                    </tr>
                `;
                selectedSuppliesTable.innerHTML += row;

                // Añade inputs ocultos para el formulario
                const hiddenInput = `
                    <input type="hidden" name="supplies[${item.id}][item_id]" value="${item.id}">
                    <input type="hidden" name="supplies[${item.id}][cantidad]" value="${item.cantidad}">
                `;
                hiddenInputsContainer.innerHTML += hiddenInput;
            }
        };

        // Manejar el evento de búsqueda con un pequeño retraso (debounce)
        let timeout = null;
        supplySearchInput.addEventListener('keyup', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                searchSupplies(this.value);
            }, 300);
        });

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

        // Manejar el evento de eliminar un suministro de la tabla principal
        selectedSuppliesTable.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-supply')) {
                const itemId = e.target.dataset.itemId;
                delete selectedSupplies[itemId];
                renderSuppliesTable();
            }
        });
    });
</script>
@endpush
