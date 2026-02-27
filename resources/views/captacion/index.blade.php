@extends('layouts.app')

@section('title', 'Control de Captación')

@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
            <p class="text-sm font-medium text-gray-500 uppercase">En Proceso Total</p>
            <p class="text-3xl font-bold text-blue-600">{{ $stats['total_prospectos'] }}</p>
        </div>
        <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
            <p class="text-sm font-medium text-gray-500 uppercase">En Revisión (Paso 4)</p>
            <p class="text-3xl font-bold text-yellow-500">{{ $stats['en_revision'] }}</p>
        </div>
        <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
            <p class="text-sm font-medium text-gray-500 uppercase">En Espera del Ministerio</p>
            <p class="text-3xl font-bold text-purple-600">{{ $stats['esperando_minpet'] }}</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-50 flex justify-between items-center">
            <h2 class="text-lg font-bold text-gray-800">Listado de Prospectos</h2>
            <input type="text" placeholder="Buscar cliente..." class="text-sm border border-gray-200 rounded-lg px-4 py-2 w-64 focus:ring-2 focus:ring-blue-500 outline-none">
        </div>

        <table class="w-full text-left">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-xs font-semibold text-gray-400 uppercase">Cliente</th>
                    <th class="px-6 py-4 text-xs font-semibold text-gray-400 uppercase">Estado Actual</th>
                    <th class="px-6 py-4 text-xs font-semibold text-gray-400 uppercase">Progreso</th>
                    <th class="px-6 py-4 text-xs font-semibold text-gray-400 uppercase text-right">Acción</th>
                </tr>
            </thead>
    <tbody class="divide-y divide-gray-50">
        @foreach($clientes as $cliente) {{-- Cambiado de 'prospectos' a 'clientes' --}}
        <tr class="hover:bg-blue-50/30 transition">
            <td class="px-6 py-4">
                <div class="font-bold text-gray-900">{{ $cliente->name }}</div>
                <div class="text-[10px] text-gray-400 font-mono tracking-widest">{{ $cliente->cliente->rif ?? 'RIF NO CARGADO' }}</div>
            </td>
            <td class="px-6 py-4">
                <div class="flex flex-col">
                    <span class="text-xs font-semibold text-slate-600">
                        Paso {{ $cliente->cliente->registro_paso }}: {{ $dashboardService->getNombrePaso($cliente->cliente->registro_paso) }}
                    </span>
                    <span class="text-[10px] text-gray-400 uppercase tracking-tighter italic">Cliente Inactivo</span>
                </div>
            </td>
            <td class="px-6 py-4">
                {{-- Barra de progreso basada en los 10 pasos --}}
                <div class="flex items-center gap-3">
                    <div class="w-full bg-gray-200 h-1.5 rounded-full overflow-hidden">
                        <div class="bg-blue-600 h-full transition-all duration-500" 
                             style="width: {{ ($cliente->cliente->registro_paso / 10) * 100 }}%"></div>
                    </div>
                    <span class="text-[10px] font-bold text-gray-500">{{ ($cliente->cliente->registro_paso / 10) * 100 }}%</span>
                </div>
            </td>
            <td class="px-6 py-4 text-right">
                <a href="{{ route('captacion.show', $cliente->id) }}" 
                class="inline-flex items-center bg-white border border-slate-200 text-slate-700 text-[11px] font-bold px-3 py-1.5 rounded-lg hover:bg-slate-50 transition shadow-sm">
                    GESTIONAR EXPEDIENTE
                </a>
            </td>
        </tr>
        @endforeach
    </tbody>
        </table>
        
        <div class="p-4 border-t border-gray-50">
            {{ $prospectos->links() }}
        </div>
    </div>
</div>
@endsection