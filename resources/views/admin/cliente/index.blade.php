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

    {{-- SECCIÓN DE PEDIDOS GLOBALES (SIMÉTRICA A TABLA CLIENTES) --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-300 overflow-hidden mb-8">
        <div class="p-6 border-b border-gray-200 bg-gray-50">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <h5 class="text-lg font-black uppercase tracking-tight text-gray-800 italic">
                    <span class="text-orange-impordiesel">|</span> Gestión Global de Pedidos
                </h5>
                
                <div class="flex items-center gap-2">
                    <form action="{{ route('clientes.index') }}" method="GET" class="flex items-center gap-2">
                        {{-- Mantenemos filtros de clientes activos --}}
                        <input type="hidden" name="search" value="{{ request('search') }}">
                        <input type="hidden" name="status" value="{{ request('status') }}">
                        
                        <select name="status_pedido" onchange="this.form.submit()"
                                class="text-xs font-black border-2 border-gray-300 rounded p-2.5 outline-none focus:border-orange-impordiesel bg-white uppercase">
                            <option value="">Todos los pedidos</option>
                            <option value="pendiente" {{ request('status_pedido') == 'pendiente' ? 'selected' : '' }}>Pendientes</option>
                            <option value="aprobado" {{ request('status_pedido') == 'aprobado' ? 'selected' : '' }}>Aprobados</option>
                            <option value="rechazado" {{ request('status_pedido') == 'rechazado' ? 'selected' : '' }}>Rechazados</option>
                        </select>
                    </form>
                </div>
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
                        <th class="px-6 py-4 text-right">Acción</th>
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
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('clientes.show', $pedido->cliente_id) }}"
                               class="inline-block bg-orange-impordiesel text-white px-5 py-2 rounded text-xs font-black uppercase hover:bg-orange-700 transition shadow-md border-b-2 border-orange-900">
                                <i class="fas fa-folder-open mr-1"></i> Expediente
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

    {{-- LISTADO --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-300 overflow-hidden mb-8">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex flex-column md-flex-row align-items-md-center justify-content-between gap-3">
                <h5 class="mb-0 fw-black text-uppercase italic tracking-tighter text-dark">
                    <span class="text-orange">|</span> Listado de Clientes — Combustible
                </h5>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <form action="{{ route('clientes.index') }}" method="GET" class="d-flex align-items-center mb-0">
                        <input type="text" name="search" value="{{ request('search') }}"
                               class="form-control form-control-sm fw-bold text-uppercase border-2 border-end-0 rounded-0 rounded-start border-light-gray shadow-none"
                               style="width: 220px; outline: none;"
                               placeholder="RIF o nombre...">
                        <button type="submit" 
                                class="btn btn-corporate btn-sm rounded-0 fw-black text-uppercase px-3 border-2 border-dark rounded-end ">
                            <i class="fas fa-search"></i>
                        </button>
                        @if(request('search'))
                            <a href="{{ route('clientes.index') }}" 
                               class="btn-danger btn-sm rounded-0 rounded-end fw-black text-uppercase px-3 border-2 border-danger">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </form>

                    <form action="{{ route('clientes.index') }}" method="GET" class="mb-0">
                        <select name="status" onchange="this.form.submit()"
                                class="form-select form-select-sm fw-black text-uppercase border-2 border-light-gray shadow-none cursor-pointer">
                            <option value="">Todos los status</option>
                            <option value="1" {{ request('status') == '1' ? 'selected' : '' }}>En Registro</option>
                            <option value="2" {{ request('status') == '2' ? 'selected' : '' }}>Aprobados</option>
                            <option value="3" {{ request('status') == '3' ? 'selected' : '' }}>Rechazados</option>
                            <option value="0" {{ request('status') == '0' ? 'selected' : '' }}>Inactivos</option>
                        </select>
                    </form>

                    <a href="{{ route('clientes.create') }}" 
                       class="btn-orange text-white btn-sm fw-black text-uppercase px-3 shadow-sm border-bottom border-dark border-1 rounded">
                        <i class="fas fa-plus me-1"></i> Nuevo Cliente
                    </a>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <div class="table-responsive">
                <table class="table table-hover align-middle border-collapse mb-0">
                    <thead class="bg-dark text-white">
                        <tr class="text-uppercase fw-black small" style="letter-spacing: 1px;">
                            <th class="px-4 py-3 border-0">Cliente / Identificación</th>
                            <th class="px-3 py-3 text-center border-0">Tipo</th>
                            <th class="px-3 py-3 text-center border-0">Estatus</th>
                            <th class="px-3 py-3 text-center border-0">Cupo SIAVCOM</th>
                            <th class="px-3 py-3 text-center border-0">Cupo GASCO</th>
                            <th class="px-3 py-3 text-center border-0">Registro</th>
                            <th class="px-3 py-3 text-center border-0">Aprobación</th>
                            <th class="px-4 py-3 text-end border-0">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse($clientes as $c)
                        <tr class="{{ !$c->es_padre ? 'bg-light bg-opacity-50' : '' }} transition-all">
                            <td class="px-4 py-3">
                                @if(!$c->es_padre)
                                    <div class="d-flex align-items-start">
                                        <span class="text-muted me-2 mt-1 fw-bold" style="font-size: 14px;">└</span>
                                        <div>
                                            <div class="fw-black text-dark text-uppercase small lh-sm">{{ $c->nombre }}</div>
                                            <div class="fw-bold text-muted mt-1" style="font-size: 10px;">RIF: {{ $c->rif }}</div>
                                            @if($c->padre)
                                                <div class="fw-bold text-orange text-uppercase mt-1" style="font-size: 9px;">
                                                    <i class="fas fa-sitemap me-1"></i> {{ $c->padre->nombre }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="fw-black text-dark text-uppercase small lh-sm">{{ $c->nombre }}</div>
                                    <div class="fw-bold text-muted mt-1" style="font-size: 10px;">RIF: {{ $c->rif }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if($c->es_padre)
                                    <span class="badge bg-dark text-white text-uppercase px-2 py-1" style="font-size: 9px;">Padre</span>
                                @else
                                    <span class="badge bg-orange-light text-orange border border-orange-subtle text-uppercase px-2 py-1" style="font-size: 9px;">Sucursal</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="badge {{ $c->color_status }} text-white text-uppercase shadow-sm px-3 py-1" style="font-size: 10px; letter-spacing: -0.3px;">
                                    {{ $c->label_status }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <div class="fw-black text-dark small">
                                    {{ number_format($c->cupo_siavcom ?? 0, 0, ',', '.') }}
                                    <span class="text-[9px] text-muted">LTS</span>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <div class="fw-black text-dark small">
                                    {{ number_format($c->cupo_gasco ?? 0, 0, ',', '.') }}
                                    <span class="text-[9px] text-muted">LTS</span>
                                </div>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <div class="fw-bold text-dark small">
                                    {{ $c->created_at?->format('d/m/Y') ?? 'N/A' }}
                                </div>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <div class="fw-bold text-dark small">
                                    {{ $c->fecha_aprobacion?->format('d/m/Y') ?? '—' }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <a href="{{ route('clientes.show', $c->id) }}"
                                   class="btn-orange btn-sm text-white fw-black text-uppercase shadow-sm border-bottom border-dark border-2 px-3 rounded">
                                    <i class="fas fa-folder-open me-1"></i> Expediente
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-6 py-5 text-center text-muted fw-black text-uppercase small tracking-widest">
                                No se encontraron registros en la base de datos.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="p-4 bg-gray-50 border-t">
            {{ $clientes->appends(request()->query())->links() }}
        </div>
    </div>

    {{-- RANKINGS DE CUPOS --}}
    <div class="row g-4 mb-4">

        {{-- TOP 5 CUPOS MÁS GRANDES --}}
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100 overflow-hidden">
                <div class="card-header bg-dark px-4 py-3 d-flex align-items-center justify-content-between">
                    <h6 class="text-uppercase fw-black text-white small mb-0" style="letter-spacing: 1px;">
                        <i class="fas fa-trophy me-2 text-orange"></i> Top 5 — Cupos Más Grandes
                    </h6>
                    <span class="badge bg-orange text-white text-uppercase fw-black" style="font-size: 9px;">
                        Litros / mes
                    </span>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($rankingMayores as $index => $cliente)
                    <div class="list-group-item px-4 py-3 list-hover border-0 border-bottom">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <span class="rank-number me-3 flex-shrink-0 
                                    {{ $index === 0 ? 'rank-1' : ($index === 1 ? 'rank-2' : ($index === 2 ? 'rank-3' : 'rank-other')) }}">
                                    {{ $index + 1 }}
                                </span>
                                <div>
                                    <p class="fw-black text-dark text-uppercase small mb-0 lh-1">{{ $cliente->nombre }}</p>
                                    <small class="fw-bold text-muted" style="font-size: 10px;">RIF: {{ $cliente->rif }}</small>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-black text-orange-600">
                                    {{ number_format($cliente->cupos_max_litros_aprobados, 0, ',', '.') }}
                                    <small class="text-[9px] text-gray-400 font-bold uppercase">L</small>
                                </p>
                                <a href="{{ route('clientes.show', $cliente->id) }}"
                                   class="text-[9px] font-black uppercase text-gray-400 hover:text-orange-impordiesel transition">
                                    Ver expediente →
                                </a>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="p-5 text-center text-muted fw-black text-uppercase small">
                        No hay clientes aprobados con cupo asignado.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- TOP 5 CUPOS MÁS PEQUEÑOS --}}
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100 overflow-hidden">
                <div class="card-header bg-dark px-4 py-3 d-flex align-items-center justify-content-between">
                    <h6 class="text-uppercase fw-black text-white small mb-0" style="letter-spacing: 1px;">
                        <i class="fas fa-arrow-down me-2 text-info"></i> Top 5 — Cupos Más Pequeños
                    </h6>
                    <span class="badge bg-info text-white text-uppercase fw-black" style="font-size: 9px;">
                        Litros / mes
                    </span>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($rankingMenores as $index => $cliente)
                    <div class="list-group-item px-4 py-3 list-hover border-0 border-bottom">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <span class="rank-number me-3 flex-shrink-0 
                                    {{ $index === 0 ? 'rank-1' : ($index === 1 ? 'rank-2' : ($index === 2 ? 'rank-3' : 'rank-other')) }}">
                                    {{ $index + 1 }}
                                </span>
                                <div>
                                    <p class="fw-black text-dark text-uppercase small mb-0 lh-1">{{ $cliente->nombre }}</p>
                                    <small class="fw-bold text-muted" style="font-size: 10px;">RIF: {{ $cliente->rif }}</small>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-black text-blue-600">
                                    {{ number_format($cliente->cupos_max_litros_aprobados, 0, ',', '.') }}
                                    <small class="text-[9px] text-gray-400 font-bold uppercase">L</small>
                                </p>
                                <a href="{{ route('clientes.show', $cliente->id) }}"
                                   class="text-[9px] font-black uppercase text-gray-400 hover:text-orange-impordiesel transition">
                                    Ver expediente →
                                </a>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="p-5 text-center text-muted fw-black text-uppercase small">
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