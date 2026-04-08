@extends('layouts.app') @section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="text-primary">Mi Historial de Pedidos</h2>
            <p class="text-muted">Consulte el estado de sus solicitudes de combustible.</p>
        </div>
        <div class="col-md-4 text-right text-end">
            <a href="{{ route('pedidos.create') }}" class="btn btn-success">
                <i class="fas fa-plus"></i> Nueva Solicitud
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="bg-light">
                        <tr>
                            <th># Pedido</th>
                            <th>Fecha de Solicitud</th>
                            <th>Empresa / Sucursal</th>
                            <th>Litros Solicitados</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pedidos as $pedido)
                            <tr>
                                <td><strong>{{ str_pad($pedido->id, 5, '0', STR_PAD_LEFT) }}</strong></td>
                                <td>{{ \Carbon\Carbon::parse($pedido->fecha_solicitud)->format('d/m/Y h:i A') }}</td>
                                <td>{{ $pedido->cliente->nombre }}</td>
                                <td>{{ number_format($pedido->cantidad_solicitada, 2, ',', '.') }} L</td>
                                <td>
                                    @if($pedido->estado == 'pendiente')
                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                    @elseif($pedido->estado == 'aprobado')
                                        <span class="badge bg-info text-white">Aprobado</span>
                                    @elseif($pedido->estado == 'despachado')
                                        <span class="badge bg-success">Despachado</span>
                                    @elseif($pedido->estado == 'cancelado')
                                        <span class="badge bg-danger">Cancelado</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($pedido->estado) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if(in_array($pedido->estado, ['pendiente', 'aprobado']))
                                        <form action="{{ route('pedidos.cancelar', $pedido->id) }}" method="POST" onsubmit="return confirm('¿Está seguro de cancelar este pedido? Se reintegrará el cupo a su balance mensual.');">
                                            @csrf
                                            @method('PUT') <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Cancelar
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No ha realizado ninguna solicitud de combustible aún.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection