@extends('layouts.app')

@section('title', 'Toma de Inventario Físico')

@push('styles')
<style>
    .bg-navy { background-color: #002855 !important; color: white; }
    .text-navy { color: #002855 !important; }
    .border-orange { border-color: #ff6600 !important; }
    .location-badge { 
        background: #f8f9fa; 
        border: 1px solid #dee2e6; 
        padding: 5px 10px; 
        border-radius: 4px;
        font-family: 'Courier New', Courier, monospace;
        font-weight: bold;
    }
    /* Resaltar la fila que se está editando */
    tr:focus-within { background-color: rgba(255, 102, 0, 0.05) !important; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <form action="{{ route('inventario.conteo.store') }}" method="POST">
        @csrf
        
        {{-- Header con Información del Proceso --}}
        <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 shadow-sm rounded border-start border-4 border-orange">
            <div>
                <h3 class="fw-bold mb-0 text-navy text-uppercase"><i class="fas fa-clipboard-check me-2"></i>Toma de Inventario Físico</h3>
                <p class="text-muted mb-0 small">Complete la columna "CONTEO REAL" según lo verificado en estante.</p>
            </div>
            <div class="text-end">
                <span class="badge bg-navy p-2 fs-6">ID AUDITORÍA: {{ $nro_auditoria }}</span>
                <input type="hidden" name="codigo" value="{{ $nro_auditoria }}">
            </div>
        </div>

        {{-- Tabla de Carga Automática --}}
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr class="small text-navy text-uppercase">
                                <th class="ps-4" style="width: 200px;">Ubicación (AL-PA-NI-CE)</th>
                                <th>Descripción del Artículo</th>
                                <th class="text-center">Stock Sistema</th>
                                <th class="text-center bg-light border-start border-end" style="width: 180px;">Conteo Real</th>
                                <th class="text-center">Unidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($productos as $item)
                            <tr>
                                <td class="ps-4">
                                    <span class="location-badge text-orange">{{ $item->codigo }}</span>
                                    <input type="hidden" name="inventario[{{ $item->id }}][codigo]" value="{{ $item->codigo }}">
                                </td>
                                <td>
                                    <div class="fw-bold">{{ strtoupper($item->nombre) }}</div>
                                    <div class="x-small text-muted">REF: {{ $item->referencia ?? 'S/R' }} | GRUPO: {{ $item->grupo }}</div>
                                </td>
                                <td class="text-center fw-bold text-muted">
                                    {{ $item->existencia }}
                                    <input type="hidden" name="inventario[{{ $item->id }}][stock_teorico]" value="{{ $item->existencia }}">
                                </td>
                                <td class="bg-light border-start border-end p-2">
                                    <input type="number" 
                                           name="inventario[{{ $item->id }}][stock_fisico]" 
                                           class="form-control form-control-sm border-navy fw-bold text-center" 
                                           placeholder="0" 
                                           step="1" 
                                           required>
                                </td>
                                <td class="text-center small text-muted">UND</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            
            {{-- Footer de Acciones --}}
            <div class="card-footer bg-white py-3">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="form-group mb-0">
                            <textarea name="observaciones" class="form-control" rows="1" placeholder="Observaciones generales del proceso..."></textarea>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <a href="{{ route('inventario.index') }}" class="btn btn-outline-secondary px-4 me-2">CANCELAR</a>
                        <button type="submit" class="btn btn-navy px-4 fw-bold">
                            <i class="fas fa-save me-1"></i> FINALIZAR Y GENERAR AJUSTES
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection