{{-- resources/views/reportes/show.blade.php --}}

@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Detalle de Reporte #{{ $reporte->id }}</h2>
    <div class="row">
        
        {{-- Columna de Detalle del Reporte --}}
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    Información del Ticket
                    <span class="float-end badge bg-light text-dark">Estatus Actual: {{ $reporte->estatus_actual }}</span>
                </div>
                <div class="card-body">
                    <p><strong>Tipo:</strong> {{ $reporte->tipo->nombre_tipo ?? 'N/A' }}</p>
                    <p><strong>Reportado Por:</strong> {{ $reporte->reportadoPor->name ?? 'Cliente Externo' }}</p>
                    <p><strong>Fecha:</strong> {{ $reporte->created_at->format('Y-m-d H:i:s') }}</p>
                    <p><strong>Ubicación:</strong> {{ $reporte->lugar_reporte }}</p>
                    <hr>
                    <p><strong>Descripción:</strong></p>
                    <p>{{ $reporte->descripcion }}</p>

                    @if ($reporte->url_imagen_evidencia)
                        <p><strong>Evidencia Gráfica:</strong></p>
                        {{-- Asume que el storage disk 'public' está vinculado a storage/app/public --}}
                        <a href="{{ Storage::url($reporte->url_imagen_evidencia) }}" target="_blank">
                            Ver Imagen
                        </a>
                    @endif

                    @if ($reporte->requiere_ot)
                        <hr>
                        <p class="text-success">
                            <i class="fas fa-check-circle"></i> 
                            <strong>Orden de Trabajo Vinculada:</strong> 
                            #{{ $reporte->orden_trabajo_id }} 
                            {{-- Aquí iría el link al detalle de la OT si ese módulo existiera --}}
                        </p>
                    @endif
                </div>
            </div>

            {{-- Historial de Estatus --}}
            <div class="card">
                <div class="card-header">Historial de Auditoría de Estatus</div>
                <div class="card-body">
                    <ul class="list-group">
                        @foreach ($reporte->historialEstatus->sortByDesc('created_at') as $historial)
                            <li class="list-group-item">
                                <strong>{{ $historial->estatus_nuevo }}</strong> 
                                por {{ $historial->usuarioModifica->name ?? 'Sistema' }} el {{ $historial->created_at->format('Y-m-d H:i') }}.
                                <br><small>{{ $historial->nota_cambio }}</small>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        {{-- Columna de Acciones de Estatus --}}
        <div class="col-md-4">
            <div class="card bg-light p-3">
                <h5 class="mb-3">Acciones de Flujo de Trabajo</h5>

                {{-- 1. Botón de Generar OT (Solo si no tiene OT y no está CERRADO) --}}
                @if (!$reporte->requiere_ot && $reporte->estatus_actual !== 'CERRADO')
                    <form method="POST" action="{{ route('reportes.generarot', $reporte->id) }}" onsubmit="return confirm('¿Confirma que desea generar la Orden de Trabajo? Esto pondrá el ticket en EN PROCESO.')">
                        @csrf
                        {{-- Laravel necesita el método POST para esta acción --}}
                        <button type="submit" class="btn btn-warning w-100 mb-2">
                            Generar Orden de Trabajo (OT)
                        </button>
                    </form>
                @endif
                
                {{-- 2. Formulario para Cambio de Estatus --}}
                @if ($reporte->estatus_actual !== 'CERRADO')
                    <hr>
                    <form method="POST" action="{{ route('reportes.update.estatus', $reporte->id) }}">
                        @csrf
                        @method('PUT') {{-- Usamos el método PUT --}}

                        <div class="mb-3">
                            <label for="nuevo_estatus">Cambiar a Estatus:</label>
                            <select name="nuevo_estatus" class="form-control" required>
                                {{-- Las opciones se limitan por la lógica de isValidTransition en el controlador --}}
                                @if ($reporte->estatus_actual === 'ABIERTO')
                                    <option value="EN_PROCESO">EN PROCESO</option>
                                    <option value="CERRADO">CERRADO (Fallo Inválido)</option>
                                @elseif ($reporte->estatus_actual === 'EN_PROCESO')
                                    <option value="CERRADO">CERRADO (Solucionado)</option>
                                @endif
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="nota_cambio">Nota/Justificación <span class="text-danger">*</span></label>
                            <textarea name="nota_cambio" class="form-control" required rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Actualizar Estatus</button>
                    </form>
                @else
                     <p class="text-center text-success">El ticket está **CERRADO**. No se permiten más acciones.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection