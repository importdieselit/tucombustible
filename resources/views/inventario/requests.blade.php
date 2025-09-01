@extends('layouts.app')

@section('title', 'Gestión de Solicitudes de Insumos')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="mb-2">Panel de Solicitudes de Insumos</h1>
        <p class="text-muted">Gestiona las solicitudes de insumos para las órdenes de trabajo.</p>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="card-title m-0">Solicitudes Pendientes y Historial</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>Nro. de Orden</th>
                        <th>Vehículo</th>
                        <th>Insumo</th>
                        <th>Cantidad Solicitada</th>
                        <th>Estatus</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($solicitudes as $solicitud)
                        <tr>
                            <td>
                                <a href="{{ route('ordenes.show', $solicitud->id_inventario_suministro) }}" class="text-decoration-none">
                                    #{{ $solicitud->orden()->first()->nro_orden ?? 'N/A' }}
                                </a>
                            </td>
                            <td>{{ $solicitud->vehiculo()->placa ?? 'N/A' }}</td>
                            <td>{{ $solicitud->inventario()->first()->descripcion ?? 'Insumo Eliminado' }}</td>
                            <td>{{ $solicitud->cantidad ?? 'N/A' }}</td>
                            <td>
                                <span class="badge bg-{{ $solicitud->estatus()->css }}" title="{{ $solicitud->estatus()->request }}">
                                            <i class="mr-1 fa-solid {{ $solicitud->estatus()->icon_request }}"></i>
                                            {{ $solicitud->estatus()->request }}
                                        </span>
               </td>
                            <td class="text-center">
                                @if ($solicitud->estatus === 2)
                                    <form action="{{ route('inventario.requests.approve', $solicitud->id_inventario_suministro) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm me-1" title="Aprobar">
                                            <i class="bi bi-check-circle"></i> Aprobar
                                        </button>
                                    </form>
                                    <form action="{{ route('inventario.requests.reject', $solicitud->id_inventario_suministro) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-sm" title="Rechazar">
                                            <i class="bi bi-x-circle"></i> Rechazar
                                        </button>
                                    </form>
                                @elseif ($solicitud->estatus === 3)
                                    <form action="{{ route('inventario.requests.dispatch', $solicitud->id_inventario_suministro) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm me-1" title="Despachar">
                                            <i class="bi bi-truck"></i> Despachar
                                        </button>
                                    </form>
                                    <form action="{{ route('inventario.requests.reject', $solicitud->id_inventario_suministro) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-sm" title="Rechazar">
                                            <i class="bi bi-x-circle"></i> Rechazar
                                        </button>
                                    </form>
                                @else
                                    <button class="btn btn-secondary btn-sm" disabled>Finalizado</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No hay solicitudes de insumos registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
