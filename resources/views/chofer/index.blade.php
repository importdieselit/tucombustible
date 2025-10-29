@extends('layouts.app')

@section('title', 'Dashboard de Choferes')

@section('content')
<div class="container-fluid mt-4">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <h3 class="text-themecolor">Dashboard de Choferes</h3>
        </div>
        <div class="col-md-6 align-self-center">
            <div class="d-flex justify-content-end">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Tarjeta de resumen de choferes -->
        <div class="col-lg-4 col-md-6">
            <div class="card shadow-sm p-4 text-center">
                <i class="fas fa-users fa-3x text-primary mb-3"></i>
                <h4 class="card-title">Total de Choferes</h4>
                <h1 class="font-weight-light">{{ $totalChoferes }}</h1>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card shadow-sm p-4 text-center">
                <i class="fas fa-truck-moving fa-3x text-success mb-3"></i>
                <h4 class="card-title">Choferes en Ruta</h4>
                <h1 class="font-weight-light">{{ $choferesEnRuta }}</h1>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card shadow-sm p-4 text-center">
                <i class="fas fa-user-check fa-3x text-info mb-3"></i>
                <h4 class="card-title">Choferes Disponibles</h4>
                <h1 class="font-weight-light">{{ $choferesDisponibles }}</h1>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Gráfica de rendimiento por estrellas -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title m-0">Rendimiento por Calificación (demo)</h5>
                </div>
                <div class="card-body">
                    <canvas id="rendimientoChart" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <!-- Gráfica de reporte de incidencias -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title m-0">Reporte de Incidencias (demo)</h5>
                </div>
                <div class="card-body">
                    <canvas id="incidenciasChart" style="height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <a href="{{ route('choferes.list') }}" class="btn btn-primary btn-lg w-100">
                Ver Listado de Choferes
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Datos de PHP convertidos a JavaScript
        const rendimientoData = @json(array_values($rendimiento));
        const rendimientoLabels = @json(array_keys($rendimiento));
        const incidenciasData = @json(array_values($incidencias));
        const incidenciasLabels = @json(array_keys($incidencias));

        // Gráfica de Rendimiento
        const ctxRendimiento = document.getElementById('rendimientoChart').getContext('2d');
        const rendimientoChart = new Chart(ctxRendimiento, {
            type: 'bar',
            data: {
                labels: rendimientoLabels,
                datasets: [{
                    label: 'Número de Choferes',
                    data: rendimientoData,
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Gráfica de Incidencias
        const ctxIncidencias = document.getElementById('incidenciasChart').getContext('2d');
        const incidenciasChart = new Chart(ctxIncidencias, {
            type: 'line',
            data: {
                labels: incidenciasLabels,
                datasets: [{
                    label: 'Incidencias por Mes',
                    data: incidenciasData,
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 2,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>
@endpush
@endsection
