@extends('layouts.app')

@section('title', 'Editar Orden de Trabajo')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="mb-2">Editar Orden de Trabajo #{{ $item->nro_orden }}</h1>
        <p class="text-muted">Modifica los detalles de la orden de reparación o mantenimiento.</p>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="card-title m-0">Datos de la Orden</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('ordenes.update', $item->id) }}" method="POST">
            @csrf
            @method('PUT')

            {{-- Datos de la Orden --}}
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="vehiculo_id" class="form-label">Vehículo</label>
                                {{ $item->vehiculo()->placa }} - {{ $item->vehiculo()->marca()->marca }} {{ $item->vehiculo()->modelo()->modelo }}
                    
                </div>
                <div class="col-md-6 mb-3">
                    <label for="nro_orden" class="form-label">Número de Orden</label>
                    <input type="text" class="form-control" id="nro_orden" name="nro_orden" value="{{ $item->nro_orden }}" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="kilometraje" class="form-label">Kilometraje</label>
                    <input type="number" class="form-control" id="kilometraje" name="kilometraje" value="{{ $item->kilometraje }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="responsable" class="form-label">Responsable Asignado</label>
                    {{ $item->responsable}}                 
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="estatus" class="form-label">Estatus</label>
                                {{ $item->estatus() }}
                </div>
                {{-- <div class="col-md-6 mb-3">
                    <label for="tipo_orden" class="form-label">Tipo de Orden</label>
                    <select class="form-select" id="tipo_orden" name="id_tipo_orden" required>
                        <option value="">Seleccione un tipo</option>
                        @foreach ($tipos_orden as $tipo)
                            <option value="{{ $tipo->id }}" {{ $item->id_tipo_orden == $tipo->id ? 'selected' : '' }}>
                                {{ $tipo->tipo }}
                            </option>
                        @endforeach
                    </select>
                </div> --}}
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="fecha_in" class="form-label">Fecha de Apertura</label>
                    <input type="date" class="form-control" id="fecha_in" name="fecha_in" value="{{ \Carbon\Carbon::parse($item->fecha_in)->format('Y-m-d') }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="hora_in" class="form-label">Hora de Apertura</label>
                    <input type="time" class="form-control" id="hora_in" name="hora_in" value="{{ $item->hora_in }}" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="descripcion_1" class="form-label">Descripción del Problema/Tarea</label>
                <textarea class="form-control" id="descripcion_1" name="descripcion_1" rows="4" required>{{ $item->descripcion_1 }}</textarea>
            </div>

            <div class="mb-3">
                <label for="observacion" class="form-label">Observaciones</label>
                <textarea class="form-control" id="observacion" name="observacion" rows="3">{{ $item->observacion }}</textarea>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('ordenes.list') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Actualizar Orden</button>
            </div>
        </form>
    </div>
</div>
@endsection
