@extends('layouts.app')

@section('title', 'Detalle de Viaje #' . $viaje->id)

@section('content')
<div class="container mt-5">
    <div class="card shadow-lg">
        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
            <h3 class="mb-0"><i class="bi bi-truck me-2"></i> Detalle de Viaje #{{ $viaje->id }}</h3>
            <a href="{{ route('viaje.list') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i> Volver al Listado</a>
        </div>
        <div class="card-body">
            
            <!-- Estatus y Fechas -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <p class="mb-0 fw-bold">Destino:</p>
                    <span class="badge bg-secondary fs-6">{{ $viaje->destino_ciudad }}</span>
                </div>
                <div class="col-md-4">
                    <p class="mb-0 fw-bold">Fecha de Salida:</p>
                    <p class="fs-6">{{ \Carbon\Carbon::parse($viaje->fecha_salida)->format('d/m/Y') }}</p>
                </div>
                <div class="col-md-4">
                    <p class="mb-0 fw-bold">Estatus:</p>
                    @php
                        $badgeClass = [
                            'PENDIENTE_ASIGNACION' => 'warning',
                            'PENDIENTE_VIATICOS' => 'danger',
                            'EN_CURSO' => 'info',
                            'FINALIZADO' => 'success',
                            'CANCELADO' => 'danger'
                        ][$viaje->status] ?? 'secondary';
                    @endphp
                    <span class="badge bg-{{ $badgeClass }} fs-6">{{ str_replace('_', ' ', $viaje->status) }}</span>
                </div>
            </div>

            <!-- Asignación (Si aplica) -->
            <h4 class="mt-4 mb-3 text-info border-bottom pb-1">Asignación de Personal y Vehículo</h4>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <p class="mb-0 fw-bold">Chofer Principal:</p>
                    <p>{{ $viaje->chofer->persona->nombre_completo ?? 'N/A' }}</p>
                </div>
                <div class="col-md-4">
                    <p class="mb-0 fw-bold">Vehículo:</p>
                    <p>{{ $viaje->vehiculo->placa ?? 'N/A' }} ({{ $viaje->vehiculo->modelo ?? '' }})</p>
                </div>
                <div class="col-md-4">
                    <p class="mb-0 fw-bold">Ayudante:</p>
                    <p>{{ $viaje->ayudantePrincipal->persona->nombre_completo ?? 'N/A' }}</p>
                </div>
            </div>
            
            <!-- Despachos Múltiples -->
            <h4 class="mt-4 mb-3 text-info border-bottom pb-1">Detalle de Despachos ({{ $viaje->despachos->count() }})</h4>
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 50%;">Cliente</th>
                            <th style="width: 25%;">Litros</th>
                            <th style="width: 20%;">Tipo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($viaje->despachos as $index => $despacho)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $despacho->cliente->nombre ?? $despacho->otro_cliente ?? 'Cliente Eliminado' }}</td>
                            <td>{{ number_format($despacho->litros, 2) }} L</td>
                            <td><span class="badge bg-{{ $despacho->cliente_id ? 'primary' : 'success' }}">{{ $despacho->cliente_id ? 'Registrado' : 'Otro Cliente' }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-secondary">
                        <tr>
                            <td colspan="2" class="fw-bold text-end">Total Litros:</td>
                            <td colspan="2" class="fw-bold">{{ number_format($viaje->despachos->sum('litros'), 2) }} L</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Viáticos Presupuestados -->
            <h4 class="mt-4 mb-3 text-info border-bottom pb-1">Cuadro de Viáticos Presupuestado</h4>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-success text-white">
                        <tr>
                            <th>Concepto</th>
                            <th>Monto Base ($)</th>
                            <th>Monto Total ($)</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($viaje->viaticos as $viatico)
                        <tr>
                            <td>{{ $viatico->concepto }}</td>
                            <td>{{ number_format($viatico->monto_base, 2) }}</td>
                            <td><span class="badge bg-primary fs-6">{{ number_format($viatico->monto, 2) }} {{ $viatico->moneda }}</span></td>
                            <td>{{ $viatico->observaciones }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">No se ha generado el cuadro de viáticos.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="2" class="fw-bold text-end">TOTAL PRESUPUESTADO:</td>
                            <td colspan="2" class="fw-bold text-success fs-5">{{ number_format($viaje->viaticos->sum('monto'), 2) }} USD</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        </div>
    </div>
</div>
@endsection