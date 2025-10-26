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
        <form action="{{ route('ordenes.store') }}" method="POST" id="orden-form"  enctype="multipart/form-data">
            @csrf
            {{-- Datos de la Orden --}}
           <input type="hidden" name="estatus"  value="2"> {{-- Estatus "Abierta" --}}
           <input type="hidden" name="fecha_in"  value="{{ date('Y-m-d') }}">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="id_vehiculo" class="form-label">Vehículo</label>
                    
                    @if(!is_null($vehiculo))
                    <h3 class="text-primary">{{$vehiculo->flota}} {{ $vehiculo->placa }} - {{ $vehiculo->marca()->marca }} {{ $vehiculo->modelo()->modelo }}</h3>
                    <hr>
                    <input type="hidden" name="id_vehiculo" value="{{ $vehiculo->id }}">
                    <input type="hidden" name="placa" value="{{ $vehiculo->placa }}">

                    @else

                    <select class="form-select" id="id_vehiculo" name="id_vehiculo" required>
                        <option value="">Seleccione un vehículo</option>
                        @foreach ($vehiculos as $vehiculo)
                            <option value="{{ $vehiculo->id }}">{{ $vehiculo->placa }} - {{ $vehiculo->marca()->marca }} {{ $vehiculo->modelo()->modelo }}</option>
                        @endforeach
                    </select>

                    @endif
                </div>
                <div class="col-md-6 mb-3">
                    <label for="nro_orden" class="form-label">Número de Orden</label>
                    <input type="text" class="form-control"  id="nro_orden" name="nro_orden" value="{{ $nro_orden }}" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="id_tipo_orden" class="form-label">Tipo de Orden</label>
                    <select class="form-select" id="id_tipo_orden" name="tipo" required>
                        <option value="">Seleccione el tipo</option>
                        @foreach ($tipos as $tipo)
                            <option value="{{ $tipo->nombre }}">{{ $tipo->nombre }}</option>
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
                    <input type="text" class="form-control" id="responsable" name="responsable" required>
                    {{-- <select class="form-select" id="responsable" name="responsable" required>
                        <option value="">Seleccione un responsable</option>
                        @foreach ($personal as $persona)
                            <option value="{{ $persona->id_personal }}">{{ $persona->nombre }} - {{ $persona->cargo }}</option>
                        @endforeach
                    </select> --}}
                </div>
            </div>

            <div class="mb-3">
                <label for="descripcion_1" class="form-label">Descripción del Problema/Tarea</label>
                <textarea class="form-control" id="descripcion_1" name="descripcion_1" rows="4" required></textarea>
            </div>

            <div class="col-md-6 mb-3">
                    <label for="foto_vehiculo" class="form-label">
                        Registro Fotografico (Usar Cámara en Móvil)
                        <i class="bi bi-camera-fill text-primary ms-1"></i> 
                    </label>
                    <input 
                        type="file" 
                        name="fotos_orden[]" 
                        id="fotos_orden" 
                        class="form-control" 
                        accept="image/*" 
                        capture="environment" 
                        required
                        multiple
                    >
                    <small class="form-text text-muted">En dispositivos móviles, esto abrirá la cámara trasera.</small>
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
             <div>
                Estatus Orden
                       <select name="estatus" id="estatus" class="form-control">
                                            <option value="2" selected>Abierta</option>
                                            <option value="1" >Cerrada</option>
                                            <option value="4" >Anulada</option>
                                        </select>
                        
            </div>
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
                <h5 class="modal-title" id="searchSupplyModalLabel">Gestionar Repuestos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                
                {{-- Navegación por pestañas --}}
                <ul class="nav nav-tabs mb-3" id="supplyTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="search-tab" data-bs-toggle="tab" data-bs-target="#search-pane" type="button" role="tab" aria-controls="search-pane" aria-selected="true">
                            <i class="bi bi-list-check me-2"></i> Buscar en Inventario
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual-pane" type="button" role="tab" aria-controls="manual-pane" aria-selected="false">
                            <i class="bi bi-pencil-square me-2"></i> Añadir Manualmente
                        </button>
                    </li>
                </ul>

                {{-- Contenido de las pestañas --}}
                <div class="tab-content" id="supplyTabsContent">
                    
                    {{-- Pestaña 1: Búsqueda en Inventario --}}
                    <div class="tab-pane fade show active" id="search-pane" role="tabpanel" aria-labelledby="search-tab" tabindex="0">
                        <div class="mb-3">
                            <input type="text" id="supply-search-input" class="form-control" placeholder="Buscar por código o descripción...">
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th style="width: 15%;">Código</th>
                                        <th style="width: 40%;">Descripción</th>
                                        <th style="width: 15%;">Existencia</th>
                                        <th style="width: 15%;">Cantidad</th>
                                        <th style="width: 15%;" class="text-center"></th>
                                    </tr>
                                </thead>
                                <tbody id="search-results-body">
                                    <tr><td colspan="5" class="text-center text-muted">Escribir para buscar repuestos.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    {{-- Pestaña 2: Entrada Manual --}}
                    <div class="tab-pane fade" id="manual-pane" role="tabpanel" aria-labelledby="manual-tab" tabindex="0">
                        <div class="alert alert-info" role="alert">
                            Use esta opción para registrar suministros que no están en el inventario.
                        </div>
                        <div class="mb-3">
                            <label for="manual-descripcion" class="form-label">Descripción</label>
                            <input type="text" id="manual-descripcion" class="form-control" placeholder="Ej: Tornillos Varios, Limpiaparabrisas Genérico" required>
                        </div>
                        <div class="mb-3">
                            <label for="manual-cantidad" class="form-label">Cantidad</label>
                            <input type="number" id="manual-cantidad" class="form-control" value="1" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="manual-cantidad" class="form-label">Precio</label>
                            <input type="number" id="manual-cantidad" class="form-control" value="1" min="1" >
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-warning" id="add-manual-supply-btn">
                                <i class="bi bi-plus-square me-2"></i> Añadir Suministro Manual
                            </button>
                        </div>
                    </div>
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
        let manualSupplyCounter = 0; // Contador para IDs únicos de suministros manuales

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
            const hiddenInput = document.getElementById('suministros-input');
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

         // Función para añadir suministro manual
    function addManualSupply(descripcion, cantidad) {
        if (!descripcion || cantidad <= 0) {
             // Usar una función de feedback más robusta que Swal o un mensaje en el modal
             console.error("Faltan datos para el suministro manual.");
             return;
        }

        // Generar un ID temporal único para este suministro manual
        manualSupplyCounter++;
        const manualId = 'MANUAL_' + manualSupplyCounter;

        const itemData = {
            id: manualId,
            codigo: 'N/A', // No tiene código de inventario
            descripcion: descripcion,
            existencia: 'N/A', // No tiene existencia de inventario
            cantidad: cantidad
        };

        selectedSupplies[manualId] = itemData;
        renderSuppliesTable();

        // Limpiar el formulario manual
        document.getElementById('manual-descripcion').value = '';
        document.getElementById('manual-cantidad').value = 1;

        // Cerrar el modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('searchSupplyModal'));
        if (modal) {
            modal.hide();
        }
    }

    });
</script>
@endpush
@endsection