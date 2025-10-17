@extends('layouts.app')

@section('title', 'Resumen de Viajes Programados/En Curso')

@section('content')

<!-- Cargar librerías necesarias para la impresión/captura -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Uso jQuery.print.js para simular PrintArea.js (se abre el diálogo de impresión/PDF) -->
<script src="{{asset('js/jquery.PrintArea.js')}}"></script>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-primary"><i class="bi bi-calendar-check me-2"></i> Programación de Viajes Pendientes</h1>
        
        <!-- Botón para Capturar la Imagen/Imprimir -->
        <button id="btn-descargar-reporte" class="btn btn-primary shadow-sm">
            <i class="bi bi-camera me-2"></i> Capturar y Descargar Reporte
        </button>
    </div>

    <!-- Contenedor del Reporte (El área que será capturada, simplificada) -->
    <div id="reporte-area" class="card shadow-sm p-3 bg-white border border-primary" style="width: 50%">
        
        <!-- Encabezado del Reporte Simplificado -->
        <div class="text-center mb-3">
            <h5 class="text-primary fw-bold mb-0">REPORTE DE PROGRAMACIÓN DE VIAJES</h5>
            <p class="text-muted small mb-1">Fecha de Emisión: {{ now()->format('d/m/Y H:i') }}</p>
            {{-- <div class="bg-light p-2 rounded d-inline-block border border-danger">
                <span class="fw-light small me-2">Presupuesto Total Estimado:</span> 
                <span class="text-danger fw-bold">$ {{ number_format($totalViaticosPresupuestados, 2) }}</span>
            </div> --}}
        </div>

        <!-- Tabla de Detalle Simplificada -->
        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
            <table class="table table-sm" style="font-size: 0.75rem;">
                <thead class="bg-primary text-white">
                    <tr style="font-weight: 700">
                        <th class="py-1"><img src="{{ asset('img/logo1.png') }}" alt="logo empresa" style="width: 300px"></th>
                        <th class="py-1">Litros</th>
                        <th class="py-1" style="background-color: navajowhite; text-align: center;    vertical-align: middle;">CHOFER / AYUDANTE</th>
                        <th class="py-1" style="background-color: navajowhite; text-align: center;    vertical-align: middle;">UNIDAD</th>
                    </tr>
                </thead>
                <tbody>
                    @php($TotalLitros=0)
                    @forelse($viajes as $viaje)
                    @php($TotalLitros += $viaje->litros ?? 0)

                      <tr style="border-bottom: 1px solid #01050a; background-color:white"   >
                        <td colspan="2">Despacho {{ \Carbon\Carbon::parse($viaje->fecha_salida)->format('d/m/Y') }}<br>
                            <strong>[{{ $viaje->destino_ciudad }}]</strong>
                        </td>
                        <td rowspan="{{$viaje->despachos->count()+1}}" style="vertical-align: middle; text-align:center">
                            <span class="fw-bold">{{ $viaje->chofer->persona->name ?? 'PENDIENTE' }}</span><br>
                            @if($viaje->ayudante)
                                <span class="d-block">Ayudante: {{ $viaje->ayudante->persona->name ?? 'N/A' }}</span>
                            @endif
                        </td>
                        <td rowspan="{{$viaje->despachos->count()+1}}" style="vertical-align: middle; text-align:center">
                            <span class="text-black fw-bold" style="font-size: 20px" >{{  $viaje->vehiculo->flota }}</span><br>
                                {{ $viaje->vehiculo->placa ?? 'PENDIENTE' }}
                            
                        </td>
                      </tr>

                      @foreach($viaje->despachos as $index => $despacho)
                        <tr>
                            <td>{{ $despacho->cliente->nombre ?? $despacho->otro_cliente ?? 'Cliente Null' }}</td>
                            <td>{{ number_format($despacho->litros, 2)}} L</td>
                        </tr>
                      @endforeach

                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">No hay viajes programados o en curso.</td>
                    </tr>
                    @endforelse
                    <tr style="font-weight: 700; border-top: 2px solid #01050a; background-color: #d1ecf1;">
                        <td class="py-1">TOTAL LITROS</td>
                        <td class="py-1">{{ $TotalLitros }}</td>
                        <td class="py-1"></td>
                        <td class="py-1"></td>
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
            printArea.printArea({
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
