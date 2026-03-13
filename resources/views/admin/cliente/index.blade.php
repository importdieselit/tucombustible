@extends('layouts.app')

@section('content')
<div class="container mx-auto py-6 px-4">
    {{-- CARDS DE RESUMEN --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-8 border-blue-600 border border-gray-200">
            <h6 class="text-xs font-black uppercase tracking-widest text-gray-500 mb-2">En Registro</h6>
            <h2 class="text-3xl font-black text-gray-800">{{ $stats['total_en_registro'] }}</h2>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border-l-8 border-orange-impordiesel border border-gray-200">
            <h6 class="text-xs font-black uppercase tracking-widest text-gray-500 mb-2">Pendientes Revisión</h6>
            <h2 class="text-3xl font-black text-gray-800">{{ $stats['en_espera_revision'] }}</h2>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border-l-8 border-green-600 border border-gray-200">
            <h6 class="text-xs font-black uppercase tracking-widest text-gray-500 mb-2">Activos</h6>
            <h2 class="text-3xl font-black text-gray-800">{{ $stats['activos'] }}</h2>
        </div>
    </div>

    {{-- BLOQUE DE NOTIFICACIONES DE PEDIDOS --}}
    <div class="flex justify-end mb-6">
        <button onclick="openPedidosModal()" class="relative bg-gray-industrial text-white px-8 py-3 rounded-lg font-black text-xs uppercase hover:bg-black transition shadow-lg border-b-4 border-gray-900">
            <i class="fas fa-bell mr-2"></i> Pedidos Pendientes
            @if(isset($pedidosPendientes) && count($pedidosPendientes) > 0)
                <span class="absolute -top-2 -right-2 bg-red-600 text-white text-[10px] w-6 h-6 flex items-center justify-center rounded-full border-2 border-white animate-bounce">
                    {{ count($pedidosPendientes) }}
                </span>
            @endif
        </button>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-300 overflow-hidden">
        <div class="p-6 border-b border-gray-200 bg-gray-50">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center">
                    <h5 class="text-lg font-black uppercase tracking-tight text-gray-800 italic">
                        <span class="text-orange-impordiesel">|</span> Listado de Clientes
                    </h5>
                </div>
                
                <form action="{{ route('clientes.index') }}" method="GET" class="flex items-center gap-0 w-full md:w-auto">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           class="w-full md:w-64 px-4 py-2 text-sm border-2 border-gray-300 rounded-l focus:border-orange-impordiesel outline-none uppercase font-bold text-gray-700" 
                           placeholder="RIF O NOMBRE...">
                    <button type="submit" class="bg-gray-industrial text-white px-6 py-2.5 text-sm font-black uppercase hover:bg-black transition border-y-2 border-r-2 border-gray-industrial">
                        <i class="fas fa-search"></i>
                    </button>
                    @if(request('search'))
                        <a href="{{ route('clientes.index') }}" class="bg-red-700 text-white px-4 py-2.5 text-sm font-black uppercase hover:bg-red-900 transition rounded-r border-y-2 border-r-2 border-red-700">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </form>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-industrial text-white text-xs font-black uppercase tracking-widest">
                        <th class="px-6 py-4">Cliente / Identificación</th>
                        <th class="px-6 py-4 text-center">Estatus actual</th>
                        <th class="px-6 py-4 text-center">Última Modificación</th>
                        <th class="px-6 py-4 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($clientes as $c)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-black text-gray-800 uppercase text-sm leading-tight">{{ $c->nombre }}</div>
                            <div class="text-xs font-bold text-gray-500 mt-1">RIF: {{ $c->rif }}</div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @php
                                $statusColor = $c->registro_paso == 10 ? 'bg-green-600' : ($c->registro_paso == 3 ? 'bg-orange-impordiesel' : 'bg-gray-500');
                            @endphp
                            <span class="{{ $statusColor }} text-white px-3 py-1 rounded text-[10px] font-black uppercase tracking-tighter shadow-sm">
                                {{ $c->registro_paso == 10 ? 'ACTIVO' : 'PASO '.$c->registro_paso }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="text-xs font-bold text-gray-700 uppercase">{{ $c->updated_at->format('d/m/Y') }}</div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('clientes.show', $c->id) }}" class="inline-block bg-orange-impordiesel text-white px-6 py-2.5 rounded text-xs font-black uppercase hover:bg-orange-700 transition shadow-md border-b-2 border-orange-900">
                                <i class="fas fa-folder-open mr-2"></i>
                                {{ $c->registro_paso == 10 ? 'VER EXPEDIENTE' : 'GESTIONAR' }}
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-400 font-black uppercase text-xs tracking-widest">
                            No se encontraron registros
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 bg-gray-50 border-t">
            {{ $clientes->appends(request()->query())->links() }}
        </div>
    </div>
</div>

{{-- MODAL DE GESTIÓN DE PEDIDOS (ADMIN) --}}
<div id="pedidosModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl overflow-hidden mx-4">
        <div class="bg-gray-industrial p-4 flex justify-between items-center border-b-4 border-orange-impordiesel">
            <h3 class="text-white font-black uppercase text-sm tracking-widest italic">
                <span class="text-orange-impordiesel">|</span> Notificaciones de Pedidos Pendientes
            </h3>
            <button onclick="closePedidosModal()" class="text-gray-400 hover:text-white text-2xl font-bold">&times;</button>
        </div>
        
        <div class="p-6 max-h-[70vh] overflow-y-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b-2 border-gray-200 text-[10px] font-black uppercase text-gray-400">
                        <th class="pb-3 px-2">Cliente</th>
                        <th class="pb-3 px-2">Combustible</th>
                        <th class="pb-3 px-2 text-center">Cantidad</th>
                        <th class="pb-3 px-2 text-center">Estado Actual</th>
                        <th class="pb-3 px-2 text-right">Acción de Proceso</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($pedidosPendientes ?? [] as $p)
                    <tr>
                        <td class="py-4 px-2">
                            <div class="font-black text-gray-800 text-xs uppercase">{{ $p->cliente->nombre }}</div>
                            <div class="text-[10px] text-gray-500 font-bold tracking-tighter uppercase">SOLICITADO: {{ $p->created_at->format('d/m h:i A') }}</div>
                        </td>
                        <td class="py-4 px-2 text-xs font-bold text-gray-600 italic uppercase">
                            {{ $p->deposito_id == 1 ? 'DIESEL' : 'MGO' }}
                        </td>
                        <td class="py-4 px-2 text-center font-black text-orange-impordiesel text-sm">
                            {{ number_format($p->cantidad_solicitada, 0) }} L
                        </td>
                        <td class="py-4 px-2 text-center">
                            <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-[9px] font-black uppercase border border-gray-300 shadow-sm">
                                {{ $p->estado_text }}
                            </span>
                        </td>
                        <td class="py-4 px-2 text-right">
                            <form action="{{ route('admin.pedidos.updateEstado', $p->id) }}" method="POST" class="inline-flex">
                                @csrf
                                <select name="estado" onchange="this.form.submit()" class="text-[10px] font-black border-2 border-gray-300 rounded p-1.5 outline-none focus:border-orange-impordiesel uppercase bg-white">
                                    <option value="" selected disabled>PASAR A...</option>
                                    <option value="pendiente">PENDIENTE</option>
                                    <option value="aprobado">APROBAR</option>
                                    <option value="en_proceso">EN PROCESO</option>
                                    <option value="completado">COMPLETADO</option>
                                    <option value="rechazado">RECHAZAR</option>
                                    <option value="cancelado">CANCELAR</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-12 text-center text-gray-400 font-black uppercase text-[10px] tracking-widest italic">
                            No existen solicitudes de combustible pendientes
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function openPedidosModal() { document.getElementById('pedidosModal').classList.remove('hidden'); }
    function closePedidosModal() { document.getElementById('pedidosModal').classList.add('hidden'); }
</script>
@endsection