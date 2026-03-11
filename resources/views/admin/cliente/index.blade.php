@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- Cards de Resumen --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-info text-white shadow-sm border-0">
                <div class="card-body text-center">
                    <h6>Clientes en Registro (Pasos 1-9)</h6>
                    <h2 class="font-weight-bold">{{ $stats['total_en_registro'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-dark shadow-sm border-0">
                <div class="card-body text-center">
                    <h6>Clientes Pendientes (Paso 3)</h6>
                    <h2 class="font-weight-bold">{{ $stats['en_espera_revision'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white shadow-sm border-0">
                <div class="card-body text-center">
                    <h6>Clientes Activos (Paso 10)</h6>
                    <h2 class="font-weight-bold">{{ $stats['activos'] }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow border-0">
        <div class="card-header bg-white py-3">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <h5 class="mb-0 text-primary font-weight-bold">
                        <i class="fas fa-users-cog mr-2"></i> Control de Clientes
                    </h5>
                </div>
                <div class="col-md-8">
                    <form action="{{ route('clientes.index') }}" method="GET" class="form-inline justify-content-end">
                        {{-- Filtro de Estatus Rápido --}}
                        <select name="status_filtro" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                            <option value="">Todos los estatus</option>
                            <option value="proceso" {{ request('status_filtro') == 'proceso' ? 'selected' : '' }}>Solo en Registro</option>
                            <option value="activos" {{ request('status_filtro') == 'activos' ? 'selected' : '' }}>Solo Activos</option>
                        </select>

                        {{-- Buscador con Botón --}}
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Buscar RIF o Razón Social..." 
                                   value="{{ request('search') }}">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                                @if(request('search') || request('status_filtro'))
                                    <a href="{{ route('clientes.index') }}" class="btn btn-secondary" title="Limpiar filtros">
                                        <i class="fas fa-times"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th class="border-0">Cliente / RIF</th>
                            <th class="border-0 text-center">Estatus / Progreso</th>
                            <th class="border-0 text-center">Última Actualización</th>
                            <th class="border-0 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clientes as $c)
                        <tr>
                            <td class="align-middle">
                                <div class="font-weight-bold text-dark">{{ $c->nombre }}</div>
                                <small class="text-muted">{{ $c->rif }}</small>
                            </td>
                            <td class="align-middle" style="width: 35%;">
                                <div class="d-flex align-items-center mb-1">
                                    <div class="progress flex-grow-1" style="height: 8px; border-radius: 10px;">
                                        <div class="progress-bar {{ $c->registro_paso == 10 ? 'bg-success' : 'bg-primary' }}" 
                                             role="progressbar" 
                                             style="width: {{ $c->registro_paso * 10 }}%">
                                        </div>
                                    </div>
                                    <span class="ml-3 badge badge-pill {{ $c->registro_paso == 10 ? 'badge-success' : 'badge-primary' }}">
                                        Paso {{ $c->registro_paso }}
                                    </span>
                                </div>
                                <small class="{{ $c->registro_paso == 10 ? 'text-success' : 'text-info' }} font-italic font-weight-bold">
                                    {{ $c->registro_paso == 10 ? 'CLIENTE ACTIVO / OPERATIVO' : $c->nombre_paso_actual }}
                                </small>
                            </td>
                            <td class="align-middle text-center text-muted small">
                                {{ $c->updated_at->format('d/m/Y h:i A') }}<br>
                                ({{ $c->updated_at->diffForHumans() }})
                            </td>
                            <td class="align-middle text-right">
                                <a href="{{ route('clientes.show', $c->id) }}" class="btn btn-sm btn-primary shadow-sm px-3">
                                    <i class="fas fa-folder-open mr-1"></i> {{ $c->registro_paso == 10 ? 'Ver Expediente' : 'Gestionar Registro' }}
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="fas fa-search fa-2x mb-3"></i><br>
                                No se encontraron clientes con los criterios de búsqueda.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-0">
            {{ $clientes->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection