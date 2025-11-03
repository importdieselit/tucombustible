@extends('layouts.app')

@section('title', 'Crear Solicitud de Compra de Combustible')

@section('content')
<div class="container mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="mb-2 text-primary">Solicitud de Compra de Combustible</h1>
            <p class="text-muted">Inicie el proceso de compra indicando la cantidad, proveedor y destino de la carga.</p>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="card-title m-0">Detalles de la Solicitud</h5>
        </div>
        <div class="card-body">
            <!-- Formulario de Solicitud -->
            <form action="{{ route('combustible.storeCompra') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <!-- Proveedor -->
                    <div class="col-md-6">
                        <label for="proveedor_id" class="form-label">Proveedor</label>
                        <select class="form-select" id="proveedor_id" name="proveedor_id" required>
                            <option value="">Seleccione un Proveedor</option>
                            {{-- Placeholder: Iterar sobre una colección de proveedores --}}
                            @foreach($proveedores as $proveedor)
                                <option value="{{ $proveedor->id }}">{{ $proveedor->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Cantidad (Litros) -->
                    <div class="col-md-6">
                        <label for="cantidad_litros" class="form-label">Cantidad (Litros)</label>
                        <input type="number" class="form-control" id="cantidad_litros" name="cantidad_litros" min="100" required>
                        <div class="form-text">Mínimo 100 litros. Esta cantidad se usará para filtrar la unidad de transporte.</div>
                    </div>

                    <!-- Planta Destino (Carga) -->
                    <div class="col-md-6">
                        <label for="planta_destino_id" class="form-label">Planta de Carga/Destino</label>
                        <select class="form-select" id="planta_destino_id" name="planta_destino_id" required>
                            <option value="">Seleccione una Planta</option>
                            {{-- Placeholder: Iterar sobre una colección de plantas --}}
                            @foreach($plantas as $planta)
                                <option value="{{ $planta->id }}">{{ $planta->nombre }} ({{ $planta->ciudad }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Fecha Requerida (Día) -->
                    <div class="col-md-6">
                        <label for="fecha_requerida" class="form-label">Fecha Requerida de Carga</label>
                        <input type="date" class="form-control" id="fecha_requerida" name="fecha_requerida" min="{{ date('Y-m-d') }}" required>
                        <div class="form-text">Día en el que se debe realizar la carga.</div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-file-earmark-plus me-2"></i> Crear Solicitud y Planificar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
