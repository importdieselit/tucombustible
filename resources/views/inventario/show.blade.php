@extends('layouts.app') 
@section('content')
<div class="container-fluid py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0 text-dark fw-bold">{{ $item->codigo }} - {{ $item->descripcion }}</h3>
            <span class="text-muted"><i class="bi bi-tag"></i> {{ $item->categoria }}</span>
        </div>
        <div>
            <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer"></i> Imprimir Ficha</button>
            <button class="btn btn-primary btn-sm"><i class="bi bi-pencil"></i> Editar Ítem</button>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column justify-content-center">
                    <div class="row text-center g-3">
                        <div class="col-12">
                            <h6 class="text-muted text-uppercase mb-1">Stock Actual</h6>
                            <h2 class="fw-bold text-primary mb-0">{{ $item->stock_actual }} <span class="fs-6 text-muted">{{ $item->unidad }}</span></h2>
                        </div>
                        <hr class="my-2">
                        <div class="col-6">
                            <h6 class="text-muted mb-1" style="font-size: 0.8rem;">Tasa de Rotación</h6>
                            <h4 class="mb-0">{{ $item->tasa_rotacion }}</h4>
                        </div>
                        <div class="col-6 border-start">
                            <h6 class="text-muted mb-1" style="font-size: 0.8rem;">Duración Promedio</h6>
                            <h4 class="mb-0">{{ $item->promedio_duracion }} <span class="fs-6 text-muted">días</span></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body pb-0">
                    <h6 class="text-muted mb-3"><i class="bi bi-graph-up"></i> Historial de Existencias (Últimos 15 días)</h6>
                    <div style="height: 200px;">
                        <canvas id="stockChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 pt-4 pb-0">
            <h6 class="text-muted mb-0"><i class="bi bi-geo-alt"></i> Ubicación Física: <strong class="text-dark">{{ $item->ubicacion_texto }}</strong></h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4 text-center">
                    <p class="text-muted small mb-2">Croquis de Almacén</p>
                    <img src="{{ $item->img_croquis_almacen }}" class="img-fluid rounded border" alt="Almacen">
                </div>
                <div class="col-md-4 text-center">
                    <p class="text-muted small mb-2">Ubicación en Estante</p>
                    <img src="{{ $item->img_croquis_estante }}" class="img-fluid rounded border" alt="Estante">
                </div>
                <div class="col-md-4 text-center">
                    <p class="text-muted small mb-2">Render 3D Referencial</p>
                    <img src="{{ $item->img_render_3d }}" class="img-fluid rounded border" alt="3D">
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom pt-3 pb-0">
            <ul class="nav nav-tabs border-0" id="itemTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold text-dark border-0" id="historial-tab" data-bs-toggle="tab" data-bs-target="#historial" type="button" role="tab">Historial (Últ. 30)</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link text-muted border-0" id="sustitutos-tab" data-bs-toggle="tab" data-bs-target="#sustitutos" type="button" role="tab">Equivalentes</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link text-muted border-0" id="vehiculos-tab" data-bs-toggle="tab" data-bs-target="#vehiculos" type="button" role="tab">Vehículos Asoc.</button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="itemTabsContent">
                
                <div class="tab-pane fade show active" id="historial" role="tabpanel">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover table-sm align-middle">
                            <thead class="table-light position-sticky top-0">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tipo</th>
                                    <th>Cant.</th>
                                    <th>Documento</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($movimientos as $mov)
                                <tr>
                                    <td class="text-muted">{{ $mov->fecha }}</td>
                                    <td>
                                        <span class="badge {{ $mov->tipo == 'Entrada' ? 'bg-success' : 'bg-danger' }}">
                                            {{ $mov->tipo }}
                                        </span>
                                    </td>
                                    <td class="fw-bold {{ $mov->cantidad > 0 ? 'text-success' : 'text-danger' }}">{{ $mov->cantidad }}</td>
                                    <td>{{ $mov->documento }}</td>
                                    <td>{{ $mov->usuario }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="sustitutos" role="tabpanel">
                    <table class="table table-hover table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Descripción</th>
                                <th>Stock Actual</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($equivalentes as $eq)
                            <tr>
                                <td class="fw-bold">{{ $eq->codigo }}</td>
                                <td>{{ $eq->descripcion }}</td>
                                <td>{{ $eq->stock }} Und</td>
                                <td><a href="#" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane fade" id="vehiculos" role="tabpanel">
                    <table class="table table-hover table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Placa</th>
                                <th>Modelo</th>
                                <th>Departamento</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($vehiculos as $veh)
                            <tr>
                                <td class="fw-bold">{{ $veh->placa }}</td>
                                <td>{{ $veh->modelo }}</td>
                                <td>{{ $veh->departamento }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Control visual de las pestañas (para mantener el estilo limpio)
    const triggerTabList = document.querySelectorAll('#itemTabs button')
    triggerTabList.forEach(tab => {
        tab.addEventListener('click', () => {
            triggerTabList.forEach(t => {
                t.classList.remove('active', 'fw-bold', 'text-dark');
                t.classList.add('text-muted');
            });
            tab.classList.remove('text-muted');
            tab.classList.add('active', 'fw-bold', 'text-dark');
        })
    });

    // Inicialización de la Gráfica
    const ctx = document.getElementById('stockChart').getContext('2d');
    const stockChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($graficaFechas) !!},
            datasets: [{
                label: 'Nivel de Stock',
                data: {!! json_encode($graficaStock) !!},
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderWidth: 2,
                pointRadius: 3,
                fill: true,
                tension: 0.3 // Hace que la línea sea curva
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [2, 4] } },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>
@endpush
@endsection