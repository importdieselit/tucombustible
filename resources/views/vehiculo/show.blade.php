@extends('layouts.app')

@section('title', 'Hoja de Vida del Vehículo')

@push('styles')
    <style>
        .card-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
            color: #495057;
        }
        .info-label {
            font-weight: 500;
            color: #6c757d;
        }
        .info-value {
            font-weight: 400;
            color: #343a40;
        }
        .indicator-card {
            background-color: #e9f5ff;
            border: 1px solid #cce5ff;
        }
        .indicator-label {
            font-size: 0.9rem;
            font-weight: 500;
            color: #007bff;
        }
        .indicator-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #007bff;
        }
        .text-success {
            color: #28a745 !important;
        }
        .text-info {
            color: #17a2b8 !important;
        }
    </style>
@endpush
@php
     // NOTA: En una aplicación real, estos datos vendrían de la base de datos.
            $rutas = collect([
                ['fecha' => '2024-05-15', 'origen' => 'Caracas', 'destino' => 'Valencia', 'km' => 170, 'conductor' => 'Pedro Pérez'],
                ['fecha' => '2024-05-12', 'origen' => 'Valencia', 'destino' => 'Maracay', 'km' => 60, 'conductor' => 'Ana López'],
                ['fecha' => '2024-05-10', 'origen' => 'Maracay', 'destino' => 'Caracas', 'km' => 120, 'conductor' => 'Juan Rivas'],
                ['fecha' => '2024-05-08', 'origen' => 'Caracas', 'destino' => 'La Guaira', 'km' => 40, 'conductor' => 'Pedro Pérez'],
                ['fecha' => '2024-05-05', 'origen' => 'La Guaira', 'destino' => 'Caracas', 'km' => 45, 'conductor' => 'Ana López'],
            ]);

            $historialMensual = collect([
                ['mes' => 'Mayo 2024', 'km' => 1500, 'consumo' => 120.5],
                ['mes' => 'Abril 2024', 'km' => 1800, 'consumo' => 145.7],
                ['mes' => 'Marzo 2024', 'km' => 2100, 'consumo' => 170.3],
                ['mes' => 'Febrero 2024', 'km' => 1950, 'consumo' => 155.0],
                ['mes' => 'Enero 2024', 'km' => 1750, 'consumo' => 135.2],
            ]);

            // Cálculo de indicadores económicos (con datos simulados)
            $precioLitroCombustible = 0.5; // Precio ficticio por litro en USD
            $consumoTotalLitros = $historialMensual->sum('consumo');
            $gastoCombustible = $consumoTotalLitros * $precioLitroCombustible;
            $kmTotales = $historialMensual->sum('km');
            $costoPorKm = $kmTotales > 0 ? $gastoCombustible / $kmTotales : 0;
            @endphp
@endphp
@if($item->estatus==3 || $item->estatus ==5)
        @php
            $orden=App\Models\Orden::where('id_vehiculo',$item->id)->where('estatus',2)->get()->first();
            if($orden){
                $fecha=$orden->fecha_in;
                $duracionDias = Illuminate\Support\Carbon::parse($fecha)->diffInDays(Illuminate\Support\Carbon::parse(now()));
                $insumos_usados = App\Models\InventarioSuministro::with('inventario')->where('id_orden', $orden->id)->get();
            
            }
        @endphp
    @endif


@section('content')
<div class="container-fluid">
    <div class="row page-titles mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h3 class="text-themecolor mb-0">Hoja de Vida del Vehículo</h3>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
                <li class="breadcrumb-item"><a href="{{ route('vehiculos.index') }}">Vehículos</a></li>
                <li class="breadcrumb-item active">{{ $item->placa }}</li>
            </ol>
        </div>
    </div>

    @if ($item)
    <div class="row">
        <div class="col-12 d-flex justify-content-end mb-4">
            <a href="{{ route('vehiculos.edit', $item->id) }}" class="btn btn-warning me-2 d-flex align-items-center">
                <i class="fas fa-edit me-2"></i> Editar
            </a>
            <a href="{{ route('ot.create', $item->id) }}" target="_blank" class="btn btn-warning btn-lg d-flex align-items-center">
                <i class="fa-solid fa-wrench me-2"></i> Crear Orden de Trabajo
            </a>
            <a href="{{ route('vehiculos.index') }}" class="btn btn-secondary d-flex align-items-center">
                <i class="fas fa-list me-2"></i> Volver al listado
            </a>
        </div>
    </div>
    
    <div class="row">
        {{-- Tarjeta de Información General --}}
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">Información General</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6 mb-3"><span class="info-label">Flota:</span> <span class="info-value">{{ $item->flota }}</span></div>
                        <div class="col-sm-6 mb-3"><span class="info-label">Placa:</span> <span class="info-value">{{ $item->placa }}</span></div>
                        <div class="col-sm-6 mb-3"><span class="info-label">Marca:</span> <span class="info-value">{{ $item->marca_rel->nombre ?? 'N/A' }}</span></div>
                        <div class="col-sm-6 mb-3"><span class="info-label">Modelo:</span> <span class="info-value">{{ $item->modelo_rel->nombre ?? 'N/A' }}</span></div>
                        <div class="col-sm-6 mb-3"><span class="info-label">Año:</span> <span class="info-value">{{ $item->anno }}</span></div>
                        <div class="col-sm-6 mb-3"><span class="info-label">Color:</span> <span class="info-value">{{ $item->color }}</span></div>
                        <div class="col-sm-6 mb-3"><span class="info-label">Estatus:</span> <span class="info-value">
                            @php
                                $estatusInfo = $estatusData->get($item->estatus);
                            @endphp
                            @if ($estatusInfo)
                                <span class="badge bg-{{ $estatusInfo['css'] }}" title="{{ $estatusInfo['descripcion'] }}">
                                    <i class="mr-1 fa-solid {{ $estatusInfo['icon'] }}"></i>
                                    {{ $estatusInfo['auto'] }}
                                    @if($orden)
                                        hace {{$duracionDias ?? 0}} dias
                                    @endif
                                </span>
                            @else
                                <span class="badge bg-secondary">Desconocido</span>
                            @endif

                        </span></div>
                        <div class="col-sm-6 mb-3"><span class="info-label">Disponibilidad:</span> <span class="info-value">{{ $item->disp ? 'Sí' : 'No' }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tarjeta de Indicadores Clave --}}
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100 indicator-card">
                <div class="card-header bg-primary text-white">Indicadores Clave</div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-sm-4 mb-3">
                            <h5 class="indicator-value">{{ number_format($item->kilometraje, 0, ',', '.') }} km</h5>
                            <span class="indicator-label">Kilometraje Inicial</span>
                        </div>
                        <div class="col-sm-4 mb-3">
                            <h5 class="indicator-value">{{ number_format($item->km_contador, 0, ',', '.') }} km</h5>
                            <span class="indicator-label">Kilometraje Actual</span>
                        </div>
                        <div class="col-sm-4 mb-3">
                            <h5 class="indicator-value">{{ number_format($item->km_mantt, 0, ',', '.') }} km</h5>
                            <span class="indicator-label">Próx. Mantenimiento</span>
                        </div>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-sm-6 mb-3">
                            <h5 class="indicator-value">{{ $item->fuel }} L</h5>
                            <span class="indicator-label">Capacidad de Combustible</span>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <h5 class="indicator-value">{{ $item->consumo }} L/100km</h5>
                            <span class="indicator-label">Consumo Promedio</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($item->estatus==3 || $item->estatus ==5)
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title m-0">Detalles de la Orden</h5>
            </div>
            <div class="card-body">
                <h5>Descripción del Problema/Tarea</h5>
                <p>{{ $orden->descripcion_1 ?? 'No hay descripción.' }}</p>

                <hr>
                <h5>Observaciones</h5>
                <p>{{ $orden->observacion ?? 'No hay observaciones.' }}</p>

                <hr>
                <h5>Insumos Utilizados</h5>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Cantidad</th>
                            <th>Costo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($insumos_usados as $insumo)
                            <tr>
                                <td>{{ $insumo->inventario->codigo ?? 'N/A' }}</td>
                                <td>{{ $insumo->inventario->descripcion ?? 'N/A' }}</td>
                                <td>{{ $insumo->cantidad ?? 'N/A' }}</td>
                                <td>${{ number_format($insumo->inventario->costo * $insumo->cantidad, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center">No se han registrado insumos para esta orden.</td>
                            </tr>
                        @endforelse
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total Costo:</strong></td>
                            <td><strong>${{ number_format($insumos_usados->sum(fn($insumo) => $insumo->inventario->costo * $insumo->cantidad), 2, ',', '.') }}</strong></td>
                    </tbody>
                </table>
            </div>
        </div>
    @endif
    
    <div class="row">
        {{-- Tarjeta de Detalles Técnicos --}}
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">Detalles Técnicos</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3"><span class="info-label">Serial Motor:</span> <span class="info-value">{{ $item->serial_motor }}</span></div>
                        <div class="col-md-6 mb-3"><span class="info-label">Serial Carrocería:</span> <span class="info-value">{{ $item->serial_carroceria }}</span></div>
                        <div class="col-md-6 mb-3"><span class="info-label">Transmisión:</span> <span class="info-value">{{ $item->transmision }}</span></div>
                        <div class="col-md-6 mb-3"><span class="info-label">HP / CC:</span> <span class="info-value">{{ $item->HP }} / {{ $item->CC }}</span></div>
                        <div class="col-md-6 mb-3"><span class="info-label">Tipo Combustible:</span> <span class="info-value">{{ $item->tipo_combustible }}</span></div>
                        <div class="col-md-6 mb-3"><span class="info-label">Tipo de Vehículo:</span> <span class="info-value">{{ $item->tipoVehiculo->nombre ?? 'N/A' }}</span></div>
                        <div class="col-md-6 mb-3"><span class="info-label">Carga Máx:</span> <span class="info-value">{{ $item->carga_max ?? 'N/A' }}</span></div>
                        <div class="col-md-6 mb-3"><span class="info-label">Aceite:</span> <span class="info-value">{{ $item->oil ?? 'N/A' }}</span></div>
                        <div class="col-md-6 mb-3"><span class="info-label">Dimensiones (L/An/Al):</span> <span class="info-value">{{ $item->largo }}m / {{ $item->ancho }}m / {{ $item->altura }}m</span></div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Tarjeta de Póliza y Seguros --}}
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">Póliza y Seguros</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3"><span class="info-label">N° de Póliza:</span> <span class="info-value">{{ $item->poliza_numero }}</span></div>
                        <div class="col-md-6 mb-3"><span class="info-label">Agencia:</span> <span class="info-value">{{ $item->agencia }}</span></div>
                        <div class="col-md-6 mb-3"><span class="info-label">Fecha de Inicio:</span> <span class="info-value">{{ \Carbon\Carbon::parse($item->poliza_fecha_in)->format('d/m/Y') }}</span></div>
                        <div class="col-md-6 mb-3"><span class="info-label">Fecha de Fin:</span> <span class="info-value">{{ \Carbon\Carbon::parse($item->poliza_fecha_out)->format('d/m/Y') }}</span></div>
                        <div class="col-md-6 mb-3"><span class="info-label">Cobertura:</span> <span class="info-value">{{ $item->cobertura }}</span></div>
                        <div class="col-md-6 mb-3"><span class="info-label">Tipo de Póliza:</span> <span class="info-value">{{ $item->tipo_poliza }}</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Secciones de historial y consumo --}}
    <div class="row">
        {{-- Rutas y Movimientos --}}
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">Últimas Rutas y Movimientos <span class="text-danger">(MODO DEMO)</span></div>
                <div class="card-body">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Ruta</th>
                                <th>KM Recorridos</th>
                                <th>Conductor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rutas as $ruta)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($ruta['fecha'])->format('d/m/Y') }}</td>
                                    <td>{{ $ruta['origen'] }} -> {{ $ruta['destino'] }}</td>
                                    <td>{{ number_format($ruta['km'], 0, ',', '.') }} km</td>
                                    <td>{{ $ruta['conductor'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">No hay rutas registradas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Indicadores Económicos --}}
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">Indicadores Económicos <span class="text-danger">(MODO DEMO)</span></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3 text-center">
                            <div class="p-3 border rounded">
                                <h6 class="text-muted">Gasto Total en Combustible</h6>
                                <h3 class="text-success">${{ number_format($gastoCombustible, 2, ',', '.') }}</h3>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3 text-center">
                            <div class="p-3 border rounded">
                                <h6 class="text-muted">Costo por Kilómetro</h6>
                                <h3 class="text-info">${{ number_format($costoPorKm, 2, ',', '.') }}</h3>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <h6 class="mb-3">Historial de Kilometraje y Consumo Mensual <span class="text-danger">(MODO DEMO)</span></h6>
                    {{-- Contenedor del gráfico --}}
                    <div id="monthly-chart" style="height: 300px; margin-bottom: 20px;"></div>
                    {{-- Tabla de historial --}}
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Mes</th>
                                <th>KM Recorridos</th>
                                <th>Consumo (L)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($historialMensual as $historial)
                                <tr>
                                    <td>{{ $historial['mes'] }}</td>
                                    <td>{{ number_format($historial['km'], 0, ',', '.') }}</td>
                                    <td>{{ number_format($historial['consumo'], 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">No hay historial mensual.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    @else
        <div class="alert alert-danger" role="alert">
            Vehículo no encontrado.
        </div>
    @endif
</div>

{{-- Scripts para Highcharts --}}
<script src="https://code.highcharts.com/highcharts.js"></script>
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
            }]
        });
    });
</script>
@endsection
