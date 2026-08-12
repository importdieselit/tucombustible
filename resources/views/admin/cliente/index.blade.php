@extends('layouts.app')
@section('title', 'Gestión de Clientes')

@section('content')
<div class="container mx-auto py-6 px-4">

    {{-- CARDS DE RESUMEN --}}
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card card-kpi border-b-orange shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase fw-bold text-muted small mb-2" style="letter-spacing: 1px;">En Registro</h6>
                    <h2 class="fw-black text-dark mb-0">{{ $stats['en_registro'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-kpi border-b-success shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase fw-bold text-muted small mb-2" style="letter-spacing: 1px;">Aprobados</h6>
                    <h2 class="fw-black text-dark mb-0">{{ $stats['aprobados'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-kpi border-b-danger shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase fw-bold text-muted small mb-2" style="letter-spacing: 1px;">Rechazados</h6>
                    <h2 class="fw-black text-dark mb-0">{{ $stats['rechazados'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-kpi border-b-corporate shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase fw-bold text-muted small mb-2" style="letter-spacing: 1px;">Inactivos</h6>
                    <h2 class="fw-black text-dark mb-0">{{ $stats['inactivos'] }}</h2>
                </div>
            </div>
        </div>
    </div>

    {{-- LISTADO DE CLIENTES (GEMELO DE PEDIDOS) --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-300 overflow-hidden mb-8">
        <div class="p-6 border-b border-gray-200 bg-gray-50">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <h5 class="text-lg font-black uppercase tracking-tight text-gray-800 italic">
                    <span class="text-orange-impordiesel">|</span> Listado de Clientes — Combustible
                </h5>

                <div class="flex items-center gap-2 flex-wrap">
                    <form action="{{ route('clientes.index') }}" method="GET" class="flex items-center">
                        <input type="text" name="search" value="{{ request('search') }}"
                            class="text-xs font-black border-2 border-gray-300 border-e-0 rounded-s p-2.5 outline-none focus:border-orange-impordiesel bg-white uppercase w-48"
                            placeholder="RIF o nombre...">
                        <button type="submit" class="bg-gray-800 text-white p-3 rounded-e border-2 border-gray-800 hover:bg-black transition">
                            <i class="fas fa-search text-xs"></i>
                        </button>
                    </form>

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
                    class="bg-orange-impordiesel text-white px-4 py-2.5 rounded text-xs font-black uppercase hover:bg-orange-700 transition shadow-md border-b-2 border-orange-900">
                        <i class="fas fa-plus mr-1"></i> Nuevo Cliente
                    </a>
                </div>
            </div>
        </div>

        {{-- CONTENEDOR DE SCROLL FORZADO --}}
        <div class="overflow-x-auto overflow-y-auto max-h-[450px]">
            <table class="w-full text-left border-collapse" style="min-width: 1100px;">
                <thead class="sticky top-0 z-20">
                    <tr class="bg-gray-industrial text-white text-xs font-black uppercase tracking-widest">
                        <th class="px-6 py-4">Cliente / Identificación</th>
                        <th class="px-4 py-4 text-center">Tipo</th>
                        <th class="px-4 py-4 text-center">Estatus</th>
                        <th class="px-4 py-4 text-center">Cupo SIAVCOM</th>
                        <th class="px-4 py-4 text-center">Cupo GASCO</th>
                        <th class="px-4 py-4 text-center">Disponible</th>
                        <th class="px-6 py-4 text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($clientes as $c)
                    <tr class="hover:bg-gray-50 transition {{ !$c->es_padre ? 'bg-gray-50/50' : '' }}">
                        <td class="px-6 py-4">
                            @if(!$c->es_padre)
                                <div class="flex items-start">
                                    <span class="text-gray-400 mr-2 font-bold" style="font-size: 16px;">└</span>
                                    <div>
                                        <div class="font-black text-gray-800 uppercase text-sm leading-tight">{{ $c->nombre }}</div>
                                        <div class="text-[10px] font-bold text-gray-500 mt-1">RIF: {{ $c->rif }}</div>
                                        @if($c->padre)
                                            <div class="text-[9px] font-black text-orange-impordiesel uppercase mt-1">
                                                <i class="fas fa-sitemap mr-1"></i> {{ $c->padre->nombre }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="font-black text-gray-800 uppercase text-sm leading-tight">{{ $c->nombre }}</div>
                                <div class="text-[10px] font-bold text-gray-500 mt-1">RIF: {{ $c->rif }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-center">
                            @if($c->es_padre)
                                <span class="bg-gray-800 text-white text-[9px] font-black uppercase px-2 py-1 rounded">Padre</span>
                            @else
                                <span class="bg-orange-100 text-orange-700 border border-orange-200 text-[9px] font-black uppercase px-2 py-1 rounded">Sucursal</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-center">
                            <span class="px-3 py-1 rounded text-[10px] font-black uppercase border shadow-sm {{ $c->color_status_tailwind ?? 'bg-gray-100' }}">
                                {{ $c->label_status }}
                            </span>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <div class="text-sm font-black text-gray-700">
                                {{ number_format($c->cupo_siavcom ?? 0, 0, ',', '.') }}
                                <span class="text-[9px] text-gray-400">LTS</span>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <div class="text-sm font-black text-gray-700">
                                {{ number_format($c->cupo_gasco ?? 0, 0, ',', '.') }}
                                <span class="text-[9px] text-gray-400">LTS</span>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-center">
                            <div class="text-sm font-black text-orange-impordiesel">
                                {{ number_format($c->disponible ?? 0, 0, ',', '.') }}
                                <span class="text-[9px] opacity-70">LTS</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <a href="{{ route('clientes.show', $c->id) }}"
                            class="inline-flex items-center justify-center bg-orange-impordiesel text-white px-5 py-2 rounded text-xs font-black uppercase hover:bg-orange-700 transition shadow-md border-b-2 border-orange-900">
                                <i class="fas fa-folder-open mr-2"></i> Expediente
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-400 font-black uppercase text-xs tracking-widest">
                            No se encontraron clientes en la base de datos.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="p-4 bg-gray-50 border-t border-gray-200">
        {{ $clientes->appends(request()->query())->links() }}
        <div class="mt-2 text-[10px] font-black uppercase text-gray-400">
            Total en base de datos: {{ $clientes->total() }} clientes registrados
        </div>
    </div>

    {{-- SECCIÓN DE PEDIDOS GLOBALES --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-300 overflow-hidden mb-8">
        <div class="p-6 border-b border-gray-200 bg-gray-50">
            <div class="flex flex-col gap-4">
                <h5 class="text-lg font-black uppercase tracking-tight text-gray-800 italic">
                    <span class="text-orange-impordiesel">|</span> Gestión Global de Pedidos
                </h5>
                
                {{-- TOOLBAR DE FILTROS --}}
                <form action="{{ route('clientes.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-5 gap-3">
                    {{-- Filtro: Buscador Cliente/RIF --}}
                    <div class="flex flex-col">
                        <label class="text-[10px] font-black uppercase text-gray-500 mb-1 ml-1">Cliente / RIF</label>
                        <input type="text" name="search_pedido" value="{{ request('search_pedido') }}"
                            class="text-xs font-black border-2 border-gray-300 rounded p-2 outline-none focus:border-orange-impordiesel bg-white uppercase"
                            placeholder="Buscar cliente...">
                    </div>

                    {{-- Filtro: Estatus --}}
                    <div class="flex flex-col">
                        <label class="text-[10px] font-black uppercase text-gray-500 mb-1 ml-1">Estatus</label>
                        <select name="status_pedido" onchange="this.form.submit()"
                                class="text-xs font-black border-2 border-gray-300 rounded p-2 outline-none focus:border-orange-impordiesel bg-white uppercase">
                            <option value="">Todos los Estatus</option>
                            <option value="pendiente" {{ request('status_pedido') == 'pendiente' ? 'selected' : '' }}>Pendientes</option>
                            <option value="aprobado" {{ request('status_pedido') == 'aprobado' ? 'selected' : '' }}>Aprobados</option>
                            <option value="en_proceso" {{ request('status_pedido') == 'en_proceso' ? 'selected' : '' }}>En Proceso</option>
                            <option value="completado" {{ request('status_pedido') == 'completado' ? 'selected' : '' }}>Completados</option>
                            <option value="rechazado" {{ request('status_pedido') == 'rechazado' ? 'selected' : '' }}>Rechazados</option>
                        </select>
                    </div>

                    {{-- Filtro: Fecha Desde --}}
                    <div class="flex flex-col">
                        <label class="text-[10px] font-black uppercase text-gray-500 mb-1 ml-1">Desde</label>
                        <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}"
                            class="text-xs font-black border-2 border-gray-300 rounded p-1.5 outline-none focus:border-orange-impordiesel bg-white uppercase">
                    </div>

                    {{-- Filtro: Fecha Hasta --}}
                    <div class="flex flex-col">
                        <label class="text-[10px] font-black uppercase text-gray-500 mb-1 ml-1">Hasta</label>
                        <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}"
                            class="text-xs font-black border-2 border-gray-300 rounded p-1.5 outline-none focus:border-orange-impordiesel bg-white uppercase">
                    </div>

                    {{-- Botones de Acción --}}
                    <div class="flex items-end gap-2">
                        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded text-xs font-black uppercase hover:bg-black transition shadow-md flex-1">
                            <i class="fas fa-filter mr-1"></i> Filtrar
                        </button>
                        @if(request('search_pedido') || request('status_pedido') || request('fecha_desde') || request('fecha_hasta'))
                            <a href="{{ route('clientes.index') }}" class="bg-red-600 text-white px-3 py-2 rounded text-xs font-black uppercase hover:bg-red-700 transition shadow-md">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="overflow-x-auto overflow-y-auto max-h-[450px]">
            <table class="w-full text-left border-collapse">
                <thead class="sticky top-0 z-20">
                    <tr class="bg-gray-industrial text-white text-xs font-black uppercase tracking-widest">
                        <th class="px-6 py-4">ID</th>
                        <th class="px-6 py-4">Cliente / Identificación</th>
                        <th class="px-6 py-4 text-center">Cantidad</th>
                        <th class="px-6 py-4 text-center">Estatus</th>
                        <th class="px-6 py-4 text-center">Fecha Solicitud</th>
                        <th class="px-6 py-4 text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($ultimosPedidos as $pedido)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-black text-gray-400 italic">
                            #{{ str_pad($pedido->id, 5, '0', STR_PAD_LEFT) }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-black text-gray-800 uppercase text-sm leading-tight">
                                {{ $pedido->cliente->nombre ?? 'N/A' }}
                            </div>
                            <div class="text-xs font-bold text-gray-500 mt-1">
                                RIF: {{ $pedido->cliente->rif ?? '-' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="text-sm font-black text-gray-700">
                                {{ number_format($pedido->cantidad_solicitada, 0, ',', '.') }}
                                <span class="text-[10px] text-gray-400 uppercase">Lts</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 rounded text-[10px] font-black uppercase border shadow-sm" 
                                style="background-color: {{ $pedido->estado_color }}20; color: {{ $pedido->estado_color }}; border-color: {{ $pedido->estado_color }}40;">
                                {{ $pedido->estado_text }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="text-xs font-bold text-gray-700">
                                {{ $pedido->fecha_solicitud ? $pedido->fecha_solicitud->format('d/m/Y h:i A') : '—' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <a href="{{ route('clientes.show', $pedido->cliente_id) }}"
                            class="inline-flex items-center justify-center bg-orange-impordiesel text-white px-5 py-2 rounded text-xs font-black uppercase hover:bg-orange-700 transition shadow-md border-b-2 border-orange-900">
                                <i class="fas fa-folder-open mr-2"></i> Expediente
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-400 font-black uppercase text-xs tracking-widest">
                            No se encontraron pedidos con este estatus.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 bg-gray-50 border-t border-gray-200 text-[10px] font-black uppercase text-gray-400">
            Total en lista: {{ $ultimosPedidos->count() }} registros
        </div>
    </div>

    <div class="p-4 bg-gray-50 border-t border-gray-200">
        {{ $ultimosPedidos->appends(request()->query())->links() }}
        <div class="mt-2 text-[10px] font-black uppercase text-gray-400">
            Mostrando {{ $ultimosPedidos->firstItem() }} al {{ $ultimosPedidos->lastItem() }} de {{ $ultimosPedidos->total() }} pedidos
        </div>
    </div>

    {{-- RANKINGS DE DESPACHOS --}}
    <div class="row g-4 mb-4">

        {{-- TOP 5 Más Despachos por Mes --}}
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100 overflow-hidden">
                <div class="card-header bg-dark px-4 py-3 d-flex align-items-center justify-content-between">
                    <h6 class="text-uppercase fw-black text-white text-xs tracking-widest mb-0">
                        <i class="fas fa-trophy me-2 text-orange"></i> Top 5 — Más Despachos por Mes
                    </h6>
                    <span class="badge bg-orange text-white text-uppercase fw-black" style="font-size: 10px;">
                        Despachos / mes
                    </span>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($rankingMayores as $index => $cliente)
                    <div class="list-group-item px-4 py-3 list-hover border-0 border-bottom bg-white">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <span class="rank-number me-3 flex-shrink-0 
                                    {{ $index === 0 ? 'rank-1' : ($index === 1 ? 'rank-2' : ($index === 2 ? 'rank-3' : 'rank-other')) }}">
                                    {{ $index + 1 }}
                                </span>
                                <div>
                                    {{-- Nombre con el mismo estilo de la tabla de pedidos --}}
                                    <div class="font-black text-gray-800 uppercase text-sm leading-tight">
                                        {{ $cliente->nombre }}
                                    </div>
                                    {{-- RIF con el estilo secundario de la tabla --}}
                                    <div class="text-xs font-bold text-gray-500 mt-1">
                                        RIF: {{ $cliente->rif }}
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                {{-- Cantidad en litros idéntica a la tabla --}}
                                <div class="text-sm font-black text-orange-600">
                                    {{ number_format($cliente->despachos_count, 0, ',', '.') }}
                                    <span class="text-[10px] text-gray-400 uppercase">Despacho (s)</span>
                                </div>
                                <div class="mt-1">
                                    <a href="{{ route('clientes.show', $cliente->id) }}"
                                    class="text-[10px] font-black uppercase text-gray-500 hover:text-orange-impordiesel transition">
                                        Ver expediente →
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="p-5 text-center text-gray-400 font-black uppercase text-xs tracking-widest">
                        No hay clientes aprobados con cupo asignado.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- TOP 5 Menos Despachos por Mes --}}
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100 overflow-hidden">
                <div class="card-header bg-dark px-4 py-3 d-flex align-items-center justify-content-between">
                    <h6 class="text-uppercase fw-black text-white text-xs tracking-widest mb-0">
                        <i class="fas fa-arrow-down me-2 text-info"></i> Top 5 — Menos Despachos por Mes
                    </h6>
                    <span class="badge bg-orange text-white text-uppercase fw-black" style="font-size: 10px;">
                        Despachos / mes
                    </span>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($rankingMenores as $index => $cliente)
                    <div class="list-group-item px-4 py-3 list-hover border-0 border-bottom bg-white">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <span class="rank-number me-3 flex-shrink-0 
                                    {{ $index === 0 ? 'rank-1' : ($index === 1 ? 'rank-2' : ($index === 2 ? 'rank-3' : 'rank-other')) }}">
                                    {{ $index + 1 }}
                                </span>
                                <div>
                                    {{-- Nombre con el mismo estilo de la tabla de pedidos --}}
                                    <div class="font-black text-gray-800 uppercase text-sm leading-tight">
                                        {{ $cliente->nombre }}
                                    </div>
                                    {{-- RIF con el estilo secundario de la tabla --}}
                                    <div class="text-xs font-bold text-gray-500 mt-1">
                                        RIF: {{ $cliente->rif }}
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                {{-- Cantidad en litros idéntica a la tabla (en azul) --}}
                                <div class="text-sm font-black text-blue-600">
                                    {{ number_format($cliente->despachos_count, 0, ',', '.') }}
                                    <span class="text-[10px] text-gray-400 uppercase">Despacho (s)</span>
                                </div>
                                <div class="mt-1">
                                    <a href="{{ route('clientes.show', $cliente->id) }}"
                                    class="text-[10px] font-black uppercase text-gray-500 hover:text-orange-impordiesel transition">
                                        Ver expediente →
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="p-5 text-center text-gray-400 font-black uppercase text-xs tracking-widest">
                        No hay clientes aprobados con cupo asignado.
                    </div>
                    @endforelse
                </div>
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