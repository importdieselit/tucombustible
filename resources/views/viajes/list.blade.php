@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" style="background-color: #f4f6f9; min-height: 100vh;">
    
    {{-- Encabezado Estilo Dashboard --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-navy"><i class="bi bi-clock-history me-2"></i>Historial de Operaciones</h2>
            <p class="text-muted">Gestión de logística y control de despachos</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('viajes.index') }}" class="btn btn-outline-dark shadow-sm">
                <i class="fa fa-home"></i>
            </a>
            <a href="{{ route('viajes.create') }}" class="btn btn-orange shadow-sm px-4 text-white fw-bold">
                <i class="bi bi-plus-circle me-1"></i> NUEVA CARGA / DESPACHO
            </a>
        </div>
    </div>

    {{-- Alertas Mejoradas --}}
    @if(session('success'))
        <div class="alert alert-success border-start border-4 border-success shadow-sm d-flex align-items-center">
            <i class="bi bi-check-circle-fill me-2 fs-4"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    {{-- Filtros Rápidos --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <span class="small fw-bold text-uppercase text-muted me-3">Filtrar Estatus:</span>
            <div class="btn-group shadow-sm">
                <a href="{{ route('viajes.list') }}" class="btn btn-sm btn-light border {{ !request('status') ? 'active fw-bold' : '' }}">Todos</a>
                <a href="{{ route('viajes.list', ['status' => 'PENDIENTE_ASIGNACION']) }}" class="btn btn-sm btn-light border {{ request('status') === 'PENDIENTE_ASIGNACION' ? 'active bg-danger text-white' : '' }}">Pendientes</a>
                <a href="{{ route('viajes.list', ['status' => 'COMPLETADO']) }}" class="btn btn-sm btn-light border {{ request('status') === 'COMPLETADO' ? 'active bg-success text-white' : '' }}">Completados</a>
            </div>
        </div>
    </div>

    {{-- Tabla Corporativa --}}
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-navy text-white">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Operación / Destino</th>
                        <th>Fecha</th>
                        <th>Personal / Vehículo</th>
                        <th>Estatus</th>
                        <th class="text-end pe-4">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($viajes as $viaje)
                    @php
                        // Determinamos el color lateral según sea Flete, Carga o Despacho
                        $esFlete = str_contains(strtoupper($viaje->destino_ciudad), 'FLETE');
                        $esDespacho = is_null($viaje->litros);
                        $lineColor = $esFlete ? '#6f42c1' : ($esDespacho ? '#28a745' : '#17a2b8');
                    @endphp
                    <tr style="border-left: 5px solid {{ $lineColor }};">
                        <td class="ps-4 fw-bold">#{{ $viaje->id }}</td>
                        <td>
                            <div class="fw-bold text-dark">{{ str_ireplace(['FLETE', '->'], ['', '»'], $viaje->destino_ciudad) }}</div>
                            <small class="text-muted">{{ $viaje->cliente->nombre ?? $viaje->otro_cliente ?? 'N/A' }}</small>
                        </td>
                        <td>
                            <div class="small fw-bold">{{ \Carbon\Carbon::parse($viaje->fecha_salida)->format('d/m/Y') }}</div>
                            <div class="text-muted small">{{ \Carbon\Carbon::parse($viaje->fecha_salida)->format('g:i A') }}</div>
                        </td>
                        <td>
                            <div class="small"><i class="fa fa-user-circle me-1"></i> {{ $viaje->chofer->persona->nombre ?? $viaje->otro_chofer }}</div>
                            <div class="small fw-bold"><i class="fa fa-truck me-1"></i> {{ $viaje->vehiculo->placa ?? $viaje->otro_vehiculo }}</div>
                        </td>
                        <td>
                            <span class="badge rounded-pill px-3 
                                @if($viaje->status == 'PENDIENTE_ASIGNACION') bg-light text-danger border border-danger
                                @elseif($viaje->status == 'COMPLETADO') bg-success 
                                @else bg-info text-dark @endif">
                                {{ str_replace('_', ' ', $viaje->status) }}
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group">
                                <a href="{{ route('viajes.show', $viaje->id) }}" class="btn btn-sm btn-outline-navy" title="Ver Detalle">
                                    <i class="fa fa-eye"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="confirmarEliminacion('{{ $viaje->id }}', '{{ $viaje->destino_ciudad }}')">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                            <form id="delete-form-{{ $viaje->id }}" action="{{ route('viaje.destroy', $viaje->id) }}" method="POST" class="d-none">
                                @csrf @method('DELETE')
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">No se encontraron registros de viajes.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .text-navy { color: #1a237e; }
    .bg-navy { background-color: #1a237e; }
    .btn-orange { background-color: #ff6600; border-color: #ff6600; }
    .btn-outline-navy { color: #1a237e; border-color: #1a237e; }
    .btn-outline-navy:hover { background-color: #1a237e; color: white; }
    .fw-black { font-weight: 900; }
</style>

<script>
function confirmarEliminacion(id, destino) {
    if (confirm(`⚠️ ATENCIÓN: ¿Deseas eliminar el Viaje #${id}?\n\nDestino: ${destino}\n\nEsta acción reversará los despachos y saldos de clientes asociados de forma permanente.`)) {
        document.getElementById(`delete-form-${id}`).submit();
    }
}
</script>
@endsection