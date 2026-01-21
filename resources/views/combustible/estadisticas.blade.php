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
</div>

<script src="https://code.highcharts.com/highcharts.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Grafico de Tendencia (Lineas)
    Highcharts.chart('grafico-tendencia', {
        title: { text: 'Tendencia de Consumo Diaria' },
        xAxis: { categories: @json($tendencia->pluck('fecha')) },
        yAxis: { title: { text: 'Litros' } },
        series: [{
            name: 'Litros Despachados',
            data: @json($tendencia->pluck('total')),
            color: '#007bff'
        }]
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