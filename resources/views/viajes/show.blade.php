@extends('layouts.app')

@section('title', 'Detalle del Viaje #{{ $viaje->id }}')

@section('content')
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-primary">
            <i class="bi bi-truck me-2"></i> 
            Detalle del Viaje #{{ $viaje->id }}
        </h1>
        <a href="{{ route('viaje.list') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver al Historial
        </a>
    </div>

    <div class="row">
        
        <!-- Columna Principal: Resumen y Estado -->
        <div class="col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Información General</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Destino:</strong> {{ $viaje->destino_ciudad }}</p>
                    <p class="mb-2"><strong>Fecha Salida:</strong> {{ $viaje->fecha_salida }}</p>
                    <p class="mb-2"><strong>Fecha de Creación:</strong> {{ $viaje->created_at->format('d/m/Y h:i A') }}</p>
                    
                    <hr>
                    
                    <h5 class="mt-3">Estado del Viaje</h5>
                    @php
                        $badgeClass = match($viaje->status) {
                            'PENDIENTE_VIATICOS' => 'warning text-dark',
                            'VIATICOS_APROBADOS' => 'primary',
                            'EN_CURSO' => 'info',
                            'COMPLETADO' => 'success',
                            default => 'secondary',
                        };
                    @endphp
                    <span class="badge bg-{{ $badgeClass }} fs-6">{{ str_replace('_', ' ', strtoupper($viaje->status)) }}</span>

                    @if ($viaje->status === 'PENDIENTE_VIATICOS' && Auth::user()->canAccess('create', 8))
                        <div class="mt-3">
                            <a href="{{ route('viajes.viaticos.edit', $viaje->id) }}" class="btn btn-sm btn-warning">
                                <i class="bi bi-pencil-square me-1"></i> Ir a Ajustar Viáticos
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Asignación -->
            <div class="card shadow mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Asignación</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Chofer:</strong> {{ $viaje->chofer->nombre ?? 'N/A' }}</p>
                    {{-- Asumimos que tienes una relación 'vehiculo' en el modelo Viaje --}}
                    <p class="mb-2"><strong>Vehículo:</strong> {{ $viaje->vehiculo->placa ?? 'N/A' }} ({{ $viaje->vehiculo->modelo ?? 'Modelo Desconocido' }})</p>
                    <p class="mb-2"><strong>Ayudantes:</strong> {{ $viaje->ayudante }}</p>
                    <p class="mb-2"><strong>Custodia:</strong> {{ $viaje->custodia_count }}</p>
                </div>
            </div>
        </div>

        <!-- Columna Viáticos: Cuadro de Detalle -->
        <div class="col-lg-7">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Cuadro de Viáticos</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Concepto</th>
                                <th class="text-end">Monto Base (USD)</th>
                                <th class="text-end">Cant.</th>
                                <th class="text-end">Total Ajustado (USD)</th>
                                <th>Aprobado Por</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $granTotal = 0; @endphp
                            @forelse ($viaje->viaticos as $viatico)
                                @php
                                    // Determinar qué monto usar: ajustado si existe, sino base.
                                    $montoFinal = $viatico->monto_ajustado ?? $viatico->monto_base;
                                    $granTotal += $montoFinal * $viatico->cantidad;
                                @endphp
                                <tr>
                                    <td>
                                        {{ $viatico->concepto }}
                                        @if($viatico->es_editable)
                                            <i class="bi bi-pencil-square text-muted ms-1" title="Monto Editable"></i>
                                        @endif
                                    </td>
                                    <td class="text-end text-muted">${{ number_format($viatico->monto_base, 2) }}</td>
                                    <td class="text-end">{{ $viatico->cantidad }}</td>
                                    <td class="text-end fw-bold text-success">${{ number_format($montoFinal * $viatico->cantidad, 2) }}</td>
                                    <td>{{ $viatico->aprobador->name ?? 'Sistema/N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No se ha generado el cuadro de viáticos.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="table-primary fw-bold">
                                <td colspan="3" class="text-end">GRAN TOTAL A PAGAR</td>
                                <td class="text-end">${{ number_format($granTotal, 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
