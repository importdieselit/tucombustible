@extends('layouts.app')

@section('title', 'Edición de Tabulador de Viáticos Base')

@section('content')
<div class="container-fluid mt-4">
    <h1 class="mb-4 text-secondary"><i class="bi bi-table me-2"></i> Tabulador Base de Viáticos (Edición Rápida)</h1>
    <p class="mb-4">Haga clic en el monto de cualquier celda editable para modificar el valor. Presione **Enter** o haga clic afuera para guardar el cambio.</p>

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
                    <th rowspan="2">Pernocta (USD)</th>
                    <th rowspan="2">Cantidad Peajes</th>
                    <th rowspan="2">Peajes I/V (USD)</th>
                </tr>
                <tr>
                    <th>Chofer Ejecutivo</th>
                    <th>Chofer</th>
                    <th>Ayudante</th>
                    <th>Desayuno</th>
                    <th>Almuerzo</th>
                    <th>Cena</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tabulador as $item)
                    <tr data-id="{{ $item->id_tabulador }}">
                        <td>{{ $item->id_tabulador }}</td>
                        <td class="bg-light text-start text-dark fw-bold">{{ $item->destino }}</td>
                        {{-- Pagos por Viaje --}}
                        <td data-field="pago_chofer_ejecutivo" class="editable-cell text-primary">${{ number_format($item->pago_chofer_ejecutivo, 2) }}</td>
                        <td data-field="pago_chofer" class="editable-cell text-primary">${{ number_format($item->pago_chofer, 2) }}</td>
                        <td data-field="pago_ayudante" class="editable-cell text-primary">${{ number_format($item->pago_ayudante, 2) }}</td>
                        {{-- Viáticos Diarios --}}
                        <td data-field="viatio_desayuno" class="editable-cell">${{ number_format($item->viatico_desayuno, 2) }}</td>
                        <td data-field="viatico_almuerzo" class="editable-cell">${{ number_format($item->viatico_almuerzo, 2) }}</td>
                        <td data-field="viatico_cena" class="editable-cell">${{ number_format($item->viatico_cena, 2) }}</td>
                        {{-- Pernocta y Peajes --}}
                        <td data-field="costo_pernocta" class="editable-cell text-success">${{ number_format($item->costo_pernocta, 2) }}</td>
                        <td data-field="peajes" class="editable-cell text-success">{{ $item->peajes }}</td>
                        <td data-field="total_peajes" class="text-success">${{ number_format($item->peajes*4, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted">No hay datos cargados en el Tabulador de Viáticos.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawR/Y1/fMFa6B2/S6iK5F8FzI7tFz5kF8FzE=" crossorigin="anonymous"></script>
<script>
    // Asumimos que jQuery está cargado en el layout principal
    $(document).ready(function() {
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        
        // 1. Manejar el clic en las celdas editables
        $('#tabuladorTable').on('click', '.editable-cell', function() {
            const $cell = $(this);
            // Evitar editar si ya estamos editando
            if ($cell.find('input').length > 0) return;

            // Obtener el valor actual (quitar el '$')
            const currentValue = $cell.text().replace('$', '').replace(/,/g, '');
            const fieldName = $cell.data('field');

            // Crear el campo de entrada (input)
            const $input = $('<input>', {
                type: 'number',
                class: 'form-control form-control-sm text-center border-primary',
                value: parseFloat(currentValue).toFixed(2), // Formatear a 2 decimales
                step: '0.01',
                min: '0',
                'data-original-value': currentValue // Guardar valor original
            });

            // Reemplazar el contenido de la celda con el input
            $cell.empty().append($input);
            $input.focus();

            // 2. Manejar la pérdida de foco (Blur) o presionar Enter
            const saveChanges = function() {
                const newValue = parseFloat($input.val());
                const originalValue = parseFloat($input.data('original-value'));

                if (isNaN(newValue) || newValue < 0) {
                    // Revertir a valor original si no es válido
                    $cell.html('$' + originalValue.toFixed(2));
                    return;
                }
                
                // Si el valor no cambió, solo revertir la apariencia
                if (newValue.toFixed(2) === originalValue.toFixed(2)) {
                     $cell.html('$' + newValue.toFixed(2));
                     return;
                }

                // Deshabilitar input mientras se guarda
                $input.prop('disabled', true);
                $cell.css('cursor', 'wait');

                // 3. Petición AJAX para guardar en el servidor
                $.ajax({
                    url: '{{ route('viaticos.tabulador.update') }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    data: {
                        id_tabulador: $cell.closest('tr').data('id'),
                        field: fieldName,
                        value: newValue
                    },
                    success: function(response) {
                        if (response.success) {
                            // Actualizar la celda con el nuevo valor formateado
                            $cell.html('$' + response.new_value);
                            $cell.addClass('bg-warning animate__animated animate__flash'); // Feedback visual
                            setTimeout(() => {
                                $cell.removeClass('bg-warning animate__animated animate__flash');
                            }, 1000);
                        } else {
                            // Mostrar mensaje de error y revertir
                            alert('Error al guardar: ' + response.message);
                            $cell.html('$' + originalValue.toFixed(2));
                        }
                    },
                    error: function(xhr) {
                        alert('Error de conexión o validación. Intente de nuevo.');
                        console.error(xhr.responseText);
                        // Revertir a valor original en caso de error
                        $cell.html('$' + originalValue.toFixed(2));
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

