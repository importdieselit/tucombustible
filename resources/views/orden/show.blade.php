@extends('layouts.app')

@section('title', 'Hoja Técnica de Orden')

@section('content')
<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h1 class="mb-2">Hoja Técnica de Orden #{{ $orden->nro_orden ?? 'N/A' }}</h1>
        <div>
            <a href="{{ route('ordenes.list') }}" class="btn btn-info me-2">
                <i class="bi bi-arrow-left"></i> Volver al Listado
            </a>
            <a href="{{ route('ordenes.edit', $orden->id ?? '') }}" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Editar
            </a>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="card-title m-0">Detalles de la Orden</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>Vehículo:</strong> {{ $orden->vehiculo()->flota ?? 'N/A' }} ({{ $orden->vehiculo()->placa ?? 'N/A' }})</li>
                    <li class="list-group-item"><strong>Responsable Asignado:</strong> {{ $orden->responsable ?? 'N/A' }}</li>
                    <li class="list-group-item"><strong>Kilometraje:</strong> {{ number_format($orden->kilometraje ?? 0, 0, ',', '.') }}</li>
                    <li class="list-group-item"><strong>Tipo de Orden:</strong> {{ $orden->tipo_orden->nombre ?? 'N/A' }}</li>
                    <li class="list-group-item"><strong>Estatus:</strong> <span class="badge bg-primary">{{ $orden->estatus_data->nombre ?? 'N/A' }}</span></li>
                </ul>
            </div>
            <div class="col-md-6">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>Apertura:</strong> 
                        @php
                        use Carbon\Carbon;
                        Carbon::setLocale('es');
                        @endphp
                           {{ Carbon::parse($orden->fecha_in)->format('d/m/Y') ?? 'N/A' }}
                        a las 
                        {{ Carbon::parse($orden->hora_in)->format('h:i a') ?? 'N/A' }}
                     <li class="list-group-item"><strong>Cierre:</strong> {{ $orden->fecha_out ?? 'N/A' }} a las {{ $orden->hora_out ?? 'N/A' }}</li>
                    {{-- <li class="list-group-item"><strong>Tiempo Promedio:</strong> {{ $orden->tiempo_promedio ?? 'N/A' }} días</li> --}}
                </ul>
            </div>
        </div>
        <hr>
        <h5>Descripción del Problema/Tarea</h5>
        <p>{{ $orden->descripcion_1 ?? 'No hay descripción.' }}</p>

        <hr>
        <h5>Observaciones</h5>
        <p>{{ $orden->observacion ?? 'No hay observaciones.' }}</p>

        <hr>
        <h5>Insumos Utilizados</h5>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Cantidad</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($insumos_usados as $insumo)
                    <tr>
                        <td>{{ $insumo->inventario()->codigo ?? 'N/A' }}</td>
                        <td>{{ $insumo->inventario()->descripcion ?? 'N/A' }}</td>
                        <td>{{ $insumo->cantidad ?? 'N/A' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center">No se han registrado insumos para esta orden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
