@extends('layouts.app')

@section('title', 'Detalles del Ítem')

@push('styles')
    <!-- CSS de Highcharts -->
    <link rel="stylesheet" href="https://code.highcharts.com/css/highcharts.css">
@endpush

@section('content')
<div class="row page-titles mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <h3 class="text-themecolor mb-0">Detalles de {{ $item->name }}</h3>
        <div>
            <a href="{{ route('inventario.edit', $item->id) }}" class="btn btn-warning d-flex align-items-center me-2">
                <i class="bi bi-pencil me-1"></i> Editar Ítem
            </a>
            <a href="{{ route('inventario.list') }}" class="btn btn-info d-flex align-items-center">
                <i class="bi bi-list me-1"></i> Ver Listado
            </a>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h5 class="card-title">Información del Ítem</h5>
                <p><strong>ID de Ítem:</strong> {{ $item->codigo ?? 'N/A' }}</p>
                <p><strong>Nombre:</strong> {{ $item->descripcion ?? 'N/A' }}</p>
                <p><strong>Categoría:</strong> {{ $item->grupo ?? 'N/A' }}</p>
                <p><strong>Almacén:</strong> {{ $item->almacen->nombre ?? 'N/A' }}</p>
            </div>
            <div class="col-md-6">
                <h5 class="card-title">Existencia y Estado</h5>
                <p>
                    <strong>Existencia Actual:</strong> {{ number_format($item->existencia ?? 0, 0, ',', '.') }}
                    @if ($item->existencia < $item->existencia_minima)
                        <span class="badge bg-danger ms-2">Bajo Stock</span>
                    @else
                        <span class="badge bg-success ms-2">Stock Óptimo</span>
                    @endif
                </p>
                <p><strong>Existencia Mínima:</strong> {{ number_format($item->existencia_minima ?? 0, 0, ',', '.') }}</p>
                <p><strong>Fecha de Registro:</strong> {{ !is_null($item->created_at)?$item->created_at->format('d/m/Y'): 'N/A' }}</p>
            </div>
        </div>
        
        <hr>

        <h5 class="card-title">Opciones de Inventario</h5>
        <div class="d-flex justify-content-start flex-wrap gap-2 mb-4">
            <a href="{{ route('inventario.entry', $item->id) }}" class="btn btn-success d-flex align-items-center">
                <i class="bi bi-plus-circle me-1"></i> Registrar Entrada
            </a>
            <a href="{{ route('inventario.adjustment', $item->id) }}" class="btn btn-warning text-dark d-flex align-items-center">
                <i class="bi bi-sliders me-1"></i> Registrar Ajuste
            </a>
        </div>
    </div>
</div>

{{-- Sección de Gráficos --}}
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title m-0">Histórico de Stock (Últimos 30 días)</h5>
            </div>
            <div class="card-body">
                <div id="stock-history-chart"></div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title m-0">Variación de Costo (Últimas Compras)</h5>
            </div>
            <div class="card-body">
                <div id="cost-variation-chart"></div>
            </div>
        </div>
    </div>
</div>
{{-- Tabla de Movimientos del Ítem --}}
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="card-title m-0">Historial de Movimientos</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Cantidad</th>
                        <th>Referencia</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Aquí iría un bucle para mostrar los movimientos, por ejemplo: --}}
                    {{-- @foreach($item->movimientos as $movimiento)
                    <tr>
                        <td>{{ $movimiento->created_at->format('d/m/Y') }}</td>
                        <td>
                            @if ($movimiento->tipo == 'entrada')
                                <span class="badge bg-success">Entrada</span>
                            @elseif ($movimiento->tipo == 'salida')
                                <span class="badge bg-danger">Salida</span>
                            @else
                                <span class="badge bg-secondary">Ajuste</span>
                            @endif
                        </td>
                        <td>{{ $movimiento->cantidad }}</td>
                        <td>{{ $movimiento->referencia ?? 'N/A' }}</td>
                    </tr>
                    @endforeach --}}
                    <tr>
                        <td>25/08/2025</td>
                        <td><span class="badge bg-success">Entrada</span></td>
                        <td>+10</td>
                        <td>Orden de Compra #1234</td>
                    </tr>
                    <tr>
                        <td>20/08/2025</td>
                        <td><span class="badge bg-danger">Salida</span></td>
                        <td>-2</td>
                        <td>Orden de Trabajo #5678</td>
                    </tr>
                    <tr>
                        <td>15/08/2025</td>
                        <td><span class="badge bg-secondary">Ajuste</span></td>
                        <td>-1 (Daño)</td>
                        <td>Ajuste de inventario</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <!-- Script de Highcharts -->
    <script src="https://code.highcharts.com/highcharts.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Ejemplo de datos, tu controlador de Laravel debe pasar esto
            // Formato esperado para el histórico de stock:
            // const stockHistoryData = {
            //     dates: ['2025-07-26', '2025-08-01', '2025-08-10', '2025-08-20', '2025-08-26'],
            //     stock: [100, 110, 95, 105, 98]
            // };
            const stockHistoryData = @json($stock_history ?? ['dates' => [], 'stock' => []]);
            
            // Gráfico Histórico de Stock
            Highcharts.chart('stock-history-chart', {
                chart: {
                    type: 'line',
                    zoomType: 'x'
                },
                title: {
                    text: 'Stock Diario'
                },
                xAxis: {
                    categories: stockHistoryData.dates,
                    title: {
                        text: 'Fecha'
                    }
                },
                yAxis: {
                    title: {
                        text: 'Cantidad en Stock'
                    }
                },
                credits: {
                    enabled: false
                },
                series: [{
                    name: 'Stock',
                    data: stockHistoryData.stock
                }]
            });
            
            // Formato esperado para la variación de costo:
            // const costVariationData = [{
            //     name: 'Compra 1',
            //     y: 15.50
            // }, {
            //     name: 'Compra 2',
            //     y: 16.00
            // }, {
            //     name: 'Compra 3',
            //     y: 15.75
            // }];
            const costVariationData = @json($cost_history ?? []);

            // Gráfico de Variación de Costo
            Highcharts.chart('cost-variation-chart', {
                chart: {
                    type: 'line',
                    zoomType: 'x'
                },
                title: {
                    text: 'Variación de Costo'
                },
                xAxis: {
                    categories: costVariationData.map(d => d.date),
                    title: {
                        text: 'Fecha de Compra'
                    }
                },
                yAxis: {
                    title: {
                        text: 'Costo ($)'
                    }
                },
                tooltip: {
                    formatter: function() {
                        return '<b>' + this.x + '</b><br/>' + 'Costo: $' + this.y.toFixed(2);
                    }
                },
                credits: {
                    enabled: false
                },
                series: [{
                    name: 'Costo',
                    data: costVariationData.map(d => d.cost)
                }]
            });
        });
    </script>
@endpush
