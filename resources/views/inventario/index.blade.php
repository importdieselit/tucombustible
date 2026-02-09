@extends('layouts.app')

@section('title', 'Dashboard de Inventario')

@section('content')
<div class="container-fluid mt-4">
    <div class="row page-titles mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h3 class="text-themecolor mb-0">Dashboard de Inventario</h3>
            <a href="{{ route('inventario.list') }}" class="btn btn-info d-flex align-items-center">
                <i class="fs fa-list me-2"></i> Ver Listado
            </a>
        </div>
    </div>

    {{-- Primera fila de métricas --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total de Ítems</h5>
                    <h2 class="card-text" id="total-items"></h2>
                    <p class="card-text"><small>Unidades de stock únicas</small></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Cantidad Total</h5>
                    <h2 class="card-text" id="total-quantity"></h2>
                    <p class="card-text"><small>Existencia total en almacenes</small></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Valor de Inventario</h5>
                    <h2 class="card-text" id="total-inventory-value"></h2>
                    <p class="card-text"><small>Costo total de tu stock</small></p>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Fila de gráficos y tabla --}}
    <div class="row mb-4">
        {{-- Gráfico de valor de inventario --}}
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Valor de Inventario Mensual</h5>
                </div>
                <div class="card-body">
                    <div id="monthly-value-chart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
        
        {{-- Gráfico de stock por categoría --}}
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Existencia por Categoría</h5>
                </div>
                <div class="card-body">
                    <div id="category-stock-chart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla de ítems con bajo stock --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Ítems con Bajo Stock</h5>
                </div>
                <div class="card-body">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th scope="col">ID de Ítem</th>
                                <th scope="col">Nombre</th>
                                <th scope="col">Existencia Actual</th>
                                <th scope="col">Existencia Mínima</th>
                                <th scope="col">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="low-stock-table-body">
                            {{-- Los datos se insertarán aquí con JavaScript --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.highcharts.com/highcharts.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Datos de prueba para simular la información del backend
        const mockData = {
            metrics: {
                totalItems: 450,
                totalQuantity: 12500,
                totalInventoryValue: '€185,500'
            },
            monthlyInventoryValue: [
                { name: 'Ene', y: 120000 },
                { name: 'Feb', y: 125000 },
                { name: 'Mar', y: 130000 },
                { name: 'Abr', y: 128000 },
                { name: 'May', y: 135000 },
                { name: 'Jun', y: 140000 },
                { name: 'Jul', y: 138000 },
                { name: 'Ago', y: 145000 },
                { name: 'Sep', y: 150000 },
                { name: 'Oct', y: 148000 },
                { name: 'Nov', y: 155000 },
                { name: 'Dic', y: 160000 }
            ],
            categoryStock: [
                { name: 'Electrónica', y: 800 },
                { name: 'Maquinaria', y: 500 },
                { name: 'Consumibles', y: 1200 },
                { name: 'Herramientas', y: 450 },
                { name: 'Materiales', y: 900 },
                { name: 'Repuestos', y: 750 },
            ],
            lowStockItems: [
                { itemId: '#INV451', name: 'Aceite de Motor', currentStock: 12, minStock: 20 },
                { itemId: '#INV223', name: 'Filtro de Aire', currentStock: 5, minStock: 10 },
                { itemId: '#INV789', name: 'Neumático Industrial', currentStock: 2, minStock: 5 },
                { itemId: '#INV115', name: 'Válvula Hidráulica', currentStock: 8, minStock: 15 },
                { itemId: '#INV990', name: 'Batería de Respaldo', currentStock: 7, minStock: 10 },
            ]
        };

        // Rellenar las tarjetas de métricas
        document.getElementById('total-items').textContent = mockData.metrics.totalItems;
        document.getElementById('total-quantity').textContent = mockData.metrics.totalQuantity;
        document.getElementById('total-inventory-value').textContent = mockData.metrics.totalInventoryValue;


        // Configurar y renderizar el gráfico de valor de inventario
        Highcharts.chart('monthly-value-chart', {
            chart: {
                type: 'line'
            },
            title: {
                text: ''
            },
            xAxis: {
                categories: mockData.monthlyInventoryValue.map(item => item.name)
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Valor (€)'
                }
            },
            tooltip: {
                formatter: function () {
                    return '<b>' + this.x + '</b><br/>' +
                        'Valor: $' + Highcharts.numberFormat(this.y, 2);
                }
            },
            credits: {
                enabled: false
            },
            series: [{
                name: 'Valor',
                data: mockData.monthlyInventoryValue.map(item => item.y)
            }]
        });

        // Configurar y renderizar el gráfico de stock por categoría
        Highcharts.chart('category-stock-chart', {
            chart: {
                type: 'pie',
                plotBackgroundColor: null,
                plotBorderWidth: null,
                plotShadow: false,
            },
            title: {
                text: ''
            },
            tooltip: {
                pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
            },
            accessibility: {
                point: {
                    valueSuffix: '%'
                }
            },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: true,
                        format: '<b>{point.name}</b>: {point.percentage:.1f} %'
                    }
                }
            },
            credits: {
                enabled: false
            },
            series: [{
                name: 'Stock',
                colorByPoint: true,
                data: mockData.categoryStock
            }]
        });
        
        // Rellenar la tabla de ítems con bajo stock
        const tableBody = document.getElementById('low-stock-table-body');
        mockData.lowStockItems.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.itemId}</td>
                <td>${item.name}</td>
                <td>${item.currentStock}</td>
                <td>${item.minStock}</td>
                <td><span class="badge bg-danger">Bajo Stock</span></td>
            `;
            tableBody.appendChild(row);
        });
    });
</script>
@endpush
