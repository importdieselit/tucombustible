@extends('layouts.app')

@section('title', 'Hoja de Vida del Vehículo')

@push('styles')
    <style>

    </style>
@endpush


@section('content')
<div class="container-fluid">
<div class="col-md-auto ms-auto d-flex gap-2 flex-wrap pt-2">
    <button class="btn btn-corporate shadow-sm" data-bs-toggle="modal" data-bs-target="#modalKm">
        <i class="fa-solid fa-gauge-high me-1"></i> KM
    </button>

    <button class="btn btn-outline-corporate shadow-sm" data-bs-toggle="modal" data-bs-target="#modalChofer">
        <i class="fa-solid fa-user-tie me-1"></i> Chofer
    </button>

    @if($esChuto)
        <button class="btn btn-outline-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAcoplar">
            <i class="fa-solid fa-link me-1"></i> Acoplar Cisterna
        </button>
    @elseif($esCisterna)
        <button class="btn btn-outline-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAsignarChuto">
            <i class="fa-solid fa-truck-front me-1"></i> Asignar Chuto
        </button>
    @endif

    <a type="button" class="btn btn-danger" href="{{ route('ot.create', $item->id) }}">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> Crear Orden de Trabajo
    </a>


    <a href="{{ route('vehiculos.edit', $item->id) }}" class="btn btn-dark">
        <i class="fa-solid fa-pen-to-square"></i>
    </a>
</div>
    <div class="card mb-4 border-0 shadow-sm bg-white">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-auto text-center">
                    <div class="rounded-circle p-3 pb-0  d-block">
                        <i class="fa-solid fa-truck-moving fa-3x text-corporate"></i>
                    </div>
                    <small class="text-muted" style="margin-top: -20px">{{ $tipo }}</small>
                </div>
                <div class="col-md">
                    <h3 class="mb-0">{{ $item->placa }} </h3>
                    <p class="text-muted mb-0">{{ $item->marca()->marca }} {{ $item->modelo()->modelo }} | {{ $item->anno }}</p>
                    <span class="badge bg-{{ $estatus->css }}">
                        {{ $estatus->auto  }} 
                    </span>
                </div>
                @if($esChuto || $esCisterna)
                    <div class="col-md-auto border-start d-none d-md-block">
                       @if($esChuto)
                            <div class="mt-1">
                                @if($item->acoplado_id)
                                   <small class="text-muted d-block">Cisterna Acoplada</small>
                                    <span class=" text-dark d-inline">
                                        <i class="fa fa-link"></i> {{ $item->cisternaAcoplada->placa }}
                                    </span>
                                    <button type="button" class="btn btn-link text-danger p-0 ms-1 d-inline" style="font-size: 0.6rem;"
                                            onclick="event.stopPropagation(); desacoplar({{ $item->id }})" 
                                            title="Desacoplar">
                                        <i class="fa fa-times-circle" style="font-size: 0.6rem;"></i>
                                    </button>
                                    @else
                                    <span class="text-muted">(Sin Cisterna)</span>
                                @endif
                            </div>
                        @else
                            @if($item->chutoAsignado)
                                
                                    <i class="fa fa-truck"></i>: {{ $item->chutoAsignado->placa }}
                                
                            @else
                                (Disponible)
                            @endif
                        @endif
                    </div>
                @endif
                <div class="col-md-auto border-start d-none d-md-block">
                    <small class="text-muted d-block">Kilometraje Actual</small>
                    <h5 class="mb-0">{{ number_format($item->kilometraje) }} km</h5>
                </div>
                <div class="col-md-auto border-start d-none d-md-block">
                    <small class="text-muted d-block">Ubicación Actual</small>
                    <h5 class="mb-0 text-primary">{{ $ubicacion['nombre'] ?? 'En Patio' }}</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white p-0">
            <ul class="nav nav-tabs card-header-tabs m-0" id="vehiculoTab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link text-corporate-emphasis active" id="resumen-tab" data-bs-toggle="tab" href="#resumen" role="tab"><i class="fa-solid fa-file-invoice me-1"></i> Hoja de Vida</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-corporate-emphasis" id="docs-tab" data-bs-toggle="tab" href="#docs" role="tab"><i class="fa-solid fa-folder-open me-1"></i> Documentación</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-corporate-emphasis" id="mantenimiento-tab" data-bs-toggle="tab" href="#mantenimiento" role="tab"><i class="fa-solid fa-screwdriver-wrench me-1"></i> Mantenimientos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-corporate-emphasis" id="viajes-tab" data-bs-toggle="tab" href="#viajes" role="tab"><i class="fa-solid fa-route me-1"></i> Viajes</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-corporate-emphasis" id="fotos-tab" data-bs-toggle="tab" href="#fotos" role="tab"><i class="fa-solid fa-image me-1"></i> Galería</a>
                </li>
            </ul>
        </div>
        
        <div class="card-body bg-white">
            <div class="tab-content" id="vehiculoTabContent">
                
                <div class="tab-pane fade show active" id="resumen" role="tabpanel">
                    <div class="row">
                        <h6 class="mb-3">Historial de Kilometraje y Consumo Mensual <span class="text-danger">(MODO DEMO)</span></h6>
                    {{-- Contenedor del gráfico --}}
                    <div class="col-md-8" id="monthly-chart" style="height: 300px; margin-bottom: 20px;"></div>
                        <div class="col-md-4 border-start ps-4 bg-white rounded shadow-sm p-2">
                            <h6 class="text-uppercase fw-bold border-bottom pb-2">Datos Técnicos</h6>
                            <table class="table table-white table-sm table-borderless bg-white">
                                <tr><td class="text-muted">Serial Motor:</td><td>{{ $item->serial_motor }}</td></tr>
                                <tr><td class="text-muted">Serial Chasis:</td><td>{{ $item->serial_carroceria }}</td></tr>
                                <tr><td class="text-muted">Carga Máxima:</td><td>{{ number_format($item->carga_max) }} kg</td></tr>
                                <tr><td class="text-muted">Tipo de Combustible:</td><td>{{ $item->tipo_combustible }}</td></tr>
                                <tr><td class="text-muted">Capacidad Tanque<td>{{ number_format($item->consumo, 2) }} Ltrs</td></tr>
                                <tr><td class="text-muted">Kilometraje Recorrido:</td><td>{{ number_format($item->km_mantt) }} km</td></tr>
                                <tr><td class="text-muted">Proximo Mantenimiento:</td><td class="font-weight-bold text-{{ 5000-$item->km_mantt < 50 ? 'danger' : (5000-$item->km_mantt< 200 ? 'warning' : 'success') }} "><strong>{{ number_format(5000-$item->km_mantt) }} km</strong></td></tr>
                                <tr><td class="text-muted">Horas de Trabajo:</td><td>{{ number_format($item->hrs_mantt) }} hrs</td></tr>
                                <tr><td class="text-muted">Próximo Mantenimiento:</td><td class="font-weight-bold text-{{ 200-$item->hrs_mantt <= 15 ? 'danger' : (200-$item->hrs_mantt<= 36 ? 'warning' : 'success') }} "><strong>{{ number_format(200-$item->hrs_mantt) }} hrs</strong></td></tr>
                                <tr><td class="text-muted">Afecta Disponibilidad:</td><td>{{ $item->es_flota ? 'Sí' : 'No' }}</td></tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="docs" role="tabpanel">
                    <div class="alert alert-info d-flex align-items-center">
                        <i class="fa-solid fa-circle-info me-2"></i> Módulo de Documentos: <strong>En Construcción</strong>
                    </div>
                    </div>

                <div class="tab-pane fade" id="mantenimiento" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nro Orden</th>
                                    <th>Fecha</th>
                                    <th>Descripción</th>
                                    <th>Responsable</th>
                                    <th>Estatus</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($mantenimientos as $m)
                                    <tr>
                                        <td><a href="{{ route('ordenes.show', $m->id) }}" class="text-decoration-none">#{{ $m->nro_orden }}</a></td>
                                        <td>{{ $m->fecha_in }}</td>
                                        <td>{{ $m->tipo }} - {{ $m->descripcion }}</td>
                                        <td>{{ $m->responsable }}</td>
                                        <td><span class="badge bg-{{ $m->estatus() ? $m->estatus()->css : 'secondary' }}">{{ $m->estatus() ? $m->estatus()->orden : 'Sin Estatus' }}</span></td>
                                    </tr>
                                @empty
                                <tr class="text-center"><td colspan="4">No hay mantenimientos registrados</td></tr>
                                    
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="viajes" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Origen</th>
                                    <th>Destino</th>
                                    <th>Conductor</th>
                                    <th>Cliente</th>
                                    <th>Carga Litros</th>
                                    <th>Kilometraje</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($viajes as $viaje)
                              
                                    <tr>
                                        <td>{{ $viaje["fecha"] }}</td>
                                        <td> Sede Principal</td>
                                        <td>{{ $viaje["destino"] }}</td>
                                        <td>{{ $viaje["chofer"]}}</td>
                                        <td>{{ $viaje["cliente"] }}</td>
                                        <td>{{ $viaje["litros"] }} L</td>
                                        <td>-- km</td>
                                    </tr>
                                @empty
                                    <tr class="text-center"><td colspan="7">No hay viajes registrados</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="fotos" role="tabpanel">
                    <div class="row g-3">
                        <div class="col-12 text-center p-5">
                            <i class="fa-solid fa-images fa-4x text-light-emphasis mb-3"></i>
                            <p class="text-muted">Módulo de Galería: <strong>En Construcción</strong></p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalKm" data-bs-backdrop="false" tabindex="-1">
    <div class="modal-dialog">
        <form action="#" method="POST" class="modal-content">
            @csrf @method('PATCH')
            <div class="modal-header"><h5>Actualizar Kilometraje</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label>Kilometraje Actual (Placa: {{ $item->placa }})</label>
                <input type="number" name="km_actual" class="form-control form-control-lg" value="{{ $item->km_actual }}" required>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary w-100">Guardar Cambios</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalAcoplar" data-bs-backdrop="false" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content text-center p-4">
            <i class="fa-solid fa-link fa-3x text-info mb-3"></i>
            <div class="modal-header">
                <h6 class="modal-title">Acoplar a <span id="placaChutoModal"></span></h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAcoplar" method="POST" action="{{ route('vehiculos.acoplar') }}">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="chuto_id" id="chuto_id_input" value="{{ $item->id }}">
                    <label class="form-label small">Seleccione Unidad</label>
                    <select name="acoplado_id" class="form-select form-select-sm" required>
                        <option value="">-- Seleccionar --</option>
                        @foreach($acoples as $cisterna)
                            <option value="{{ $cisterna->id }}">{{ $cisterna->flota }} - {{ $cisterna->placa }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Confirmar Acople</button>
                </div>
            </
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {


      

        // Datos de PHP pasados a JavaScript
        const historialMensual = @json($historialMensual);

        // Prepara los datos para Highcharts
        const categories = historialMensual.map(item => item.mes).reverse();
        const kmSeries = historialMensual.map(item => item.km).reverse();
        const consumoSeries = historialMensual.map(item => item.consumo).reverse();

        // Configuración y renderización del gráfico
        Highcharts.chart('monthly-chart', {
            chart: {
                type: 'line'
            },
            title: {
                text: 'Kilometraje vs Consumo Mensual'
            },
            xAxis: {
                categories: categories,
                title: {
                    text: 'Mes'
                }
            },
            yAxis: [{
                // Eje Y para Kilometraje
                title: {
                    text: 'Kilometraje (km)',
                    style: {
                        color: Highcharts.getOptions().colors[0]
                    }
                },
                labels: {
                    format: '{value} km',
                    style: {
                        color: Highcharts.getOptions().colors[0]
                    }
                }
            }, {
                // Eje Y secundario para Consumo
                title: {
                    text: 'Consumo (L)',
                    style: {
                        color: Highcharts.getOptions().colors[1]
                    }
                },
                labels: {
                    format: '{value} L',
                    style: {
                        color: Highcharts.getOptions().colors[1]
                    }
                },
                opposite: true // Ubica el eje en el lado opuesto
            }],
            tooltip: {
                shared: true
            },
            series: [{
                name: 'KM Recorridos',
                data: kmSeries,
                color: Highcharts.getOptions().colors[0],
                tooltip: {
                    valueSuffix: ' km'
                }
            }, {
                name: 'Consumo',
                data: consumoSeries,
                yAxis: 1, // Asigna esta serie al segundo eje Y
                color: Highcharts.getOptions().colors[1],
                tooltip: {
                    valueSuffix: ' L'
                }
            }],
            credits: false
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
@endsection
