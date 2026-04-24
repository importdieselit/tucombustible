@extends('layouts.app')

@section('title', 'Control de Inventario y Almacenes')

@push('styles')
<style>
    /* Estándares Corporativos */
    .bg-navy { background-color: #002855 !important; }
    .bg-orange { background-color: #ff6600 !important; }
    .text-navy { color: #002855 !important; }
    .text-orange { color: #ff6600 !important; }
    .border-orange { border-color: #ff6600 !important; }
    
    .card-kpi { border: none; border-radius: 8px; transition: transform 0.2s; }
    .card-kpi:hover { transform: translateY(-5px); }
    .stats-number { font-size: 2rem; font-weight: 800; line-height: 1; }
    .stats-label { font-size: 0.7rem; text-uppercase; font-weight: 700; color: #6c757d; letter-spacing: 0.5px; }
    
    .table-alerts thead { font-size: 0.7rem; background: #f8f9fa; }
    .badge-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 5px; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    {{-- Header Gerencial --}}
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 shadow-sm rounded border-start border-1 border-orange">
        <div>
            <h3 class="fw-bold mb-0 text-navy text-uppercase"><i class="fas fa-warehouse text-orange me-2"></i>Control Maestro de Almacén</h3>
            <p class="text-muted mb-0 small">Monitoreo de existencias, rotación y alertas de reposición.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-navy btn-sm fw-bold px-3"><i class="fas fa-plus me-1"></i> NUEVA ENTRADA</button>
            <button class="btn btn-outline-dark btn-sm fw-bold px-3"><i class="fas fa-file-export me-1"></i> INVENTARIO FÍSICO</button>
        </div>
    </div>

    {{-- KPIs de Almacén --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card card-kpi border-start border-4 border-navy shadow-sm">
                <div class="card-body">
                    <div class="stats-label">Valor Total del Inventario</div>
                    <div class="d-flex align-items-center justify-content-between mt-1">
                        <div class="stats-number text-navy">${{ number_format($valorTotal, 2) }}</div>
                        <i class="fas fa-dollar-sign fa-2x text-light"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-kpi border-start border-4 border-orange shadow-sm">
                <div class="card-body">
                    <div class="stats-label">Items bajo Stock Mínimo</div>
                    <div class="d-flex align-items-center justify-content-between mt-1">
                        <div class="stats-number text-orange">{{ $itemsBajoStock }}</div>
                        <i class="fas fa-exclamation-triangle fa-2x text-light"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-kpi border-start border-4 border-success shadow-sm">
                <div class="card-body">
                    <div class="stats-label">Despachos Pendientes</div>
                    <div class="d-flex align-items-center justify-content-between mt-1">
                        <div class="stats-number text-success">{{ $despachosPendientes }}</div>
                        <i class="fas fa-shuttle-van fa-2x text-light"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-kpi border-start border-4 border-info shadow-sm">
                <div class="card-body">
                    <div class="stats-label">Movimientos (Últ. 24h)</div>
                    <div class="d-flex align-items-center justify-content-between mt-1">
                        <div class="stats-number text-info">{{ $movimientosRecientes }}</div>
                        <i class="fas fa-exchange-alt fa-2x text-light"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Gráfica de Rotación/Movimientos --}}
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title m-0 fw-bold text-navy small text-uppercase">Histórico de Entradas vs Salidas</h5>
                </div>
                <div class="card-body">
                    <canvas id="movimientosChart" style="height: 350px;"></canvas>
                </div>
            </div>
        </div>

        {{-- Alertas de Reposición Crítica --}}
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm h-100 border-danger">
                <div class="card-header bg-danger text-white py-3">
                    <h5 class="card-title m-0 fw-bold small text-uppercase"><i class="fas fa-bell me-2"></i>Stock Crítico</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 table-alerts">
                            <thead>
                                <tr>
                                    <th class="ps-3">PRODUCTO</th>
                                    <th class="text-center">ACTUAL</th>
                                    <th class="text-center">MIN</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                @forelse($stockCritico as $item)
                                <tr>
                                    <td class="ps-3 fw-bold">{{ $item->descripcion }}</td>
                                    <td class="text-center text-danger fw-bold">{{ $item->cantidad }}</td>
                                    <td class="text-center text-muted">{{ $item->existencia_minima }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center py-4">Sin alertas críticas</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light text-center">
                    <a href="#" class="small fw-bold text-navy">Generar Orden de Compra <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Distribución por Categoría --}}
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title m-0 fw-bold text-navy small text-uppercase">Inversión por Categoría</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-7">
                            <canvas id="categoriaChart" style="height: 250px;"></canvas>
                        </div>
                        <div class="col-md-5">
                            <ul class="list-unstyled small">
                                @foreach($categorias as $cat)
                                <li class="mb-2 d-flex justify-content-between border-bottom pb-1">
                                    <span><span class="badge-dot" style="background-color: {{ $cat->color }}"></span> {{ $cat->grupo }}</span>
                                    <span class="fw-bold">{{ number_format($cat->porcentaje, 1) }}%</span>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Accesos Directos --}}
        <div class="col-lg-6 mb-4">
            <div class="row g-3">
                <div class="col-6">
                    <a href="{{ route('inventario.list') }}" class="btn btn-light border p-4 w-100 h-100 d-flex flex-column align-items-center justify-content-center shadow-sm">
                        <i class="fas fa-boxes fa-2x text-orange mb-2"></i>
                        <span class="fw-bold text-navy text-uppercase small">Maestro Artículos</span>
                    </a>
                </div>
                <div class="col-6">
                    <a href="{{ route('inventario.conteo') }}" class="btn btn-light border p-4 w-100 h-100 d-flex flex-column align-items-center justify-content-center shadow-sm">
                        <i class="fas fa-clipboard-list fa-2x text-navy mb-2"></i>
                        <span class="fw-bold text-navy text-uppercase small">Conteo de Inventario</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const colorOrange = '#ff6600';
        const colorNavy = '#002855';

        // Gráfica de Entradas vs Salidas
        const ctxMov = document.getElementById('movimientosChart').getContext('2d');
        new Chart(ctxMov, {
            type: 'line',
            data: {
                labels: @json($mesesMovimientos),
                datasets: [
                    {
                        label: 'Entradas',
                        data: @json($entradasData),
                        borderColor: colorOrange,
                        backgroundColor: 'rgba(255, 102, 0, 0.1)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Salidas',
                        data: @json($salidasData),
                        borderColor: colorNavy,
                        backgroundColor: 'transparent',
                        borderDash: [5, 5],
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });

        // Gráfica de Categorías
        const ctxCat = document.getElementById('categoriaChart').getContext('2d');
        new Chart(ctxCat, {
            type: 'doughnut',
            data: {
                labels: @json($categoriasNombres),
                datasets: [{
                    data: @json($categoriasValores),
                    backgroundColor: [colorNavy, colorOrange, '#6c757d', '#198754', '#ffc107'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                cutout: '70%',
                plugins: { legend: { display: false } }
            }
        });
    });
</script>
@endpush
@endsection