@extends('layouts.app')
@section('title', 'Logística y Planificación de Despachos')

@section('content')
<div class="container-fluid py-4 px-4">

    <div class="mb-4 d-flex justify-content-between align-items-center">
        <h2 class="h4 font-weight-bold text-gray-800 text-uppercase mb-0">
            <i class="fas fa-route text-primary mr-2"></i> Centro de Logística y Despacho
        </h2>
        <a href="{{ route('logistica.create') }}" class="btn btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Nueva Planificación
        </a>
    </div>

    {{-- BARRA DE FILTROS --}}
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body bg-light rounded">
            <form action="{{ route('logistica.index') }}" method="GET" class="form-inline">
                <div class="form-group mr-3">
                    <label class="small font-weight-bold text-uppercase text-muted mr-2">Estatus</label>
                    <select name="estado" class="form-control form-control-sm">
                        <option value="">TODOS</option>
                        <option value="PROGRAMADO" {{ request('estado') == 'PROGRAMADO' ? 'selected' : '' }}>Programado</option>
                        <option value="EN RUTA" {{ request('estado') == 'EN RUTA' ? 'selected' : '' }}>En Ruta</option>
                        <option value="COMPLETADO" {{ request('estado') == 'COMPLETADO' ? 'selected' : '' }}>Completado</option>
                    </select>
                </div>

                <div class="form-group mr-3">
                    <label class="small font-weight-bold text-uppercase text-muted mr-2">Fecha</label>
                    <input type="date" name="fecha" value="{{ request('fecha') }}" class="form-control form-control-sm">
                </div>

                <button type="submit" class="btn btn-dark btn-sm font-weight-bold text-uppercase mr-2">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                <a href="{{ route('logistica.index') }}" class="text-muted small font-weight-bold underline">Limpiar</a>
            </form>
        </div>
    </div>

    {{-- TABLA DE VIAJES / PLANIFICACIONES --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr class="text-uppercase small">
                            <th># Viaje</th>
                            <th>Fecha Salida</th>
                            <th>Combustible</th>
                            <th>Transporte</th>
                            <th>Litros Totales</th>
                            <th>Destinos</th>
                            <th>Estatus</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($viajes as $viaje)
                            <tr>
                                <td class="align-middle font-weight-bold text-primary">V-{{ str_pad($viaje->id, 5, '0', STR_PAD_LEFT) }}</td>
                                <td class="align-middle">{{ \Carbon\Carbon::parse($viaje->fecha_salida)->format('d/m/Y') }}</td>
                                <td class="align-middle">
                                    <span class="badge badge-info">{{ $viaje->tipoCombustible->nombre ?? 'N/A' }}</span>
                                </td>
                                <td class="align-middle">
                                    @if($viaje->es_transporte_externo)
                                        <span class="text-muted"><i class="fas fa-truck"></i> Externo: {{ $viaje->vehiculo_externo }}</span>
                                    @else
                                        <strong><i class="fas fa-truck-moving text-success"></i> {{ $viaje->vehiculo->placa ?? 'Sin Asignar' }}</strong>
                                    @endif
                                </td>
                                <td class="align-middle font-weight-bold">{{ number_format($viaje->litros_totales) }} L</td>
                                <td class="align-middle text-center">
                                    <span class="badge badge-secondary">{{ $viaje->detalles->count() }}</span>
                                </td>
                                <td class="align-middle">
                                    @php
                                        $color = match($viaje->status) {
                                            'PROGRAMADO' => 'warning',
                                            'EN RUTA' => 'primary',
                                            'COMPLETADO' => 'success',
                                            'CANCELADO' => 'danger',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <span class="badge badge-{{ $color }} text-uppercase">{{ $viaje->status }}</span>
                                </td>
                                <td class="align-middle text-center">
                                    {{-- Aquí irán los botones para ver detalles o imprimir la guía --}}
                                    <button class="btn btn-sm btn-outline-primary" title="Ver Detalles">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" title="Imprimir Guía de Despacho">
                                        <i class="fas fa-file-pdf"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fas fa-clipboard-list fa-3x mb-3 d-block"></i>
                                    No hay planificaciones de viajes registradas con estos filtros.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            {{ $viajes->links() }}
        </div>
    </div>
</div>
@endsection