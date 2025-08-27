@extends('layouts.app')

@section('title', 'Listado de Choferes')

@section('content')
<div class="container-fluid mt-4">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <h3 class="text-themecolor">Listado de Choferes</h3>
        </div>
        <div class="col-md-6 align-self-center">
            <div class="d-flex justify-content-end">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('choferes.index') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Listado</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title m-0">Lista de Choferes</h5>
            <a href="{{ route('choferes.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i> Registrar Chofer
            </a>
        </div>
        <div class="card-body">
            @if(Session::has('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ Session::get('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Cédula</th>
                            <th>Licencia</th>
                            <th>Vencimiento de Licencia</th>
                            <th>Vehículo Asignado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $chofer)
                            <tr>
                                <td>{{ $chofer->persona->nombre }}</td>
                                <td>{{ $chofer->persona->dni }}</td>
                                <td>{{ $chofer->licencia_numero }}</td>
                                <td>
                                    <span class="badge {{ $chofer->licenciaVencida() ? 'bg-danger' : ($chofer->licenciaPorVencer() ? 'bg-warning' : 'bg-success') }}">
                                        {{ $chofer->licencia_vencimiento }}
                                    </span>
                                </td>
                                <td>
                                    @if ($chofer->vehiculo)
                                        {{ $chofer->vehiculo->placa }}
                                    @else
                                        <span class="text-muted">No asignado</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('choferes.show', $chofer->id) }}" class="btn btn-sm btn-info text-white" title="Ver Detalle">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('choferes.edit', $chofer->id) }}" class="btn btn-sm btn-warning text-white" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('choferes.destroy', $chofer->id) }}" method="POST" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de que deseas eliminar a este chofer?')" title="Eliminar">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No hay choferes registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
