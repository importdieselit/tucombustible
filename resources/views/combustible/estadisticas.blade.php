@extends('layouts.app')
@push('styles')
    <!-- CSS de DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.7/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.dataTables.css" />
@endpush
@section('content')
<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-md-8">
           <h3>
                @if($clienteSeleccionado)
                    📊 Reporte de Consumo: {{ $clienteSeleccionado->nombre }}
                @else
                    📊 Indicadores de Despacho Industrial (Tanque 00)
                @endif
            </h3>
        </div>
 <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded shadow-sm">
    <div class="btn-group shadow-sm">
        <a href="?view=hoy" class="btn btn-outline-primary {{ $view == 'hoy' ? 'active' : '' }}">Día</a>
        <a href="?view=semana" class="btn btn-outline-primary {{ $view == 'semana' ? 'active' : '' }}">Semana</a>
        <a href="?view=mes" class="btn btn-outline-primary {{ $view == 'mes' ? 'active' : '' }}">Mes</a>
    </div>
<form action="{{ route('combustible.estadisticas') }}" method="GET" class="d-flex align-items-center">
    <input type="hidden" name="view" value="{{ $view }}">
    
    <div class="me-3" style="min-width: 250px;">
        <select name="cliente_id" class="form-select fw-bold border-primary" onchange="this.form.submit()">
            <option value="">-- Todos los Clientes --</option>
            @foreach($clientes as $c)
                <option value="{{ $c->id }}" {{ request('cliente_id') == $c->id ? 'selected' : '' }}>
                    {{ $c->nombre }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="input-group">
        <span class="input-group-text bg-white border-end-0"><i class="fa fa-calendar-check-o text-primary"></i></span>
        
        @if($view == 'hoy')
            <input type="date" name="date" class="form-control border-start-0 fw-bold" value="{{ $date }}" onchange="this.form.submit()">
        @elseif($view == 'semana')
            <input type="week" name="date" class="form-control border-start-0 fw-bold" value="{{ \Carbon\Carbon::parse($date)->format('Y-\WW') }}" onchange="this.form.submit()">
        @else
            <input type="month" name="date" class="form-control border-start-0 fw-bold" value="{{ \Carbon\Carbon::parse($date)->format('Y-m') }}" onchange="this.form.submit()">
        @endif
    </div>

    <div class="ms-3 d-none d-md-block">
        <span class="badge bg-light text-primary border p-2 text-uppercase">
            <i class="fa fa-info-circle"></i> {{ $label }}
        </span>
    </div>

    @if(request('cliente_id') || request('date') != now()->format('Y-m-d'))
        <a href="{{ route('combustible.estadisticas', ['view' => $view]) }}" class="ms-2 btn btn-sm btn-outline-danger" title="Resetear Filtros">
            <i class="fa fa-times"></i>
        </a>
    @endif
</form>
</div>
    </div>

    <div class="row mb-4">
        @if($clienteSeleccionado)
            <div class="col-md-3">
                <div class="card shadow-sm border-0 bg-dark text-white">
                    <div class="card-body">
                        <h6>DISPONIBLE ACTUAL</h6>
                        <h2 class="mb-0">{{ number_format($clienteSeleccionado->prepagado, 2) }} L</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 {{ $diasAutonomia < 3 ? 'bg-danger' : 'bg-info' }} text-white">
                    <div class="card-body">
                        <h6>AUTONOMÍA ESTIMADA</h6>
                        <h2 class="mb-0">{{ round($diasAutonomia) }} Días</h2>
                    </div>
                </div>
            </div>
        @endif
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
                    <h6>@if($clienteSeleccionado) CONSUMO PROMEDIO @else PROMEDIO POR CLIENTE @endif</h6>
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
        @if(!$clienteSeleccionado)
            <div class="col-md-3">
                <div class="card shadow-sm border-0 bg-warning text-dark">
                    <div class="card-body">
                        <h6>CLIENTE MÁS ACTIVO</h6>
                        <h5 class="mb-0">{{ $porCliente->sortByDesc('total')->first()->cliente->nombre ?? 'N/A' }}</h5>
                    </div>
                </div>
            </div>
        @endif
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
                <div class="table-responsive ">
                    <table class="table table-hover datatable align-middle" id="tabla-resumen">
                        <thead class="table-light">
                            <tr>
                                @if($clienteSeleccionado)
                                    <th>Fecha</th>
                                    <th>Vehículo/Placa</th>
                                    <th>Ticket</th>
                                    <th class="text-end">Litros</th>
                                    <th>Operador</th>
                                @else
                                    <th>#</th>
                                    <th>Cliente</th>
                                    <th class="text-end">Saldo Actual (Lts)</th>
                                    <th class="text-end">Total Consumido</th>
                                    <th class="text-end">Promedio x Despacho</th>
                                    <th class="text-center">Nro. Despachos</th>
                                    <th class="text-center">Estatus Saldo</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @if($clienteSeleccionado)
                                @foreach($tendenciaDetallada as $mov) {{-- Necesitarás traer estos movs en el controlador --}}
                                <tr>
                                    <td>{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ $mov->vehiculo->placa }}</td>
                                    <td>{{ $mov->nro_ticket }}</td>
                                    <td class="text-end fw-bold">{{ number_format($mov->cantidad_litros, 2) }} L</td>
                                    <td>--</td>
                                </tr>
                                @endforeach
                            @else
                                @foreach($resumenClientes as $index => $c)
                                <tr>
                                    <td><strong>{{ $index + 1 }}</strong></td>
                                    <td>{{ $c->nombre }}</td>
                                    <td class="text-end fw-bold">{{ number_format($c->prepagado, 2) }} L</td>
                                    <td class="text-end text-primary fw-bold">{{ number_format($c->total_consumido ?? 0, 2) }} L</td>
                                    <td class="text-end">{{ number_format($c->promedio_consumo ?? 0, 2) }} L</td>
                                    <td class="text-center">{{ $c->total_despachos }}</td>
                                    <td class="text-center">
                                        @if($c->prepagado <= 50)
                                            <span class="badge bg-danger">Saldo Crítico</span>
                                        @elseif($c->prepagado <= 100)
                                            <span class="badge bg-warning text-dark">Saldo Bajo</span>
                                        @else
                                            <span class="badge bg-success">Óptimo</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection


@push('scripts')

    <!-- Script de jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://code.highcharts.com/highcharts.js"></script>
    <!-- Script de DataTables -->
    <script src="https://cdn.datatables.net/2.0.7/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.0.2/js/dataTables.buttons.js"></script>
    <script src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
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
        title: { text: '{{ $clienteSeleccionado ? "% Por Unidad" : "% Por Cliente" }}' },
        series: [{
            name: 'Litros',
            data: @json($clienteSeleccionado 
                ? $distribucionUnidades->map(fn($u) => ['name' => $u->vehiculo->placa ?? 'S/P', 'y' => (float)$u->total])
                : $porCliente->map(fn($c) => ['name' => $c->cliente->nombre, 'y' => (float)$c->total]))
        }]
    });
});

 $(document).ready(function() {
     $('.datatable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
                },
                layout: {
                    topStart: {
                        buttons: ['csv', 'excel', 'pdf', 'print']
                    }
                },
            });
});
</script>
@endpush