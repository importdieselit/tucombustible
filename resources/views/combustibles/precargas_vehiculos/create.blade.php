@extends('layouts.app')
@section('title', 'Registrar Precarga de Vehículo')

@section('content')
<div class="container-fluid py-4 px-4">

    {{-- ENCABEZADO --}}
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="h4 fw-black text-dark text-uppercase mb-1">
                <i class="fas fa-truck-loading text-orange me-2"></i> Registrar Precarga de Vehículo
            </h2>
            <p class="text-muted small mb-0">Carga preventiva de combustible a cisterna o vehículo</p>
        </div>
        <a href="{{ route('combustibles.precargas_vehiculos.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase shadow-sm px-3" style="font-size: 12px; height: 32px;">
            <i class="fas fa-arrow-left me-1"></i> Volver a Precargados
        </a>
    </div>

    {{-- ALERTAS DE ERROR --}}
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm fw-bold small" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> Por favor verifique los campos del formulario:
            <ul class="mb-0 mt-1 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- FORMULARIO --}}
    <div class="card shadow-sm border-0" style="max-width: 850px; border-left: 4px solid #ff6600;">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-black text-uppercase small text-dark">
                <i class="fas fa-edit text-orange me-2"></i> Datos de la Precarga
            </h6>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('combustibles.precargas_vehiculos.store') }}" method="POST" id="form_precarga">
                @csrf

                <div class="row g-3">
                    {{-- SEDE --}}
                    <div class="col-md-6">
                        <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Sede Operativa *</label>
                        <select name="id_sede" id="id_sede" class="form-select form-select-sm fw-bold text-dark @error('id_sede') is-invalid @enderror" style="font-size: 13px;" required>
                            <option value="">-- SELECCIONE SEDE --</option>
                            @foreach($sedes as $sede)
                                <option value="{{ $sede->id }}" {{ old('id_sede') == $sede->id ? 'selected' : '' }}>
                                    {{ $sede->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- VEHÍCULO --}}
                    <div class="col-md-6">
                        <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Vehículo / Cisterna *</label>
                        <select name="id_vehiculo" id="id_vehiculo" class="form-select form-select-sm fw-bold text-dark @error('id_vehiculo') is-invalid @enderror" style="font-size: 13px;" required>
                            <option value="" data-carga="0">-- SELECCIONE VEHÍCULO --</option>
                            @foreach($vehiculos as $vehiculo)
                                <option value="{{ $vehiculo->id }}" 
                                        data-carga="{{ $vehiculo->carga_max ?? 0 }}"
                                        {{ old('id_vehiculo') == $vehiculo->id ? 'selected' : '' }}>
                                    {{ $vehiculo->placa }} (Capacidad: {{ number_format($vehiculo->carga_max ?? 0, 2, ',', '.') }} Lts)
                                </option>
                            @endforeach
                        </select>
                        <div id="info_capacidad_vehiculo" class="text-primary fw-bold mt-1 d-none" style="font-size: 11px;">
                            <i class="fas fa-info-circle me-1"></i> Capacidad Máxima: <span id="lbl_capacidad_max">0,00</span> Lts
                        </div>
                    </div>

                    {{-- TIPO DE COMBUSTIBLE --}}
                    <div class="col-md-6">
                        <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Tipo de Combustible *</label>
                        <select name="id_tipo_combustible" id="id_tipo_combustible" class="form-select form-select-sm fw-bold text-dark @error('id_tipo_combustible') is-invalid @enderror" style="font-size: 13px;" required>
                            <option value="">-- SELECCIONE PRODUCTO --</option>
                            @foreach($tiposCombustible as $tipo)
                                <option value="{{ $tipo->id }}" {{ old('id_tipo_combustible') == $tipo->id ? 'selected' : '' }}>
                                    {{ $tipo->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- CANTIDAD EN LITROS --}}
                    <div class="col-md-6">
                        <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Cantidad (Litros) *</label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" min="0.01" name="cantidad_litros" id="cantidad_litros" class="form-control fw-bold text-dark @error('cantidad_litros') is-invalid @enderror" style="font-size: 13px;" placeholder="0.00" value="{{ old('cantidad_litros') }}" required>
                            <span class="input-group-text fw-bold text-muted" style="font-size: 11px;">LTS</span>
                        </div>
                        <div id="alerta_exceso_capacidad" class="text-danger small fw-bold mt-1 d-none" style="font-size: 11px;">
                            <i class="fas fa-exclamation-triangle me-1"></i> La cantidad supera la capacidad máxima del vehículo (<span id="lbl_exceso_max">0</span> Lts).
                        </div>
                    </div>

                    {{-- OBSERVACIONES --}}
                    <div class="col-12">
                        <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Observaciones (Opcional)</label>
                        <textarea name="observaciones" id="observaciones" rows="2" class="form-control form-control-sm fw-bold text-dark @error('observaciones') is-invalid @enderror" style="font-size: 13px;" placeholder="Notas adicionales o detalles de la precarga...">{{ old('observaciones') }}</textarea>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('combustibles.precargas_vehiculos.index') }}" class="btn btn-sm btn-light fw-bold text-uppercase px-4" style="font-size: 11px;">
                        Cancelar
                    </a>
                    <button type="submit" id="btn_submit" class="btn btn-sm btn-warning fw-black text-uppercase px-4" style="font-size: 11px; color: #000; background-color: #ff6600; border-color: #ff6600;">
                        <i class="fas fa-save me-1"></i> Registrar Precarga
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const vehiculoSelect = document.getElementById('id_vehiculo');
    const cantidadInput = document.getElementById('cantidad_litros');
    const infoCapacidad = document.getElementById('info_capacidad_vehiculo');
    const lblCapacidadMax = document.getElementById('lbl_capacidad_max');
    const alertaExceso = document.getElementById('alerta_exceso_capacidad');
    const lblExcesoMax = document.getElementById('lbl_exceso_max');
    const btnSubmit = document.getElementById('btn_submit');

    function actualizarCapacidadVehiculo() {
        const selectedOption = vehiculoSelect.options[vehiculoSelect.selectedIndex];
    
        if (!vehiculoSelect.value) {
            infoCapacidad.classList.add('d-none');
            alertaExceso.classList.add('d-none');
            cantidadInput.classList.remove('is-invalid');
            btnSubmit.disabled = false;
            return;
        }

        const cargaMax = parseFloat(selectedOption.getAttribute('data-carga')) || 0;

        infoCapacidad.classList.remove('d-none');
        lblCapacidadMax.textContent = cargaMax.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        lblExcesoMax.textContent = cargaMax.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        cantidadInput.setAttribute('max', cargaMax);

        validarExcesoCapacidad();
    }

    function validarExcesoCapacidad() {
        if (!vehiculoSelect.value) return;

        const selectedOption = vehiculoSelect.options[vehiculoSelect.selectedIndex];
        const cargaMax = parseFloat(selectedOption.getAttribute('data-carga')) || 0;
        const cantidadLitros = parseFloat(cantidadInput.value) || 0;

        if (cantidadLitros > cargaMax) {
            alertaExceso.classList.remove('d-none');
            cantidadInput.classList.add('is-invalid');
            btnSubmit.disabled = true;
        } else {
            alertaExceso.classList.add('d-none');
            cantidadInput.classList.remove('is-invalid');
            btnSubmit.disabled = false;
        }
    }

    vehiculoSelect.addEventListener('change', actualizarCapacidadVehiculo);
    cantidadInput.addEventListener('input', validarExcesoCapacidad);

    actualizarCapacidadVehiculo();
});
</script>

<style>
    .text-orange { color: #ff6600 !important; }
    .fw-black { font-weight: 900; }
</style>
@endsection