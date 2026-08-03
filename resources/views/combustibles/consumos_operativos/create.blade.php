@extends('layouts.app')
@section('title', 'Registrar Consumo Operativo')

@section('content')
<div class="container-fluid py-4 px-4">

    {{-- ENCABEZADO UNIFICADO --}}
    <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h2 class="h4 fw-black text-dark text-uppercase mb-1">
                <i class="fas fa-tools text-orange me-2"></i> Registrar Consumo Operativo
            </h2>
            <p class="text-muted small mb-0">Registre salidas de combustible destinadas a vehículos de la flota interna o maquinaria/plantas</p>
        </div>
        <div class="d-flex flex-wrap align-items-center justify-content-md-end gap-2">
            <a href="{{ route('combustibles.consumos_operativos.index') }}" class="btn btn-sm btn-dark fw-bold text-uppercase shadow-sm px-3 d-inline-flex align-items-center" style="font-size: 12px; height: 32px;">
                <i class="fas fa-history me-1"></i> Volver al Historial
            </a>
        </div>
    </div>

    {{-- ALERTA OPERATIVA --}}
    <div class="alert bg-white shadow-sm d-flex align-items-center mb-4 py-3" style="border-left: 4px solid #ff6600; border-radius: 4px;">
        <i class="fas fa-exclamation-triangle text-orange fa-lg me-3"></i>
        <div>
            <strong class="text-dark d-block" style="font-size: 14px;">Afectación de Stock Físico Directo</strong>
            <span class="text-muted small">Esta transacción rebajará de forma instantánea el volumen del depósito seleccionado.</span>
        </div>
    </div>

    {{-- ERRORES DE VALIDACIÓN --}}
    @if ($errors->any() || Session::has('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <div class="fw-bold mb-1"><i class="fas fa-exclamation-triangle me-2"></i> Errores encontrados:</div>
            <ul class="mb-0 small">
                @if(Session::has('error'))
                    <li>{{ Session::get('error') }}</li>
                @endif
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- FORMULARIO --}}
    <form action="{{ route('combustibles.consumos_operativos.store') }}" method="POST" id="form-consumo">
        @csrf

        <div class="row g-3 mb-4">
            {{-- BLOQUE PRINCIPAL (IZQUIERDA) --}}
            <div class="col-md-8">
                <div class="card shadow-sm border-0 h-100" style="border-left: 4px solid #ff6600;">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-black text-uppercase small text-dark">
                            <i class="fas fa-edit text-orange me-2"></i> Datos del Suministro
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            
                            {{-- SELECCIÓN DE SEDE EMISORA --}}
                            <div class="col-md-6">
                                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Sede Emisora</label>
                                <select name="sede_id" id="select-sede" class="form-select fw-bold text-dark" style="font-size: 14px;" required>
                                    <option value="">-- SELECCIONE LA SEDE --</option>
                                    @foreach($sedes as $sede)
                                        <option value="{{ $sede->id }}" {{ old('sede_id') == $sede->id ? 'selected' : '' }}>
                                            {{ $sede->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- SELECCIÓN DEL DEPÓSITO (FILTRADO POR JAVASCRIPT CON DATA-COMBUSTIBLE) --}}
                            <div class="col-md-6">
                                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Tanque / Depósito Origen</label>
                                <select name="deposito_id" id="select-deposito" class="form-select fw-bold text-dark" style="font-size: 14px;" required disabled>
                                    <option value="">-- SELECCIONE PRIMERO LA SEDE --</option>
                                    @foreach($tanques as $tanque)
                                        <option value="{{ $tanque->id }}" 
                                                data-sede="{{ $tanque->id_sede }}" 
                                                data-combustible="{{ $tanque->tipo_combustible_id }}"
                                                {{ old('deposito_id') == $tanque->id ? 'selected' : '' }} 
                                                style="display: none;">
                                            {{ $tanque->serial }} — Stock Actual: {{ number_format($tanque->nivel_actual_litros, 2, ',', '.') }} Lts
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- TIPO DE COMBUSTIBLE (READONLY VISUAL PERO SUBIBLE) --}}
                            <div class="col-md-6">
                                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Tipo de Combustible (Confirmación)</label>
                                <select name="tipo_combustible_id" id="select-combustible" class="form-select fw-bold text-dark" style="font-size: 14px; pointer-events: none; background-color: #e9ecef;" tabindex="-1" required>
                                    <option value="">-- AUTO-SELECCIONADO POR TANQUE --</option>
                                    @foreach($tiposCombustible as $tipo)
                                        <option value="{{ $tipo->id }}" {{ old('tipo_combustible_id') == $tipo->id ? 'selected' : '' }}>
                                            {{ $tipo->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- TIPO DE DESTINATARIO (VEHÍCULO O MAQUINARIA) --}}
                            <div class="col-md-6">
                                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Tipo de Destino</label>
                                <div class="d-flex gap-3 mt-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="destino_tipo" id="radio-vehiculo" value="vehiculo" {{ old('destino_tipo', 'vehiculo') == 'vehiculo' ? 'checked' : '' }}>
                                        <label class="form-check-label small fw-bold text-dark" for="radio-vehiculo">
                                            Vehículo (Flota)
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="destino_tipo" id="radio-maquinaria" value="maquinaria" {{ old('destino_tipo') == 'maquinaria' ? 'checked' : '' }}>
                                        <label class="form-check-label small fw-bold text-dark" for="radio-maquinaria">
                                            Equipo / Maquinaria Fija
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- CONTENEDOR DE SELECCIÓN DE VEHÍCULO (TIPO 1,3,5,6 - SOLO PLACAS) --}}
                            <div class="col-md-12" id="wrapper-vehiculo">
                                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Vehículo Autorizado (Placa)</label>
                                <select name="vehiculo_id" id="select-vehiculo" class="form-select fw-bold text-dark font-monospace" style="font-size: 14px;">
                                    <option value="">-- SELECCIONE LA PLACA --</option>
                                    @foreach($vehiculos as $vehiculo)
                                        @if(in_array($vehiculo->tipo, ['1', '3', '5', '6']))
                                            <option value="{{ $vehiculo->id }}" {{ old('vehiculo_id') == $vehiculo->id ? 'selected' : '' }}>
                                                {{ $vehiculo->placa }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>

                            {{-- CONTENEDOR DE MAQUINARIA --}}
                            <div class="col-md-12 d-none" id="wrapper-maquinaria">
                                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Nombre de la Maquinaria / Equipo</label>
                                <input type="text" 
                                       name="equipo_maquinaria" 
                                       id="input-maquinaria"
                                       class="form-control fw-bold text-dark text-uppercase" 
                                       style="font-size: 14px;" 
                                       placeholder="Ej. PLANTA ELECTRICA AUXILIAR GRANDE" 
                                       value="{{ old('equipo_maquinaria') }}">
                            </div>

                            {{-- CANTIDAD EN LITROS --}}
                            <div class="col-12">
                                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Litros Consumidos</label>
                                <div class="input-group">
                                    <input type="number" 
                                           name="cantidad_litros" 
                                           class="form-control fw-black text-center text-dark" 
                                           step="0.01" 
                                           min="0.01" 
                                           value="{{ old('cantidad_litros') }}" 
                                           placeholder="0.00" 
                                           required 
                                           style="font-size: 16px; border-radius: 4px 0 0 4px;">
                                    <span class="input-group-text bg-light fw-bold text-muted" style="font-size: 12px;">LTS</span>
                                </div>
                            </div>

                            {{-- OBSERVACIONES --}}
                            <div class="col-12">
                                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Observaciones / Justificación</label>
                                <textarea name="observaciones" 
                                          rows="3" 
                                          class="form-control fw-bold text-dark" 
                                          style="font-size: 13px;" 
                                          placeholder="Ej. Llenado rutinario de tanque diario o consumo por kilometraje...">{{ old('observaciones') }}</textarea>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            {{-- BLOQUE DE CONTROL DE ACCIÓN --}}
            <div class="col-md-4 d-flex align-items-stretch">
                <div class="card shadow-sm border-0 w-100 bg-dark text-white">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-4">
                        <i class="fas fa-gas-pump text-orange fa-2x mb-2"></i>
                        <h5 class="fw-black text-uppercase mb-1" style="font-size: 14px; letter-spacing: 0.5px;">Control de Suministro</h5>
                        <p class="text-muted small mb-4">Esta acción registrará la salida y reajustará de inmediato el stock físico del tanque de origen.</p>
                        
                        <button type="submit" class="btn btn-warning w-100 fw-black text-uppercase py-2 shadow mb-2" style="color: #000; font-size: 13px; background-color: #ff6600; border-color: #ff6600;">
                            <i class="fas fa-check-circle me-1"></i> Autorizar Consumo
                        </button>
                        
                        <a href="{{ route('combustibles.dashboard') }}" class="btn btn-sm btn-outline-light w-100 text-uppercase fw-bold opacity-70" style="font-size: 11px;">
                            Cancelar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectSede = document.getElementById('select-sede');
    const selectDeposito = document.getElementById('select-deposito');
    const selectCombustible = document.getElementById('select-combustible');
    const optionsDeposito = Array.from(selectDeposito.options);

    const radioVehiculo = document.getElementById('radio-vehiculo');
    const radioMaquinaria = document.getElementById('radio-maquinaria');
    const wrapperVehiculo = document.getElementById('wrapper-vehiculo');
    const wrapperMaquinaria = document.getElementById('wrapper-maquinaria');
    
    const selectVehiculo = document.getElementById('select-vehiculo');
    const inputMaquinaria = document.getElementById('input-maquinaria');

    // 1. Carga Dinámica de Depósitos según la Sede Seleccionada
    function filtrarDepositos() {
        const sedeId = selectSede.value;

        if (!sedeId) {
            selectDeposito.value = "";
            selectDeposito.disabled = true;
            selectCombustible.value = ""; // Limpiar combustible si no hay sede
            optionsDeposito.forEach(opt => {
                if (opt.value !== "") opt.style.display = 'none';
            });
            return;
        }

        selectDeposito.disabled = false;
        let coincidenciaEncontrada = false;

        optionsDeposito.forEach(opt => {
            if (opt.value === "") {
                opt.style.display = 'block';
            } else if (opt.getAttribute('data-sede') === sedeId) {
                opt.style.display = 'block';
                if (opt.value === selectDeposito.value) {
                    coincidenciaEncontrada = true;
                }
            } else {
                opt.style.display = 'none';
            }
        });

        if (!coincidenciaEncontrada && selectDeposito.value !== "") {
            selectDeposito.value = "";
            selectCombustible.value = ""; // Limpiar combustible si el tanque seleccionado ya no es válido
        }
    }

    // 2. Mapeo automático del Tipo de Combustible basado en el Tanque seleccionado
    function mapearCombustible() {
        const selectedOption = selectDeposito.options[selectDeposito.selectedIndex];
        
        if (selectedOption && selectedOption.value !== "") {
            const combustibleId = selectedOption.getAttribute('data-combustible');
            selectCombustible.value = combustibleId;
        } else {
            selectCombustible.value = "";
        }
    }

    // 3. Control de Campos Mutuamente Excluyentes (Vehículo / Maquinaria)
    function alternarDestino() {
        if (radioVehiculo.checked) {
            wrapperVehiculo.classList.remove('d-none');
            selectVehiculo.setAttribute('required', 'required');
            
            wrapperMaquinaria.classList.add('d-none');
            inputMaquinaria.removeAttribute('required');
            inputMaquinaria.value = ""; 
        } else {
            wrapperMaquinaria.classList.remove('d-none');
            inputMaquinaria.setAttribute('required', 'required');
            
            wrapperVehiculo.classList.add('d-none');
            selectVehiculo.removeAttribute('required');
            selectVehiculo.value = ""; 
        }
    }

    // Event Listeners
    selectSede.addEventListener('change', filtrarDepositos);
    selectDeposito.addEventListener('change', mapearCombustible);
    radioVehiculo.addEventListener('change', alternarDestino);
    radioMaquinaria.addEventListener('change', alternarDestino);
    
    inputMaquinaria.addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });

    // Inicializar estados por si venimos de un fallo de validación anterior
    if (selectSede.value) {
        filtrarDepositos();
        mapearCombustible();
    }
    alternarDestino();
});
</script>

<style>
    .text-orange { color: #ff6600 !important; }
    .fw-black { font-weight: 900; }
</style>
@endsection