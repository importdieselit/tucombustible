@extends('layouts.app')
@section('title', 'Registrar Abastecimiento de Tanque')

@section('content')
<div class="container-fluid py-4 px-4">

    {{-- ENCABEZADO --}}
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="h4 fw-black text-dark text-uppercase mb-1">
                <i class="fas fa-dolly-flatbed text-orange me-2"></i> Registrar Abastecimiento de Tanque
            </h2>
            <p class="text-muted small mb-0">Trasegado de combustible desde un vehículo/cisterna hacia un depósito local</p>
        </div>
        <a href="{{ route('combustibles.abastecimientos_tanques.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase shadow-sm px-3" style="font-size: 12px; height: 32px;">
            <i class="fas fa-arrow-left me-1"></i> Volver al Histórico
        </a>
    </div>

    {{-- ALERTAS DE ERROR DEL SERVIDOR --}}
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

    {{-- ALERTA DE INCOMPATIBILIDAD DE COMBUSTIBLE (JS) --}}
    <div id="alerta_incompatibilidad" class="alert alert-danger shadow-sm fw-bold small d-none" role="alert" style="max-width: 850px;">
        <i class="fas fa-ban me-2"></i> <span id="lbl_incompatibilidad_msg">Incompatibilidad de combustible detectada.</span>
    </div>

    {{-- FORMULARIO --}}
    <div class="card shadow-sm border-0" style="max-width: 850px; border-left: 4px solid #ff6600;">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-black text-uppercase small text-dark">
                <i class="fas fa-edit text-orange me-2"></i> Datos del Trasegado
            </h6>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('combustibles.abastecimientos_tanques.store') }}" method="POST" id="form_abastecimiento">
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

                    {{-- VEHÍCULO ORIGEN --}}
                    <div class="col-md-6">
                        <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Vehículo Origen (Cisterna/Camión) *</label>
                        <select name="id_vehiculo" id="id_vehiculo" class="form-select form-select-sm fw-bold text-dark @error('id_vehiculo') is-invalid @enderror" style="font-size: 13px;" required>
                            <option value="" data-carga="0" data-precarga="0" data-precarga-tipo="" data-precarga-tipo-nombre="">-- SELECCIONE VEHÍCULO --</option>
                            @foreach($vehiculos as $vehiculo)
                                @php
                                    $precarga = \App\Models\VehiculoPrecargado::with('tipoCombustible')
                                                ->where('id_vehiculo', $vehiculo->id)
                                                ->where('estatus', 0)
                                                ->first();
                                    $nombreTipoPrecarga = $precarga && $precarga->tipoCombustible ? $precarga->tipoCombustible->nombre : '';
                                @endphp
                                <option value="{{ $vehiculo->id }}" 
                                        data-carga="{{ $vehiculo->carga_max ?? 0 }}"
                                        data-precarga="{{ $precarga ? $precarga->cantidad_litros : 0 }}"
                                        data-precarga-tipo="{{ $precarga ? $precarga->id_tipo_combustible : '' }}"
                                        data-precarga-tipo-nombre="{{ $nombreTipoPrecarga }}"
                                        {{ old('id_vehiculo') == $vehiculo->id ? 'selected' : '' }}>
                                    {{ $vehiculo->placa }} {{ $precarga ? '(PRECARGA: '.number_format($precarga->cantidad_litros, 2, ',', '.').' Lts - '.$nombreTipoPrecarga.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <div id="info_vehiculo" class="mt-1 d-none" style="font-size: 11px;">
                            <span class="text-primary fw-bold d-block"><i class="fas fa-info-circle me-1"></i> Capacidad Máxima: <span id="lbl_capacidad_vehiculo">0,00</span> Lts</span>
                            <span id="box_precarga_activa" class="text-success fw-bold d-none">
                                <i class="fas fa-gas-pump me-1"></i> Precarga Activa: <span id="lbl_precarga_vehiculo">0,00</span> Lts 
                                <span id="badge_precarga_tipo" class="badge bg-success text-white ms-1"></span>
                            </span>
                        </div>
                    </div>

                    {{-- DEPÓSITO DESTINO --}}
                    <div class="col-md-6">
                        <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Depósito Destino (Tanque) *</label>
                        <select name="id_deposito" id="id_deposito" class="form-select form-select-sm fw-bold text-dark @error('id_deposito') is-invalid @enderror" style="font-size: 13px;" required>
                            <option value="" data-sede="" data-tipo-combustible="" data-tipo-combustible-nombre="" data-capacidad="0" data-nivel="0" data-disponible="0">-- SELECCIONE DEPÓSITO --</option>
                            @foreach($depositos as $deposito)
                                @php
                                    $espacioLibre = max(0, (float)$deposito->capacidad_litros - (float)$deposito->nivel_actual_litros);
                                    $nombreTipoTanque = $deposito->tipoCombustible->nombre ?? 'N/A';
                                @endphp
                                <option value="{{ $deposito->id }}" 
                                        data-sede="{{ $deposito->id_sede }}" 
                                        data-tipo-combustible="{{ $deposito->tipo_combustible_id }}"
                                        data-tipo-combustible-nombre="{{ $nombreTipoTanque }}"
                                        data-capacidad="{{ $deposito->capacidad_litros }}"
                                        data-nivel="{{ $deposito->nivel_actual_litros }}"
                                        data-disponible="{{ $espacioLibre }}"
                                        {{ old('id_deposito') == $deposito->id ? 'selected' : '' }}>
                                    {{ $deposito->serial }} ({{ $nombreTipoTanque }} - Disp: {{ number_format($espacioLibre, 2, ',', '.') }} Lts)
                                </option>
                            @endforeach
                        </select>
                        <div id="info_deposito" class="mt-1 text-secondary fw-bold d-none" style="font-size: 11px;">
                            <i class="fas fa-warehouse me-1"></i> Tanque: <span id="lbl_espacio_libre">0,00</span> Lts libres / <span id="lbl_capacidad_tanque">0,00</span> Lts 
                            <span id="badge_deposito_tipo" class="badge bg-secondary text-white ms-1"></span>
                        </div>
                    </div>

                    {{-- TIPO DE COMBUSTIBLE --}}
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="small fw-bold text-uppercase text-muted mb-0" style="font-size: 11px;">Tipo de Combustible a Trasegar *</label>
                            <span id="badge_auto_detect" class="badge bg-dark text-white text-uppercase d-none" style="font-size: 9px;">Fijado por Tanque</span>
                        </div>
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
                        <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Cantidad a Trasegar (Litros) *</label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" min="0.01" name="cantidad_litros" id="cantidad_litros" class="form-control fw-bold text-dark @error('cantidad_litros') is-invalid @enderror" style="font-size: 13px;" placeholder="0.00" value="{{ old('cantidad_litros') }}" required>
                            <span class="input-group-text fw-bold text-muted" style="font-size: 11px;">LTS</span>
                        </div>
                        <div id="alerta_error_cantidad" class="text-danger small fw-bold mt-1 d-none" style="font-size: 11px;">
                            <i class="fas fa-exclamation-triangle me-1"></i> <span id="lbl_error_cantidad">Error en la cantidad ingresada.</span>
                        </div>
                    </div>

                    {{-- OBSERVACIONES --}}
                    <div class="col-md-6">
                        <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Observaciones (Opcional)</label>
                        <input type="text" name="observaciones" id="observaciones" class="form-control form-control-sm fw-bold text-dark" style="font-size: 13px;" placeholder="Notas adicionales..." value="{{ old('observaciones') }}">
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('combustibles.abastecimientos_tanques.index') }}" class="btn btn-sm btn-light fw-bold text-uppercase px-4" style="font-size: 11px;">
                        Cancelar
                    </a>
                    <button type="submit" id="btn_submit" class="btn btn-sm btn-warning fw-black text-uppercase px-4" style="font-size: 11px; color: #000; background-color: #ff6600; border-color: #ff6600;">
                        <i class="fas fa-save me-1"></i> Registrar Abastecimiento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sedeSelect = document.getElementById('id_sede');
    const vehiculoSelect = document.getElementById('id_vehiculo');
    const depositoSelect = document.getElementById('id_deposito');
    const tipoCombustibleSelect = document.getElementById('id_tipo_combustible');
    const cantidadInput = document.getElementById('cantidad_litros');
    const btnSubmit = document.getElementById('btn_submit');

    const infoVehiculo = document.getElementById('info_vehiculo');
    const lblCapacidadVehiculo = document.getElementById('lbl_capacidad_vehiculo');
    const boxPrecargaActiva = document.getElementById('box_precarga_activa');
    const lblPrecargaVehiculo = document.getElementById('lbl_precarga_vehiculo');
    const badgePrecargaTipo = document.getElementById('badge_precarga_tipo');

    const infoDeposito = document.getElementById('info_deposito');
    const lblEspacioLibre = document.getElementById('lbl_espacio_libre');
    const lblCapacidadTanque = document.getElementById('lbl_capacidad_tanque');
    const badgeDepositoTipo = document.getElementById('badge_deposito_tipo');
    const badgeAutoDetect = document.getElementById('badge_auto_detect');

    const alertaIncompatibilidad = document.getElementById('alerta_incompatibilidad');
    const lblIncompatibilidadMsg = document.getElementById('lbl_incompatibilidad_msg');
    const alertaErrorCantidad = document.getElementById('alerta_error_cantidad');
    const lblErrorCantidad = document.getElementById('lbl_error_cantidad');

    // 1. Filtrar depósitos según sede
    function filtrarDepositosPorSede() {
        const sedeId = sedeSelect.value;
        Array.from(depositoSelect.options).forEach(option => {
            if (option.value === '') return;
            if (!sedeId || option.getAttribute('data-sede') === sedeId) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
            }
        });

        if (depositoSelect.options[depositoSelect.selectedIndex] && 
            depositoSelect.options[depositoSelect.selectedIndex].style.display === 'none') {
            depositoSelect.value = '';
        }
        
        actualizarEstadoCompleto();
    }

    // 2. Control unificado de selección, sincronización y validación cruzada
    function actualizarEstadoCompleto() {
        const optVehiculo = vehiculoSelect.options[vehiculoSelect.selectedIndex];
        const optDeposito = depositoSelect.options[depositoSelect.selectedIndex];

        // --- A. Vehículo Info ---
        const cargaMaxVehiculo = parseFloat(optVehiculo ? optVehiculo.getAttribute('data-carga') : 0) || 0;
        const precargaLitros = parseFloat(optVehiculo ? optVehiculo.getAttribute('data-precarga') : 0) || 0;
        const precargaTipoId = optVehiculo ? optVehiculo.getAttribute('data-precarga-tipo') : '';
        const precargaTipoNombre = optVehiculo ? optVehiculo.getAttribute('data-precarga-tipo-nombre') : '';

        if (vehiculoSelect.value) {
            infoVehiculo.classList.remove('d-none');
            lblCapacidadVehiculo.textContent = cargaMaxVehiculo.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            if (precargaLitros > 0) {
                boxPrecargaActiva.classList.remove('d-none');
                lblPrecargaVehiculo.textContent = precargaLitros.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                badgePrecargaTipo.textContent = precargaTipoNombre;
            } else {
                boxPrecargaActiva.classList.add('d-none');
            }
        } else {
            infoVehiculo.classList.add('d-none');
        }

        // --- B. Depósito Info ---
        const capacidadTanque = parseFloat(optDeposito ? optDeposito.getAttribute('data-capacidad') : 0) || 0;
        const disponibleTanque = parseFloat(optDeposito ? optDeposito.getAttribute('data-disponible') : 0) || 0;
        const depositoTipoId = optDeposito ? optDeposito.getAttribute('data-tipo-combustible') : '';
        const depositoTipoNombre = optDeposito ? optDeposito.getAttribute('data-tipo-combustible-nombre') : '';

        if (depositoSelect.value) {
            infoDeposito.classList.remove('d-none');
            lblCapacidadTanque.textContent = capacidadTanque.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            lblEspacioLibre.textContent = disponibleTanque.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            badgeDepositoTipo.textContent = depositoTipoNombre;
        } else {
            infoDeposito.classList.add('d-none');
        }

        // --- C. Asignación y Bloqueo del Tipo de Combustible ---
        // Regla: El tanque receptor tiene máxima prioridad para fijar el producto.
        if (depositoTipoId) {
            tipoCombustibleSelect.value = depositoTipoId;
            badgeAutoDetect.textContent = 'Fijado por Tanque Destino';
            badgeAutoDetect.classList.remove('d-none');
            tipoCombustibleSelect.style.pointerEvents = 'none';
            tipoCombustibleSelect.classList.add('bg-light');
        } else if (precargaTipoId) {
            tipoCombustibleSelect.value = precargaTipoId;
            badgeAutoDetect.textContent = 'Fijado por Precarga';
            badgeAutoDetect.classList.remove('d-none');
            tipoCombustibleSelect.style.pointerEvents = 'none';
            tipoCombustibleSelect.classList.add('bg-light');
        } else {
            badgeAutoDetect.classList.add('d-none');
            tipoCombustibleSelect.style.pointerEvents = 'auto';
            tipoCombustibleSelect.classList.remove('bg-light');
        }

        // --- D. Validación Cruzada de Combustibles (Evitar Mezclas) ---
        let hayIncompatibilidad = false;
        if (precargaTipoId && depositoTipoId && (precargaTipoId !== depositoTipoId)) {
            hayIncompatibilidad = true;
            lblIncompatibilidadMsg.textContent = `Conflicto de producto: El vehículo tiene una precarga de "${precargaTipoNombre}", pero el tanque de destino es de "${depositoTipoNombre}". No se pueden mezclar tipos de combustible.`;
            alertaIncompatibilidad.classList.remove('d-none');
            vehiculoSelect.classList.add('is-invalid');
            depositoSelect.classList.add('is-invalid');
        } else {
            alertaIncompatibilidad.classList.add('d-none');
            vehiculoSelect.classList.remove('is-invalid');
            depositoSelect.classList.remove('is-invalid');
        }

        // --- E. Validación de Cantidades ---
        const cantidad = parseFloat(cantidadInput.value) || 0;
        let errorCantidad = null;

        if (cantidad > 0) {
            if (cargaMaxVehiculo > 0 && cantidad > cargaMaxVehiculo) {
                errorCantidad = `La cantidad (${cantidad.toLocaleString('es-VE')} Lts) supera la capacidad máxima del camión (${cargaMaxVehiculo.toLocaleString('es-VE')} Lts).`;
            } else if (precargaLitros > 0 && cantidad > precargaLitros) {
                errorCantidad = `El vehículo solo posee ${precargaLitros.toLocaleString('es-VE')} Lts precargados disponibles.`;
            } else if (depositoSelect.value && cantidad > disponibleTanque) {
                errorCantidad = `El espacio libre en el tanque (${disponibleTanque.toLocaleString('es-VE')} Lts) es insuficiente.`;
            }
        }

        if (errorCantidad) {
            lblErrorCantidad.textContent = errorCantidad;
            alertaErrorCantidad.classList.remove('d-none');
            cantidadInput.classList.add('is-invalid');
        } else {
            alertaErrorCantidad.classList.add('d-none');
            cantidadInput.classList.remove('is-invalid');
        }

        // Bloqueo del botón de envío si hay incompatibilidad o error en cantidades
        btnSubmit.disabled = (hayIncompatibilidad || !!errorCantidad);
    }

    // Eventos
    sedeSelect.addEventListener('change', filtrarDepositosPorSede);
    vehiculoSelect.addEventListener('change', actualizarEstadoCompleto);
    depositoSelect.addEventListener('change', actualizarEstadoCompleto);
    cantidadInput.addEventListener('input', actualizarEstadoCompleto);

    // Inicialización al cargar la vista
    filtrarDepositosPorSede();
    actualizarEstadoCompleto();
});
</script>

<style>
    .text-orange { color: #ff6600 !important; }
    .fw-black { font-weight: 900; }
</style>
@endsection