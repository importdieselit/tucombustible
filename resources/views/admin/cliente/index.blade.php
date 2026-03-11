@extends('layouts.app')

@section('content')
<div class="container mx-auto py-6 px-4">
    {{-- CARDS DE RESUMEN: Colores sólidos de la marca --}}
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

    <div class="bg-white rounded-lg shadow-sm border border-gray-300 overflow-hidden">
        <div class="p-6 border-b border-gray-200 bg-gray-50">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center">
                    <h5 class="text-lg font-black uppercase tracking-tight text-gray-800 italic">
                        <span class="text-orange-impordiesel">|</span> Listado de Clientes
                    </h5>
                </div>
                
                {{-- BUSCADOR: Botón de búsqueda corregido a color sólido --}}
                <form action="{{ route('clientes.index') }}" method="GET" class="flex items-center gap-0 w-full md:w-auto">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           class="w-full md:w-64 px-4 py-2 text-sm border-2 border-gray-300 rounded-l focus:border-orange-impordiesel outline-none uppercase font-bold text-gray-700" 
                           placeholder="RIF O NOMBRE...">
                    {{-- BOTÓN BÚSQUEDA: Gris Industrial sólido --}}
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
                            {{-- BOTÓN GESTIONAR: Naranja sólido con sombra para resaltar --}}
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
@endsection