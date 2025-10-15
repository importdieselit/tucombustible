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
            <a href="{{ route('viaje.list', ['status' => 'PENDIENTE_ASIGNACION']) }}" class="btn btn-sm btn-outline-warning @if(request('status') === 'PENDIENTE_ASIGNACION') active @endif">Pendiente Asignacion</a>
            <a href="{{ route('viaje.list', ['status' => 'PENDIENTE_VIATICOS']) }}" class="btn btn-sm btn-outline-warning @if(request('status') === 'PENDIENTE_VIATICOS') active @endif">Pendiente Viáticos</a>
            <a href="{{ route('viaje.list', ['status' => 'EN_CURSO']) }}" class="btn btn-sm btn-outline-info @if(request('status') === 'EN_CURSO') active @endif">En Curso</a>
            <a href="{{ route('viaje.list', ['status' => 'COMPLETADO']) }}" class="btn btn-sm btn-outline-success @if(request('status') === 'COMPLETADO') active @endif">Completado</a>
        </div>
        <a href="{{ route('viajes.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Nuevo Viaje
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Destino</th>
                    <th>Chofer</th>
                    <th>Salida</th>
                    <th>Estado</th>
                    <th>Fecha Creación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <!-- Simulando datos de viajes (Reemplazar con el loop $viajes del controlador) -->
                @php
                    // Datos de ejemplo
                    $viajes_ejemplo = [
                        (object)['id' => 5, 'destino_ciudad' => 'PLANTA PALITO', 'chofer' => (object)['name' => 'Luis Pérez'], 'fecha_salida' => 2, 'status' => 'PENDIENTE_VIATICOS', 'created_at' => now()->subDay()],
                        (object)['id' => 4, 'destino_ciudad' => 'BARQUISIMETO', 'chofer' => (object)['name' => 'Ana Rodríguez'], 'fecha_salida' => 3, 'status' => 'EN_CURSO', 'created_at' => now()->subDays(3)],
                        (object)['id' => 3, 'destino_ciudad' => 'VALENCIA', 'chofer' => (object)['name' => 'Luis Pérez'], 'fecha_salida' => 1, 'status' => 'COMPLETADO', 'created_at' => now()->subWeeks(1)],
                    ];
                    
                    // Aplicar el filtro de ejemplo para simular la vista
                    if (request('status')) {
                        $viajes_ejemplo = array_filter($viajes_ejemplo, fn($v) => $v->status === request('status'));
                        //$viajes = array_filter($viajes, fn($v) => $v->status === request('status'));
                    }
                @endphp
                
                @forelse ($viajes_ejemplo as $viaje)
                <tr>
                    <td>{{ $viaje->id }}</td>
                    <td>{{ $viaje->destino_ciudad }}</td>
                    <td>{{ $viaje->chofer->name }}</td>
                    <td>{{ $viaje->fecha_salida }}</td>
                    <td>
                        @if($viaje->status == 'PENDIENTE_VIATICOS')
                            <span class="badge bg-warning text-dark">Pendiente Viáticos</span>
                        @elseif($viaje->status == 'EN_CURSO')
                            <span class="badge bg-info">En Curso</span>
                        @elseif($viaje->status == 'COMPLETADO')
                            <span class="badge bg-success">Completado</span>
                        @else
                            <span class="badge bg-secondary">{{ $viaje->status }}</span>
                        @endif
                    </td>
                    <td>{{ $viaje->created_at->format('d/m/Y') }}</td>
                    <td>
                        @if($viaje->status == 'PENDIENTE_VIATICOS')
                            <!-- Botón de acceso directo para el Coordinador Administrativo -->
                            <a href="{{ route('viajes.viaticos.edit', $viaje->id) }}" class="btn btn-sm btn-warning" title="Revisar Viáticos">
                                <i class="bi bi-pencil-square"></i> Editar Viáticos
                            </a>
                        @else
                            <a href="{{ route('viajes.show', $viaje->id) }}" class="btn btn-sm btn-secondary" title="Ver Detalles">
                                <i class="bi bi-eye"></i> Ver
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
