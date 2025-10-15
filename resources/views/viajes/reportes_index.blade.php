@extends('layouts.app')

@section('title', 'Generador de Reportes de Viajes')

@section('content')
<div class="container-fluid mt-4">
    <h1 class="mb-4 text-secondary"><i class="bi bi-bar-chart-line-fill me-3"></i> Reportes Administrativos de Viajes</h1>
    
    <!-- Sección de Filtros -->
    <div class="card shadow mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">Filtros de Búsqueda</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('viajes.report.generate') }}" method="GET">
                <div class="row g-3">
                    <!-- Filtro Fecha Inicio -->
                    <div class="col-md-3">
                        <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="{{ old('fecha_inicio', $filtros['fecha_inicio'] ?? '') }}">
                    </div>
                    <!-- Filtro Fecha Fin -->
                    <div class="col-md-3">
                        <label for="fecha_fin" class="form-label">Fecha Fin</label>
                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="{{ old('fecha_fin', $filtros['fecha_fin'] ?? '') }}">
                    </div>
                    <!-- Filtro Chofer -->
                    <div class="col-md-3">
                        <label for="chofer_id" class="form-label">Chofer</label>
                        <select class="form-select" id="chofer_id" name="chofer_id">
                            <option value="">Seleccione Chofer...</option>
                            @foreach ($choferes as $chofer)
                                <option value="{{ $chofer->id }}" {{ (isset($filtros['chofer_id']) && $filtros['chofer_id'] == $chofer->id) ? 'selected' : '' }}>
                                    {{ $chofer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Filtro Ciudad -->
                    <div class="col-md-3">
                        <label for="destino_ciudad" class="form-label">Destino</label>
                        <select class="form-select" id="destino_ciudad" name="destino_ciudad">
                            <option value="">Seleccione Ciudad...</option>
                            @foreach ($ciudades as $ciudad)
                                <option value="{{ $ciudad }}" {{ (isset($filtros['destino_ciudad']) && $filtros['destino_ciudad'] == $ciudad) ? 'selected' : '' }}>
                                    {{ $ciudad }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Botón Generar -->
                    <div class="col-12 mt-4 text-end">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-file-earmark-bar-graph me-2"></i> Generar Reporte
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Sección de Resultados del Reporte -->
    @if(isset($viajes_reporte))
        <hr>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4">Resultados ({{ $viajes_reporte->count() }} viajes)</h2>
            {{-- Botón de Exportar (Funcionalidad avanzada a implementar) --}}
            <button class="btn btn-success">
                <i class="bi bi-file-earmark-excel me-1"></i> Exportar a Excel
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Fecha Creación</th>
                        <th>Destino</th>
                        <th>Chofer</th>
                        <th>Fecha Salida</th>
                        <th>Estatus</th>
                        <th class="text-end">Costo Viáticos (USD)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($viajes_reporte as $viaje)
                        @php
                            // Calcular el total de viáticos para el viaje
                            $costoTotalViaticos = $viaje->viaticos->sum(function($viatico) {
                                $monto = $viatico->monto_ajustado ?? $viatico->monto_base;
                                return $monto * $viatico->cantidad;
                            });
                        @endphp
                        <tr>
                            <td><a href="{{ route('viajes.show', $viaje->id) }}">{{ $viaje->id }}</a></td>
                            <td>{{ $viaje->created_at->format('d/m/Y') }}</td>
                            <td>{{ $viaje->destino_ciudad }}</td>
                            <td>{{ $viaje->chofer->name ?? 'N/A' }}</td>
                            <td>{{ $viaje->fecha_salida }}</td>
                            <td>
                                @php
                                    $badgeClass = ($viaje->status == 'VIATICOS_APROBADOS') ? 'primary' : (($viaje->status == 'COMPLETADO') ? 'success' : 'warning text-dark');
                                @endphp
                                <span class="badge bg-{{ $badgeClass }}">{{ str_replace('_', ' ', strtoupper($viaje->status)) }}</span>
                            </td>
                            <td class="text-end fw-bold">${{ number_format($costoTotalViaticos, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No hay viajes que coincidan con los filtros aplicados.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="table-info fw-bold fs-5">
                        <td colspan="6" class="text-end">GRAN TOTAL VIÁTICOS DEL REPORTE</td>
                        <td class="text-end">${{ number_format($granTotalViaticos ?? 0, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</div>
@endsection
