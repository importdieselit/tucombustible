@extends('layouts.app')

@section('title', 'Estadísticas de Inventario')

@push('styles')
<style>
    .card-kpi {
        transition: transform 0.2s;
        border: none;
    }
    .card-kpi:hover {
        transform: translateY(-5px);
    }
    .bg-exacto { background-color: #28a745; color: white; }
    .bg-faltante { background-color: #dc3545; color: white; }
    .bg-sobrante { background-color: #ffc107; color: #212529; }
    .bg-ajuste { background-color: #17a2b8; color: white; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 shadow-sm rounded">
        <div>
            <h3 class="fw-bold mb-0 text-uppercase"><i class="fas fa-chart-pie text-navy me-2"></i>Análisis de Diferencias</h3>
            <p class="text-muted mb-0 small">Resumen estadístico basado en los últimos conteos físicos realizados.</p>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-print me-1"></i> Imprimir Reporte
            </button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        {{-- Exactitud --}}
        <div class="col-md-3">
            <div class="card card-kpi shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted small text-uppercase fw-bold">Items Exactos</h6>
                            <h2 class="fw-bold mb-0 text-success">{{ number_format($stats->exactos) }}</h2>
                        </div>
                        <div class="p-2 bg-success-light rounded">
                            <i class="fas fa-check-double text-success fa-lg"></i>
                        </div>
                    </div>
                    <p class="small text-muted mt-2 mb-0">Coincidencia total sistema vs físico.</p>
                </div>
            </div>
        </div>

        {{-- Faltantes --}}
        <div class="col-md-3">
            <div class="card card-kpi shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted small text-uppercase fw-bold">Items Faltantes</h6>
                            <h2 class="fw-bold mb-0 text-danger">{{ number_format($stats->faltantes) }}</h2>
                        </div>
                        <div class="p-2 bg-danger-light rounded">
                            <i class="fas fa-arrow-down text-danger fa-lg"></i>
                        </div>
                    </div>
                    <p class="small text-muted mt-2 mb-0">Productos con pérdida registrada.</p>
                </div>
            </div>
        </div>

        {{-- Sobrantes --}}
        <div class="col-md-3">
            <div class="card card-kpi shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted small text-uppercase fw-bold">Items Sobrantes</h6>
                            <h2 class="fw-bold mb-0 text-warning">{{ number_format($stats->sobrantes) }}</h2>
                        </div>
                        <div class="p-2 bg-warning-light rounded">
                            <i class="fas fa-arrow-up text-warning fa-lg"></i>
                        </div>
                    </div>
                    <p class="small text-muted mt-2 mb-0">Ingresos no registrados en sistema.</p>
                </div>
            </div>
        </div>

        {{-- Movimiento Total --}}
        <div class="col-md-3">
            <div class="card card-kpi shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted small text-uppercase fw-bold">Volumen de Ajuste</h6>
                            <h2 class="fw-bold mb-0 text-info">{{ number_format($stats->total_ajuste_unidades) }}</h2>
                        </div>
                        <div class="p-2 bg-info-light rounded">
                            <i class="fas fa-boxes text-info fa-lg"></i>
                        </div>
                    </div>
                    <p class="small text-muted mt-2 mb-0">Total unidades (Neto absoluto).</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-balance-scale me-2"></i>Distribución de la Auditoría</h5>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center" style="min-height: 300px;">
                    {{-- Aquí puedes integrar Chart.js --}}
                    <p class="text-muted italic">Visualización gráfica de la precisión del almacén.</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100 bg-light">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Acciones Sugeridas</h5>
                    <ul class="list-group list-group-flush bg-transparent">
                        <li class="list-group-item bg-transparent border-0 px-0">
                            <i class="fas fa-file-invoice text-navy me-2"></i> Generar vales de ajuste para los <span class="badge bg-danger">{{ $stats->faltantes }}</span> faltantes.
                        </li>
                        <li class="list-group-item bg-transparent border-0 px-0">
                            <i class="fas fa-sync text-navy me-2"></i> Sincronizar stock teórico con los <span class="badge bg-warning text-dark">{{ $stats->sobrantes }}</span> sobrantes.
                        </li>
                        <li class="list-group-item bg-transparent border-0 px-0 mt-3">
                            <div class="alert alert-info py-2 small">
                                <i class="fas fa-info-circle me-1"></i> El volumen total de ajuste representa la desviación operativa del periodo.
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection