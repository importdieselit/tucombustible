@extends('layouts.app')

@section('title', 'Listado de Órdenes de Trabajo')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.7/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.dataTables.css" />
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 shadow-sm rounded">
        <div>
            <h3 class="fw-bold mb-0 text-uppercase"><i class="fas fa-list-ul text-orange me-2"></i>Órdenes de Trabajo</h3>
            <p class="text-muted mb-0 small">Gestión de reparaciones y mantenimientos de la flota.</p>
        </div>
        <div>
            <a href="{{ route('ordenes.create') }}" class="btn btn-orange shadow-sm fw-bold">
                <i class="fas fa-plus-circle me-1"></i> CREAR NUEVA ORDEN
            </a>
        </div>
    </div>

    @if(Session::has('success') || Session::has('error'))
        <div class="row">
            <div class="col-12">
                @if(Session::has('success'))
                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                        <i class="fas fa-check-circle me-2"></i> {{ Session::get('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if(Session::has('error'))
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i> {{ Session::get('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="card card-step border-orange shadow-sm">
        <div class="card-body">
            <div class="bg-light p-3 rounded mb-4 border-start border-4 border-orange">
                <h6 class="fw-bold text-uppercase small mb-3"><i class="fa fa-info-circle me-2 text-orange"></i> Monitoreo de Tiempos y Estados</h6>
                <div class="d-flex flex-wrap gap-4">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-danger me-2" style="width: 12px; height: 12px; border-radius: 50%;"> </span>
                        <span class="small fw-bold">Crítico (> 48h)</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-warning me-2" style="width: 12px; height: 12px; border-radius: 50%;"> </span>
                        <span class="small fw-bold">Atención (> 24h)</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-info me-2" style="width: 12px; height: 12px; border-radius: 50%;"> </span>
                        <span class="small fw-bold">Abierta (Reciente)</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-success me-2" style="width: 12px; height: 12px; border-radius: 50%;"> </span>
                        <span class="small fw-bold">Finalizada</span>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table id="ordenesTable" class="table table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th class="ps-3">ID</th>
                            <th>Nro. Orden</th>
                            <th>Vehículo / Unidad</th>
                            <th>Tipo Servicio</th>
                            <th>Apertura</th>
                            <th>Transcurrido</th>
                            <th class="text-center">Estatus</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $orden)
                        <tr class="clickable-row" data-id="{{ $orden->id }}">
                            <td class="ps-3 fw-bold text-muted">{{ $orden->id }}</td>
                            <td><span class="badge bg-corporate">#{{ $orden->nro_orden }}</span></td>
                            <td>
                                <div class="fw-bold text-dark">{{ $orden->vehiculo() ? $orden->vehiculo()->flota : 'N/A' }}</div>
                                <div class="small text-muted">{{ $orden->vehiculo() ? $orden->vehiculo()->placa : '' }}</div>
                            </td>
                            <td><span class="text-uppercase small fw-bold">{{ $orden->tipo }}</span></td>
                            <td>
                                <div class="small">{{ $orden->created_at ? $orden->created_at->format('d/m/Y') : 'N/A' }}</div>
                                <div class="text-muted" style="font-size: 0.7rem;">{{ $orden->created_at ? $orden->created_at->format('h:i A') : '' }}</div>
                            </td>
                            <td class="small">{{ $orden->created_at->diffForHumans(now()) }}</td>
                            <td class="text-center">
                                @php $estatusInfo = $estatusData->get($orden->estatus); @endphp
                                @if ($estatusInfo)
                                    @php
                                        $horas = $orden->created_at->diffInHours(now());
                                        $css = ($horas >= 48 && in_array($orden->estatus, [2, 3])) 
                                            ? 'danger' 
                                            : (($horas >= 24 && in_array($orden->estatus, [2, 3])) ? 'warning' : $estatusInfo->css);
                                    @endphp
                                    <span class="badge bg-{{ $css }} p-2 w-100" style="max-width: 130px;" title="{{ $estatusInfo->descripcion }}">
                                        <i class="me-1 fa {{ $estatusInfo->icon_orden }}"></i>
                                        {{ strtoupper($estatusInfo->orden) }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary">DESCONOCIDO</span>
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
            var table = $('#ordenesTable').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                },
                layout: {
                    topStart: {
                        buttons: [
                            { extend: 'excel', text: '<i class="fas fa-file-excel me-1"></i> Excel' },
                            { extend: 'pdf', text: '<i class="fas fa-file-pdf me-1"></i> PDF' },
                            { extend: 'print', text: '<i class="fas fa-print me-1"></i> Imprimir' }
                        ]
                    }
                },
                order: [[ 0, 'desc' ]],
                pageLength: 25,
                columnDefs: [
                    { targets: [6], orderable: false }
                ]
            });

            // Redirección al clic
            $('#ordenesTable tbody').on('click', 'tr', function() {
                var id = $(this).data('id');
                if (id) {
                    window.location.href = "{{ route('ordenes.show', '__ID__') }}".replace('__ID__', id);
                }
            });
        });
    </script>
@endpush