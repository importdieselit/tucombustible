@extends('layouts.app')

@section('title', 'Historial de Ventas - Impordiesel')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.7/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.dataTables.css" />
@endpush

@section('content')
<div class="container-fluid py-4">
    
    {{-- ENCABEZADO --}}
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 shadow-sm rounded">
        <div>
            <h3 class="fw-bold mb-0 text-uppercase">
                <i class="fas fa-file-invoice-dollar text-orange me-2"></i>Historial de Ventas
            </h3>
            <p class="text-muted mb-0 small">Registro de salidas de almacén y transacciones Profit.</p>
        </div>
        <div>
            <a href="{{ route('ventas.create') }}" class="btn btn-orange fw-bold shadow-sm">
                <i class="fas fa-plus-circle me-1"></i> NUEVA VENTA
            </a>
        </div>
    </div>

    {{-- TABLA DE VENTAS --}}
    <div class="card border-orange shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="ventasTable" class="table table-hover align-middle mb-0">
                    <thead class="bg-corporate text-white small text-uppercase">
                        <tr>
                            <th class="ps-3">Fecha</th>
                            <th>Nro. Venta</th>
                            <th>Nro. Profit</th>
                            <th>Cliente</th>
                            <th class="text-end">Total $</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ventas as $venta)
                        <tr class="clickable-row" data-id="{{ $venta->id }}">
                            <td class="ps-3">
                                <span class="fw-bold">{{ \Carbon\Carbon::parse($venta->fecha)->format('d/m/Y') }}</span>
                                <br><small class="text-muted">{{ \Carbon\Carbon::parse($venta->fecha)->format('h:i A') }}</small>
                            </td>
                            <td class="fw-bold text-orange">{{ $venta->nro_venta }}</td>
                            <td>
                                @if($venta->nro_profit)
                                    <span class="badge bg-light text-dark border"><i class="fas fa-tag me-1"></i>{{ $venta->nro_profit }}</span>
                                @else
                                    <span class="text-muted small">---</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold">{{ $venta->cliente->nombre }}</div>
                                <small class="text-muted text-uppercase">{{ $venta->cliente->rif }}</small>
                            </td>
                            <td class="text-end fw-bold">
                                {{ number_format($venta->total_venta, 2, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="{{ route('ventas.show', $venta->id) }}" class="btn btn-sm btn-outline-dark" title="Ver Detalle">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button class="btn btn-sm btn-outline-danger" onclick="imprimirNota({{ $venta->id }})" title="Imprimir Nota">
                                        <i class="fas fa-file-pdf"></i>
                                    </button>
                                </div>
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

    <script>
        $(document).ready(function() {
            const table = $('#ventasTable').DataTable({
                dom: '<"d-flex justify-content-between p-3 border-bottom"Bf>rt<"d-flex justify-content-between p-3"ip>',
                buttons: [
                    { extend: 'excel', className: 'btn btn-sm btn-success', text: '<i class="fas fa-file-excel"></i> Excel' },
                    { extend: 'pdf', className: 'btn btn-sm btn-danger', text: '<i class="fas fa-file-pdf"></i> PDF' }
                ],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json',
                    searchPlaceholder: "Buscar por cliente, nro o profit..."
                },
                pageLength: 15,
                order: [[0, 'desc']] // Mostrar las más recientes primero
            });

            // Redirección al hacer clic en la fila
            $('#ventasTable tbody').on('click', 'td:not(:last-child)', function() {
                const id = $(this).closest('tr').data('id');
                if (id) window.location.href = '/ventas/' + id;
            });
        });

        function imprimirNota(id) {
            window.open(`/ventas/imprimir/${id}`, '_blank');
        }
    </script>
@endpush