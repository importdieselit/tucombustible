@extends('layouts.app')

@section('title', 'Control de Inventario - Impordiesel')

@section('content')
<div class="container-fluid py-4">
    
    {{-- ENCABEZADO ESTILO CORPORATIVO --}}
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 shadow-sm rounded">
        <div>
            <h3 class="fw-bold mb-0 text-uppercase">
                <i class="fas fa-boxes text-orange me-2"></i>Control de Inventario
            </h3>
            <p class="text-muted mb-0 small">Consulta de existencias, stock mínimo y gestión de repuestos.</p>
        </div>
        <div>
            <a href="{{ route('ventas.create') }}" class="btn btn-orange fw-bold shadow-sm">
                <i class="fas fa-plus-circle me-1"></i> REGISTRAR VENTA
            </a>
            <a href="{{ route('inventario.create') }}" class="btn btn-orange fw-bold shadow-sm">
                <i class="fas fa-plus-circle me-1"></i> NUEVO ÍTEM
            </a>
        </div>
    </div>

    {{-- BARRA DE FILTROS --}}
    <div class="card border-orange shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-uppercase text-muted">
                        <i class="fas fa-warehouse me-1"></i> Seleccionar Almacén / Depósito
                    </label>
                    <select id="filter-almacen" class="form-select border-2">
                        <option value="">TODOS LOS ALMACENES</option>
                        @foreach($almacenes ?? [] as $almacen)
                            <option value="{{ $almacen->nombre }}">{{ $almacen->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-8 text-end pt-4">
                    <span class="badge bg-light text-dark border p-2">
                        <i class="fas fa-info-circle text-orange me-1"></i> 
                        Haga clic en una fila para ver el historial de movimientos.
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- TABLA DE DATOS --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="inventarioTable" class="table table-hover align-middle m-0">
                    <thead class="bg-corporate text-white small text-uppercase">
                        <tr>
                            <th class="ps-3" width="5%">#</th>
                            <th>Código</th>
                            <th width="50%">Descripción del Producto</th>
                            <th style="width: 1%; white-space: nowrap;">Ubicacion</th>
                            <th>Grupo / Categoría</th>
                            <th>Almacén</th> {{-- Columna para el filtro --}}
                            <th class="text-center">Existencia total</th>
                            <th class="text-center">Mínimo</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $index => $item)
                        <tr class="clickable-row" data-id="{{ $item->id }}">
                            <td class="ps-3 text-muted small">{{ $index + 1 }}</td>
                            <td class="fw-bold text-orange">{{ $item->codigo ?? 'N/A' }}</td>
                            <td>
                                <div class="fw-bold">{{ $item->descripcion ?? 'N/A' }}</div>
                                <div class="x-small text-muted text-uppercase">Reg: {{ !is_null($item->fecha_in) ? \Carbon\Carbon::parse(strtotime($item->fecha_in))->format('d/m/Y') : 'N/A' }}</div>
                            </td>
                            <td>
                                <div class="text-dark leading-normal fw-bold small text-nowrap" style="width: 1%;">
                                    {!! $item->ubicaciones_html !!}
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border">{{ $item->grupo ?? 'N/A' }}</span></td>
                            <td><small class="fw-bold text-muted">{{ $item->almacen->nombre ?? 'Principal - Boleita' }}</small></td>
                            <td class="text-center fw-bold fs-6">
                                {{ number_format($item->existencia + $item->getExistenciaTotalAttribute() ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="text-center text-muted">
                                {{ number_format($item->existencia_minima ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                 @if ($item->existencia <= ($item->existencia_minima ?? 0))
                                    <span class="badge bg-danger animate__animated animate__flash animate__infinite">
                                        <i class="fas fa-exclamation-triangle"></i> BAJO STOCK
                                    </span>
                                @else
                                    <span class="badge bg-success-soft text-success border border-success">
                                        <i class="fas fa-check-circle"></i> ÓPTIMO
                                    </span>
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
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/2.0.7/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.0.2/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

    <script>
        $(document).ready(function() {
            // Inicializar DataTables con el estándar corporativo
            const table = $('#inventarioTable').DataTable({
                dom: '<"d-flex justify-content-between p-3 border-bottom"Bf>rt<"d-flex justify-content-between p-3"ip>',
                buttons: [
                    { extend: 'excel', className: 'btn btn-sm btn-success', text: '<i class="fas fa-file-excel"></i> Excel' },
                    { extend: 'pdf', className: 'btn btn-sm btn-danger', text: '<i class="fas fa-file-pdf"></i> PDF' }
                ],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json',
                    search: "_INPUT_",
                    searchPlaceholder: "Buscar repuesto..."
                },
                pageLength: 25,
                order: [[7, 'asc']] // Ordenar por estado de stock
            });

            // LÓGICA DEL FILTRO DE ALMACÉN
            $('#filter-almacen').on('change', function() {
                const val = $(this).val();
                // Filtramos la columna 4 (Almacén)
                table.column(4).search(val).draw();
            });

            // Redirección al detalle
            $('#inventarioTable tbody').on('click', 'tr', function() {
                const id = $(this).data('id');
                if (id) window.location.href = '/inventario/' + id;
            });
        });
    </script>
@endpush