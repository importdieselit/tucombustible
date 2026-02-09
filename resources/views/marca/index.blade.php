@extends('layouts.app')

@section('title', 'Gestión de Marcas')


@php
     // Datos de prueba para las marcas
        $marcas = collect([
            (object)['id' => 1, 'nombre' => 'Toyota', 'estado' => 'activo'],
            (object)['id' => 2, 'nombre' => 'Ford', 'estado' => 'activo'],
            (object)['id' => 3, 'nombre' => 'Chevrolet', 'estado' => 'activo'],
            (object)['id' => 4, 'nombre' => 'Nissan', 'estado' => 'inactivo'],
            (object)['id' => 5, 'nombre' => 'BMW', 'estado' => 'activo'],
        ]);

        // Datos de prueba para el resumen de vehículos por marca
        $vehiculos_por_marca = [
            'Toyota' => 12,
            'Ford' => 8,
            'Chevrolet' => 5,
            'Nissan' => 3,
            'BMW' => 2,
        ];
        
@endphp


@section('content')
<div class="container-fluid mt-4">
    <div class="row page-titles mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h3 class="text-themecolor mb-0">Marcas de Vehículos</h3>
            <button type="button" class="btn btn-primary d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#addMarcaModal">
                <i class="fas fa-plus-circle me-2"></i> Agregar Marca
            </button>
        </div>
    </div>

    {{-- Gráfico de vehículos por marca --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Resumen de Vehículos por Marca</h5>
        </div>
        <div class="card-body">
            <div id="vehiculos-por-marca-chart" style="height: 300px;"></div>
        </div>
    </div>

    {{-- Tabla de Marcas --}}
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-4">Listado de Marcas</h5>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($marcas as $marca)
                        <tr data-id="{{ $marca->id }}" data-nombre="{{ $marca->nombre }}" data-estado="{{ $marca->estado }}">
                            <td>{{ $marca->id }}</td>
                            <td>{{ $marca->nombre }}</td>
                            <td>
                                @if ($marca->estado == 'activo')
                                <span class="badge bg-success">Activa</span>
                                @else
                                <span class="badge bg-danger">Inactiva</span>
                                @endif
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning edit-btn" data-bs-toggle="modal" data-bs-target="#editMarcaModal">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                @if ($marca->estado == 'activo')
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

{{-- Modal para Agregar Marca --}}
<div class="modal fade" id="addMarcaModal" tabindex="-1" aria-labelledby="addMarcaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addMarcaModalLabel">Agregar Nueva Marca</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('marcas.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="marca-nombre" class="form-label">Nombre de la Marca</label>
                        <input type="text" class="form-control" id="marca-nombre" name="nombre" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar Marca</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal para Editar Marca --}}
<div class="modal fade" id="editMarcaModal" tabindex="-1" aria-labelledby="editMarcaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editMarcaModalLabel">Editar Marca</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editMarcaForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <input type="hidden" id="edit-marca-id" name="id">
                    <div class="mb-3">
                        <label for="edit-marca-nombre" class="form-label">Nombre de la Marca</label>
                        <input type="text" class="form-control" id="edit-marca-nombre" name="nombre" required>
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

{{-- Scripts para Highcharts --}}
<script src="https://code.highcharts.com/highcharts.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Maneja el clic en el botón de Editar
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function () {
                const row = this.closest('tr');
                const id = row.dataset.id;
                const nombre = row.dataset.nombre;
                
                const form = document.getElementById('editMarcaForm');
                const modalIdInput = document.getElementById('edit-marca-id');
                const modalNombreInput = document.getElementById('edit-marca-nombre');

                // Establece los valores en el modal de edición
                modalIdInput.value = id;
                modalNombreInput.value = nombre;
                form.action = `/marcas/${id}`; // Actualiza la URL del formulario
            });
        });

        // Maneja el clic en el botón de Deshabilitar/Habilitar
        document.querySelectorAll('.disable-btn, .enable-btn').forEach(button => {
            button.addEventListener('click', function () {
                const row = this.closest('tr');
                const id = row.dataset.id;
                const action = this.classList.contains('disable-btn') ? 'deshabilitar' : 'habilitar';
                
                if (confirm(`¿Estás seguro de que quieres ${action} la marca ${row.dataset.nombre}?`)) {
                    // Simulación de envío de formulario para deshabilitar
                    window.location.href = `/marcas/${id}/disable`;
                }
            });
        });
        
        // --- Highcharts para el gráfico de barras ---
        const vehiculosPorMarca = @json($vehiculos_por_marca);

        Highcharts.chart('vehiculos-por-marca-chart', {
            chart: {
                type: 'bar'
            },
            title: {
                text: 'Cantidad de Vehículos por Marca',
                align: 'left'
            },
            xAxis: {
                categories: vehiculosPorMarca.map(item => item.name),
                title: {
                    text: null
                }
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Cantidad de Vehículos',
                    align: 'high'
                },
                labels: {
                    overflow: 'justify'
                }
            },
            tooltip: {
                valueSuffix: ' vehículos'
            },
            plotOptions: {
                bar: {
                    dataLabels: {
                        enabled: true
                    }
                }
            },
            credits: {
                enabled: false
            },
            series: [{
                name: 'Vehículos',
                data: vehiculosPorMarca
            }]
        });
    });
</script>
@endsection
