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
            <p class="text-muted small mb-0">Recepción de combustible en depósitos de la sede desde Vehículo Precargado o Compra de Combustible</p>
        </div>
        <a href="{{ route('combustibles.abastecimientos_tanques.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase shadow-sm px-3" style="font-size: 12px; height: 32px;">
            <i class="fas fa-arrow-left me-1"></i> Volver al Histórico
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
                <i class="fas fa-edit text-orange me-2"></i> Datos del Abastecimiento
            </h6>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('combustibles.abastecimientos_tanques.store') }}" method="POST" id="form_abastecimiento">
                @csrf

                <div class="row g-3">
                    {{-- SEDE DESTINO --}}
                    <div class="col-md-6">
                        <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Sede Destino (Tanque) *</label>
                        <select name="id_sede" id="id_sede" class="form-select form-select-sm fw-bold text-dark @error('id_sede') is-invalid @enderror" style="font-size: 13px;" required>
                            <option value="">-- SELECCIONE SEDE --</option>
                            @foreach($sedes as $sede)
                                <option value="{{ $sede->id }}" {{ old('id_sede') == $sede->id ? 'selected' : '' }}>
                                    {{ $sede->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- SELECCIÓN DE TIPO DE ORIGEN --}}
                    <div class="col-md-6">
                        <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Origen del Combustible *</label>
                        <div class="d-flex gap-3 mt-1">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo_origen" id="origen_precarga" value="precarga" {{ old('tipo_origen', 'precarga') == 'precarga' ? 'checked' : '' }}>
                                <label class="form-check-label small fw-bold" for="origen_precarga">Vehículo Precargado</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="tipo_origen" id="origen_compra" value="compra" {{ old('tipo_origen') == 'compra' ? 'checked' : '' }}>
                                <label class="form-check-label small fw-bold" for="origen_compra">Compra de Combustible</label>
                            </div>
                        </div>
                    </div>

                    {{-- SELECT: VEHÍCULO PRECARGADO (GLOBAL / CUALQUIER SEDE) --}}
                    <div class="col-md-6" id="box_select_precarga">
                        <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Vehículo Precargado Origen *</label>
                        <select name="id_precarga_origen" id="id_precarga_origen" class="form-select form-select-sm fw-bold text-dark @error('id_precarga_origen') is-invalid @enderror" style="font-size: 13px;">
                            <option value="" data-litros="0" data-tipo="" data-sede="">-- SELECCIONE VEHÍCULO PRECARGADO --</option>
                            @foreach($precargas as $precarga)
                                @php
                                    $placaVehiculo = $precarga->vehiculo->placa ?? 'S/P';
                                    $nombreTipo = $precarga->tipoCombustible->nombre ?? 'N/A';
                                    $nombreSedeOrigen = $precarga->sede->nombre ?? 'N/A';
                                @endphp
                                <option value="{{ $precarga->id }}" 
                                        data-litros="{{ $precarga->cantidad_litros }}"
                                        data-tipo="{{ $nombreTipo }}"
                                        data-sede="{{ $precarga->id_sede }}"
                                        {{ old('id_precarga_origen') == $precarga->id ? 'selected' : '' }}>
                                    {{ $placaVehiculo }} - {{ number_format($precarga->cantidad_litros, 2, ',', '.') }} Lts ({{ $nombreTipo }})
                                </option>
                            @endforeach
                        </select>
                        <div id="info_precarga" class="mt-1 text-primary fw-bold d-none" style="font-size: 11px;">
                            <i class="fas fa-info-circle me-1"></i> Disponible en Precarga: <span id="lbl_litros_precarga">0,00</span> Lts (<span id="lbl_tipo_precarga"></span>)
                        </div>
                    </div>

                    {{-- SELECT: COMPRA DE COMBUSTIBLE (FILTRADO POR SEDE) --}}
                    <div class="col-md-6 d-none" id="box_select_compra">
                        <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Compra de Combustible Origen *</label>
                        <select name="id_compra_combustible" id="id_compra_combustible" class="form-select form-select-sm fw-bold text-dark @error('id_compra_combustible') is-invalid @enderror" style="font-size: 13px;">
                            <option value="" data-litros="0" data-tipo="" data-fecha="" data-sede="">-- SELECCIONE COMPRA DE COMBUSTIBLE --</option>
                            @foreach($compras as $compra)
                                @php
                                    $litrosCompra = $compra->cantidad_recibida ?? $compra->cantidad_litros;
                                    $proveedorNombre = $compra->proveedor->nombre ?? $compra->otro_proveedor ?? 'PDVSA';
                                    $fechaCompra = $compra->fecha ? \Carbon\Carbon::parse($compra->fecha)->format('d/m/Y') : 'S/F';
                                @endphp
                                <option value="{{ $compra->id }}" 
                                        data-litros="{{ $litrosCompra }}"
                                        data-tipo="{{ $compra->tipo }}"
                                        data-fecha="{{ $fechaCompra }}"
                                        data-sede="{{ $compra->planta_destino_id }}"
                                        {{ old('id_compra_combustible') == $compra->id ? 'selected' : '' }}>
                                    #{{ $compra->id }} | {{ $fechaCompra }} - {{ $proveedorNombre }} - {{ number_format($litrosCompra, 2, ',', '.') }} Lts ({{ $compra->tipo }})
                                </option>
                            @endforeach
                        </select>
                        <div id="info_compra" class="mt-1 text-primary fw-bold d-none" style="font-size: 11px;">
                            <i class="fas fa-info-circle me-1"></i> Volumen Compra: <span id="lbl_litros_compra">0,00</span> Lts (<span id="lbl_tipo_compra"></span>)
                        </div>
                    </div>

                    {{-- CANTIDAD EN LITROS --}}
                    <div class="col-md-6">
                        <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Cantidad a Ingresar (Litros) *</label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" min="0.01" name="cantidad_litros" id="cantidad_litros" class="form-control fw-bold text-dark @error('cantidad_litros') is-invalid @enderror" style="font-size: 13px;" placeholder="0.00" value="{{ old('cantidad_litros') }}" required>
                            <span class="input-group-text fw-bold text-muted" style="font-size: 11px;">LTS</span>
                        </div>
                        <div id="alerta_error_cantidad" class="text-danger small fw-bold mt-1 d-none" style="font-size: 11px;">
                            <i class="fas fa-exclamation-triangle me-1"></i> <span id="lbl_error_cantidad"></span>
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
    const radioPrecarga = document.getElementById('origen_precarga');
    const radioCompra = document.getElementById('origen_compra');
    
    const boxSelectPrecarga = document.getElementById('box_select_precarga');
    const boxSelectCompra = document.getElementById('box_select_compra');
    
    const selectPrecarga = document.getElementById('id_precarga_origen');
    const selectCompra = document.getElementById('id_compra_combustible');
    const cantidadInput = document.getElementById('cantidad_litros');
    const btnSubmit = document.getElementById('btn_submit');

    const infoPrecarga = document.getElementById('info_precarga');
    const lblLitrosPrecarga = document.getElementById('lbl_litros_precarga');
    const lblTipoPrecarga = document.getElementById('lbl_tipo_precarga');

    const infoCompra = document.getElementById('info_compra');
    const lblLitrosCompra = document.getElementById('lbl_litros_compra');
    const lblTipoCompra = document.getElementById('lbl_tipo_compra');

    const alertaErrorCantidad = document.getElementById('alerta_error_cantidad');
    const lblErrorCantidad = document.getElementById('lbl_error_cantidad');

    function alternarTipoOrigen() {
        if (radioPrecarga.checked) {
            boxSelectPrecarga.classList.remove('d-none');
            boxSelectCompra.classList.add('d-none');
            selectPrecarga.required = true;
            selectCompra.required = false;
            selectCompra.value = '';
        } else {
            boxSelectCompra.classList.remove('d-none');
            boxSelectPrecarga.classList.add('d-none');
            selectCompra.required = true;
            selectPrecarga.required = false;
            selectPrecarga.value = '';
        }
        actualizarEstadoCompleto();
    }

    function filtrarOrigenesPorSede() {
        const sedeId = sedeSelect.value;

        // Únicamente se filtran las Compras según la sede destino seleccionada
        Array.from(selectCompra.options).forEach(option => {
            if (option.value === '') return;
            const compraSede = option.getAttribute('data-sede');
            if (!sedeId || compraSede === sedeId) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
            }
        });

        if (selectCompra.options[selectCompra.selectedIndex] && 
            selectCompra.options[selectCompra.selectedIndex].style.display === 'none') {
            selectCompra.value = '';
        }

        actualizarEstadoCompleto();
    }

    function actualizarEstadoCompleto() {
        let disponibleOrigen = 0;

        if (radioPrecarga.checked) {
            infoCompra.classList.add('d-none');
            const optPrecarga = selectPrecarga.options[selectPrecarga.selectedIndex];
            disponibleOrigen = parseFloat(optPrecarga ? optPrecarga.getAttribute('data-litros') : 0) || 0;
            const tipoNombre = optPrecarga ? optPrecarga.getAttribute('data-tipo') : '';

            if (selectPrecarga.value) {
                infoPrecarga.classList.remove('d-none');
                lblLitrosPrecarga.textContent = disponibleOrigen.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                lblTipoPrecarga.textContent = tipoNombre;
            } else {
                infoPrecarga.classList.add('d-none');
            }
        } else {
            infoPrecarga.classList.add('d-none');
            const optCompra = selectCompra.options[selectCompra.selectedIndex];
            disponibleOrigen = parseFloat(optCompra ? optCompra.getAttribute('data-litros') : 0) || 0;
            const tipoNombre = optCompra ? optCompra.getAttribute('data-tipo') : '';

            if (selectCompra.value) {
                infoCompra.classList.remove('d-none');
                lblLitrosCompra.textContent = disponibleOrigen.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                lblTipoCompra.textContent = tipoNombre;
            } else {
                infoCompra.classList.add('d-none');
            }
        }

        const cantidad = parseFloat(cantidadInput.value) || 0;
        let errorCantidad = null;

        if (cantidad > 0 && disponibleOrigen > 0 && cantidad > disponibleOrigen) {
            errorCantidad = `La cantidad ingresada (${cantidad.toLocaleString('es-VE')} Lts) supera lo disponible en el origen (${disponibleOrigen.toLocaleString('es-VE')} Lts).`;
        }

        if (errorCantidad) {
            lblErrorCantidad.textContent = errorCantidad;
            alertaErrorCantidad.classList.remove('d-none');
            cantidadInput.classList.add('is-invalid');
            btnSubmit.disabled = true;
        } else {
            alertaErrorCantidad.classList.add('d-none');
            cantidadInput.classList.remove('is-invalid');
            btnSubmit.disabled = false;
        }
    }

    radioPrecarga.addEventListener('change', alternarTipoOrigen);
    radioCompra.addEventListener('change', alternarTipoOrigen);
    sedeSelect.addEventListener('change', filtrarOrigenesPorSede);
    selectPrecarga.addEventListener('change', actualizarEstadoCompleto);
    selectCompra.addEventListener('change', actualizarEstadoCompleto);
    cantidadInput.addEventListener('input', actualizarEstadoCompleto);

    alternarTipoOrigen();
    filtrarOrigenesPorSede();
});
</script>

<style>
    .text-orange { color: #ff6600 !important; }
    .fw-black { font-weight: 900; }
</style>
@endsection