@extends('layouts.app')

@section('content')
<div class="container mx-auto py-6 px-4">
    {{-- KPI GRID ESTILO INDUSTRIAL --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-orange-50 p-6 rounded-xl border border-orange-100 shadow-sm transition-transform hover:-translate-y-1">
            <span class="text-[10px] font-black uppercase text-orange-impordiesel tracking-widest mb-2 block">Cupo de Diesel</span>
            <h3 class="text-3xl font-black text-gray-800">{{ number_format($cliente->litros_diesel, 0, ',', '.') }} <small class="text-xs text-gray-500 uppercase">Litros</small></h3>
        </div>
        <div class="bg-gray-50 p-6 rounded-xl border border-gray-200 shadow-sm transition-transform hover:-translate-y-1">
            <span class="text-[10px] font-black uppercase text-gray-500 tracking-widest mb-2 block">Cupo de MGO</span>
            <h3 class="text-3xl font-black text-gray-800">{{ number_format($cliente->litros_mgo, 0, ',', '.') }} <small class="text-xs text-gray-500 uppercase">Litros</small></h3>
        </div>
        <div class="bg-blue-50 p-6 rounded-xl border border-blue-100 shadow-sm transition-transform hover:-translate-y-1">
            <span class="text-[10px] font-black uppercase text-blue-600 tracking-widest mb-2 block">Total Consumido</span>
            <h3 class="text-3xl font-black text-gray-800">{{ number_format($kpis['consumido'] ?? 0, 0, ',', '.') }} <small class="text-xs text-gray-500 uppercase">L</small></h3>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- GRÁFICO DE CONSUMO --}}
        <div class="bg-white p-6 rounded-xl shadow-sm border-t-4 border-gray-industrial">
            <h5 class="text-xs font-black uppercase tracking-widest text-gray-500 mb-6 flex items-center">
                <i class="fas fa-chart-line mr-2 text-orange-impordiesel"></i> Historial de Consumo Mensual
            </h5>
            <div id="monthly-chart" class="w-full h-80"></div>
        </div>

        {{-- ÚLTIMAS COMPRAS --}}
        <div class="bg-white p-6 rounded-xl shadow-sm border-t-4 border-orange-impordiesel">
            <h5 class="text-xs font-black uppercase tracking-widest text-gray-500 mb-6 flex items-center">
                <i class="fas fa-shopping-cart mr-2 text-orange-impordiesel"></i> Registro de Operaciones
            </h5>
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-400 font-black uppercase tracking-widest border-b">
                            <th class="py-3 px-2">Fecha</th>
                            <th class="py-3 px-2">Tipo</th>
                            <th class="py-3 px-2 text-right">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 italic font-medium">
                        @foreach($ultimasCompras ?? [] as $compra)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-3 px-2 text-gray-500">{{ $compra->fecha }}</td>
                            <td class="py-3 px-2 font-bold text-gray-800 uppercase">{{ $compra->tipo }}</td>
                            <td class="py-3 px-2 text-right font-black text-orange-impordiesel">{{ number_format($compra->litros, 0) }} L</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.highcharts.com/highcharts.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        Highcharts.chart('monthly-chart', {
            chart: { type: 'spline', style: { fontFamily: 'inherit' } },
            title: { text: '' },
            xAxis: { type: 'datetime' },
            yAxis: { title: { text: 'Litros (L)' } },
            series: [{
                name: 'Consumo Mensual',
                data: {!! json_encode($consumoSeries ?? []) !!},
                color: '#FF6B00',
                lineWidth: 3
            }]
        });
    });
</script>
@endpush