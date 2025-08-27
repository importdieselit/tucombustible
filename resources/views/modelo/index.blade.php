@extends('layouts.app')

@section('title', 'Gestión de Modelos')
@php
    // Datos de prueba para las marcas
        $marcas = collect([
            (object)['id' => 1, 'nombre' => 'Toyota'],
            (object)['id' => 2, 'nombre' => 'Ford'],
            (object)['id' => 3, 'nombre' => 'Chevrolet'],
            (object)['id' => 4, 'nombre' => 'Nissan'],
            (object)['id' => 5, 'nombre' => 'BMW'],
        ]);

        // Datos de prueba para los modelos
        $modelos = collect([
            (object)['id' => 101, 'nombre' => 'Corolla', 'marca_id' => 1, 'estado' => 'activo'],
            (object)['id' => 102, 'nombre' => 'Hilux', 'marca_id' => 1, 'estado' => 'activo'],
            (object)['id' => 103, 'nombre' => 'Ranger', 'marca_id' => 2, 'estado' => 'activo'],
            (object)['id' => 104, 'nombre' => 'F-150', 'marca_id' => 2, 'estado' => 'inactivo'],
            (object)['id' => 105, 'nombre' => 'Spark', 'marca_id' => 3, 'estado' => 'activo'],
            (object)['id' => 106, 'nombre' => 'Silverado', 'marca_id' => 3, 'estado' => 'activo'],
            (object)['id' => 107, 'nombre' => 'Versa', 'marca_id' => 4, 'estado' => 'activo'],
            (object)['id' => 108, 'nombre' => '3 Series', 'marca_id' => 5, 'estado' => 'activo'],
        ]);
@endphp
@section('content')
<div class="container-fluid mt-4">
    <div class="row page-titles mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h3 class="text-themecolor mb-0">Modelos de Vehículos</h3>
            <button type="button" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#addModeloModal">
                <i class="fas fa-plus-circle me-2"></i> Agregar Modelo
            </button>
        </div>
    </div>

    {{-- Filtro por Marcas --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title mb-3">Filtrar por Marca</h5>
            <select id="marca-filter" class="form-select">
                <option value="all">Todas las Marcas</option>
                @foreach ($marcas as $marca)
                <option value="{{ $marca->id }}">{{ $marca->nombre }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Tabla de Modelos --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-4">Listado de Modelos</h5>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Modelo</th>
                            <th>Marca</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="modelos-table-body">
                        @foreach ($modelos as $modelo)
                        <tr data-id="{{ $modelo->id }}" data-nombre="{{ $modelo->nombre }}" data-marca-id="{{ $modelo->marca_id }}" data-estado="{{ $modelo->estado }}">
                            <td>{{ $modelo->id }}</td>
                            <td>{{ $modelo->nombre }}</td>
                            <td>
                                @foreach ($marcas as $marca)
                                @if ($marca->id === $modelo->marca_id)
                                {{ $marca->nombre }}
                                @endif
                                @endforeach
                            </td>
                            <td>
                                @if ($modelo->estado == 'activo')
                                <span class="badge bg-success">Activo</span>
                                @else
                                <span class="badge bg-danger">Inactivo</span>
                                @endif
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning edit-btn" data-bs-toggle="modal" data-bs-target="#editModeloModal">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                @if ($modelo->estado == 'activo')
                                <button class="btn btn-sm btn-danger disable-btn">
                                    <i class="fas fa-ban"></i> Deshabilitar
                                </button>
                                @else
                                <button class="btn btn-sm btn-success enable-btn">
                                    <i class="fas fa-check-circle"></i> Habilitar
                                </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal para Agregar Modelo --}}
<div class="modal fade" id="addModeloModal" tabindex="-1" aria-labelledby="addModeloModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addModeloModalLabel">Agregar Nuevo Modelo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('modelos.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="modelo-nombre" class="form-label">Nombre del Modelo</label>
                        <input type="text" class="form-control" id="modelo-nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="modelo-marca" class="form-label">Marca</label>
                        <select class="form-select" id="modelo-marca" name="marca_id" required>
                            @foreach ($marcas as $marca)
                            <option value="{{ $marca->id }}">{{ $marca->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar Modelo</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal para Editar Modelo --}}
<div class="modal fade" id="editModeloModal" tabindex="-1" aria-labelledby="editModeloModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModeloModalLabel">Editar Modelo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editModeloForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <input type="hidden" id="edit-modelo-id" name="id">
                    <div class="mb-3">
                        <label for="edit-modelo-nombre" class="form-label">Nombre del Modelo</label>
                        <input type="text" class="form-control" id="edit-modelo-nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit-modelo-marca" class="form-label">Marca</label>
                        <select class="form-select" id="edit-modelo-marca" name="marca_id" required>
                            @foreach ($marcas as $marca)
                            <option value="{{ $marca->id }}">{{ $marca->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modelosData = @json($modelos);
        const marcasData = @json($marcas);
        const tableBody = document.getElementById('modelos-table-body');
        const marcaFilter = document.getElementById('marca-filter');

        // Función para renderizar la tabla según el filtro
        function renderTable(filterId) {
            tableBody.innerHTML = ''; // Limpia la tabla
            
            const filteredModelos = filterId === 'all' 
                ? modelosData
                : modelosData.filter(modelo => modelo.marca_id == filterId);

            filteredModelos.forEach(modelo => {
                const marcaNombre = marcasData.find(m => m.id === modelo.marca_id).nombre;
                const estadoBadge = modelo.estado === 'activo'
                    ? '<span class="badge bg-success">Activo</span>'
                    : '<span class="badge bg-danger">Inactivo</span>';

                const disableButton = modelo.estado === 'activo'
                    ? `<button class="btn btn-sm btn-danger disable-btn">
                            <i class="fas fa-ban"></i> Deshabilitar
                        </button>`
                    : `<button class="btn btn-sm btn-success enable-btn">
                            <i class="fas fa-check-circle"></i> Habilitar
                        </button>`;

                const row = `
                    <tr data-id="${modelo.id}" data-nombre="${modelo.nombre}" data-marca-id="${modelo.marca_id}" data-estado="${modelo.estado}">
                        <td>${modelo.id}</td>
                        <td>${modelo.nombre}</td>
                        <td>${marcaNombre}</td>
                        <td>${estadoBadge}</td>
                        <td>
                            <button class="btn btn-sm btn-warning edit-btn" data-bs-toggle="modal" data-bs-target="#editModeloModal">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            ${disableButton}
                        </td>
                    </tr>
                `;
                tableBody.innerHTML += row;
            });

            // Reasignar eventos a los nuevos botones
            assignButtonEvents();
        }

        // Manejador del cambio en el filtro de marca
        marcaFilter.addEventListener('change', function() {
            renderTable(this.value);
        });

        // Función para asignar eventos a los botones de la tabla
        function assignButtonEvents() {
            // Maneja el clic en el botón de Editar
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const row = this.closest('tr');
                    const id = row.dataset.id;
                    const nombre = row.dataset.nombre;
                    const marcaId = row.dataset.marcaId;
                    
                    const form = document.getElementById('editModeloForm');
                    const modalIdInput = document.getElementById('edit-modelo-id');
                    const modalNombreInput = document.getElementById('edit-modelo-nombre');
                    const modalMarcaSelect = document.getElementById('edit-modelo-marca');

                    // Establece los valores en el modal de edición
                    modalIdInput.value = id;
                    modalNombreInput.value = nombre;
                    modalMarcaSelect.value = marcaId;
                    form.action = `/modelos/${id}`; // Actualiza la URL del formulario
                });
            });

            // Maneja el clic en el botón de Deshabilitar/Habilitar
            document.querySelectorAll('.disable-btn, .enable-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const row = this.closest('tr');
                    const id = row.dataset.id;
                    const action = this.classList.contains('disable-btn') ? 'deshabilitar' : 'habilitar';
                    
                    if (confirm(`¿Estás seguro de que quieres ${action} el modelo ${row.dataset.nombre}?`)) {
                        // Simulación de envío de formulario para deshabilitar
                        window.location.href = `/modelos/${id}/disable`;
                    }
                });
            });
        }
        
        // Renderiza la tabla inicial
        renderTable('all');
    });
</script>
@endsection
