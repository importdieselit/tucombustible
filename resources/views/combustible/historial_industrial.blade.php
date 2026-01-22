@extends('layouts.app')

@section('title', 'Historial de Despachos Industriales')

@section('content')
<div class="container-fluid mt-4">
    <div class="row page-titles">
        <div class="col-md-6">
            <h3 class="text-themecolor">Historial Tanque 00 (Industrial)</h3>
        </div>
        <div class="col-md-6 d-flex justify-content-end align-items-center">
            <a href="{{ route('combustible.createDespachoIndustrial') }}" class="btn btn-success">
                <i class="fa fa-plus-circle"></i> Nuevo Despacho
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered display" id="historialTable" style="width:100%">
    <thead>
        <tr class="table-dark">
            <th>Fecha</th>
            <th>Cliente</th>
            <th>Nro Nota</th>
            <th>Vehículo / Placa</th>
            <th>Cant. (Lts)</th>
            <th>Stock Inicial</th>
            <th>Stock Final</th>
            <th>Obs.</th>
        </tr>
    </thead>
    <tbody>
        @foreach($historial as $mov)
        <tr>
            <td>{{ \Carbon\Carbon::parse($mov->created_at)->format('d/m/Y H:i') }}</td>
            <td><b>{{ $mov->cliente->nombre ?? 'N/A' }}</b></td>
            {{-- Columna editable con DataTables --}}
            <td class="editable-ticket" data-id="{{ $mov->id }}" title="Doble clic para editar">
                <span class="ticket-text">{{ $mov->nro_ticket ?? '---' }}</span>
            </td>
            <td>
                <span class="badge bg-light text-dark border">{{ $mov->vehiculo->placa ?? 'Sin Placa' }}</span>
            </td>
            <td class="text-danger fw-bold">- {{ number_format($mov->cantidad_litros, 2) }} L</td>
            <td>{{ number_format($mov->cant_inicial, 2) }}</td>
            <td class="text-primary">{{ number_format($mov->cant_final, 2) }}</td>
            <td><small>{{ $mov->observaciones }}</small></td>
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
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    // 1. Inicializar DataTable con estilos corregidos
    var table = $('#historialTable').DataTable({
        "order": [[ 0, "desc" ]], // Ordenar por fecha reciente
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" // Idioma español
        },
        "pageLength": 15,
        "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        "buttons": [
            {
                extend: 'pdfHtml5',
                text: '<i class="fa fa-file-pdf-o"></i> Exportar PDF',
                className: 'btn btn-danger btn-sm',
                title: 'Reporte de Despachos Industriales - Tanque 00',
                orientation: 'landscape',
                pageSize: 'LEGAL',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6] // Excluimos la columna de Obs si es muy larga
                },
                customize: function (doc) {
                    doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split('');
                    doc.styles.tableHeader.fillColor = '#2d3436';
                    doc.styles.tableHeader.color = 'white';
                }
            },
            {
                extend: 'print',
                text: '<i class="fa fa-print"></i> Imprimir',
                className: 'btn btn-info btn-sm'
            }
        ]
    });

    // 2. Función de edición (Doble Clic)
    // Usamos delegación de eventos para que funcione al cambiar de página en el DataTable
    $('#historialTable').on('dblclick', '.editable-ticket', function() {
        let cell = $(this);
        if (cell.find('input').length > 0) return;

        let currentVal = cell.find('.ticket-text').text().trim();
        if (currentVal === '---') currentVal = '';

        let input = $('<input>', {
            type: 'text',
            class: 'form-control form-control-sm',
            value: currentVal,
            style: 'width: 100%;'
        });

        cell.find('.ticket-text').hide();
        cell.append(input);
        input.focus();

        input.on('blur keypress', function(e) {
            if (e.type === 'keypress' && e.which !== 13) return;
            
            let newVal = $(this).val().toUpperCase();
            let id = cell.data('id');

            $.ajax({
                url: "{{ route('combustible.updateTicket') }}",
                method: "POST",
                data: { _token: "{{ csrf_token() }}", id: id, nro_ticket: newVal },
                success: function() {
                    cell.find('.ticket-text').text(newVal || '---').show();
                    input.remove();
                    // Actualizar el valor interno de DataTables para que el buscador lo encuentre
                    table.cell(cell).data(cell.html()).draw(false);
                }
            });
        });
    });
});
</script>
@endpush