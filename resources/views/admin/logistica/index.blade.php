@extends('layouts.app')
@section('title', 'Logística y Planificación de Despachos')

@section('content')
<div class="container mx-auto py-6 px-4">

    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800 uppercase tracking-tight">
            <i class="fas fa-route text-orange-impordiesel mr-2"></i> Centro de Logística
        </h1>
    </div>

    {{-- BARRA DE FILTROS --}}
    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200 mb-6">
        <form action="{{ route('logistica.planificacion') }}" method="GET" class="flex flex-wrap gap-4 items-end">
            
            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Estado</label>
                <select name="estado" class="border border-gray-300 rounded p-2 text-xs uppercase focus:outline-none focus:border-orange-impordiesel">
                    <option value="">TODOS</option>
                    <option value="pendiente" {{ ($filtros['estado'] ?? '') == 'pendiente' ? 'selected' : '' }}>Pendientes</option>
                    <option value="aprobado" {{ ($filtros['estado'] ?? '') == 'aprobado' ? 'selected' : '' }}>Aprobados (Por despachar)</option>
                    <option value="en_proceso" {{ ($filtros['estado'] ?? '') == 'en_proceso' ? 'selected' : '' }}>En Tránsito</option>
                    <option value="completado" {{ ($filtros['estado'] ?? '') == 'completado' ? 'selected' : '' }}>Completados</option>
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Fecha Entrega Sugerida</label>
                <input type="date" name="fecha" value="{{ $filtros['fecha'] ?? '' }}" class="border border-gray-300 rounded p-2 text-xs uppercase focus:outline-none focus:border-orange-impordiesel">
            </div>

            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded text-xs font-black uppercase hover:bg-black transition">
                <i class="fas fa-filter mr-1"></i> Filtrar
            </button>
            
            <a href="{{ route('logistica.planificacion') }}" class="text-gray-500 hover:text-orange-impordiesel text-xs font-bold underline">
                Limpiar
            </a>
        </form>
    </div>

    {{-- ACÁ VA TU DISEÑO DE LA TABLA DE PLANIFICACIÓN --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        {{-- Aquí puedes iterar sobre $pedidos como lo tenías pensado --}}
        {{-- @foreach($pedidos as $pedido) ... @endforeach --}}
    </div>

    {{-- PAGINACIÓN --}}
    <div class="mt-4">
        {{ $pedidos->links() }}
    </div>

</div>
@endsection