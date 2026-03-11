@extends('layouts.app')
@section('title', 'TuCombustible - '.Auth::user()->name)

@section('content')
<div class="container mx-auto py-6 px-4" id="dashboard-main-view">
    {{-- Header del Dashboard --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 bg-white p-6 rounded-xl shadow-sm border-t-4 border-orange-impordiesel">
        <div class="flex items-center mb-4 md:mb-0">
            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center text-orange-impordiesel mr-4 shadow-inner">
                <i class="fas fa-user-tie fa-lg"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-800 uppercase tracking-tight">{{ Auth::user()->name }}</h1>
                <p class="text-gray-500 text-xs font-bold uppercase tracking-widest">Portal de Autogestión — ImporDiesel</p>
            </div>
        </div>
        <div class="flex space-x-2">
            <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-tighter border border-green-200">
                <i class="fas fa-check-circle mr-1"></i> Cliente Operativo
            </span>
        </div>
    </div>

    {{-- KPIs - Grid de indicadores (Cambiado de $kpis a $stats según el Service) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        
        <div class="bg-white p-6 rounded-xl shadow-sm hover:shadow-md transition-shadow border-b-4 border-blue-500">
            <div class="flex items-center justify-between mb-4 text-blue-500">
                <span class="text-xs font-bold uppercase tracking-widest">Cupo Asignado</span>
                <i class="fas fa-gas-pump fa-lg"></i>
            </div>
            {{-- Ajustado a los índices que devuelve tu DashboardService --}}
            <div class="text-2xl font-black text-gray-800">{{ number_format($stats['cupo_total'] ?? 0, 0, ',', '.') }} L</div>
            <div class="text-xs text-gray-400 mt-2">Capacidad total mensual</div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm hover:shadow-md transition-shadow border-b-4 border-green-500">
            <div class="flex items-center justify-between mb-4 text-green-500">
                <span class="text-xs font-bold uppercase tracking-widest">Disponible</span>
                <i class="fas fa-check-double fa-lg"></i>
            </div>
            <div class="text-2xl font-black text-gray-800">{{ number_format($stats['disponible'] ?? 0, 0, ',', '.') }} L</div>
            <div class="text-xs text-green-600 mt-2 font-bold">{{ number_format($stats['porcentaje_disponible'] ?? 0, 1) }}% restante</div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm hover:shadow-md transition-shadow border-b-4 border-orange-impordiesel">
            <div class="flex items-center justify-between mb-4 text-orange-impordiesel">
                <span class="text-xs font-bold uppercase tracking-widest">Consumido</span>
                <i class="fas fa-chart-line fa-lg"></i>
            </div>
            <div class="text-2xl font-black text-gray-800">{{ number_format($stats['consumo_mes'] ?? 0, 0, ',', '.') }} L</div>
            <div class="text-xs text-gray-400 mt-2">Acumulado del mes actual</div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm hover:shadow-md transition-shadow border-b-4 border-red-500">
            <div class="flex items-center justify-between mb-4 text-red-500">
                <span class="text-xs font-bold uppercase tracking-widest">Pendientes</span>
                <i class="fas fa-file-invoice-dollar fa-lg"></i>
            </div>
            <div class="text-2xl font-black text-gray-800">{{ $stats['pedidos_activos'] ?? 0 }}</div>
            <div class="text-xs text-red-600 mt-2 font-bold">Por favor, regularice sus pagos</div>
        </div>
    </div>

    {{-- Sección de Gráfico y Detalles --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm border-t-4 border-gray-industrial">
            <h5 class="text-sm font-bold text-gray-700 uppercase mb-6 tracking-widest flex items-center">
                <i class="fas fa-chart-bar mr-2 text-orange-impordiesel"></i> Comparativa de Consumo por Sucursal
            </h5>
            <div id="chart-container" class="w-full h-80"></div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border-t-4 border-gray-industrial">
            <h5 class="text-sm font-bold text-gray-700 uppercase mb-6 tracking-widest flex items-center">
                <i class="fas fa-history mr-2 text-orange-impordiesel"></i> Últimas Operaciones
            </h5>
            <div class="space-y-4">
                @foreach($recentActivity ?? [] as $activity)
                <div class="flex items-center p-3 hover:bg-gray-50 rounded-lg border-l-4 border-orange-impordiesel transition-colors">
                    <div class="flex-grow">
                        <div class="text-sm font-bold text-gray-800">{{ $activity->descripcion }}</div>
                        <div class="text-xs text-gray-400">{{ $activity->fecha }}</div>
                    </div>
                    <div class="text-sm font-black text-gray-700">{{ number_format($activity->monto, 0) }} L</div>
                </div>
                @endforeach
            </div>
            <button class="w-full mt-6 py-2 text-xs font-bold text-gray-500 uppercase tracking-widest border border-gray-200 rounded hover:bg-gray-50 transition-colors">
                Ver todo el historial
            </button>
        </div>
    </div>
</div>

{{-- VISTA SUCURSALES (Hidden por defecto) --}}
<div id="sucursales-list-container" class="container mx-auto py-6 px-4 hidden">
    <div class="mb-4">
        <button onclick="location.reload()" class="text-orange-impordiesel font-bold uppercase text-xs">
            <i class="fas fa-arrow-left mr-1"></i> Volver al Dashboard
        </button>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6 border-t-4 border-orange-impordiesel">
        <h2 class="text-xl font-bold uppercase text-gray-800 mb-6">Listado de Sucursales y Activos</h2>
        {{-- Aquí iría la lógica de sucursales --}}
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.highcharts.com/highcharts.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const chartData = {!! json_encode($chartData ?? []) !!};
        if (chartData.length > 0) {
            Highcharts.chart('chart-container', {
                chart: { type: 'column', style: { fontFamily: 'inherit' } },
                title: { text: '' },
                xAxis: { categories: chartData.map(d => d.name), labels: { style: { fontWeight: 'bold', fontSize: '10px' } } },
                yAxis: { title: { text: 'Litros (L)' }, stacking: 'normal' },
                plotOptions: { column: { stacking: 'normal', borderRadius: 4, borderWidth: 0 } },
                series: [{
                    name: 'Consumido',
                    data: chartData.map(d => d.consumido),
                    color: '#FF6B00'
                }, {
                    name: 'Disponible',
                    data: chartData.map(d => d.disponible),
                    color: '#4C474F'
                }]
            });
        }
    });
</script>
@endpush