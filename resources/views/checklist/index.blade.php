@extends('layouts.app')

@section('title', 'Dashboard de Inspecciones y Mantenimiento')

@section('content')
<div class="container-fluid">
    <div class="row page-titles mb-4">
        <div class="col-12">
            <h3 class="text-themecolor mb-0">Gestión de Inspecciones y Taller 🛠️</h3>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
                <li class="breadcrumb-item active">Dashboard Inspecciones</li>
            </ol>
        </div>
    </div>
    
    {{-- Asume que las variables $resumenAlertas, $inspeccionesPendientes y $ordenesAbiertas se definen en el controlador --}}

    {{-- ******* SECCIÓN 1: INDICADORES CLAVE (Kpis) ******* --}}
    <h4 class="mt-4 mb-3 text-secondary">Indicadores Rápidos</h4>
    <div class="row">
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card card-hover bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-triangle-exclamation fa-2x me-3"></i>
                        <div>
                            <h4 class="mb-0 text-white">{{ $resumenAlertas['warnings'] ?? '0' }}</h4>
                            <span class="text-white op-5">Inspecciones con WARNING</span>
                        </div>
                    </div>
                </div>
                <a href="{{ route('inspeccion.list', ['estatus' => 'ATENCION']) }}" class="small-box-footer text-white text-end p-2 d-block">Revisar <i class="fa-solid fa-arrow-circle-right"></i></a>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card card-hover bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-clipboard-list fa-2x me-3"></i>
                        <div>
                            <h4 class="mb-0 text-white">{{ $resumenAlertas['ordenes_abiertas'] ?? '0' }}</h4>
                            <span class="text-white op-5">Órdenes de Trabajo Abiertas</span>
                        </div>
                    </div>
                </div>
                <a href="{{ route('ordenes.index') }}" class="small-box-footer text-white text-end p-2 d-block">Ir a Órdenes <i class="fa-solid fa-arrow-circle-right"></i></a>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card card-hover bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <i class="fa-solid fa-truck-ramp-box fa-2x me-3"></i>
                        <div>
                            <h4 class="mb-0 text-white">{{ $resumenAlertas['vehiculos_mantenimiento'] ?? '0' }}</h4>
                            <span class="text-white op-5">Vehículos en Taller/Mantenimiento</span>
                        </div>
                    </div>
                </div>
                <a href="{{ route('vehiculos.index', ['estatus' => 2]) }}" class="small-box-footer text-white text-end p-2 d-block">Ver Vehículos <i class="fa-solid fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>
    
    ---

    {{-- ******* SECCIÓN 2: ACCESOS DIRECTOS ******* --}}
    <h4 class="mt-4 mb-3 text-secondary">Accesos Directos a Gestión</h4>
    <div class="row">
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card text-center shadow">
                <div class="card-body">
                    <i class="fa-solid fa-list-check fa-4x text-info mb-3"></i>
                    <h5 class="card-title">Listado de Inspecciones</h5>
                    <p class="card-text text-muted">Revisa el historial y el estatus general de todos los Checklists.</p>
                    <a href="{{ route('inspeccion.list') }}" class="btn btn-info w-100">Ver Todas las Inspecciones</a>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card text-center shadow">
                <div class="card-body">
                    <i class="fa-solid fa-truck fa-4x text-success mb-3"></i>
                    <h5 class="card-title">Gestión de Vehículos</h5>
                    <p class="card-text text-muted">Accede a la lista de vehículos para crear una nueva Orden de Trabajo.</p>
                    <a href="{{ route('vehiculos.list') }}" class="btn btn-success w-100">Ir a Vehículos</a>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card text-center shadow">
                <div class="card-body">
                    <i class="fa-solid fa-file-invoice fa-4x text-warning mb-3"></i>
                    <h5 class="card-title">Órdenes de Trabajo (OT)</h5>
                    <p class="card-text text-muted">Revisa, edita y cierra las órdenes de trabajo de mantenimiento.</p>
                    <a href="{{ route('ordenes.list') ?? '#' }}" class="btn btn-warning w-100">Gestionar Órdenes</a>
                </div>
            </div>
        </div>
    </div>
    <div class="text-center my-5">
            {{-- <button class="btn btn-primary-custom btn-lg rounded-pill px-4 py-2 shadow-lg fs-5" data-bs-toggle="modal" data-bs-target="#hacerPedidoModal">
                <i class="fas fa-plus-circle me-2"></i> Hacer Pedido
            </button> --}}
            @if(Auth::user()->canAccess('create', 6))
            <button class="btn btn-warning btn-lg rounded-pill px-4 py-2 shadow-lg fs-5" id="btn-inspeccion-salida">
                <i class="fa fa-clipboard-check me-2"></i> Checkout Vehículo
            </button>
            @endif
            
        </div>
    
</div>
@endsection

@push('scripts')
<script>
    
        document.addEventListener('DOMContentLoaded', function () {
            const btnInspeccion = document.getElementById('btn-inspeccion-salida');
            if (btnInspeccion) {
                btnInspeccion.addEventListener('click', mostrarSelectorVehiculoParaInspeccion);
            }
        });

        function getTipoVehiculoString(tipoId) {
    // Mapeo de tipos de vehículo según tu solicitud
    const tipoMap = {
        1: 'camion sencillo',
        2: 'cisterna',
        3: 'chuto',
    };
    // Devuelve el tipo mapeado o 'otro' por defecto
    return tipoMap[tipoId] || 'otro';
}

async function mostrarSelectorVehiculoParaInspeccion() {
    // 💡 NOTA: En un sistema real, esta data debería venir de un endpoint API real:
    // fetch('/api/vehiculos/activos').then(res => res.json())
    const vehiculosActivos = @json($vehiculosDisponibles);
    
    // Convertir el array de vehículos a opciones para el input de SweetAlert2
    const inputOptions = vehiculosActivos.reduce((options, vehiculo) => {
        const tipoString = getTipoVehiculoString(vehiculo.tipo);
        options[vehiculo.id] = `${vehiculo.flota} - ${vehiculo.placa} (${tipoString})`;

        return options;
    }, {});


    const { value: vehiculoId } = await Swal.fire({
        title: 'Selecciona el Vehículo para Inspección',
        input: 'select',
        inputPlaceholder: 'Selecciona un vehículo...',
        inputOptions: inputOptions,
        inputValidator: (value) => {
            if (!value) {
                return 'Debes seleccionar un vehículo para continuar.';
            }
        },
        showCancelButton: true,
        confirmButtonText: 'Abrir Checklist'
    });

    if (vehiculoId) {
        // Redirigir a la ruta definida en el InspeccionController
        // La ruta usa el ID del vehículo seleccionado
        const urlInspeccion = `/vehiculos/inspeccion/${vehiculoId}/salida`;
        
        Swal.fire({
            title: 'Cargando Checklist...',
            didOpen: () => Swal.showLoading()
        });
        
        // Redirección
        window.location.href = urlInspeccion;
    }
}
</script>
    @endpush