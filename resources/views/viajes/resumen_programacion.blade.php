@extends('layouts.app')

@section('title', 'Resumen de Viajes Programados/En Curso')

@section('content')

<!-- Cargar librerías necesarias para la impresión/captura -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Uso jQuery.print.js para simular PrintArea.js (se abre el diálogo de impresión/PDF) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jQuery.print/1.6.0/jQuery.print.min.js"></script>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-primary"><i class="bi bi-calendar-check me-2"></i> Programación de Viajes Pendientes</h1>
        
        <!-- Botón para Capturar la Imagen/Imprimir -->
        <button id="btn-descargar-reporte" class="btn btn-primary shadow-sm">
            <i class="bi bi-camera me-2"></i> Capturar y Descargar Reporte
        </button>
    </div>

    <!-- Contenedor del Reporte (El área que será capturada, simplificada) -->
    <div id="reporte-area" class="card shadow-sm p-3 bg-white border border-primary">
        
        <!-- Encabezado del Reporte Simplificado -->
        <div class="text-center mb-3">
            <h5 class="text-primary fw-bold mb-0">REPORTE DE PROGRAMACIÓN DE VIAJES</h5>
            <p class="text-muted small mb-1">Fecha de Emisión: {{ now()->format('d/m/Y H:i') }}</p>
            <div class="bg-light p-2 rounded d-inline-block border border-danger">
                <span class="fw-light small me-2">Presupuesto Total Estimado:</span> 
                <span class="text-danger fw-bold">$ {{ number_format($totalViaticosPresupuestados, 2) }}</span>
            </div>
        </div>

        <!-- Tabla de Detalle Simplificada -->
        <div class="table-responsive">
            <table class="table table-sm" style="font-size: 0.75rem;">
                <thead class="bg-primary text-white">
                    <tr>
                        <th class="py-1">ID</th>
                        <th class="py-1">FECHA</th>
                        <th class="py-1">DESTINO</th>
                        <th class="py-1">CHOFER / VEHÍCULO</th>
                        <th class="py-1">PERSONAL APOYO</th>
                        <th class="py-1">VIÁTICOS (EST.)</th>
                        <th class="py-1">ESTATUS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($viajes as $viaje)
                    <tr>
                        <td>{{ $viaje->id }}</td>
                        <td>{{ \Carbon\Carbon::parse($viaje->fecha_salida)->format('d/m/Y') }}</td>
                        <td>{{ $viaje->destino_ciudad }}</td>
                        <td>
                            <span class="fw-bold">{{ $viaje->chofer->persona->name ?? 'PENDIENTE' }}</span><br>
                            <span class="text-muted">{{ $viaje->vehiculo->placa ?? 'PENDIENTE' }}</span>
                        </td>
                        <td>
                            @if($viaje->ayudantePrincipal)
                                <span class="d-block small">Ayudante: {{ $viaje->ayudantePrincipal->persona->name ?? 'N/A' }}</span>
                            @else
                                <span class="d-block small">Ayudante: N/A</span>
                            @endif
                            <span class="d-block small">Custodia: {{ $viaje->custodia_count ?? 0 }}</span>
                        </td>
                        <td class="text-end fw-bold text-success">
                            $ {{ number_format($viaje->viaticos->sum('monto'), 2) }}
                        </td>
                        <td>
                            <span class="badge 
                                @if($viaje->status == 'PENDIENTE_ASIGNACION') bg-danger 
                                @elseif($viaje->status == 'PENDIENTE_VIATICOS') bg-warning text-dark
                                @elseif($viaje->status == 'ASIGNADO') bg-info
                                @else bg-secondary
                                @endif">
                                {{ str_replace('_', ' ', $viaje->status) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">No hay viajes programados o en curso.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pie de página simple para el reporte -->
        <div class="mt-3 border-top pt-2 text-end small text-muted">
            Generado por el Sistema de Viajes.
        </div>
    </div>
    
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const printArea = $('#reporte-area');
        const downloadButton = document.getElementById('btn-descargar-reporte');

        downloadButton.addEventListener('click', function() {
            // Usando jQuery.print.js para simular la funcionalidad de PrintArea
            // Esto abrirá el diálogo de impresión (el usuario puede Guardar como PDF o Imprimir).
            printArea.print({
                globalStyles: true,
                mediaPrint: false,
                iframe: true,
                manuallyCopyFormValues: false,
                deferred: $.Deferred()
            });
        });
    });
</script>
@endsection
