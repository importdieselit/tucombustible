@extends('layouts.app')

@section('title', 'Dashboard de Órdenes')

@section('content')
<div class="container-fluid mt-4">
    <div class="row page-titles mb-4">
        
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h3 class="text-themecolor mb-0">Dashboard de Órdenes de Trabajo</h3>
            <a href="{{ route('ordenes.compra') }}" class="btn btn-info d-flex align-items-center">
                <i class="fas fa-list me-2"></i> Orden de Compra
            </a>
            <a href="{{ route('ordenes.list') }}" class="btn btn-info d-flex align-items-center">
                <i class="fas fa-list me-2"></i> Ver Listado
            </a>
        </div>
    </div>

    {{-- Filtros --}}
    {{-- Primera fila de métricas --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Tiempo Promedio de Orden</h5>
                    <h2 class="card-text">{{ $tiempo_promedio_orden }} días</h2>
                    <p class="card-text"><small>Tiempo entre apertura y cierre</small></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Próximos Mantenimientos</h5>
                    <ul class="list-unstyled mb-0">
                        @foreach ($mantenimientos_proximos as $m)
                        <li><strong>{{ $m->vehiculo }}:</strong> {{ $m->tarea }} ({{ $m->fecha }})</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Alertas de Kilometraje</h5>
                    <ul class="list-unstyled mb-0">
                        @foreach ($alertas_kilometraje as $alerta)
                        <li><strong>{{ $alerta->vehiculo }} ({{ $alerta->placa }}):</strong> @if($alerta->kilometraje>5000) Excedió {{ $alerta->kilometraje - 5000 }} @else faltan {{5000- $alerta->kilometraje }} @endif  km</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    

    {{-- Segunda fila de gráficos --}}
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Gasto Mensual en Insumos</h5>
                </div>
                <div class="card-body">
                    <div id="gasto-mensual-chart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Vehículos con más Reportes de Falla</h5>
                </div>
                <div class="card-body">
                    <div id="vehiculos-fallas-chart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Timeline de reportes de falla --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Línea de Tiempo de Reportes de Falla</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        @foreach ($reportes_falla as $reporte)
                        <li class="list-group-item">
                            <span class="badge bg-secondary me-2">{{ $reporte->fecha }}</span>
                            {{ $reporte->descripcion }}
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Scripts para Highcharts --}}
<script src="https://code.highcharts.com/highcharts.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- Gráfico de Gasto Mensual ---
        const gastoMensual = @json($gasto_mensual);
        Highcharts.chart('gasto-mensual-chart', {
            chart: {
                type: 'column'
            },
            title: {
                text: ''
            },
            xAxis: {
                categories: gastoMensual.map(item => item.name)
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Gasto ($)'
                }
            },
            tooltip: {
                formatter: function () {
                    return '<b>' + this.x + '</b><br/>' +
                        'Gasto: $' + Highcharts.numberFormat(this.y, 2);
                }
            },
            plotOptions: {
                column: {
                    pointPadding: 0.2,
                    borderWidth: 0
                }
            },
            credits: {
                enabled: false
            },
            series: [{
                name: 'Gasto',
                data: gastoMensual.map(item => item.y)
            }]
        });

        // --- Gráfico de Vehículos con más Fallas ---
        const vehiculosFallas = @json($vehiculos_mas_fallas);
        Highcharts.chart('vehiculos-fallas-chart', {
            chart: {
                plotBackgroundColor: null,
                plotBorderWidth: null,
                plotShadow: false,
                type: 'pie'
            },
            title: {
                text: ''
            },
            tooltip: {
                pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
            },
            accessibility: {
                point: {
                    valueSuffix: '%'
                }
            },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: true,
                        format: '<b>{point.name}</b>: {point.percentage:.1f} %'
                    }
                }
            },
            credits: {
                enabled: false
            },
            series: [{
                name: 'Reportes',
                colorByPoint: true,
                data: vehiculosFallas
            }]
        });
    });
</script>
@endsection
