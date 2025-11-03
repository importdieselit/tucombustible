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
            <div class="card p-2 mb-3 shadow-sm border-light">
                <p class="mb-1 fw-bold">Leyenda de Estatus de Documentos:</p>
                <div class="d-flex flex-wrap gap-3 small">
                    <span class="badge bg-success" style="min-width: 150px;">
                        <i class="bi bi-check-circle me-1"></i> Vigente / OK
                    </span>
                    <span class="badge bg-warning" style="min-width: 150px;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Próximo a Vencer (< 30 días)
                    </span>
                    <span class="badge bg-danger" style="min-width: 150px;">
                        <i class="bi bi-x-octagon-fill me-1"></i> Vencido / Sin Permiso (S/P)
                    </span>
                    <span class="badge bg-secondary" style="min-width: 150px;">
                        <i class="bi bi-slash-circle me-1"></i> Sin Informacion / N/A
                    </span>
                </div>
            </div>
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
                        <th>Dias Fuera de servicio</th>
                        <th>Documentos Vencidos</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $index => $vehiculo)
                     @php $orden=false; @endphp
                     @if($vehiculo->estatus==3 || $vehiculo->estatus ==5)
                        @php

                            $orden=App\Models\Orden::where('id_vehiculo',$vehiculo->id)->where('estatus',2)->get()->first();
                            if($orden){
                                $fecha=$orden->fecha_in;
                                $duracionDias = Illuminate\Support\Carbon::parse($fecha)->diffInDays(Illuminate\Support\Carbon::parse(now()));
                            }
                            @endphp
                        @endif
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
                                @if($orden)
                                 <a href="/ordenes/{{$orden->id}} " style="decoration:none; cursor: pointer;" target="_blank" >   
                                @endif
                                <span class="badge bg-{{ $estatusInfo->css }}" title="{{ $estatusInfo->descripcion }}">
                                    <i class="mr-1 fa-solid {{ $estatusInfo->icon_auto }}"></i>
                                    {{ $estatusInfo->auto }}
                                </span>
                                @if($orden)
                                </a> 
                                @endif
                            @else
                                <span class="badge bg-gray">Desconocido</span>
                            @endif
                        </td>
                        <td>
                                @if($orden)
                                 <a href="/ordenes/{{$orden->id}} " style="decoration:none; cursor: pointer;" target="_blank" >   
                                        hace {{$duracionDias ?? 0}} dias
                                </a> 
                                @else
                                    n/a
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
                                    'SEMCAMMER'    => [null, 'semcamer'], // Solo campo de texto
                                    'Homologacion INTT' => [null, 'homologacion_intt'], // Solo campo de texto
                                    'Permiso INTT' => ['permiso_intt',null], // O si tiene campo de texto, ajusta a [null, 'permiso_intt']
                                ];
                                $hasAlerts = false; 
                            @endphp

                            @foreach ($documentos as $label => $fields)
                                @php
                                    // 1. Llama al método del modelo para obtener el estatus
                                    $status = $vehiculo->getDocumentStatus($label, $fields[0], $fields[1]);
                                    $statusClass = $status['class'] ?? 'bg-secondary';
                                @endphp

                                {{-- 2. Mostrar SOLO si el estatus NO es de éxito. --}}
                                @if ($statusClass === 'bg-danger' || $statusClass === 'bg-warning' || $statusClass === 'bg-secondary')
                                    <x-document-status-badge :status="$status" label="{{ $label }}" />
                                    @php $hasAlerts = true; @endphp
                                @endif
                            @endforeach
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
              <div class="card p-2 mb-3 shadow-sm border-light">
                <p class="mb-1 fw-bold">Leyenda de Estatus de Documentos:</p>
                <div class="d-flex flex-wrap gap-3 small">
                    <span class="badge bg-success" style="min-width: 150px;">
                        <i class="bi bi-check-circle me-1"></i> Vigente / OK
                    </span>
                    <span class="badge bg-warning" style="min-width: 150px;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Próximo a Vencer (< 30 días)
                    </span>
                    <span class="badge bg-danger" style="min-width: 150px;">
                        <i class="bi bi-x-octagon-fill me-1"></i> Vencido / Sin Permiso (S/P)
                    </span>
                    <span class="badge bg-secondary" style="min-width: 150px;">
                        <i class="bi bi-slash-circle me-1"></i> Sin Informacion / N/A
                    </span>
                </div>
            </div>
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
