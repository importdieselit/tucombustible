@extends('layouts.app')

@section('title', 'Control de Flota | Dashboard')

@push('styles')
<style>
    /* Estándar Impordiesel */
    .card-kpi { border: none; border-bottom: 4px solid #4C474F; transition: all 0.3s ease; }
    .card-kpi:hover { transform: translateY(-5px); box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important; }
    .card-kpi.border-orange { border-bottom-color: #f2A435; }
    
    .bg-corporate { background-color: #4C474F !important; color: white; }
    .text-corporate { color: #f2A435 !important; }
    
    .timeline-item { border-left: 2px solid #f2A435; padding-left: 15px; position: relative; }
    .timeline-item::before { 
        content: ''; position: absolute; left: -7px; top: 5px; 
        width: 12px; height: 12px; border-radius: 50%; background: #4C474F; 
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    {{-- Header de Acción Rápida --}}
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center bg-white p-3 shadow-sm rounded">
            <div>
                <h4 class="fw-bold mb-0 text-uppercase">Gestión de Órdenes y Mantenimiento</h4>
                <small class="text-muted">Resumen operativo en tiempo real</small>
            </div>
            <div class="btn-group">
                <a href="{{ route('ordenes.compra') }}" class="btn btn-outline-dark">
                    <i class="fas fa-shopping-cart me-2"></i> Compras
                </a>
                <a href="{{ route('ordenes.reporte_gerencial') }}" class="btn btn-corporate">
                    <i class="fas fa-table me-2"></i> Reporte General
                </a>
                <a href="{{ route('ordenes.list') }}" class="btn btn-dark">
                    <i class="fas fa-table me-2"></i> Listado General
                </a>
                <a href="{{ route('ordenes.create') }}" class="btn btn-orange" style="background-color: #f2A435; color: white;">
                    <i class="fas fa-plus me-2"></i> Nueva OT
                </a>
            </div>
        </div>
    </div>

    {{-- KPIs Operativos --}}
    <div class="row g-3 mb-4">
        {{-- Órdenes Abiertas --}}
        <div class="col-md-4">
            <div class="card card-kpi shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted text-uppercase small fw-bold">Órdenes Activas</h6>
                            <h2 class="fw-bold mb-0">{{ $ordenes_abiertas }}</h2>
                        </div>
                        <div class="icon-shape bg-light text-success rounded p-3">
                            <i class="fas fa-wrench fa-2x"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <span class="text-success small fw-bold"><i class="fas fa-check-circle"></i> En proceso</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Mantenimientos (Lista limpia) --}}
        <div class="col-md-4">
            <div class="card card-kpi border-orange shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted text-uppercase small fw-bold mb-3">Próximos Servicios</h6>
                    <div class="overflow-auto" style="max-height: 120px;">
                        @foreach ($mantenimientos_proximos as $m)
                        <div class="d-flex align-items-center mb-2">
                            <div class="badge bg-light text-dark me-2">{{ $m->fecha_programada }}</div>
                            <small class="text-truncate"><strong>{{ $m->vehiculo }}:</strong> {{ $m->tipo_mantenimiento }}</small>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Alertas de KM --}}
        <div class="col-md-4">
            <div class="card card-kpi bg-corporate shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-white-50 text-uppercase small fw-bold mb-3">Alertas de Kilometraje</h6>
                    <div class="overflow-auto" style="max-height: 120px;">
                        @foreach ($alertas_kilometraje as $alerta)
                        <div class="mb-2 border-bottom border-secondary pb-1">
                            <small class="d-block">
                                <span class="text-corporate fw-bold">{{ $alerta->vehiculo }}</span>
                                @php $diff = $alerta->kilometraje - 5000; @endphp
                                <span class="{{ $diff > 0 ? 'text-danger' : 'text-white' }}">
                                    {{ $diff > 0 ? "Excedido por $diff km" : "Faltan ".abs($diff)." km" }}
                                </span>
                            </small>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Gráficos de Gestión --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-dark">Inversión Mensual en Insumos ($)</h6>
                </div>
                <div class="card-body">
                    <div id="gasto-mensual-chart" style="height: 320px;"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-dark">Frecuencia de Fallas por Unidad ultimos 60 días</h6>
                </div>
                <div class="card-body">
                    <div id="vehiculos-fallas-chart" style="height: 320px;"></div>
                </div>
            </div>
        </div>
        
    </div>

    {{-- Timeline de Reportes --}}
    <div class="row">
        <div class="col-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-corporate text-white">
                    <h6 class="m-0 fw-bold">Bitácora Reciente de Incidencias</h6>
                </div>
                <div class="card-body p-4">
                    @foreach ($reportes_falla as $reporte)
                    <div class="timeline-item mb-3 pb-2">
                        <small class="text-muted fw-bold">{{ $reporte->fecha }}</small>
                        <p class="mb-0">{{ $reporte->descripcion }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    <div class="col-lg-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-dark">Fallas por Tipo</h6>
                </div>
                <div class="card-body">
                    <div id="tipos-fallas-chart" style="height: 320px;"></div>
                </div>
            </div>
        </div>
    </div>
    
</div>

{{-- Scripts con validación de carga --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof Highcharts === 'undefined') {
            console.error("Highcharts no detectado. Cargue la librería localmente.");
            return;
        }

        // Configuración Global de Colores
        const corpoColors = ['#f2A435', '#4C474F', '#f16201', '#FCD124', '#7d787e'];

        // Gráfico de Gastos (Barras Corporativas)
        Highcharts.chart('gasto-mensual-chart', {
            chart: { type: 'column', backgroundColor: 'transparent' },
            title: { text: null },
            xAxis: { 
                categories: @json(collect($gasto_mensual)->pluck('name')),
                labels: { style: { color: '#4C474F', fontWeight: 'bold' } }
            },
            yAxis: { title: { text: 'Dólares ($)' }, gridLineDashStyle: 'Dot' },
            tooltip: { shared: true, valuePrefix: '$ ' },
            series: [{
                name: 'Gasto Mensual',
                data: @json(collect($gasto_mensual)->pluck('y')),
                color: '#f2A435',
                borderRadius: 4
            }],
            credits: { enabled: false }
        });

        // Gráfico de Fallas (Doughnut Moderno)
        Highcharts.chart('vehiculos-fallas-chart', {
            chart: { type: 'pie', backgroundColor: 'transparent' },
            title: { text: null },
            plotOptions: {
                pie: { 
                    innerSize: '70%', 
                    dataLabels: { enabled: true, format: '{point.name}: {point.y} Ordenes' } 
                }
            },
            series: [{
                name: 'Fallas',
                data: @json($vehiculos_mas_fallas),
                colors: corpoColors
            }],
            credits: { enabled: false }
        });


        Highcharts.chart('tipos-fallas-chart', {
            chart: { type: 'pie', backgroundColor: 'transparent' },
            title: { text: null },
            plotOptions: {
                pie: { 
                    innerSize: '70%', 
                    dataLabels: { enabled: true, format: '{point.name}: {point.y}' } 
                }
            },
            series: [{
                name: 'Fallas',
                data: @json($ordenes_por_tipo->map(function($item) {
                    return ['name' => $item->tipo, 'y' => $item->total];
                })),
                colors: corpoColors
            }],
            credits: { enabled: false }
        });
    });
</script>
@endsection