@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        {{-- Cards de Resumen --}}
        <div class="col-md-4">
            <div class="card bg-info text-white shadow-sm border-0">
                <div class="card-body text-center">
                    <h6>Clientes en Registro (Pasos 1-9)</h6>
                    <h2 class="font-weight-bold">{{ $stats['total_en_registro'] }}</h2> {{-- Mantengo la variable del service para no romper, pero cambio el texto --}}
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-dark shadow-sm border-0">
                <div class="card-body text-center">
                    <h6>Clientes Pendientes por Revisión (Paso 3)</h6>
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
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-primary font-weight-bold">
                <i class="fas fa-users-cog mr-2"></i> Control de Registro de Clientes
            </h5>
            <div class="search-box">
                <form action="{{ route('clientes.index') }}" method="GET" class="form-inline">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Buscar por RIF o Razón Social...">
                </form>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th class="border-0">Cliente / RIF</th>
                            <th class="border-0 text-center">Progreso de Registro</th>
                            <th class="border-0 text-center">Última Actualización</th>
                            <th class="border-0 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clientes as $c)
                        <tr>
                            <td class="align-middle">
                                <div class="font-weight-bold text-dark">{{ $c->razon_social }}</div>
                                <small class="text-muted">{{ $c->rif }}</small>
                            </td>
                            <td class="align-middle" style="width: 35%;">
                                <div class="d-flex align-items-center mb-1">
                                    <div class="progress flex-grow-1" style="height: 8px; border-radius: 10px;">
                                        <div class="progress-bar bg-primary" 
                                             role="progressbar" 
                                             style="width: {{ $c->registro_paso * 10 }}%" 
                                             aria-valuenow="{{ $c->registro_paso * 10 }}" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                    <span class="ml-3 badge badge-pill badge-primary">Paso {{ $c->registro_paso }}</span>
                                </div>
                                <small class="text-info font-italic font-weight-bold">
                                    {{ $c->nombre_paso_actual }}
                                </small>
                            </td>
                            <td class="align-middle text-center text-muted small">
                                {{ $c->updated_at->format('d/m/Y h:i A') }}<br>
                                ({{ $c->updated_at->diffForHumans() }})
                            </td>
                            <td class="align-middle text-right">
                                <a href="{{ route('clientes.show', $c->id) }}" class="btn btn-sm btn-primary shadow-sm px-3">
                                    <i class="fas fa-folder-open mr-1"></i> Gestionar Expediente
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="fas fa-info-circle fa-2x mb-3"></i><br>
                                No se encontraron clientes en proceso de registro.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-0">
            {{ $clientes->links() }}
        </div>
    </div>
</div>
@endsection