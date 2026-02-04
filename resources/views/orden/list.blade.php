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
            <div class="card mb-3 shadow-sm">
    <div class="card-body p-3">
        <h6 class="text-muted mb-3"><i class="fa fa-info-circle me-2"></i> Leyenda de Estados y Tiempos</h6>
        <div class="d-flex flex-wrap gap-3">
            <div class="d-flex align-items-center me-4">
                <div class="rounded-circle bg-danger me-2" style="width: 15px; height: 15px; border: 1px solid #0002;"></div>
                <span class="small">Crítico (> 48h Abierta)</span>
            </div>
            <div class="d-flex align-items-center me-4">
                <div class="rounded-circle bg-warning me-2" style="width: 15px; height: 15px; border: 1px solid #0002;"></div>
                <span class="small">Atención (> 24h Abierta)</span>
            </div>
            
            <div class="d-flex align-items-center me-4">
                <div class="rounded-circle bg-success me-2" style="width: 15px; height: 15px; border: 1px solid #0002;"></div>
                <span class="small">Cerrada / Finalizada</span>
            </div>
            <div class="d-flex align-items-center me-4">
                <div class="rounded-circle bg-secondary me-2" style="width: 15px; height: 15px; border: 1px solid #0002;"></div>
                <span class="small">Cancelada / Anulada</span>
            </div>
        </div>
    </div>
</div>
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
                        <td>{{ $orden->vehiculo()?$orden->vehiculo()->flota.' '.$orden->vehiculo()->placa:null }}</td>
                        <td>{{ $orden->tipo }}</td>
                        <td>
                            @if(isset($orden->created_at) && $orden->created_at)
                                {{ $orden->created_at->format('d/m/Y') }}
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{$orden->created_at->diffForHumans(now())}}</td>
                        <td>
                            @php $estatusInfo = $estatusData->get($orden->estatus); @endphp
                            @if ($estatusInfo)
                               @php
                                    $horas = $orden->created_at->diffInHours(now());
                                    $css = ($horas >= 48) 
                                        ? 'danger' 
                                        : (($horas >= 24) ? 'warning' : $estatusInfo->css);
                                @endphp
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
                language: {
                    "decimal": "",
                    "emptyTable": "No hay información",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ Entradas",
                    "infoEmpty": "Mostrando 0 a 0 de 0 Entradas",
                    "infoFiltered": "(Filtrado de _MAX_ total entradas)",
                    "infoPostFix": "",
                    "thousands": ",",
                    "lengthMenu": "Mostrar _MENU_ Entradas",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Buscar:",
                    "zeroRecords": "Sin resultados encontrados",
                    "paginate": {
                        "first": "Primero",
                        "last": "Ultimo",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    }
                },
                layout: {
                    topStart: {
                        buttons: ['csv', 'excel', 'pdf', 'print']
                    }
                },
                "order": [
                    [ 0, 'desc' ] 
                ]
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
