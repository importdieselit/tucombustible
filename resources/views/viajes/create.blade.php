@extends('layouts.app')

@section('title', 'Planificar Nuevo Viaje con Despachos')

@section('content')
<div class="container mt-5">
    <div class="card shadow-lg">
        <div class="card-header bg-success text-white">
            <h3 class="mb-0"><i class="bi bi-calendar-plus me-2"></i> Planificación de Viaje y Despachos</h3>
        </div>
        <div class="card-body">
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            
            <!-- El formulario enviará a ViajesController@store -->
            <form action="{{ route('viajes.store') }}" method="POST">
                @csrf
                
                <!-- 1. Detalle del Viaje (Fijo) -->
                <h4 class="mt-4 mb-3 text-success border-bottom pb-1">Detalles del Viaje</h4>
                <div class="row g-3 mb-4">
                    <!-- Ciudad de Destino -->
                    <div class="col-md-6">
                        <label for="destino_ciudad" class="form-label fw-bold">Ciudad de Destino</label>
                        <select name="destino_ciudad" id="destino_ciudad" class="form-select @error('destino_ciudad') is-invalid @enderror" required>
                            <option value="">Seleccione un destino del Tabulador</option>
                            
                            <!-- Cargar las ciudades del TabuladorViatico -->
                            @foreach($destino_ciudades as $ciudad)
                                <option value="{{ $ciudad }}" {{ old('destino_ciudad') == $ciudad ? 'selected' : '' }}>{{ $ciudad }}</option>
                            @endforeach
                        </select>
                        @error('destino_ciudad')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Fecha de Salida -->
                    <div class="col-md-6">
                        <label for="fecha_salida" class="form-label fw-bold">Fecha de Salida</label>
                        <input type="date" name="fecha_salida" id="fecha_salida" class="form-control @error('fecha_salida') is-invalid @enderror" value="{{ old('fecha_salida', now()->toDateString()) }}" required>
                        @error('fecha_salida')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- 2. Sección de Despachos (Dinámico) -->
                <h4 class="mt-4 mb-3 text-success border-bottom pb-1 d-flex justify-content-between align-items-center">
                    Detalles de Despachos 
                    <button type="button" class="btn btn-sm btn-primary" id="add-despacho">
                        <i class="bi bi-plus-circle"></i> Agregar Despacho
                    </button>
                </h4>
                
                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="despachos-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 35%;">Cliente Registrado</th>
                                <th style="width: 35%;">Otro Cliente (Si no está en lista)</th>
                                <th style="width: 20%;">Litros</th>
                                <th style="width: 10%;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Las filas se añadirán aquí mediante JavaScript -->
                            
                            <!-- Manejo de errores de validación de Laravel para filas existentes (si el formulario falla) -->
                            @if(old('despachos'))
                                @foreach(old('despachos') as $index => $despacho)
                                    <tr id="row-{{ $index }}">
                                        <td>
                                            <select name="despachos[{{ $index }}][cliente_id]" class="form-select form-select-sm cliente-select @error("despachos.{$index}.cliente_id") is-invalid @enderror" {{ old("despachos.{$index}.otro_cliente") ? 'disabled' : '' }}>
                                                <option value="">-- Seleccione Cliente --</option>
                                                @foreach($clientes as $cliente)
                                                    <option value="{{ $cliente->id }}" {{ old("despachos.{$index}.cliente_id") == $cliente->id ? 'selected' : '' }}>{{ $cliente->nombre }}</option>
                                                @endforeach
                                            </select>
                                            @error("despachos.{$index}.cliente_id")
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="text" name="despachos[{{ $index }}][otro_cliente]" class="form-control form-control-sm otro-cliente-input @error("despachos.{$index}.otro_cliente") is-invalid @enderror" placeholder="Nombre o Razón Social" value="{{ old("despachos.{$index}.otro_cliente") }}" {{ old("despachos.{$index}.cliente_id") ? 'disabled' : '' }}>
                                            @error("despachos.{$index}.otro_cliente")
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="number" name="despachos[{{ $index }}][litros]" class="form-control form-control-sm @error("despachos.{$index}.litros") is-invalid @enderror" placeholder="Cantidad" step="any" required value="{{ old("despachos.{$index}.litros") }}">
                                            @error("despachos.{$index}.litros")
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm remove-despacho" data-index="{{ $index }}"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif

                        </tbody>
                    </table>
                </div>
                
                <!-- Este error general es para cuando el array 'despachos' está vacío -->
                @error('despachos')
                    <div class="alert alert-danger mt-2">Debe agregar al menos un despacho.</div>
                @enderror

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success btn-lg mt-3">
                        <i class="bi bi-save me-2"></i> Crear Viaje (Pendiente de Asignación)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const despachosTableBody = document.getElementById('despachos-table').getElementsByTagName('tbody')[0];
        const addDespachoButton = document.getElementById('add-despacho');
        // Inicializa el índice para que continúe donde se quedó si hay old('despachos')
        let despachoIndex = {{ old('despachos') ? max(array_keys(old('despachos'))) + 1 : 0 }};
        
        // Función para aplicar la lógica de exclusividad
        const applyExclusivityLogic = (row) => {
            const select = row.querySelector('.cliente-select');
            const input = row.querySelector('.otro-cliente-input');
            
            if (!select || !input) return; // Salir si los elementos no existen
            
            // Listener para el Select
            select.addEventListener('change', function() {
                if (this.value) {
                    input.value = '';
                    input.disabled = true;
                } else {
                    // Solo habilitar si el input no tiene valor
                    if (!input.value) {
                        input.disabled = false;
                    }
                }
            });
            
            // Listener para el Input
            input.addEventListener('input', function() {
                if (this.value) {
                    select.value = '';
                    select.disabled = true;
                } else {
                    // Solo habilitar si el select no tiene valor
                    if (!select.value) {
                        select.disabled = false;
                    }
                }
            });
            
            // Lógica de estado inicial (necesaria para filas viejas o al cargar)
            if (select.value) {
                input.disabled = true;
            } else if (input.value) {
                select.disabled = true;
            }
        };

        // Plantilla de la Fila
        const createRow = (initialData = {}) => {
            const index = despachoIndex++;
            const newRow = despachosTableBody.insertRow();
            newRow.id = `row-${index}`;
            
            // Obtener las opciones de cliente del servidor para el HTML
            const clienteOptions = `@foreach($clientes as $cliente)<option value=\"{{ $cliente->id }}\">{{ $cliente->nombre }}</option>@endforeach`;

            // Columna Cliente Registrado
            newRow.insertCell(0).innerHTML = `
                <select name="despachos[${index}][cliente_id]" class="form-select form-select-sm cliente-select" ${initialData.otro_cliente ? 'disabled' : ''}>
                    <option value="">-- Seleccione Cliente --</option>
                    ${clienteOptions}
                </select>
            `;

            // Columna Otro Cliente
            newRow.insertCell(1).innerHTML = `
                <input type="text" name="despachos[${index}][otro_cliente]" class="form-control form-control-sm otro-cliente-input" placeholder="Nombre o Razón Social" ${initialData.cliente_id ? 'disabled' : ''}>
            `;

            // Columna Litros
            newRow.insertCell(2).innerHTML = `
                <input type="number" name="despachos[${index}][litros]" class="form-control form-control-sm" placeholder="Cantidad" step="any" required>
            `;

            // Columna Acción
            newRow.insertCell(3).innerHTML = `
                <button type="button" class="btn btn-danger btn-sm remove-despacho" data-index="${index}"><i class="bi bi-trash"></i></button>
            `;
            
            // Aplicar la lógica de exclusividad a la nueva fila
            applyExclusivityLogic(newRow);
        };
        
        // ----------------------------------------------------
        // Lógica de Inicialización
        // ----------------------------------------------------
        
        // 1. Si no hay filas de old('despachos'), agrega la primera fila.
        // Esto previene duplicados si la validación falla y Laravel ya restauró las filas.
        if (despachosTableBody.rows.length === 0) {
             createRow();
        } else {
             // 2. Si hay filas de old('despachos'), aplicarles la lógica de exclusividad
            Array.from(despachosTableBody.rows).forEach(applyExclusivityLogic);
        }

        
        // Manejador del botón 'Agregar Despacho'
        addDespachoButton.addEventListener('click', createRow);

        // Manejador del botón 'Eliminar'
        despachosTableBody.addEventListener('click', function(e) {
            if (e.target.closest('.remove-despacho')) {
                const button = e.target.closest('.remove-despacho');
                const row = button.closest('tr');
                
                // Solo eliminar si quedan más de 1 filas
                if (despachosTableBody.rows.length > 1) {
                    row.remove();
                } else {
                    // Cambiado de alert() a un mensaje en el console para cumplir con las reglas del ambiente
                    console.error('Debe haber al menos un despacho por viaje.'); 
                    // Podrías añadir un pequeño modal o toast aquí en un entorno real.
                }
            }
        });

    });
</script>
@endpush
