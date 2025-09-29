@extends('layouts.app')

@section('title', 'Listado de Vehículos')

@push('styles')
    <!-- CSS de DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.7/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.dataTables.css" />
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="mb-2">Vehículos</h1>
        <p class="text-muted">Gestión de la flota, consulta de registros y estado de los vehículos.</p>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="card-title m-0">Lista de Vehículos</h5>
        <div>
            <a href="{{ route('vehiculos.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>
                Crear Nuevo
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
            <table id="vehiculosTable" class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th>Flota</th>
                        {{-- <th>Cliente</th> --}}
                        {{-- <th>Clase</th> --}}
                        <th>Marca/Modelo</th>
                        <th>Año</th>
                        <th>Placa</th>
                        <th>Tipo</th>
                        <th>Kilometraje</th>
                        <th>Estatus</th>
                        <th>Documentos</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $index => $vehiculo)
                    <tr class="clickable-row" data-id="{{ $vehiculo->id }}">
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $vehiculo->flota ?? 'N/A' }}</td>
                        {{-- <td>{{ $vehiculo->cliente->nombre ?? 'N/A' }}</td> --}}
                        {{-- <td>{{ $vehiculo->clase ?? 'N/A' }}</td> --}}
                        <td>{{ $vehiculo->marca()->marca ?? 'N/A' }} / {{ $vehiculo->modelo()->modelo ?? 'N/A' }}</td>
                        <td>{{ $vehiculo->anno }}</td>
                        <td>{{ $vehiculo->placa }}</td>
                        <td>{{ $vehiculo->tipoVehiculo->tipo ?? 'N/A' }}</td>
                        <td>{{ number_format($vehiculo->kilometraje ?? 0, 0, ',', '.') }} km</td>
                        <td>
                            @php
                                $estatusInfo = $estatusData->get($vehiculo->estatus);
                            @endphp
                            @if ($estatusInfo)
                                <span class="badge bg-{{ $estatusInfo->css }}" title="{{ $estatusInfo->descripcion }}">
                                    <i class="mr-1 fa-solid {{ $estatusInfo->icon_auto }}"></i>
                                    {{ $estatusInfo->auto }}
                                </span>
                            @else
                                <span class="badge bg-gray">Desconocido</span>
                            @endif
                        </td>
                        <td>
                           @php
    // Definimos un array asociativo con el nombre y los campos del vehículo.
    // 'Documento' => ['campo_fecha', 'campo_texto']
                                $documentos = [
                                    'Póliza'       => ['poliza_fecha_out', null],
                                    'RCV'          => ['rcv', null],
                                    'RACDA'        => ['racda', null],
                                    'ROTC'         => ['rotc_venc', null],
                                  //  'SEMCAMMER'    => [null, 'semcamer'], // Solo campo de texto
                                  //  'Homologacion INTT' => [null, 'homologacion_intt'], // Solo campo de texto
                                    //'Permiso INTT' => ['permiso_intt',null], // O si tiene campo de texto, ajusta a [null, 'permiso_intt']
                                ];
                            @endphp

                            @foreach ($documentos as $label => $fields)
                                @php
                                    // Llama al método del modelo para obtener el estatus
                                    $status = $vehiculo->getDocumentStatus($label, $fields[0], $fields[1]);
                                @endphp
                                <x-document-status-badge :status="$status" label="{{ $label }}" />
                            @endforeach
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
            $('#vehiculosTable').DataTable({
                "language": {
                    "url": "https://cdn.datatables.net/plug-ins/2.0.8/i18n/es-ES.json"
                },
                layout: {
                    topStart: {
                        buttons: ['csv', 'excel', 'pdf', 'print']
                    }
                }
            });

            // Lógica para redirigir al hacer clic en una fila
            $('#vehiculosTable tbody').on('click', 'tr', function() {
                var id = $(this).data('id');
                if (id) {
                    window.location.href = '/vehiculos/' + id;
                }
            });
        });
    </script>
@endpush
