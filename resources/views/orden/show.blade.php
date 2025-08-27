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
                    <li class="list-group-item"><strong>Tipo:</strong> {{ $orden->tipo_orden->descripcion ?? 'N/A' }}</li>
                    <li class="list-group-item">
                        <strong>Estatus:</strong>
                       @php
                                        $estatusInfo = $estatusData->get($orden->estatus);
                                    @endphp
                                    @if ($estatusInfo)
                                        <span class="badge bg-{{ $estatusInfo->css }}" title="{{ $estatusInfo->descripcion }}">
                                            <i class="mr-1 fa-solid {{ $estatusInfo->icon_orden }}"></i>
                                            {{ $estatusInfo->orden }}
                                        </span>
                                    @else
                                        <span class="badge bg-gray">Desconocido</span>
                                    @endif
                    </li>
                </ul>
            </div>
            <div class="col-md-6">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>Kilometraje:</strong> {{ number_format($orden->kilometraje ?? 0, 0, ',', '.') }} km</li>
                    <li class="list-group-item"><strong>Apertura:</strong> {{ $orden->fecha_in->format('d/m/Y') ?? 'N/A' }} a las {{ $orden->hora_in ?? 'N/A' }}</li>
                    <li class="list-group-item"><strong>Cierre:</strong> {{ $orden->fecha_out ?? 'N/A' }} a las {{ $orden->hora_out ?? 'N/A' }}</li>
                    <li class="list-group-item"><strong>Tiempo Promedio:</strong> {{ $orden->tiempo_promedio ?? 'N/A' }} días</li>
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
                    <th>Nombre</th>
                    <th>Cantidad</th>
                    <th>Unidad</th>
                </tr>
            </thead>
            <tbody>
                {{-- @foreach ($insumos_usados as $insumo) --}}
                <tr>
                    <td>Aceite de motor</td>
                    <td>5</td>
                    <td>Litros</td>
                </tr>
                <tr>
                    <td>Filtro de aceite</td>
                    <td>1</td>
                    <td>Unidad</td>
                </tr>
                {{-- @endforeach --}}
            </tbody>
        </table>
    </div>
</div>
@endsection
