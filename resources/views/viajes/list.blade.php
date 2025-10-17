@extends('layouts.app')

@section('title', 'Historial y Listado de Viajes')

@section('content')
<div class="container-fluid mt-4">
    <h1 class="mb-4 text-info"><i class="bi bi-clock-history me-3"></i> Historial de Viajes</h1>
    
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Filtro por Estatus -->
    <div class="mb-3 d-flex justify-content-between align-items-center">
        <div>
            <span class="fw-bold me-2">Filtrar por Estatus:</span>
            <a href="{{ route('viaje.list') }}" class="btn btn-sm btn-outline-primary @unless(request('status')) active @endunless">Todos</a>
            <a href="{{ route('viaje.list', ['status' => 'PENDIENTE_ASIGNACION']) }}" class="btn btn-sm btn-outline-danger @if(request('status') === 'PENDIENTE_ASIGNACION') active @endif">Pendiente Asignacion</a>
            <a href="{{ route('viaje.list', ['status' => 'PENDIENTE_VIATICOS']) }}" class="btn btn-sm btn-outline-warning @if(request('status') === 'PENDIENTE_VIATICOS') active @endif">Pendiente Viáticos</a>
            <a href="{{ route('viaje.list', ['status' => 'ASIGNADO']) }}" class="btn btn-sm btn-outline-info @if(request('status') === 'ASIGNADO') active @endif">Asignado</a>
            <a href="{{ route('viaje.list', ['status' => 'COMPLETADO']) }}" class="btn btn-sm btn-outline-success @if(request('status') === 'COMPLETADO') active @endif">Completado</a>
        </div>
        <a href="{{ route('viajes.index') }}" class="btn btn-info shadow-sm">
            <i class="fa fa-home"></i> Volver a Inicio
        </a>
        <a href="{{ route('viajes.create') }}" class="btn btn-info shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> Nuevo Viaje
        </a>
    </div>

    <!-- Tabla de Viajes -->
    <div class="table-responsive">
        <table class="table table-hover table-striped shadow-sm">
            <thead class="bg-info text-white">
                <tr>
                    <th>#</th>
                    <th>Destino</th>
                    <th>Fecha Salida</th>
                    <th>Chofer</th>
                    <th>Vehículo</th>
                    <th>Estatus</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($viajes as $viaje)
                @if(!is_null($viaje->chofer_id))
                    @php($chofer=  \App\Models\Chofer::find($viaje->chofer_id))
                    @php($persona=  \App\Models\Persona::find($chofer->persona_id))
                    
                     @php($viaje->chofer = $persona ? $persona->nombre : 'sin asignar')
                @else
                    @php($viaje->chofer = 'sin asignar')
                @endif
                 @php($viaje->vehiculo = $viaje->vehiculo_id ? \App\Models\Vehiculo::find($viaje->vehiculo_id)->placa : 'N/A')
                <tr>
                    <td>{{ $viaje->id }}</td>
                    <td>[{{ $viaje->destino_ciudad }}] {{ $viaje->cliente?$viaje->cliente->nombre:($viaje->otro_cliente ??'N/A')}}</td>
                    <td>{{ $viaje->fecha_salida }}</td>
                    <td>{{ $viaje->chofer }}</td>
                    
                    <!-- Usando la nueva relación vehiculo -->
                    <td>{{ $viaje->vehiculo }}</td> 
                    <td>
                        <span class="badge 
                            @if($viaje->status == 'PENDIENTE_ASIGNACION') bg-danger 
                            @elseif($viaje->status == 'PENDIENTE_VIATICOS') bg-warning 
                            @elseif($viaje->status == 'ASIGNADO') bg-info
                            @elseif($viaje->status == 'COMPLETADO') bg-success 
                            @else bg-secondary
                            @endif">
                            {{ str_replace('_', ' ', $viaje->status) }}
                        </span>
                    </td>
                    <td>
                         @if($viaje->status == 'PENDIENTE_VIATICOS')
                            <!-- Botón de acceso directo para el Coordinador Administrativo -->
                            <a href="{{ route('viajes.viaticos.edit', $viaje->id) }}" class="btn btn-sm btn-warning" title="Revisar Viáticos">
                                <i class="bi bi-currency-dollar"></i> Generar Viáticos
                            </a>
                        @elseif($viaje->status == 'PENDIENTE_ASIGNACION')
                            <!-- Botón para el usuario de Asignación -->
                            <a href="{{ route('viajes.assign', $viaje->id) }}" class="btn btn-sm btn-danger" title="Asignar Recursos">
                                <i class="fa fa-truck"></i> Asignar Recursos
                            </a>
                        @else
                            <a href="{{ route('viajes.show', $viaje->id) }}" class="btn btn-sm btn-secondary" title="Ver Viaje">
                                <i class="fa fa-eye"></i> Ver Detalles
                            </a>
                            <a href="{{ route('viajes.resumenProgramacion', $viaje->id) }}" class="btn btn-sm btn-info" title="Resumen de Programación">
                                <i class="fa fa-journal-text"></i> Resumen
                            </a>
                        @endif
                        
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted">No se encontraron viajes con el filtro aplicado.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- En un proyecto real, se colocaría la paginación aquí -->
    {{-- $viajes->links() --}}
</div>
@endsection
