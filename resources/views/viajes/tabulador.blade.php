@extends('layouts.app')

@section('title', 'Edición de Tabulador de Viáticos Base y Parámetros')

@section('content')
<!-- Simulación de variables de datos (DEBE SER REEMPLAZADO POR LA LÓGICA DEL CONTROLADOR) -->
<?php
$ruta_tabulador_update = route('viaticos.tabulador.update'); // Ruta para actualizar el tabulador principal
$ruta_parametros_update = route('viaticos.parametros.update'); // Ruta para actualizar parámetros


?>
<!-- Fin Simulación -->

<div class="container-fluid mt-4">
    <h1 class="mb-4 text-secondary"><i class="bi bi-table me-2"></i> Tabulador Base de Viáticos (Edición Rápida)</h1>
    <p class="mb-4">Haga clic en el monto de cualquier celda editable para modificar el valor. Presione **Enter** o haga clic afuera para guardar el cambio.</p>

    {{-- Aquí iría la lógica de session('success') --}}
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    
    <div class="table-responsive shadow-lg rounded">
        <table class="table table-bordered table-striped table-sm text-center align-middle" id="tabuladorTable">
            <thead class="table-dark sticky-top">
                <tr>
                    <th rowspan="2">ID</th>
                    <th rowspan="2" style="min-width: 150px;">Destino</th>
                    <th colspan="3">Pago por Viaje (USD)</th>
                    <th colspan="4">Viáticos Diarios (USD)</th> 
                    <th rowspan="2">Cantidad Peajes</th>
                    <th rowspan="2">Peajes I/V (USD)</th> {{-- Campo calculado, no editable --}}
                </tr>
                <tr>
                    <th>Chofer Ejecutivo</th>
                    <th>Chofer</th>
                    <th>Ayudante</th>
                    <th>Desayuno</th>
                    <th>Almuerzo</th>
                    <th>Cena</th>
                    <th>Pernocta (USD)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tabulador as $item)
                    <tr data-id="{{ $item->id }}">
                        <td>{{ $item->id }}</td>
                        <td class="bg-light text-start text-dark fw-bold">{{ $item->destino }}</td>
                        {{-- Pagos por Viaje --}}
                        <td data-field="pago_chofer_ejecutivo" class="editable-cell text-primary">${{ number_format($item->pago_chofer_ejecutivo, 2) }}</td>
                        <td data-field="pago_chofer" class="editable-cell text-primary">${{ number_format($item->pago_chofer, 2) }}</td>
                        <td data-field="pago_ayudante" class="editable-cell text-primary">${{ number_format($item->pago_ayudante, 2) }}</td>
                        {{-- Viáticos Diarios --}}
                        <td data-field="viatico_desayuno" class="editable-cell">${{ number_format($item->viatico_desayuno, 2) }}</td>
                        <td data-field="viatico_almuerzo" class="editable-cell">${{ number_format($item->viatico_almuerzo, 2) }}</td>
                        <td data-field="viatico_cena" class="editable-cell">${{ number_format($item->viatico_cena, 2) }}</td>
                        {{-- Pernocta y Peajes --}}
                        <td data-field="costo_pernocta" class="editable-cell text-success">${{ number_format($item->costo_pernocta, 2) }}</td>
                        <td data-field="peajes" class="editable-cell text-success">{{ $item->peajes }}</td>
                        {{-- Cálculo de Peajes I/V (USA EL VALOR DE $parametros->peaje_unitario) --}}
                        <td data-field="total_peajes" class="total-peajes-cell text-success">${{ number_format($item->peajes * $parametros->peaje, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted">No hay datos cargados en el Tabulador de Viáticos.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Nueva Tabla de Parámetros Globales -->
    <div class="card shadow-lg mt-5">
        <div class="card-header bg-secondary text-white fw-bold">Parámetros Globales de Viáticos (USD)</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0 align-middle" id="parametrosTable">
                    <tbody>
                        {{-- Peaje Unitario (Editable) --}}
                        <tr data-id="{{ $parametros->id }}">
                            <td class="bg-light text-start fw-bold" style="width: 50%;">Peaje Unitario (Valor por Peaje)</td>
                            <td data-field="peaje" class="editable-param-cell text-success text-end pe-4" style="width: 50%;">
                                ${{ number_format($parametros['peaje'], 2) }}
                            </td>
                        </tr>
                        {{-- Costo Desayuno (Editable) --}}
                        <tr data-id="{{ $parametros->id }}">
                            <td class="bg-light text-start fw-bold">Viático Desayuno</td>
                            <td data-field="desayuno" class="editable-param-cell text-end pe-4">
                                ${{ number_format($parametros['desayuno'], 2) }}
                            </td>
                        </tr>
                        {{-- Costo Almuerzo (Editable) --}}
                        <tr data-id="{{ $parametros->id }}">
                            <td class="bg-light text-start fw-bold">Viático Almuerzo</td>
                            <td data-field="almuerzo" class="editable-param-cell text-end pe-4">
                                ${{ number_format($parametros['almuerzo'], 2) }}
                            </td>
                        </tr>
                        {{-- Costo Cena (Editable) --}}
                        {{-- <tr data-id="{{ $parametros->id }}">
                            <td class="bg-light text-start fw-bold">Viático Cena</td>
                            <td data-field="costo_cena" class="editable-param-cell text-end pe-4">
                                ${{ number_format($parametros->cena, 2) }}
                            </td>
                        </tr> --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Fin Nueva Tabla de Parámetros Globales -->

</div>
@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawR/Y1/fMFa6B2/S6iK5F8FzI7tFz5kF8FzE=" crossorigin="anonymous"></script>
<script>
    $(document).ready(function() {
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        
        // Rutas (usamos variables de Blade para obtenerlas dinámicamente)
        const tabuladorUpdateRoute = '{{ $ruta_tabulador_update }}';
        const parametrosUpdateRoute = '{{ $ruta_parametros_update }}';
        
        // ----------------------------------------------------
        // FUNCIÓN DE RECALCULO GLOBAL DE PEAJES
        // ----------------------------------------------------
        // Función para actualizar todos los totales de peajes en la tabla principal
        const updateAllTabuladorPeajes = (newUnitCost) => {
            newUnitCost = parseFloat(newUnitCost);
            
            // Recorrer todas las filas del tabulador principal
            $('#tabuladorTable tbody tr').each(function() {
                const $row = $(this);
                // Obtener la cantidad de peajes (peajes)
                const peajesCell = $row.find('td[data-field="peajes"]').text().trim();
                const peajesCantidad = parseInt(peajesCell) || 0;
                
                // Calcular el nuevo total
                const totalPeajes = (peajesCantidad * newUnitCost).toFixed(2);
                
                // Actualizar la celda siguiente (Total Peajes I/V)
                $row.find('.total-peajes-cell').html('$' + totalPeajes);
            });
            console.log(`[AJAX Success] Peaje unitario actualizado. Recalculando ${$('#tabuladorTable tbody tr').length} filas del tabulador.`);
        };
        
        // ----------------------------------------------------
        // MANEJADOR DE EDICIÓN (GENERALIZADO PARA AMBAS TABLAS)
        // ----------------------------------------------------

        // La clase editable-cell es para la tabla principal, editable-param-cell para la de parámetros
        const $editableCells = $('#tabuladorTable, #parametrosTable').find('.editable-cell, .editable-param-cell');

        $editableCells.on('click', function() {
            const $cell = $(this);
            // Evitar editar si ya estamos editando
            if ($cell.find('input').length > 0) return;

            // Determinar si es un campo de cantidad (entero) o monto (decimal)
            const fieldName = $cell.data('field');
            const isPeajesCantidad = fieldName === 'peajes';
            const isPeajeUnitario = fieldName === 'peaje';

            // Obtener el valor actual (quitar el '$' si existe)
            const currentValue = $cell.text().replace('$', '').replace(/,/g, '').trim();

            let inputOptions = {
                class: 'form-control form-control-sm text-center border-primary',
                value: currentValue,
                'data-original-value': currentValue // Guardar valor original
            };

            if (isPeajesCantidad) {
                // Cantidad de Peajes (Entero)
                inputOptions.type = 'number';
                inputOptions.step = '1';
                inputOptions.min = '0';
                inputOptions.value = parseInt(currentValue) || 0;
            } else {
                // Montos (Decimal)
                inputOptions.type = 'number';
                inputOptions.step = '0.01';
                inputOptions.min = '0';
                inputOptions.value = parseFloat(currentValue).toFixed(2);
            }

            // Crear el campo de entrada (input)
            const $input = $('<input>', inputOptions);

            // Reemplazar el contenido de la celda con el input
            $cell.html($input);
            $cell.empty().append($input);
            $input.focus();

            // ----------------------------------------------------
            // FUNCIÓN DE GUARDADO
            // ----------------------------------------------------
            const saveChanges = function() {
                let newValue = $input.val();
                const originalValue = $input.data('original-value');
                const isParamCell = $cell.hasClass('editable-param-cell');
                const updateRoute = isParamCell ? parametrosUpdateRoute : tabuladorUpdateRoute;
                const recordId = $cell.closest('tr').data('id');
                
                // VALIDACIÓN Y PARSEO
                if (isPeajesCantidad) {
                    newValue = parseInt(newValue);
                    if (isNaN(newValue) || newValue < 0 || newValue === parseInt(originalValue)) {
                        $cell.html(originalValue); // Revertir si no es válido o no hay cambio
                        return;
                    }
                } else {
                    newValue = parseFloat(newValue);
                    if (isNaN(newValue) || newValue < 0) {
                        $cell.html('$' + parseFloat(originalValue).toFixed(2));
                        return;
                    }
                    if (newValue.toFixed(2) === parseFloat(originalValue).toFixed(2)) {
                        $cell.html('$' + newValue.toFixed(2));
                        return;
                    }
                    newValue = newValue.toFixed(2); // Formatear para envío
                }

                // Deshabilitar input mientras se guarda
                $input.prop('disabled', true);
                $cell.css('cursor', 'wait');

                // Petición AJAX para guardar en el servidor
                $.ajax({
                    url: updateRoute,
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    data: {
                        id: recordId,
                        field: fieldName,
                        value: newValue
                    },
                    success: function(response) {
                        if (response.success) {
                            let displayValue = (!isPeajesCantidad) ? ('$' + parseFloat(response.new_value).toFixed(2)) : response.new_value;
                            
                            // A. Actualizar la celda editada
                            $cell.html(displayValue);

                            // B. Lógica de Recalculo Específica: Si es 'peaje_unitario', actualizar todos los totales de la tabla principal.
                            if (isParamCell && isPeajeUnitario) {
                                updateAllTabuladorPeajes(response.new_value);
                            } 
                            // C. Lógica de Recalculo Específica: Si es 'peajes' (cantidad), actualizar solo el total de esa fila.
                            else if (!isParamCell && isPeajesCantidad) {
                                // Leer el costo unitario actual desde la tabla de parámetros
                                const currentUnitCost = parseFloat($('#parametrosTable td[data-field="peaje"]').text().replace('$', '').trim());
                                const totalPeajes = (parseInt(response.new_value) * currentUnitCost).toFixed(2);
                                $cell.closest('tr').find('.total-peajes-cell').html('$' + totalPeajes);
                            }
                            
                            $cell.addClass('bg-warning animate__animated animate__flash'); // Feedback visual
                            setTimeout(() => {
                                $cell.removeClass('bg-warning animate__animated animate__flash');
                            }, 1000);

                        } else {
                            const errorMessage = 'Error al guardar: ' + (response.message || 'Error desconocido.');
                            console.error(errorMessage); 
                            // Revertir a valor original
                            $cell.html((!isPeajesCantidad) ? ('$' + parseFloat(originalValue).toFixed(2)) : originalValue);
                            // Aquí se podría mostrar un modal de error
                        }
                    },
                    error: function(xhr) {
                        const errorMessage = 'Error de conexión o validación. Intente de nuevo.';
                        console.error(errorMessage, xhr.responseText);
                        // Revertir a valor original en caso de error
                        $cell.html((!isPeajesCantidad) ? ('$' + parseFloat(originalValue).toFixed(2)) : originalValue);
                        // Aquí se podría mostrar un modal de error
                    },
                    complete: function() {
                        $cell.css('cursor', 'pointer');
                    }
                });
            };

            // Evento al perder el foco (click afuera)
            $input.on('blur', saveChanges);

            // Evento al presionar Enter
            $input.on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault(); // Previene el envío del formulario
                    $input.off('blur', saveChanges); // Evita doble guardado con blur
                    saveChanges();
                }
            });
        });
    });
</script>
@endpush
@endsection
