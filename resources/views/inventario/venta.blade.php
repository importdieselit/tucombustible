@extends('layouts.app')

@section('title', 'Nueva Venta - Impordiesel')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@section('content')
<div class="container-fluid py-4">
    <form action="{{ route('ventas.store') }}" method="POST" id="form-venta">
        @csrf
        
        {{-- ENCABEZADO --}}
        <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 shadow-sm rounded">
            <div>
                <h3 class="fw-bold mb-0 text-uppercase"><i class="fas fa-shopping-cart text-orange me-2"></i>Nueva Transacción de Venta</h3>
            </div>
            <button type="submit" class="btn btn-orange fw-bold shadow-sm px-4">
                <i class="fas fa-save me-1"></i> PROCESAR VENTA
            </button>
        </div>

        <div class="row">
            {{-- DATOS DEL CLIENTE Y GENERALES --}}
            <div class="col-md-4">
                <div class="card border-orange shadow-sm mb-4">
                    <div class="card-header bg-white fw-bold small text-uppercase">Datos del Cliente</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Seleccionar Cliente</label>
                            <select name="id_cliente" id="select-cliente" class="form-select select2" required>
                                <option value="">Busque por RIF o Nombre...</option>
                                <option value="NUEVO" class="fw-bold text-orange">+ OTROS / NUEVO CLIENTE</option>
                                @foreach($clientes as $cliente)
                                    <option value="{{ $cliente->id }}">{{ $cliente->rif }} - {{ $cliente->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- SECCIÓN DINÁMICA: REGISTRO RÁPIDO CLIENTE --}}
                        <div id="section-nuevo-cliente" style="display: none;" class="bg-light p-3 rounded border border-orange">
                            <h6 class="text-orange fw-bold small mb-3">DATOS DE REGISTRO</h6>
                            <div class="mb-2">
                                <input type="text" name="nuevo_rif" class="form-control form-control-sm" placeholder="RIF (Ej: J-123456789)">
                            </div>
                            <div class="mb-2">
                                <input type="text" name="nuevo_nombre" class="form-control form-control-sm" placeholder="Nombre o Razón Social">
                            </div>
                            <div class="mb-2">
                                <input type="email" name="nuevo_correo" class="form-control form-control-sm" placeholder="Correo (Opcional)">
                            </div>
                            <div class="mb-2">
                                <input type="text" name="nuevo_telefono" class="form-control form-control-sm" placeholder="Teléfono (Opcional)">
                            </div>
                        </div>

                        <hr>

                        <div class="mb-2">
                            <label class="form-label small fw-bold text-uppercase">Nro Profit (Referencia)</label>
                            <input type="text" name="nro_profit" class="form-control border-orange" placeholder="Ej: 998877">
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-bold text-uppercase">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- DETALLE DE ÍTEMS --}}
            <div class="col-md-8">
                <div class="card card-step shadow-sm">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold text-uppercase small text-orange"><i class="fas fa-list me-2"></i>Artículos para Venta</h6>
                        <span class="badge bg-light text-dark">Solo items con existencia en este almacén</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="tabla-venta">
                            <thead class="bg-corporate text-white small">
                                <tr>
                                    <th width="50%">Artículo / Repuesto</th>
                                    <th width="15%" class="text-center">Cant.</th>
                                    <th width="15%" class="text-end">Precio Unit.</th>
                                    <th width="15%" class="text-end">Subtotal</th>
                                    <th width="5%"></th>
                                </tr>
                            </thead>
                            <tbody id="detalles-body">
                                {{-- Fila inicial vacía o para agregar --}}
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5" class="p-3">
                                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="agregarFila()">
                                            <i class="fas fa-plus me-1"></i> AGREGAR LÍNEA
                                        </button>
                                    </td>
                                </tr>
                                <tr class="bg-light">
                                    <td colspan="3" class="text-end fw-bold text-uppercase">Total Venta $:</td>
                                    <td class="text-end fs-5 fw-bold text-orange" id="total-general">0.00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const inventario = @json($items);
       
        $(document).ready(function() {
            $('.select2').select2({ width: '100%' });

            // Toggle para nuevo cliente
            $('#select-cliente').on('change', function() {
                if ($(this).val() === 'NUEVO') {
                    $('#section-nuevo-cliente').slideDown();
                    $('[name^="nuevo_"]').prop('required', true);
                } else {
                    $('#section-nuevo-cliente').slideUp();
                    $('[name^="nuevo_"]').prop('required', false);
                }
            });

            // Agregar la primera fila automáticamente
            agregarFila();
        });

        let filaCount = 0;

        function agregarFila() {
            filaCount++;
            const html = `
                <tr id="fila-${filaCount}">
                    <td>
                        <select name="items[${filaCount}][id_inventario]" class="form-select select2-item" onchange="actualizarPrecio(this, ${filaCount})" required>
                            <option value="">Seleccione item...</option>
                            ${inventario.map(i => `<option value="${i.id}" data-precio="${i.costo}" data-stock="${i.existencia}">${i.codigo} - ${i.descripcion} (Stock: ${i.existencia})</option>`).join('')}
                        </select>
                    </td>
                    <td>
                        <input type="number" name="items[${filaCount}][cantidad]" class="form-control text-center input-cantidad" min="1" value="1" oninput="calcularFila(${filaCount})" required>
                    </td>
                    <td>
                        <input type="number" name="items[${filaCount}][precio_unitario]" class="form-control text-end input-precio" step="0.01" oninput="calcularFila(${filaCount})" required>
                    </td>
                    <td class="text-end fw-bold text-muted subtotal-fila" id="subtotal-${filaCount}">0.00</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-link text-danger p-0" onclick="eliminarFila(${filaCount})"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
            `;
            $('#detalles-body').append(html);
            $('.select2-item').select2();
        }

        function actualizarPrecio(select, id) {
            const option = $(select).find(':selected');
            const precio = option.data('precio') || 0;
            $(`#fila-${id} .input-precio`).val(precio);
            calcularFila(id);
        }

        function calcularFila(id) {
            const cant = parseFloat($(`#fila-${id} .input-cantidad`).val()) || 0;
            const precio = parseFloat($(`#fila-${id} .input-precio`).val()) || 0;
            const subtotal = cant * precio;
            $(`#subtotal-${id}`).text(subtotal.toFixed(2));
            calcularTotal();
        }

        function calcularTotal() {
            let total = 0;
            $('.subtotal-fila').each(function() {
                total += parseFloat($(this).text()) || 0;
            });
            $('#total-general').text(total.toFixed(2));
        }

        function eliminarFila(id) {
            if ($('#detalles-body tr').length > 1) {
                $(`#fila-${id}`).remove();
                calcularTotal();
            }
        }
    </script>
@endpush