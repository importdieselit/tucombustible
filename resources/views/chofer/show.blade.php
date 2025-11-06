@extends('layouts.app')

@section('title', 'Detalle de Chofer')

@section('content')
<div class="container-fluid mt-4">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <h3 class="text-themecolor">Detalle de Chofer</h3>
        </div>
        <div class="col-md-6 align-self-center">
            <div class="d-flex justify-content-end">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('choferes.index') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('choferes.list') }}">Listado</a></li>
                    <li class="breadcrumb-item active">Detalle</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-5 col-md-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title m-0">Información del Chofer</h5>
                    <a href="{{ route('choferes.edit', $chofer->id) }}" class="btn btn-warning text-white btn-sm" title="Editar">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column align-items-center text-center">
                        @if(is_null($chofer->foto))
                            <i class="fas fa-user-circle fa-8x text-secondary mb-3"></i>
                        @else
                            <img src="{{ asset('storage/choferes/foto/' . $chofer->foto) }}" class="text-secondary mb-3 round" style="border-radius: 50%; height: 250px;" alt="foto {{ $chofer->persona->nombre }}">
                        @endif
                        <h4 class="mb-0">{{ $chofer->persona->nombre }}</h4>
                        <p class="text-muted">{{ $chofer->persona->dni }}</p>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted">Licencia No.</h6>
                            <p class="font-weight-bold">{{ $chofer->licencia_numero }}</p>
                            <h6 class="text-muted">Vencimiento Licencia</h6>
                            <p class="font-weight-bold">
                                <span class="badge {{ $chofer->licenciaVencida() ? 'bg-danger' : ($chofer->licenciaPorVencer() ? 'bg-warning' : 'bg-success') }}">
                                    {{ $chofer->licencia_vencimiento }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Documento Vialidad No.</h6>
                            <p class="font-weight-bold">{{ $chofer->documento_vialidad_numero ?? 'N/A' }}</p>
                            <h6 class="text-muted">Vencimiento Doc. Vialidad</h6>
                            <p class="font-weight-bold">{{ $chofer->documento_vialidad_vencimiento ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <h6 class="text-muted mt-3">Vehículo Asignado</h6>
                    <p class="font-weight-bold">{{ $chofer->vehiculo ? $chofer->vehiculo->placa . ' - ' . $chofer->vehiculo->marca : 'No asignado' }}</p>
                </div>
            </div>
        </div>

        <div class="col-lg-7 col-md-12 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title m-0">Historial de Viajes</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Ruta</th>
                                    <th>Fecha</th>
                                    <th>Incidencias</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($historialViajes as $viaje)
                                    <tr>
                                        <td>{{ $viaje['ruta'] }}</td>
                                        <td>{{ $viaje['fecha'] }}</td>
                                        <td>{{ $viaje['incidencias'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h5 class="card-title m-0">Rendimiento Histórico</h5>
                </div>
                <div class="card-body">
                    <canvas id="rendimientoHistoricoChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const graficaRendimiento = @json($graficaRendimiento);
        const ctx = document.getElementById('rendimientoHistoricoChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: graficaRendimiento.labels,
                datasets: [{
                    label: 'Calificación',
                    data: graficaRendimiento.data,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    fill: true,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 5,
                        title: {
                            display: true,
                            text: 'Puntuación (1-5 Estrellas)'
                        }
                    }
                }
            }
        });
    });
</script>
@endpush
@endsection
