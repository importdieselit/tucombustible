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


    {{-- LISTADO --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-300 overflow-hidden mb-8">
        <div class="card-header bg-white border-bottom py-3">
    <div class="d-flex flex-column md-flex-row align-items-md-center justify-content-between gap-3">
        {{-- Título con estilo corporativo --}}
        <h5 class="mb-0 fw-black text-uppercase italic tracking-tighter text-dark">
            <span class="text-orange">|</span> Listado de Clientes — Combustible
        </h5>

        <div class="d-flex align-items-center gap-2 flex-wrap">

            {{-- Buscador --}}
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

            {{-- Filtro por status --}}
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

            {{-- Botón Nuevo Cliente --}}
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
                    {{-- Usamos la clase dinámica que ya tienes, asegurando que sea un badge de bootstrap --}}
                    <span class="badge {{ $c->color_status }} text-white text-uppercase shadow-sm px-3 py-1" style="font-size: 10px; letter-spacing: -0.3px;">
                        {{ $c->label_status }}
                    </span>
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
                <td colspan="6" class="px-6 py-5 text-center text-muted fw-black text-uppercase small tracking-widest">
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
                        <div class="text-end">
                            <p class="h5 fw-black text-orange mb-0">
                                {{ number_format($cliente->cupos_max_litros_aprobados, 0, ',', '.') }}
                                <small class="text-muted fw-bold" style="font-size: 9px;">L</small>
                            </p>
                            <a href="{{ route('clientes.show', $cliente->id) }}"
                               class="text-decoration-none fw-black text-uppercase text-muted hover-orange" style="font-size: 9px;">
                                Ver expediente <i class="fas fa-chevron-right ms-1"></i>
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
                        <div class="text-end">
                            <p class="h5 fw-black text-info mb-0">
                                {{ number_format($cliente->cupos_max_litros_aprobados, 0, ',', '.') }}
                                <small class="text-muted fw-bold" style="font-size: 9px;">L</small>
                            </p>
                            <a href="{{ route('clientes.show', $cliente->id) }}"
                               class="text-decoration-none fw-black text-uppercase text-muted hover-orange" style="font-size: 9px;">
                                Ver expediente <i class="fas fa-chevron-right ms-1"></i>
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