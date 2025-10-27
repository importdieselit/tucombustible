@extends('layouts.app')

@section('title', 'Listado de Órdenes de Trabajo')

@push('styles')
    <!-- CSS de DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.7/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.dataTables.css" />
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="mb-2">Órdenes de Trabajo</h1>
        <p class="text-muted">Gestión de reparaciones y mantenimientos de la flota.</p>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="card-title m-0">Lista de Órdenes</h5>
        <div>
            <a href="{{ route('ordenes.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>
                Crear Nueva Orden
            </a>
        </div>
    </div>
    <div class="card-body">
        @if(Session::has('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ Session::get('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(Session::has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ Session::get('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        <div class="table-responsive">
            <table id="ordenesTable" class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th>Nro. Orden</th>
                        <th>Vehículo</th>
                        <th>Tipo</th>
                        <th>Fecha de Apertura</th>
                        <th>Tiempo abierta</th>
                        <th>Estatus</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $orden)
                    <tr class="clickable-row" data-id="{{ $orden->id }}">
                        <td>{{ $orden->id }}</td>
                        <td>{{ $orden->nro_orden }}</td>
                        <td>{{ $orden->vehiculo?$orden->vehiculo()->placa:null }}</td>
                        <td>{{ $orden->tipo }}</td>)
                        <td>
                            @if(isset($orden->created_at) && $orden->created_at)
                                {{ $orden->created_at->format('d/m/Y') }}
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{$orden->created_at->diffForHumans(now())}}</td>
                        <td>
                            @php($estatusInfo = $estatusData->get($orden->estatus))
                            @if ($estatusInfo)
                                @php($css=$orden->created_at->diffInDays(now())>1?'danger':$estatusInfo->css)
                                <span class="badge bg-{{ $css }}" title="{{ $estatusInfo->descripcion }}">
                                    <i class="mr-1 fa-solid {{ $estatusInfo->icon_orden }}"></i>
                                    {{ $estatusInfo->orden }}
                                </span>
                            @else
                                <span class="badge bg-gray">Desconocido</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <!-- Script de jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Script de DataTables -->
    <script src="https://cdn.datatables.net/2.0.7/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.0.2/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

    <script>
        $(document).ready(function() {
            // Inicializar DataTables
            $('#ordenesTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
                },
                layout: {
                    topStart: {
                        buttons: ['csv', 'excel', 'pdf', 'print']
                    }
                }
            });

            // Lógica para redirigir al hacer clic en una fila
            $('#ordenesTable tbody').on('click', 'tr', function() {
                var id = $(this).data('id');
                if (id) {
                    window.location.href = '/ordenes/' + id;
                }
            });
        });
    </script>
@endpush
