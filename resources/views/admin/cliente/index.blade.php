@extends('layouts.app')
@section('title', 'Gestión de Clientes')

@section('content')
<div class="container mx-auto py-6 px-4">

    {{-- CARDS DE RESUMEN --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-8 border-blue-600 border border-gray-200">
            <h6 class="text-xs font-black uppercase tracking-widest text-gray-500 mb-2">En Registro</h6>
            <h2 class="text-3xl font-black text-gray-800">{{ $stats['en_registro'] }}</h2>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-8 border-green-600 border border-gray-200">
            <h6 class="text-xs font-black uppercase tracking-widest text-gray-500 mb-2">Aprobados</h6>
            <h2 class="text-3xl font-black text-gray-800">{{ $stats['aprobados'] }}</h2>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-8 border-red-600 border border-gray-200">
            <h6 class="text-xs font-black uppercase tracking-widest text-gray-500 mb-2">Rechazados</h6>
            <h2 class="text-3xl font-black text-gray-800">{{ $stats['rechazados'] }}</h2>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-8 border-gray-400 border border-gray-200">
            <h6 class="text-xs font-black uppercase tracking-widest text-gray-500 mb-2">Inactivos</h6>
            <h2 class="text-3xl font-black text-gray-800">{{ $stats['inactivos'] }}</h2>
        </div>
    </div>

    {{-- LISTADO --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-300 overflow-hidden mb-8">
        <div class="p-6 border-b border-gray-200 bg-gray-50">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <h5 class="text-lg font-black uppercase tracking-tight text-gray-800 italic">
                    <span class="text-orange-impordiesel">|</span> Listado de Clientes — Combustible
                </h5>
                <div class="flex items-center gap-2 flex-wrap">

                    {{-- Buscador --}}
                    <form action="{{ route('clientes.index') }}" method="GET" class="flex items-center gap-0">
                        <input type="text" name="search" value="{{ request('search') }}"
                               class="w-full md:w-56 px-4 py-2 text-sm border-2 border-gray-300 rounded-l focus:border-orange-impordiesel outline-none uppercase font-bold text-gray-700"
                               placeholder="RIF o nombre...">
                        <button type="submit"
                                class="bg-gray-industrial text-white px-5 py-2.5 text-sm font-black uppercase hover:bg-black transition border-y-2 border-r-2 border-gray-industrial">
                            <i class="fas fa-search"></i>
                        </button>
                        @if(request('search'))
                            <a href="{{ route('clientes.index') }}"
                               class="bg-red-700 text-white px-4 py-2.5 text-sm font-black uppercase hover:bg-red-900 transition rounded-r border-y-2 border-r-2 border-red-700">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </form>

                    {{-- Filtro por status --}}
                    <form action="{{ route('clientes.index') }}" method="GET">
                        <select name="status" onchange="this.form.submit()"
                                class="text-xs font-black border-2 border-gray-300 rounded p-2.5 outline-none focus:border-orange-impordiesel bg-white uppercase">
                            <option value="">Todos los status</option>
                            <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>En Registro</option>
                            <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>Aprobados</option>
                            <option value="3" {{ request('status') == '3' ? 'selected' : '' }}>Rechazados</option>
                            <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Inactivos</option>
                        </select>
                    </form>

                    <a href="{{ route('clientes.create') }}"
                       class="bg-orange-impordiesel text-white px-5 py-2.5 rounded text-xs font-black uppercase hover:bg-orange-700 transition shadow-md border-b-2 border-orange-900">
                        <i class="fas fa-plus mr-1"></i> Nuevo Cliente
                    </a>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-industrial text-white text-xs font-black uppercase tracking-widest">
                        <th class="px-6 py-4">Cliente / Identificación</th>
                        <th class="px-6 py-4 text-center">Tipo</th>
                        <th class="px-6 py-4 text-center">Estatus</th>
                        <th class="px-6 py-4 text-center">Fecha Registro</th>
                        <th class="px-6 py-4 text-center">Fecha Aprobación</th>
                        <th class="px-6 py-4 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($clientes as $c)
                    <tr class="hover:bg-gray-50 {{ !$c->es_padre ? 'bg-gray-50' : '' }}">
                        <td class="px-6 py-4">
                            @if(!$c->es_padre)
                                <div class="flex items-start">
                                    <span class="text-gray-300 mr-2 mt-1 text-xs">└</span>
                                    <div>
                                        <div class="font-black text-gray-700 uppercase text-sm leading-tight">{{ $c->nombre }}</div>
                                        <div class="text-xs font-bold text-gray-400 mt-1">RIF: {{ $c->rif }}</div>
                                        @if($c->padre)
                                            <div class="text-[9px] font-bold text-orange-impordiesel uppercase mt-0.5">
                                                <i class="fas fa-sitemap mr-1"></i> {{ $c->padre->nombre }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="font-black text-gray-800 uppercase text-sm leading-tight">{{ $c->nombre }}</div>
                                <div class="text-xs font-bold text-gray-500 mt-1">RIF: {{ $c->rif }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($c->es_padre)
                                <span class="bg-gray-industrial text-white px-2 py-1 rounded text-[9px] font-black uppercase">
                                    Padre
                                </span>
                            @else
                                <span class="bg-orange-100 text-orange-impordiesel px-2 py-1 rounded text-[9px] font-black uppercase border border-orange-200">
                                    Sucursal
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="{{ $c->color_status }} text-white px-3 py-1 rounded text-[10px] font-black uppercase tracking-tighter shadow-sm">
                                {{ $c->label_status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="text-xs font-bold text-gray-700">
                                {{ $c->created_at?->format('d/m/Y') ?? 'N/A' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="text-xs font-bold text-gray-700">
                                {{ $c->fecha_aprobacion?->format('d/m/Y') ?? '—' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('clientes.show', $c->id) }}"
                               class="inline-block bg-orange-impordiesel text-white px-5 py-2 rounded text-xs font-black uppercase hover:bg-orange-700 transition shadow-md border-b-2 border-orange-900">
                                <i class="fas fa-folder-open mr-1"></i> Expediente
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-400 font-black uppercase text-xs tracking-widest">
                            No se encontraron registros.
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

    {{-- RANKINGS DE CUPOS --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        {{-- TOP 5 CUPOS MÁS GRANDES --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gray-industrial px-6 py-4 flex items-center justify-between">
                <h5 class="text-xs font-black uppercase text-white tracking-widest">
                    <i class="fas fa-trophy mr-2 text-orange-impordiesel"></i> Top 5 — Cupos Más Grandes
                </h5>
                <span class="bg-orange-impordiesel text-white text-[9px] px-2 py-1 rounded-full font-black uppercase">
                    Litros / mes
                </span>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($rankingMayores as $index => $cliente)
                <div class="flex items-center justify-between px-6 py-4 hover:bg-gray-50">
                    <div class="flex items-center gap-4">
                        <span class="w-8 h-8 rounded-full flex items-center justify-center font-black text-sm
                            {{ $index === 0 ? 'bg-yellow-400 text-white' : ($index === 1 ? 'bg-gray-300 text-gray-700' : ($index === 2 ? 'bg-orange-300 text-white' : 'bg-gray-100 text-gray-500')) }}">
                            {{ $index + 1 }}
                        </span>
                        <div>
                            <p class="text-xs font-black text-gray-800 uppercase leading-tight">{{ $cliente->nombre }}</p>
                            <p class="text-[9px] font-bold text-gray-400 mt-0.5">{{ $cliente->rif }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-black text-orange-impordiesel">
                            {{ number_format($cliente->cupos_max_litros_aprobados, 0, ',', '.') }}
                            <small class="text-[9px] text-gray-400 font-bold uppercase">L</small>
                        </p>
                        <a href="{{ route('clientes.show', $cliente->id) }}"
                           class="text-[9px] font-black uppercase text-gray-400 hover:text-orange-impordiesel transition">
                            Ver expediente →
                        </a>
                    </div>
                </div>
                @empty
                <div class="px-6 py-10 text-center text-gray-400 font-black uppercase text-xs">
                    No hay clientes aprobados con cupo asignado.
                </div>
                @endforelse
            </div>
        </div>

        {{-- TOP 5 CUPOS MÁS PEQUEÑOS --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gray-industrial px-6 py-4 flex items-center justify-between">
                <h5 class="text-xs font-black uppercase text-white tracking-widest">
                    <i class="fas fa-arrow-down mr-2 text-blue-400"></i> Top 5 — Cupos Más Pequeños
                </h5>
                <span class="bg-blue-600 text-white text-[9px] px-2 py-1 rounded-full font-black uppercase">
                    Litros / mes
                </span>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($rankingMenores as $index => $cliente)
                <div class="flex items-center justify-between px-6 py-4 hover:bg-gray-50">
                    <div class="flex items-center gap-4">
                        <span class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center font-black text-sm text-gray-500">
                            {{ $index + 1 }}
                        </span>
                        <div>
                            <p class="text-xs font-black text-gray-800 uppercase leading-tight">{{ $cliente->nombre }}</p>
                            <p class="text-[9px] font-bold text-gray-400 mt-0.5">{{ $cliente->rif }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-black text-blue-600">
                            {{ number_format($cliente->cupos_max, 0, ',', '.') }}
                            <small class="text-[9px] text-gray-400 font-bold uppercase">L</small>
                        </p>
                        <a href="{{ route('clientes.show', $cliente->id) }}"
                           class="text-[9px] font-black uppercase text-gray-400 hover:text-orange-impordiesel transition">
                            Ver expediente →
                        </a>
                    </div>
                </div>
                @empty
                <div class="px-6 py-10 text-center text-gray-400 font-black uppercase text-xs">
                    No hay clientes aprobados con cupo asignado.
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ENLACE A CLIENTES LUBRICANTES --}}
    <div class="text-right">
        <a href="{{ route('clientes.lubricantes.index') }}"
           class="text-xs font-black uppercase text-gray-500 hover:text-orange-impordiesel transition">
            <i class="fas fa-oil-can mr-1"></i> Ver Clientes de Lubricantes →
        </a>
    </div>

</div>
@endsection