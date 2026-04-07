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
        <div class="card mb-3 shadow-sm">
                <div class="card-body p-3">
                    <h6 class="text-muted mb-3"><i class="fa fa-info-circle me-2"></i> Leyenda de Estatus de Vehiculos</h6>
                    <div class="d-flex flex-wrap gap-3">
                        <div class="d-flex align-items-center me-4">
                            <div class="rounded-circle bg-danger me-2" style="width: 15px; height: 15px; border: 1px solid #0002;"></div>
                            <span class="small">Fuera de Servicio</span>
                        </div>
                        <div class="d-flex align-items-center me-4">
                            <div class="rounded-circle bg-warning me-2" style="width: 15px; height: 15px; border: 1px solid #0002;"></div>
                            <span class="small">En Mantenimiento</span>
                        </div>
                        
                        <div class="d-flex align-items-center me-4">
                            <div class="rounded-circle bg-info me-2" style="width: 15px; height: 15px; border: 1px solid #0002;"></div>
                            <span class="small">En Ruta</span>
                        </div>
                        <div class="d-flex align-items-center me-4">
                            <div class="rounded-circle bg-success me-2" style="width: 15px; height: 15px; border: 1px solid #0002;"></div>
                            <span class="small">Disponible</span>
                        </div>
                        <div class="d-flex align-items-center me-4">
                            <div class="rounded-circle bg-secondary me-2" style="width: 15px; height: 15px; border: 1px solid #0002;"></div>
                            <span class="small">Desincorporado/Inoperativo</span>
                        </div>
                    </div>
                </div>
            
                <div class="card p-3 mb-3 shadow-sm border-light">
                    <h6 class="text-muted mb-3"><i class="fa fa-info-circle me-2"></i> Leyenda de Estatus de Documentos</h6>
                    <div class="d-flex flex-wrap gap-3 small">
                        <div class="d-flex align-items-center me-4">
                            <div class="rounded-circle bg-success me-2" style="width: 15px; height: 15px; border: 1px solid #0002;"></div>
                            <span class="small">Vigente / OK</span>
                            
                        </div>
                        <div class="d-flex align-items-center me-4">
                            <div class="rounded-circle bg-warning me-2" style="width: 15px; height: 15px; border: 1px solid #0002;"></div>
                            <span class="small">Próximo a Vencer (< 30 días)</span>
                        </div>
                        <div class="d-flex align-items-center me-4">
                            <div class="rounded-circle bg-danger me-2" style="width: 15px; height: 15px; border: 1px solid #0002;"></div>
                            <span class="small">Vencido / Sin Permiso (S/P)</span>
                        </div>
                        <div class="d-flex align-items-center me-4">
                            <div class="rounded-circle bg-secondary me-2" style="width: 15px; height: 15px; border: 1px solid #0002;"></div>
                            <span class="small">Sin Informacion / N/A</span>
                        </div>
                    </div>
                </div>
            </div>
        <div class="table-responsive">
            
            @php
                $agrupados = $data->groupBy(function($vehiculo) {
                    return $vehiculo->tipoVehiculo->tipo ?? 'OTROS';
                });
            @endphp 

        <ul class="nav nav-tabs mb-3" id="tipoVehiculoTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold" data-filter="all" type="button">
                    TODOS <span class="badge bg-secondary ms-1">{{ $data->count() }}</span>
                </button>
            </li>
            @foreach($agrupados as $tipo => $vehiculosPorTipo)
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold text-uppercase" data-filter="{{ $tipo }}" type="button">
                        {{ $tipo }} <span class="badge bg-primary ms-1">{{ $vehiculosPorTipo->count() }}</span>
                    </button>
                </li>
            @endforeach
        </ul>
            <table id="vehiculosTable" class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th width="5%"  class="d-none d-md-table-cell">#</th>
                        <th>Flota</th>
                        {{-- <th>Cliente</th> --}}
                        {{-- <th>Clase</th> --}}
                        <th class="d-none d-md-table-cell">Marca/Modelo</th>
                        <th class="d-none d-md-table-cell">Año</th>
                        <th width="10%">Placa</th>
                        <th class="d-none d-md-table-cell">Tipo</th>
                        <th class="d-none d-md-table-cell">Kilometraje</th>
                        <th>Estatus</th>
                        <th class="d-none d-md-table-cell">Dias Fuera de servicio</th>
                        <th class="d-none d-md-table-cell">Documentos Vencidos</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $index => $vehiculo)
                     @php $orden=false; @endphp
                        @php

                            $orden=App\Models\Orden::where('id_vehiculo',$vehiculo->id)->where('estatus',2)->get()->first();
                            if($orden){
                                $fecha=$orden->fecha_in;
                                $duracionDias = Illuminate\Support\Carbon::parse($fecha)->diffInDays(Illuminate\Support\Carbon::parse(now()));
                            }
                            @endphp
                    <tr class="clickable-row" @if($orden && $duracionDias>3) style="border: none red !important; font-weight: bold;" @endif  data-id="{{ $vehiculo->id }}">
                        <td class="d-none d-md-table-cell clickable-td">{{ $index + 1 }}</td>
                        <td class="clickable-td">{{ $vehiculo->flota ?? 'N/A' }}</td>
                        {{-- <td>{{ $vehiculo->cliente->nombre ?? 'N/A' }}</td> --}}
                        {{-- <td>{{ $vehiculo->clase ?? 'N/A' }}</td> --}}
                        <td  class="d-none d-md-table-cell clickable-td">{{ $vehiculo->marca()->marca ?? 'N/A' }} / {{ $vehiculo->modelo()->modelo ?? 'N/A' }}</td>
                        <td  class="d-none d-md-table-cell clickable-td">{{ $vehiculo->anno }}</td>
                        <td class="clickable-td"><div class="d-flex flex-column">
                        <strong>{{ $vehiculo->placa }}</strong>
                        
                        {{-- Lógica de Acople --}}
                        @php $tipo = strtoupper($vehiculo->tipoVehiculo->tipo ?? ''); @endphp

                        @if($tipo == 'CHUTO')
                            <div class="mt-1">
                                @if($vehiculo->acoplado_id && $vehiculo->cisternaAcoplada)
                                    <span class="badge bg-info text-dark d-inline" style="font-size: 0.6rem;">
                                        <i class="fa fa-link" style="font-size: 0.5rem;"></i> {{ $vehiculo->cisternaAcoplada->placa }}
                                    </span>
                                    <button type="button" class="btn btn-link text-danger p-0 ms-1 d-inline" style="font-size: 0.6rem;"
                                            onclick="event.stopPropagation(); desacoplar({{ $vehiculo->id }})" 
                                            title="Desacoplar">
                                        <i class="fa fa-times-circle" style="font-size: 0.6rem;"></i>
                                    </button>
                                @else
                                    <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" 
                                            style="font-size: 0.75rem;"
                                            onclick="event.stopPropagation(); abrirModalAcoplar({{ $vehiculo->id }}, '{{ $vehiculo->placa }}')">
                                        <i class="fa fa-plus-circle"></i> Cisterna
                                    </button>
                                @endif
                            </div>
                        @elseif($tipo == 'CISTERNA' || $tipo == 'TANQUE')
                            @if($vehiculo->chutoAsignado)
                                <small class="text-muted" style="font-size: 0.7rem;">
                                    <i class="fa fa-truck"></i>: {{ $vehiculo->chutoAsignado->placa }}
                                </small>
                            @else
                                <small class="text-muted" style="font-size: 0.7rem;">(Disponible)</small>
                            @endif
                        @endif
                    </div></td>
                        <td  class="d-none d-md-table-cell clickable-td">{{ $vehiculo->tipoVehiculo->tipo ?? 'N/A' }}</td>
                        <td class="d-none d-md-table-cell clickable-td">{{ number_format($vehiculo->kilometraje ?? 0, 0, ',', '.') }} km</td>
                        <td>
                            @php
                                $estatusInfo = $estatusData->get($vehiculo->estatus);
                            @endphp
                            @if ($estatusInfo)
                                @if($orden)
                                 <a href="/ordenes/{{$orden->id}} " style="decoration:none; cursor: pointer;" target="_blank" >   
                                @endif
                                    @php
                                        // Lógica para determinar si mostrar el badge dual
                                        $isHabilitadoAbierto = ($vehiculo->estatus == 1 && $orden);
                                    @endphp
                                    @if($isHabilitadoAbierto)
                                        <span class="badge badge-dual-status" title="Unidad Habilitada con Tareas Pendientes">
                                            <i class="fa-solid fa-truck-fast d-none d-md-table-cell"></i>
                                            <span>Operativo con Falla</span>
                                        </span>
                                    @else
                                        <span class="badge bg-{{ $estatusInfo->css }} " title="{{ $estatusInfo->descripcion }}">
                                            <i class="mr-1 fa-solid d-none d-md-table-cell {{ $estatusInfo->icon_auto }}"></i>
                                            {{ $estatusInfo->auto }}
                                        </span>
                                    @endif
                                @if($orden)
                                    </a> 
                                @endif
                            @else
                                <span class="badge bg-gray">Desconocido</span>
                            @endif
                        </td>
                        <td  class="d-none d-md-table-cell">
                            @if($vehiculo->ordenActiva)
                                <span class="badge bg-danger text-white cursor-pointer btn-detalle-orden" 
                                    style="cursor: pointer;"
                                    data-nro="{{ $vehiculo->ordenActiva->nro_orden }}"
                                    data-flota="{{ $vehiculo->flota }}"
                                    data-placa="{{ $vehiculo->placa }}"
                                    data-tipo="{{ $vehiculo->ordenActiva->tipo ?? 'Mantenimiento' }}"
                                    data-desc="{{ $vehiculo->ordenActiva->descripcion }}"
                                    data-obs="{{ $vehiculo->ordenActiva->observaciones ?? 'Sin observaciones adicionales' }}"
                                    data-url="{{ route('ordenes.show', $vehiculo->ordenActiva->id) }}"
                                    title="Click para ver detalles">
                                    <i class="fa fa-info-circle"></i>
                                    {{ $duracionDias ?? 0 }} {{ $vehiculo->ordenActiva->tipo }}
                                </span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td class="d-none d-md-table-cell text-center clickable-td">
                            @php
                                $alertas = $vehiculo->getDocumentosAlertas();
                            @endphp

                            @if($alertas['vencidos']->count() > 0)
                                <span class="badge rounded-pill bg-danger" 
                                    style="cursor: help;"
                                    data-bs-toggle="tooltip" 
                                    data-bs-html="true" 
                                    title="<b>VENCIDOS:</b><br>{{ $alertas['vencidos']->implode('<br>') }}">
                                    {{ $alertas['vencidos']->count() }}
                                </span>
                            @endif

                            @if($alertas['por_vencer']->count() > 0)
                                <span class="badge rounded-pill bg-warning text-dark" 
                                    style="cursor: help;"
                                    data-bs-toggle="tooltip" 
                                    data-bs-html="true" 
                                    title="<b>POR VENCER:</b><br>{{ $alertas['por_vencer']->implode('<br>') }}">
                                    {{ $alertas['por_vencer']->count() }}
                                </span>
                            @endif
                            @if($alertas['sin_registrar']->count() > 0)
                                <span class="badge rounded-pill bg-secondary" 
                                    style="cursor: help;"
                                    data-bs-toggle="tooltip" 
                                    data-bs-html="true" 
                                    title="<b>SIN REGISTRAR:</b><br>{{ $alertas['sin_registrar']->implode('<br>') }}">
                                    {{ $alertas['sin_registrar']->count() }}
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

<div class="modal fade" id="modalAcoplar" data-bs-backdrop="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Acoplar a <span id="placaChutoModal"></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAcoplar" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="chuto_id" id="chuto_id_input">
                    <label class="form-label small">Seleccione Cisterna/Tanque</label>
                    <select name="acoplado_id" class="form-select form-select-sm" required>
                        <option value="">-- Seleccionar --</option>
                        @foreach($data->whereIn('tipoVehiculo.tipo', ['CISTERNA', 'TANQUE'])->whereNull('chutoAsignado') as $cisterna)
                            <option value="{{ $cisterna->id }}">{{ $cisterna->flota }} - {{ $cisterna->placa }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Confirmar Acople</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetalleOrden" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg"> <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white py-3">
                <div class="d-flex align-items-center">
                    <div class="bg-primary rounded-circle p-2 me-3">
                        <i class="bi bi-tools text-white fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0 fw-bold text-uppercase tracking-wider">Ficha de Orden de Trabajo</h5>
                        <small class="text-info opacity-75">Control de Mantenimiento y Disponibilidad</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 bg-light">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-3 text-center">
                                <span class="text-muted small d-block text-uppercase mb-1">Nro. Orden</span>
                                <span id="txt-nro-orden" class="h5 fw-bold text-dark mb-0">---</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-3 text-center">
                                <span class="text-muted small d-block text-uppercase mb-1">Unidad</span>
                                <span id="txt-unidad" class="h5 fw-bold text-primary mb-0">---</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-3 text-center">
                                <span class="text-muted small d-block text-uppercase mb-1">Tipo de Orden</span>
                                <div id="txt-tipo-orden" class="mt-1"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-4">
                        <h6 class="fw-bold border-bottom pb-2 mb-3">
                            <i class="bi bi-file-text me-2"></i>DESCRIPCIÓN DEL REQUERIMIENTO
                        </h6>
                        <p id="txt-descripcion" class="text-dark leading-relaxed mb-4" style="text-align: justify;"></p>

                        <h6 class="fw-bold border-bottom pb-2 mb-3">
                            <i class="bi bi-chat-dots me-2"></i>OBSERVACIONES DE TALLER
                        </h6>
                        <div id="wrapper-obs" class="p-3 bg-white border-start border-4 border-warning rounded">
                            <p id="txt-observaciones" class="small mb-0 text-secondary italic"></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer border-0 bg-white p-3">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cerrar</button>
                <a href="#" id="btn-ir-orden" class="btn btn-primary px-4 shadow-sm">
                    <i class="bi bi-box-arrow-up-right me-2"></i>Gestionar Orden Completa
                </a>
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
           const table = $('#vehiculosTable').DataTable({
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
                dom: '<"row"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>rtip', // Fuerza el grid de Bootstrap
                layout: {
                    topStart: {
                        buttons: ['csv', 'excel', 'pdf', 'print']
                    }
                },
               drawCallback: function() {
                    // Activa los tooltips cada vez que la tabla cambie (filtro, página, etc)
                    $('[data-bs-toggle="tooltip"]').tooltip();
                },
                "order": [
                    [ 8, 'desc' ] 
                ]
            });

            $('#tipoVehiculoTabs button').on('click', function() {
                $('#tipoVehiculoTabs button').removeClass('active');
                $(this).addClass('active');

                const filterValue = $(this).data('filter');

                if (filterValue === 'all') {
                    table.column(5).search('').draw();
                } else {
                    // Buscamos el término exacto en la columna 5 (Tipo)
                    table.column(5).search('^' + filterValue + '$', true, false).draw();
                }
            });

            // Lógica para redirigir al hacer clic en una fila
            $('#vehiculosTable tbody').on('click', 'td.clickable-td', function() {
                var id = $(this).closest('tr').data('id');
                if (id) {
                    let url = "{{ route('vehiculos.show', ':id') }}";
                    window.location.href = url.replace(':id', id);
                }
            });

            $('.btn-detalle-orden').on('click', function() {
                console.log('Detalle orden clicked');
                const btn = $(this);
                
                // Extraer datos de los atributos data-
                const nro = btn.data('nro');
                const tipo = btn.data('tipo');
                const placa = btn.data('placa');
                const flota = btn.data('flota');
                const desc = btn.data('desc');
                const obs = btn.data('obs');
                const url = btn.data('url');
                console.log({ nro, tipo, desc, obs, url });
                // Inyectar en el modal
                $('#txt-nro-orden').text(nro);
                $('#txt-tipo-orden').text(tipo);
                $('#txt-unidad').text(flota + ' [' + placa+']');
                $('#txt-descripcion').text(desc);
                $('#txt-observaciones').text(obs);
                $('#btn-ir-orden').attr('href', url);

                // Mostrar el modal
                $('#modalDetalleOrden').modal('show');
            });
        });
    function abrirModalAcoplar(id, placa) {
        // Limpiar el select por si se abrió antes
        $('#formAcoplar')[0].reset();
        
        // Asignar valores a los campos ocultos
        $('#chuto_id_input').val(id);
        $('#placaChutoModal').text(placa);
        
        // Configurar la acción del formulario dinámicamente
        $('#formAcoplar').attr('action', "{{ route('vehiculos.acoplar') }}");
        
        $('#modalAcoplar').modal('show');
    }

    $('#modalAcoplar').on('shown.bs.modal', function () {
        $('.form-select').select2({
            dropdownParent: $('#modalAcoplar'),
            placeholder: "Buscar cisterna por placa o flota..."
        });
    });

    function desacoplar(id) {
        Swal.fire({
            title: '¿Desacoplar unidad?',
            text: "El chuto y la cisterna figurarán como independientes.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, desacoplar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `/vehiculos/desacoplar/${id}`;
            }
        });

        
    }
    </script>
@endpush
