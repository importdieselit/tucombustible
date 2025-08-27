@extends('layouts.app')

@section('title', 'Ficha del Cliente')

@push('styles')
    <style>
        /* Estilos personalizados del template */
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
        
        /* Estilos adicionales para los KPIs del cliente */
        .kpi-card {
            background-color: #fff;
            border: 1px solid #e9ecef;
            border-radius: 0.5rem;
        }
        .kpi-icon {
            background-color: #e9f5ff;
            color: #007bff;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
@endpush

@php
    // Simulamos los datos para los KPIs, asumiendo que $item ya contiene los datos del cliente.
    // En una aplicación real, esta lógica debería estar en el controlador.
    if (isset($item)) {
        // Datos de prueba para el historial y predicción
        $item->historico_compras = collect([
            ['fecha' => '2025-01-10', 'litros' => 1200],
            ['fecha' => '2025-02-15', 'litros' => 1350],
            ['fecha' => '2025-03-20', 'litros' => 1400],
            ['fecha' => '2025-04-25', 'litros' => 1600],
            ['fecha' => '2025-05-30', 'litros' => 1550],
            ['fecha' => '2025-06-05', 'litros' => 1480],
            ['fecha' => '2025-07-12', 'litros' => 1520],
            ['fecha' => '2025-08-15', 'litros' => 1610]
        ]);
        $item->consumo_mensual_promedio = 1500;
        $item->dias_faltantes_proxima_compra = 5;
        $item->proxima_compra_prediccion = [
            'fecha' => '2025-08-20',
            'litros_predichos' => 1450
        ];
    }
@endphp

@section('content')
<div class="container-fluid">
    <div class="row page-titles mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h3 class="text-themecolor mb-0">Ficha del Cliente</h3>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="#">Inicio</a></li>
                <li class="breadcrumb-item"><a href="#">Clientes</a></li>
                <li class="breadcrumb-item active">Detalles</li>
            </ol>
        </div>
    </div>
    
    @if ($item)
    <div class="row">
        <div class="col-12 d-flex justify-content-end mb-4">
            <a href="#" class="btn btn-warning me-2 d-flex align-items-center">
                <i class="fas fa-edit me-2"></i> Editar
            </a>
            <a href="{{ route('clientes.list')}}" class="btn btn-secondary d-flex align-items-center">
                <i class="fas fa-list me-2"></i> Volver al listado
            </a>
        </div>
    </div>
    
    <div class="row">
        {{-- Tarjeta de Información General --}}
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">Información General del Cliente</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-12 mb-3"><span class="info-label">Nombre:</span> <span class="info-value">{{ $item->nombre }}</span></div>
                        <div class="col-sm-12 mb-3"><span class="info-label">rif:</span> <span class="info-value">{{ $item->rif ?? 'N/A' }}</span></div>
                        <div class="col-sm-12 mb-3"><span class="info-label">Contacto:</span> <span class="info-value">{{ $item->contacto ?? 'N/A' }}</span></div>
                        <div class="col-sm-12 mb-3"><span class="info-label">Teléfono:</span> <span class="info-value">{{ $item->telefono ?? 'N/A' }}</span></div>
                        <div class="col-sm-12 mb-3"><span class="info-label">Email:</span> <span class="info-value">{{ $item->email ?? 'N/A' }}</span></div>
                        <div class="col-sm-12 mb-3"><span class="info-label">Dirección:</span> <span class="info-value">{{ $item->direccion ?? 'N/A' }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tarjeta de Indicadores Clave del Cliente --}}
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100 indicator-card">
                <div class="card-header bg-primary text-white">Indicadores Clave</div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-sm-6 mb-3">
                            <h5 class="indicator-value">{{ number_format($item->consumo_mensual_promedio, 0, ',', '.') }} L</h5>
                            <span class="indicator-label">Consumo Promedio Mensual</span>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <h5 class="indicator-value">
                                @if($item->dias_faltantes_proxima_compra <= 7)
                                    <span class="text-danger">{{ $item->dias_faltantes_proxima_compra }} días</span>
                                @else
                                    <span class="text-success">{{ $item->dias_faltantes_proxima_compra }} días</span>
                                @endif
                            </h5>
                            <span class="indicator-label">Días para Próx. Compra</span>
                        </div>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-sm-12 mb-3">
                            <h6 class="text-muted">Próxima Compra Predicha</h6>
                            <p class="mb-0"><span class="info-label">Fecha estimada:</span> <span class="info-value">{{ \Carbon\Carbon::parse($item->proxima_compra_prediccion['fecha'])->format('d/m/Y') }}</span></p>
                            <p class="mb-0"><span class="info-label">Litros estimados:</span> <span class="info-value">{{ number_format($item->proxima_compra_prediccion['litros_predichos'], 0, ',', '.') }} L</span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Secciones de historial y consumo --}}
    <div class="row">
        {{-- Historial de Consumo Mensual --}}
        <div class="col-12 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">Historial de Consumo Mensual</div>
                <div class="card-body">
                    {{-- Contenedor del gráfico --}}
                    <div id="monthly-chart" style="height: 300px; margin-bottom: 20px;"></div>
                    
                    {{-- Tabla de historial --}}
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Mes</th>
                                <th>Consumo (L)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($item->historico_compras as $compra)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($compra['fecha'])->translatedFormat('F Y') }}</td>
                                    <td>{{ number_format($compra['litros'], 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2">No hay historial de consumo.</td>
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
            Cliente no encontrado.
        </div>
    @endif
</div>

{{-- Scripts para Highcharts --}}
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Datos de PHP pasados a JavaScript
        const historialMensual = @json($item->historico_compras);

        // Prepara los datos para Highcharts
        const consumoSeries = historialMensual.map(item => {
            return [new Date(item.fecha).getTime(), item.litros];
        }).sort((a, b) => a[0] - b[0]); // Asegura que los datos estén ordenados temporalmente

        // Configuración y renderización del gráfico de línea
        Highcharts.chart('monthly-chart', {
            chart: {
                type: 'spline',
                zoomType: 'x'
            },
            title: {
                text: 'Historial de Consumo Mensual'
            },
            xAxis: {
                type: 'datetime',
                dateTimeLabelFormats: {
                    month: '%e. %b',
                    year: '%b'
                },
                title: {
                    text: 'Fecha'
                }
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Consumo (L)'
                },
                labels: {
                    format: '{value} L'
                }
            },
            tooltip: {
                headerFormat: '<b>{point.x:%B %Y}</b><br/>',
                pointFormat: '{series.name}: <b>{point.y:.2f} L</b>'
            },
            series: [{
                name: 'Consumo',
                data: consumoSeries,
                marker: {
                    enabled: true,
                    radius: 4
                }
            }]
        });
    });
</script>
@endsection
