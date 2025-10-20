{{-- resources/views/reportes/index.blade.php --}}

@extends('layouts.app') 

@section('content')
<div class="container">
    <h2>Gestión de Alertas</h2>
    
    {{-- Botón para crear un nuevo reporte --}}
    <a href="{{ route('reportes.create') }}" class="btn btn-primary mb-3">
        + Nuevo Reporte
    </a>
    
    {{-- Sección de Filtros --}}
    <div class="card mb-4">
        <div class="card-header">Filtros</div>
        <div class="card-body">
            <form method="GET" action="{{ route('alertas.index') }}">
                <div class="row">
                    <div class="col-md-4">
                        <label for="estatus_filter">Estatus</label>
                        <select name="estatus_filter" class="form-control">
                            <option value="">Todos</option>
                            <option value="0" {{ request('estatus_filter') == 'ABIERTO' ? 'selected' : '' }}>Abierto</option>
                            <option value="1" {{ request('estatus_filter') == 'EN_PROCESO' ? 'selected' : '' }}>En Proceso</option>
                            <option value="2" {{ request('estatus_filter') == 'CERRADO' ? 'selected' : '' }}>Cerrado</option>
                        </select>
                    </div>
                    <div class="col-md-4 align-self-end">
                        <button type="submit" class="btn btn-secondary">Aplicar Filtro</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Tabla de Reportes (Asume que el controlador ReporteController.php llama a list() del BaseController) --}}
    <table class="table table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Tipo</th>
                <th>Lugar</th>
                <th>Estatus</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($alertas as $reporte)
                <tr>
                    <td>{{ $reporte->id }}</td>
                    <td>{{ $reporte->created_at->format('Y-m-d H:i') }}</td>
                    {{-- Usando la relación definida en el modelo --}}
                    <td>{{ $reporte->observacion ?? 'N/A' }}</td> 
                    <td>
                        {{-- Colorear el estatus para visualización rápida --}}
                        <span class="badge {{ $reporte->estatus === '0' ? 'bg-danger' : ($reporte->estatus === '1' ? 'bg-warning text-dark' : 'bg-success') }}">
                            {{ $reporte->estatus === '0' ? 'Abierto' : ($reporte->estatus === '1' ? 'En Proceso' : 'Cerrado') }}
                        </span>
                    </td>
                    <td>
                        {{-- <a href="{{ route('reportes.show', $reporte->id) }}" class="btn btn-sm btn-info">Ver Detalle</a> --}}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    {{-- Paginación Condicional --}}
@if (method_exists($data, 'links'))
    <div class="d-flex justify-content-center mt-4">
        {{ $data->links() }}
    </div>
@endif

</div>
@endsection

@push('styles')
<style>
/* Estilos básicos para badges de bootstrap 5 (si no los tiene) */
.badge.bg-warning { color: #212529 !important; } 
</style>
@endpush