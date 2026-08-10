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
            <p class="text-muted small mb-0">Carga preventiva de combustible a cisterna desde fosa local o por compra precintada</p>
        </div>
        <a href="{{ route('combustibles.precargas_vehiculos.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase shadow-sm px-3" style="font-size: 12px; height: 32px;">
            <i class="fas fa-arrow-left me-1"></i> Volver a Precargados
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
                                    {{ $vehiculo->placa }} {{ isset($vehiculo->modelo) ? '- '.$vehiculo->modelo : '' }} (Capacidad: {{ number_format($vehiculo->carga_max ?? 0, 2, ',', '.') }} Lts)
                                </option>
                            @endforeach
                        </select>
                        <div id="info_capacidad_vehiculo" class="text-primary fw-bold mt-1 d-none" style="font-size: 11px;">
                            <i class="fas fa-info-circle me-1"></i> Capacidad Máxima: <span id="lbl_capacidad_max">0,00</span> Lts
                        </div>
                    </div>

                    {{-- ¿POSEE PRECINTO DE COMPRA? --}}
                    <div class="col-md-6">
                        <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">¿Posee Precinto de Compra? *</label>
                        <select name="esta_precintado" id="esta_precintado" class="form-select form-select-sm fw-bold text-dark @error('esta_precintado') is-invalid @enderror" style="font-size: 13px;" required>
                            <option value="0" {{ old('esta_precintado', '0') == '0' ? 'selected' : '' }}>NO (Sale de Depósito de Sede)</option>
                            <option value="1" {{ old('esta_precintado') == '1' ? 'selected' : '' }}>SÍ (Viene de Compra Precintada)</option>
                        </select>
                    </div>

                    {{-- DEPÓSITO ORIGEN --}}
                    <div class="col-md-6" id="contenedor_deposito">
                        <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Depósito Origen *</label>
                        <select name="id_deposito" id="id_deposito" class="form-select form-select-sm fw-bold text-dark @error('id_deposito') is-invalid @enderror" style="font-size: 13px;">
                            <option value="">-- SELECCIONE DEPÓSITO --</option>
                            @foreach($depositos as $deposito)
                                <option value="{{ $deposito->id }}" 
                                        data-sede="{{ $deposito->id_sede }}" 
                                        data-tipo-combustible="{{ $deposito->tipo_combustible_id }}"
                                        {{ old('id_deposito') == $deposito->id ? 'selected' : '' }}>
                                    {{ $deposito->serial }} ({{ number_format($deposito->nivel_actual_litros, 2, ',', '.') }} Lts disponibles)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- TIPO DE COMBUSTIBLE --}}
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="small fw-bold text-uppercase text-muted mb-0" style="font-size: 11px;">Tipo de Combustible *</label>
                            <span id="badge_auto_detect" class="badge bg-secondary text-uppercase d-none" style="font-size: 9px;">Auto-detectado</span>
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
                        <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Cantidad (Litros) *</label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" min="0.01" name="cantidad_litros" id="cantidad_litros" class="form-control fw-bold text-dark @error('cantidad_litros') is-invalid @enderror" style="font-size: 13px;" placeholder="0.00" value="{{ old('cantidad_litros') }}" required>
                            <span class="input-group-text fw-bold text-muted" style="font-size: 11px;">LTS</span>
                        </div>
                        <div id="alerta_exceso_capacidad" class="text-danger small fw-bold mt-1 d-none" style="font-size: 11px;">
                            <i class="fas fa-exclamation-triangle me-1"></i> La cantidad supera la capacidad máxima del vehículo (<span id="lbl_exceso_max">0</span> Lts).
                        </div>
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
    const estaPrecintadoSelect = document.getElementById('esta_precintado');
    const depositoSelect = document.getElementById('id_deposito');
    const tipoCombustibleSelect = document.getElementById('id_tipo_combustible');
    const contenedorDeposito = document.getElementById('contenedor_deposito');
    const sedeSelect = document.getElementById('id_sede');
    const badgeAutoDetect = document.getElementById('badge_auto_detect');
    
    const vehiculoSelect = document.getElementById('id_vehiculo');
    const cantidadInput = document.getElementById('cantidad_litros');
    const infoCapacidad = document.getElementById('info_capacidad_vehiculo');
    const lblCapacidadMax = document.getElementById('lbl_capacidad_max');
    const alertaExceso = document.getElementById('alerta_exceso_capacidad');
    const lblExcesoMax = document.getElementById('lbl_exceso_max');
    const btnSubmit = document.getElementById('btn_submit');

    // 1. Sincronizar el tipo de combustible desde el depósito
    function syncTipoCombustible() {
        const esPrecintado = estaPrecintadoSelect.value === '1';

        if (!esPrecintado) {
            const selectedOption = depositoSelect.options[depositoSelect.selectedIndex];
            const tipoCombustibleId = selectedOption ? selectedOption.getAttribute('data-tipo-combustible') : null;

            if (tipoCombustibleId) {
                tipoCombustibleSelect.value = tipoCombustibleId;
                badgeAutoDetect.classList.remove('d-none');
            } else {
                tipoCombustibleSelect.value = '';
                badgeAutoDetect.classList.add('d-none');
            }

            tipoCombustibleSelect.style.pointerEvents = 'none';
            tipoCombustibleSelect.classList.add('bg-light');
            tipoCombustibleSelect.setAttribute('tabindex', '-1');
        } else {
            badgeAutoDetect.classList.add('d-none');
            tipoCombustibleSelect.style.pointerEvents = 'auto';
            tipoCombustibleSelect.classList.remove('bg-light');
            tipoCombustibleSelect.removeAttribute('tabindex');
        }
    }

    // 2. Alternar visibilidad de depósitos
    function toggleDeposito() {
        if (estaPrecintadoSelect.value === '1') {
            contenedorDeposito.style.display = 'none';
            depositoSelect.removeAttribute('required');
            depositoSelect.value = '';
        } else {
            contenedorDeposito.style.display = 'block';
            depositoSelect.setAttribute('required', 'required');
        }
        syncTipoCombustible();
    }

    // 3. Filtrar depósitos por sede seleccionada
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
        
        syncTipoCombustible();
    }

    // 4. Validar Capacidad del Vehículo en tiempo real
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

    // Listeners
    estaPrecintadoSelect.addEventListener('change', toggleDeposito);
    depositoSelect.addEventListener('change', syncTipoCombustible);
    sedeSelect.addEventListener('change', filtrarDepositosPorSede);
    vehiculoSelect.addEventListener('change', actualizarCapacidadVehiculo);
    cantidadInput.addEventListener('input', validarExcesoCapacidad);

    // Inicialización al cargar
    toggleDeposito();
    filtrarDepositosPorSede();
    actualizarCapacidadVehiculo();
});
</script>

<style>
    .text-orange { color: #ff6600 !important; }
    .fw-black { font-weight: 900; }
</style>
@endsection