@extends('layouts.app')

@section('title', 'Panel de Control - Inspecciones')

@section('content')
<div class="container-fluid px-3">
    <div class="row py-3 mb-2 bg-white shadow-sm sticky-top d-md-none">
        <div class="col-12 text-center">
            <h5 class="fw-bold mb-0">Gestión de Flota 🛠️</h5>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <button onclick="seleccionarVehiculo()" class="btn bg-chutos w-100 shadow rounded-4 py-3 d-flex align-items-center justify-content-center gap-3 border-0 transition-scale">
                <i class="fa-solid fa-clipboard-check fa-2x text-white"></i>
                <div class="text-start text-white">
                    <span class="d-block fw-bold fs-5">Nueva Inspección</span>
                    <small class="opacity-75">Click para seleccionar unidad</small>
                </div>
            </button>
        </div>
    </div>

    <div class="row g-3 mb-4 text-center">
        <div class="col-6 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-warning bg-opacity-10 h-100">
                <div class="card-body p-3">
                    <div class="small text-muted fw-bold mb-1">ALERTAS</div>
                    <h3 class="text-warning fw-bold mb-0">{{ $resumenAlertas['warnings'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-danger bg-opacity-10 h-100">
                <div class="card-body p-3">
                    <div class="small text-muted fw-bold mb-1">OTs ABIERTAS</div>
                    <h3 class="text-danger fw-bold mb-0">{{ $resumenAlertas['ordenes_abiertas'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark">Últimas Inspecciones</h6>
            <a href="{{ route('inspecciones.list') }}" class="btn btn-light btn-sm rounded-pill text-primary fw-bold">Ver Todo</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="border-0 small fw-bold ps-4">UNIDAD</th>
                        <th class="border-0 small fw-bold text-center">ESTATUS</th>
                        <th class="border-0 small fw-bold text-end pe-4">ACCIÓN</th>
                    </tr>
                </thead>
                <tbody>
                    @if($inspeccionesRecientes)
                    @foreach($inspeccionesRecientes as $insp)
                    <tr>
                        <td class="ps-4">
                            <span class="fw-bold d-block">{{ $insp->vehiculo->flota }}</span>
                            <small class="text-muted">{{ $insp->vehiculo->placa }}</small>
                        </td>
                        <td class="text-center">
                            @php $color = $insp->estatus_general == 'OK' ? 'success' : ($insp->estatus_general == 'WARNING' ? 'warning' : 'danger'); @endphp
                            <span class="badge rounded-pill bg-{{ $color }} px-3">{{ $insp->estatus_general }}</span>
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('inspeccion.create', ['vehiculo_id' => $insp->vehiculo->id , 'tipo' => 'entrada']) }}" class="btn btn-outline-primary btn-sm rounded-circle"><i class="fa fa-eye"></i></a>
                        </td>
                    </tr>
                    @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    console.log("Input Options:", @json($vehiculosDisponibles)); // Verificar que las opciones se están pasando correctamente
    async function seleccionarVehiculo() {
        const { value: vehiculoId } = await Swal.fire({
            title: 'Seleccionar Unidad',
            input: 'select',
            inputOptions: @json($vehiculosDisponibles),
            inputPlaceholder: 'Busca por flota o placa...',
            showCancelButton: true,
            confirmButtonColor: '#ff6600',
            confirmButtonText: 'Abrir Checklist',
            cancelButtonText: 'Cancelar'
        });

    if (vehiculoId) {
        window.location.href = `/vehiculos/inspeccion/${vehiculoId}/salida`;
    }
}
</script>
@endsection