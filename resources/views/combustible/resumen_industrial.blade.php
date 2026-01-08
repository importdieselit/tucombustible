@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col-md-6">
            <h3>📊 Resumen de Consumo Industrial</h3>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group shadow-sm">
                <a href="{{ route('combustible.resumenDesp', ['periodo' => 'diario']) }}" 
                   class="btn btn-{{ $periodo == 'diario' ? 'primary' : 'outline-primary' }}">Hoy</a>
                <a href="{{ route('combustible.resumenDesp', ['periodo' => 'semanal']) }}" 
                   class="btn btn-{{ $periodo == 'semanal' ? 'primary' : 'outline-primary' }}">Semana</a>
                <a href="{{ route('combustible.resumenDesp', ['periodo' => 'mensual']) }}" 
                   class="btn btn-{{ $periodo == 'mensual' ? 'primary' : 'outline-primary' }}">Mes</a>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Cliente</th>
                            <th class="text-center">Cant. Despachos</th>
                            <th class="text-end">Total Litros</th>
                            <th class="text-end">Promedio x Carga</th>
                            <th>Última Actividad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($resumen as $item)
                        <tr>
                            <td><strong>{{ $item->cliente->nombre ?? 'Desconocido' }}</strong></td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-info text-dark">
                                    {{ $item->total_despachos }}
                                </span>
                            </td>
                            <td class="text-end font-weight-bold">
                                {{ number_format($item->total_litros, 2) }} L
                            </td>
                            <td class="text-end text-muted">
                                {{ number_format($item->total_litros / $item->total_despachos, 2) }} L
                            </td>
                            <td>
                                <small>{{ \Carbon\Carbon::parse($item->ultimo_despacho)->diffForHumans() }}</small>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No hay consumos registrados en este periodo.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($resumen->count() > 0)
                    <tfoot class="table-secondary">
                        <tr>
                            <th>TOTAL GENERAL</th>
                            <th class="text-center">{{ $resumen->sum('total_despachos') }}</th>
                            <th class="text-end">{{ number_format($resumen->sum('total_litros'), 2) }} L</th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div id="container-chart" style="width:100%; height:400px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    
    <script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dataCategorias = @json($chartData['categorias']);
    const dataSeries = @json($chartData['series']);

    Highcharts.chart('container-chart', {
        chart: {
            type: 'column', // Barras verticales
            backgroundColor: 'transparent'
        },
        title: {
            text: 'Consumo por Cliente (' + '{{ ucfirst($periodo) }}' + ')'
        },
        xAxis: {
            categories: dataCategorias,
            crosshair: true
        },
        yAxis: {
            min: 0,
            title: {
                text: 'Litros (L)'
            }
        },
        tooltip: {
            headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
            pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                '<td style="padding:0"><b>{point.y:.2f} L</b></td></tr>',
            footerFormat: '</table>',
            shared: true,
            useHTML: true
        },
        plotOptions: {
            column: {
                pointPadding: 0.2,
                borderWidth: 0,
                colorByPoint: true // Colores distintos para cada cliente
            }
        },
        series: [{
            name: 'Diesel Industrial',
            data: dataSeries
        }],
        credits: { enabled: false }
    });
});
</script>
@endpush