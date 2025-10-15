@extends('layouts.app')

@section('title', 'Dashboard de Gestión de Viajes')

@section('content')
<div class="container-fluid mt-4">
    <h1 class="mb-4 text-primary">
        <i class="bi bi-compass-fill me-3"></i> 
        Centro de Gestión Logística y Viáticos
    </h1>

    <p class="lead text-muted">Bienvenido al panel central. Aquí puede administrar la planificación, asignación, viáticos y reportes de todos los viajes.</p>
    
    <hr>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        
        <!-- Tarjeta 1: Planificación y Asignación (Crear Nuevo Viaje) -->
        <div class="col">
            <div class="card h-100 shadow border-start border-4 border-success">
                <div class="card-body">
                    <h5 class="card-title text-success"><i class="bi bi-calendar-plus me-2"></i> Planificación Rápida</h5>
                    <p class="card-text">Inicia un nuevo viaje, define el destino y los días estimados. El sistema generará el cuadro de viáticos automáticamente.</p>
                    <a href="{{ route('viajes.create') }}" class="btn btn-success w-100">Crear y Asignar Viaje</a>
                </div>
            </div>
        </div>

        <!-- Tarjeta 2: Edición y Aprobación de Viáticos -->
        <div class="col">
            <div class="card h-100 shadow border-start border-4 border-warning">
                <div class="card-body">
                    <h5 class="card-title text-warning"><i class="bi bi-cash-stack me-2"></i> Cuadro de Viáticos</h5>
                    <p class="card-text">Revisa, ajusta y aprueba los montos de viáticos generados. Solo para viajes en estado **PENDIENTE_VIATICOS**.</p>
                    <!-- Esta ruta debe listar los viajes PENDIENTES, aquí apuntamos al índice de viajes -->
                    <a href="{{ route('viajes.index') }}?status=PENDIENTE_VIATICOS" class="btn btn-warning w-100">Revisar Viáticos</a>
                </div>
            </div>
        </div>

        <!-- Tarjeta 3: Historial y Trazabilidad -->
        <div class="col">
            <div class="card h-100 shadow border-start border-4 border-info">
                <div class="card-body">
                    <h5 class="card-title text-info"><i class="bi bi-clock-history me-2"></i> Historial de Viajes</h5>
                    <p class="card-text">Consulta todos los viajes realizados, en curso y pendientes. Filtra por chofer, vehículo o fecha.</p>
                    <a href="{{ route('viajes.index') }}" class="btn btn-info w-100">Ver Historial Completo</a>
                </div>
            </div>
        </div>
        
        <!-- Tarjeta 4: Reportes y Métricas -->
        <div class="col">
            <div class="card h-100 shadow border-start border-4 border-secondary">
                <div class="card-body">
                    <h5 class="card-title text-secondary"><i class="bi bi-graph-up me-2"></i> Reportes y Costos</h5>
                    <p class="card-text">Genera reportes de costos de viáticos, rendimiento por ruta y eficiencia de vehículos y choferes.</p>
                    <a href="{{ route('reportes.viajes') }}" class="btn btn-secondary w-100 disabled" aria-disabled="true">Generar Reporte (WIP)</a>
                </div>
            </div>
        </div>

         <!-- Tarjeta 5: Ver Tabulador de Viaticos -->
        <div class="col">
            <div class="card h-100 shadow border-start border-4 border-secondary">
                <div class="card-body">
                    <h5 class="card-title text-secondary"><i class="bi bi-graph-up me-2"></i> Tabulador de Viaticos</h5>
                    <p class="card-text">Consultar y editar la tabla de viáticos por viaje.</p>
                    <a href="{{ route('viajes.tabulador') }}" class="btn btn-primary w-100 " aria-disabled="true">Ver Tabulador</a>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
