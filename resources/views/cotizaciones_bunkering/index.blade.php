@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
        <div>
            <h1 class="m-0">Cotizaciones Bunkering & STS</h1>
            <p class="text-muted small m-0">Historial de cotizaciones emitidas y seguimiento de pedidos</p>
        </div>
        <a href="{{ route('cotizaciones-bunkering.create') }}" class="btn btn-primary btn-lg">
            <i class="fas fa-plus-circle me-1"></i> Nueva Cotización
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Tarjeta Principal del Listado -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list me-1"></i> Historial de Pedidos</span>
            <span class="badge bg-secondary">{{ $cotizaciones->total() }} Registros</span>
        </div>
        <div class="card-body">
            
            <!-- Tabla de Registros -->
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 10%;">N° Pedido</th>
                            <th class="text-center" style="width: 10%;">Fecha</th>
                            <th style="width: 25%;">Buque / Buyer</th>
                            <th style="width: 15%;">Puerto / Entrega</th>
                            <th class="text-center" style="width: 10%;">Selector Costo</th>
                            <th class="text-end" style="width: 12%;">Delivery Fee</th>
                            <th class="text-end" style="width: 13%;">Total General ($)</th>
                            <th class="text-center" style="width: 5%;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cotizaciones as $cotizacion)
                            <tr>
                                <td class="text-center fw-bold">
                                    <span class="badge bg-outline-primary text-primary border border-primary">
                                        {{ $cotizacion->numero_pedido }}
                                    </span>
                                </td>
                                <td class="text-center small text-muted">
                                    {{ $cotizacion->created_at->format('d/m/Y') }}<br>
                                    <small>{{ $cotizacion->created_at->format('H:i') }}</small>
                                </td>
                                <td>
                                    <strong class="text-dark d-block">{{ $cotizacion->nombre_buque }}</strong>
                                    <small class="text-muted">Buyer: {{ $cotizacion->pagador ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    <span>{{ $cotizacion->punto_entrega }}</span>
                                    @if($cotizacion->metodo_despacho)
                                        <small class="d-block text-muted">{{ $cotizacion->metodo_despacho }}</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @switch($cotizacion->nivel_precio_cliente)
                                        @case(1)
                                            <span class="badge bg-info text-dark">Costo 1 (+10%)</span>
                                            @break
                                        @case(2)
                                            <span class="badge bg-warning text-dark">Costo 2 (+15%)</span>
                                            @break
                                        @case(3)
                                            <span class="badge bg-danger">Costo 3 (+20%)</span>
                                            @break
                                        @default
                                            <span class="badge bg-secondary">Costo 1</span>
                                    @endswitch
                                </td>
                                <td class="text-end text-warning fw-bold">
                                    ${{ number_format($cotizacion->costo_delivery, 2, ',', '.') }}
                                </td>
                                <td class="text-end text-success fw-bold h6 mb-0">
                                    ${{ number_format($cotizacion->total_general, 2, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('cotizaciones-bunkering.show', $cotizacion->id) }}" class="btn btn-outline-primary btn-sm" title="Ver / Imprimir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    No hay cotizaciones de Bunkering/STS registradas aún.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="d-flex justify-content-end mt-3">
                {{ $cotizaciones->links() }}
            </div>
        </div>
    </div>
</div>
@endsection