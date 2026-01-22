@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h3>📊 Indicadores de Despacho Industrial (Tanque 00)</h3>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group">
                <a href="?periodo=mensual" class="btn btn-{{ $periodo == 'mensual' ? 'primary' : 'outline-primary' }}">Este Mes</a>
                <a href="?periodo=anual" class="btn btn-{{ $periodo == 'anual' ? 'primary' : 'outline-primary' }}">Este Año</a>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-primary text-white">
                <div class="card-body">
                    <h6>TOTAL SURTIDO</h6>
                    <h2 class="mb-0">{{ number_format($stats->total_litros, 2) }} L</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-success text-white">
                <div class="card-body">
                    <h6>PROMEDIO POR CLIENTE</h6>
                    <h2 class="mb-0">{{ number_format($stats->promedio_ticket, 2) }} L</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-info text-white">
                <div class="card-body">
                    <h6>TOTAL DESPACHOS</h6>
                    <h2 class="mb-0">{{ $stats->total_despachos }} ops</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-warning text-dark">
                <div class="card-body">
                    <h6>CLIENTE MÁS ACTIVO</h6>
                    <h5 class="mb-0">{{ $porCliente->sortByDesc('total')->first()->cliente->nombre ?? 'N/A' }}</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div id="grafico-tendencia"></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div id="grafico-clientes"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-4">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="ti-layout-list-post text-primary"></i> Ranking de Clientes (Consumo vs Saldo)</h5>
                <button class="btn btn-sm btn-outline-secondary" onclick="exportTableToCSV('resumen.csv')">Exportar</button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="tabla-resumen">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Cliente</th>
                                <th class="text-end">Saldo Actual (Lts)</th>
                                <th class="text-end">Total Consumido</th>
                                <th class="text-end">Promedio x Despacho</th>
                                <th class="text-center">Nro. Despachos</th>
                                <th class="text-center">Estatus Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($resumenClientes as $index => $c)
                            <tr>
                                <td><strong>{{ $index + 1 }}</strong></td>
                                <td>{{ $c->nombre }}</td>
                                <td class="text-end fw-bold">{{ number_format($c->saldo_litros, 2) }} L</td>
                                <td class="text-end text-primary fw-bold">{{ number_format($c->total_consumido ?? 0, 2) }} L</td>
                                <td class="text-end">{{ number_format($c->promedio_consumo ?? 0, 2) }} L</td>
                                <td class="text-center">{{ $c->total_despachos }}</td>
                                <td class="text-center">
                                    @if($c->saldo_litros <= 100)
                                        <span class="badge bg-danger">Saldo Crítico</span>
                                    @elseif($c->saldo_litros <= 500)
                                        <span class="badge bg-warning text-dark">Saldo Bajo</span>
                                    @else
                                        <span class="badge bg-success">Óptimo</span>
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
</div>
</div>

<script src="https://code.highcharts.com/highcharts.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Grafico de Tendencia (Lineas)
        Highcharts.chart('grafico-tendencia', {
            title: { text: 'Tendencia de Consumo Diaria' },
            chart: { type: 'line' },
            xAxis: { categories: @json($tendencia->pluck('fecha')) },
            yAxis: { 
                title: { text: 'Litros' },
                labels: { format: '{value} L' } 
            },
            plotOptions: {
                line: {
                    dataLabels: {
                        enabled: true, // <--- ESTO ACTIVA LAS ETIQUETAS
                        format: '{y} L', // Muestra el valor seguido de "L"
                        style: {
                            fontWeight: 'bold',
                            color: '#333'
                        }
                    },
                    enableMouseTracking: true
                }},
            series: [{
                name: 'Litros Despachados',
                data: @json($tendencia->pluck('total')).map(Number),
                color: '#007bff'
            }],
            credits:false
        });

    // Grafico de Distribucion (Pastel)
    Highcharts.chart('grafico-clientes', {
        chart: { type: 'pie' },
        title: { text: '% Consumo por Cliente' },
        series: [{
            name: 'Litros',
            colorByPoint: true,
            data: @json($porCliente->map(fn($c) => ['name' => $c->cliente->nombre, 'y' => (float)$c->total]))
        }]
    });
});
</script>
@endsection