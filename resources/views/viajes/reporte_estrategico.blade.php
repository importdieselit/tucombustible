@extends('layouts.app')

@section('content')
<style>
    @media print {
        .no-print, nav, .sidebar, form, .btn, footer, .navbar {
            display: none !important;
        }
        body { background-color: #fff !important; font-size: 11pt; }
        .container-fluid { width: 100% !important; padding: 0 !important; margin: 0 !important; }
        .card { border: 1px solid #ddd !important; box-shadow: none !important; margin-bottom: 15px !important; page-break-inside: avoid; }
        .table-responsive { overflow: visible !important; }
        .print-header { display: block !important; margin-bottom: 15px; text-align: center; }
    }
    .print-header { display: none; }
</style>

<div class="container-fluid">
    
    <!-- Encabezado exclusivo de Impresión/PDF -->
    <div class="print-header">
        <h2>Reporte Estratégico de Operaciones de Carga</h2>
        <p>Rango: {{ \Carbon\Carbon::parse($fechaInicio)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($fechaFin)->format('d/m/Y') }}</p>
        <hr>
    </div>

    <!-- Barra Superior -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h2 class="h3 text-gray-800">Dashboard Estratégico de Operaciones</h2>
        <button onclick="window.print()" class="btn btn-primary shadow-sm">
            <i class="fas fa-print fa-sm text-white-50"></i> Imprimir / Exportar PDF
        </button>
    </div>

    <!-- Filtros Dinámicos -->
    <div class="card shadow mb-4 no-print">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter"></i> Filtros y Criterios</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('viajes.reporte_estrategico') }}" class="row g-3">
                <div class="col-md-2">
                    <label>Desde</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="{{ $fechaInicio }}">
                </div>
                <div class="col-md-2">
                    <label>Hasta</label>
                    <input type="date" name="fecha_fin" class="form-control" value="{{ $fechaFin }}">
                </div>
                <div class="col-md-2">
                    <label>Chofer</label>
                    <select name="chofer_id" class="form-control">
                        <option value="">Todos</option>
                        @foreach($choferes as $chofer)
                            <option value="{{ $chofer->id }}" {{ $choferId == $chofer->id ? 'selected' : '' }}>
                                {{ $chofer->persona->nombre }} {{ $chofer->persona->apellido }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Destino</label>
                    <select name="destino_ciudad" class="form-control">
                        <option value="">Todos</option>
                        @foreach($destinos as $dest)
                            <option value="{{ $dest }}" {{ $destino == $dest ? 'selected' : '' }}>{{ $dest }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Agrupar Tabla</label>
                    <select name="agrupar_por" class="form-control border-primary fw-bold">
                        <option value="ninguno" {{ $agruparPor == 'ninguno' ? 'selected' : '' }}>Sin Agrupar (Detalle)</option>
                        <option value="chofer" {{ $agruparPor == 'chofer' ? 'selected' : '' }}>Por Chofer</option>
                        <option value="destino" {{ $agruparPor == 'destino' ? 'selected' : '' }}>Por Destino</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100"><i class="fas fa-search"></i> Aplicar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row mb-4">
        <div class="col-md-6 mb-2">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Viajes Procesados</div>
                    <div class="h4 mb-0 font-weight-bold text-gray-800">{{ number_format($totalViajes) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-2">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Volumen Total Cargado/Movilizado</div>
                    <div class="h4 mb-0 font-weight-bold text-gray-800">{{ number_format($totalLitros, 2) }} Lts</div>
                </div>
            </div>
        </div>
    </div>

    <!-- GRILLA DE LOS 4 GRÁFICOS HIGHCHARTS -->
    <div class="row">
        <!-- 1. Choferes -->
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Viajes por Chofer</h6></div>
                <div class="card-body"><div id="choferesChart" style="width:100%; height:280px;"></div></div>
            </div>
        </div>
        <!-- 2. Ayudantes -->
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-info">Viajes por Ayudante</h6></div>
                <div class="card-body"><div id="ayudantesChart" style="width:100%; height:280px;"></div></div>
            </div>
        </div>
        <!-- 3. Top Destinos -->
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-success">Top Destinos</h6></div>
                <div class="card-body"><div id="destinosChart" style="width:100%; height:280px;"></div></div>
            </div>
        </div>
        <!-- 4. Estatus -->
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-warning">Distribución por Estatus</h6></div>
                <div class="card-body"><div id="statusChart" style="width:100%; height:280px;"></div></div>
            </div>
        </div>
    </div>

    <!-- TABLA DINÁMICA -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                @if($agruparPor === 'chofer')
                    Resumen Agrupado por Chofer
                @elseif($agruparPor === 'destino')
                    Resumen Agrupado por Destino
                @else
                    Detalle General de Viajes
                @endif

                @if($destino)
                    - Destino: <span class="fw-bold">{{ $destino }}</span>
                @endif
                
                @if($choferId)
                    - Chofer: <span class="fw-bold">{{ $choferes->firstWhere('id', $choferId)->persona->nombre ?? 'N/A' }}</span>
                @endif
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                @if($agruparPor !== 'ninguno')
                    <table class="table table-bordered table-striped" width="100%">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>{{ ucfirst($agruparPor) }}</th>
                                <th class="text-center">Total Viajes</th>
                                <th class="text-end">Volumen Total (Lts)</th>
                                <th class="text-center">% Part. Viajes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tablaAgrupada as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="fw-bold">{{ $item['criterio'] }}</td>
                                    <td class="text-center">{{ $item['total_viajes'] }}</td>
                                    <td class="text-end">{{ number_format($item['total_litros'], 2) }}</td>
                                    <td class="text-center">
                                        {{ $totalViajes > 0 ? number_format(($item['total_viajes'] / $totalViajes) * 100, 1) : 0 }}%
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center">Sin información para agrupar.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @else
                    <table class="table table-bordered table-striped" width="100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Destino</th>
                                <th>Chofer</th>
                                <th>Ayudante</th>
                                <th>Volumen (Lts)</th>
                                <th>Estatus</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($viajes as $viaje)
                                <tr>
                                    <td>#{{ $viaje->id }}</td>
                                    <td>{{ \Carbon\Carbon::parse($viaje->fecha_salida)->format('d/m/Y H:i') }}</td>
                                    <td>{{ $viaje->destino_ciudad }}</td>
                                    <td>{{ $viaje->chofer->persona->nombre ?? 'N/A' }}</td>
                                    <td>{{ $viaje->ayudante_chofer->persona->nombre ?? 'N/A' }}</td>
                                    <td>{{ number_format($viaje->despachos->sum('litros') + ($viaje->litros ?? 0), 2) }}</td>
                                    <td><span class="badge bg-secondary">{{ $viaje->status }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center">No se encontraron registros.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>


@push('scripts')
<script src="https://code.highcharts.com/highcharts.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Datos PHP a JSON
        const dataChoferes = @json($viajesPorChofer);
        const dataAyudantes = @json($viajesPorAyudante);
        const dataDestinos = @json($viajesPorDestino);
        const dataStatus = @json($viajesPorStatus);

        // Helper para mapear objetos clave/valor al formato de pie de Highcharts [{name: 'X', y: Y}]
        const formatPieData = (obj) => Object.keys(obj).map(key => ({ name: key, y: obj[key] }));

        const commonColumnOptions = {
            chart: { type: 'column' },
            title: { text: null },
            yAxis: { min: 0, title: { text: 'Cantidad' } },
            credits: { enabled: false }
        };

        const commonPieOptions = {
            chart: { type: 'pie' },
            title: { text: null },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: { enabled: true, format: '<b>{point.name}</b>: {point.y}' }
                }
            },
            credits: { enabled: false }
        };

        // 1. Gráfico Choferes (Barras Verticales)
        Highcharts.chart('choferesChart', Highcharts.merge(commonColumnOptions, {
            xAxis: { categories: Object.keys(dataChoferes) },
            series: [{ name: 'Viajes', data: Object.values(dataChoferes), color: '#4e73df' }]
        }));

        // 2. Gráfico Ayudantes (Barras Verticales)
        Highcharts.chart('ayudantesChart', Highcharts.merge(commonColumnOptions, {
            xAxis: { categories: Object.keys(dataAyudantes) },
            series: [{ name: 'Viajes', data: Object.values(dataAyudantes), color: '#36b9cc' }]
        }));

        // 3. Gráfico Top Destinos (Donut/Pie)
        Highcharts.chart('destinosChart', Highcharts.merge(commonPieOptions, {
            chart: { type: 'pie' },
            plotOptions: { pie: { innerSize: '50%' } }, // Formato Donut
            series: [{ name: 'Viajes', data: formatPieData(dataDestinos) }]
        }));

        // 4. Gráfico Estatus (Pie)
        Highcharts.chart('statusChart', Highcharts.merge(commonPieOptions, {
            series: [{ name: 'Viajes', data: formatPieData(dataStatus) }]
        }));
    });
</script>
@endpush
@endsection