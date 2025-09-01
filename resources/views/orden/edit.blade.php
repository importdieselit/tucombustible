@extends('layouts.app')

@section('title', 'Editar Orden de Trabajo')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="mb-2">Editar Orden de Trabajo #{{ $item->nro_orden }}</h1>
        <p class="text-muted">Modifica los detalles de la orden de reparación o mantenimiento.</p>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="card-title m-0">Datos de la Orden</h5>
    </div>
    <div class="card-body bg-white">
        <form action="{{ route('ordenes.update', $item->id) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- Datos de la Orden --}}
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="vehiculo_id" class="form-label">Vehículo</label>
                     {{ $item->vehiculo()->flota }}    <strong>{{ $item->vehiculo()->placa }}</strong> - {{ $item->vehiculo()->marca()->marca }} {{ $item->vehiculo()->modelo()->modelo }}
                    
                </div>
                <div class="col-md-6 mb-3">
                    <label for="kilometraje" class="form-label">Kilometraje</label>
                    <input type="number" class="form-control" id="kilometraje" name="kilometraje" value="{{ $item->kilometraje }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="responsable" class="form-label">Responsable Asignado</label>
                    {{ $item->responsable}}                 
                </div>
            
                <div class="col-md-6 mb-3">
                    <label for="estatus" class="form-label">Estatus</label>
                        <span class="badge bg-{{ $item->estatus()->css }}" title="{{ $item->estatus()->descripcion }}">
                            <i class="mr-1 fa-solid {{ $item->estatus()->icon_orden }}"></i>
                           {{ $item->estatus()->orden }}
                        </span>
                </div>
                {{-- <div class="col-md-6 mb-3">
                    <label for="tipo_orden" class="form-label">Tipo de Orden</label>
                    <select class="form-select" id="tipo_orden" name="id_tipo_orden" required>
                        <option value="">Seleccione un tipo</option>
                        @foreach ($tipos_orden as $tipo)
                            <option value="{{ $tipo->id }}" {{ $item->id_tipo_orden == $tipo->id ? 'selected' : '' }}>
                                {{ $tipo->tipo }}
                            </option>
                        @endforeach
                    </select>
                </div> --}}
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="fecha_in" class="form-label">Fecha de Apertura</label>
                    <input type="date" class="form-control" id="fecha_in" name="fecha_in" value="{{ \Carbon\Carbon::parse($item->fecha_in)->format('Y-m-d') }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="hora_in" class="form-label">Hora de Apertura</label>
                    <input type="time" class="form-control" id="hora_in" name="hora_in" value="{{ $item->hora_in }}" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="descripcion_1" class="form-label">Descripción del Problema/Tarea</label>
                <textarea class="form-control" id="descripcion_1" name="descripcion_1" rows="4" required>{{ $item->descripcion_1 }}</textarea>
            </div>

            <div class="mb-3">
                <label for="observacion" class="form-label">Observaciones</label>
                <textarea class="form-control" id="observacion" name="observacion" rows="3">{{ $item->observacion }}</textarea>
            </div>
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
                            <th>Costo</th>
                            <th>Estatus</th>
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

            <div class="d-flex justify-content-between">
                <a href="{{ route('ordenes.list') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Actualizar Orden</button>
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
@php 
use App\Models\InventarioSuministro;
 $insumos = InventarioSuministro::with('inventario')->where('id_orden', $item->id)->get();
 
@endphp
@push('scripts')
    <!-- Script de jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Script de Bootstrap 5 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {


         let selectedSupplies = {};

            // Cargar los suministros existentes
            const existingSupplies = @json($insumos);
            console.log(existingSupplies);
            existingSupplies.forEach(supply => {
                selectedSupplies[supply.id_inventario_suministro] = {
                    id: supply.id_inventario_suministro,
                    db_id: supply.id_inventario_suministro, // Guardar el ID de la tabla pivote
                    codigo: supply.inventario.codigo,
                    estatus: supply.estatus,
                    descripcion: supply.inventario.descripcion,
                    existencia: supply.inventario.existencia_actual,
                    cantidad: supply.cantidad,
                    costo_unitario: supply.inventario.costo,
                };
            });
        
        const searchResultsTable = document.getElementById('searchResultsTable');
        const suppliesTableBody = document.querySelector('#selectedSuppliesTable tbody');
        const form = document.getElementById('orden-form');


        // Función para renderizar la tabla de insumos seleccionados
         function renderSuppliesTable() {
            suppliesTableBody.innerHTML = ''; 
            let html = '';
            const supplyIds = Object.keys(selectedSupplies);
            let totalCosto = 0;
            if (supplyIds.length > 0) {
                supplyIds.forEach(id => {
                    const supply = selectedSupplies[id];
                    let estatusText;
                    let estatusBadgeClass;
                    let costo = supply.costo_unitario * supply.cantidad;
                    totalCosto += costo;
                    switch (supply.estatus) {
                        case 1:
                            estatusText = 'Solicitado';
                            estatusBadgeClass = 'bg-primary';
                            break;
                        case 2:
                            estatusText = 'Aprobado';
                            estatusBadgeClass = 'bg-success';
                            break;
                        case 3:
                            estatusText = 'Despachado';
                            estatusBadgeClass = 'bg-info';
                            break;
                        case 4:
                            estatusText = 'Rechazado';
                            estatusBadgeClass = 'bg-danger';
                            break;
                        default:
                            estatusText = 'Desconocido';
                            estatusBadgeClass = 'bg-secondary';
                            break;
                    }
                     const isEditable = supply.estatus !== 2 && supply.estatus !== 3;

                    html += `
                        <tr data-supply-id="${supply.id}">
                            <td>${supply.codigo}</td>
                            <td>${supply.descripcion}</td>
                            <td>
                                ${isEditable ? 
                                    `<input type="number" class="form-control form-control-sm supply-quantity" value="${supply.cantidad}" min="1" max="${supply.existencia}" data-db-id="${supply.id}">` : 
                                    supply.cantidad
                                }
                            </td>
                            <td>$ ${costo} </td>
                            <td><span class="badge ${estatusBadgeClass}">${estatusText}</span></td>
                            <td>
                                ${isEditable ? 
                                    `<button type="button" class="btn btn-sm btn-danger delete-supply" data-db-id="${supply.id}">
                                        <i class="bi bi-trash"></i> Eliminar
                                    </button>` : 
                                    ''
                                }
                            </td>
                        </tr>
                    `;
                });
                html += `
                    <tr>
                        <td colspan="3" class="text-end"><strong>Total Costo:</strong></td>
                        <td colspan="3"><strong>$ ${totalCosto.toFixed(2)}</strong></td>
                    </tr>
                `;
            } else {
                html = `<tr><td colspan="5" class="text-center">No hay insumos asignados.</td></tr>`;
            }
            suppliesTableBody.innerHTML = html;
        }

        // Manejar el evento de búsqueda con un pequeño retraso (debounce)
        let timeout = null;
        renderSuppliesTable();

 
        const supplySearchInput = document.getElementById('supplySearchInput');
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


        supplySearchInput.addEventListener('keyup', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                searchSupplies(this.value);
            }, 300);
        });

        // Renderiza los resultados de la búsqueda
        const renderSearchResults = (data) => {
            let html = '';
            if (data.length > 0) {
                data.forEach(item => {
                    if(item.existencia <= 0) {
                        return; // Saltar insumos sin existencia
                    }
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
        
        // Manejar el evento de agregar un suministro desde el modal
        searchResultsTable.addEventListener('click', async function(e) {
            if (e.target.classList.contains('add-supply')) {
                const itemId = e.target.dataset.itemId;
                const row = e.target.closest('tr');
                const quantityInput = row.querySelector('.search-quantity');
                const quantity = parseInt(quantityInput.value, 10);
                const orderId = '{{ $item->id }}';

                if (!orderId ) {
                    alert('Por favor, complete los campos "Número de Orden" y "Vehículo" primero.');
                    return;
                }

                // Deshabilitar el botón para evitar múltiples clics
                e.target.disabled = true;

                try {
                    const response = await fetch('{{ route("ordenes.supplies.store") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            id_orden: orderId,
                            id_inventario: itemId,
                            cantidad: quantity
                        })
                    });
                    
                    const result = await response.json();
                    console.log(result);
                    if (result.success) {
                        const itemData = {
                            id: itemId,
                            db_id: result.supply.id_inventario_suministro, // ID del registro en la DB
                            codigo: row.cells[0].textContent,
                            descripcion: row.cells[1].textContent,
                            estatus: 1, // Nuevo insumo siempre comienza como 'Solicitado'
                            existencia: parseInt(row.cells[2].textContent, 10),
                            cantidad: result.supply.cantidad
                        };
                        selectedSupplies[itemData.db_id] = itemData;
                        renderSuppliesTable();

                          // ** FIX: Usar getOrCreateInstance para asegurar que el objeto no es nulo **
                        const modalElement = document.getElementById('searchSupplyModal');
                        const modalbackdrop = document.querySelector('.modal-backdrop');
                        if (modalbackdrop) {   
                            modalbackdrop.remove();
                        }
                        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
                        modal.hide()
                    } else {
                        alert('Error al guardar el insumo: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    console.log('Error:', error);
                    alert('Error de conexión. Intente de nuevo.');
                } finally {
                    e.target.disabled = false;
                }
            }
        });

        // Manejar el evento de eliminar un suministro de la tabla
        suppliesTableBody.addEventListener('click', async function(e) {
            if (e.target.closest('.delete-supply')) {
                const button = e.target.closest('.delete-supply');
                const dbId = button.dataset.dbId;

                if (confirm('¿Está seguro de que desea eliminar este insumo?')) {
                    try {
                        const response = await fetch(`/ordenes/supplies/${dbId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        const result = await response.json();
                        console.log(result);
                        if (result.success) {
                            delete selectedSupplies[dbId];
                            renderSuppliesTable();
                        } else {
                            alert('Error al eliminar el insumo: ' + result.message);
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('Error de conexión. Intente de nuevo.');
                    }
                }
            }
        });

        // Manejar el evento de cambio de cantidad en tiempo real
        suppliesTableBody.addEventListener('change', async function(e) {
            if (e.target.classList.contains('supply-quantity')) {
                const input = e.target;
                const dbId = input.dataset.dbId;
                const newQuantity = parseInt(input.value, 10);
                
                if (newQuantity <= 0 || newQuantity > parseInt(input.max, 10)) {
                    alert(`La cantidad debe ser entre 1 y ${input.max}.`);
                    input.value = selectedSupplies[dbId].cantidad;
                    return;
                }

                try {
                    const response = await fetch(`/ordenes/supplies/${dbId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ cantidad: newQuantity })
                    });
                    const result = await response.json();

                    if (result.success) {
                        selectedSupplies[dbId].cantidad = newQuantity;
                        renderSuppliesTable();
                    } else {
                        alert('Error al actualizar la cantidad: ' + result.message);
                        // Revertir el valor si falla la actualización
                        input.value = selectedSupplies[dbId].cantidad;
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error de conexión. Intente de nuevo.');
                    input.value = selectedSupplies[dbId].cantidad;
                }
            }
        });
    });
</script>
@endpush
@endsection
