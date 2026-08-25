@extends('layouts.app')
@section('title', 'Registrar Trasegado Interno')

@section('content')
<div class="container-fluid py-4 px-4">

    {{-- ENCABEZADO --}}
    <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h2 class="h4 fw-black text-dark text-uppercase mb-1">
                <i class="fas fa-exchange-alt text-orange me-2"></i> Nuevo Trasegado Interno
            </h2>
            <p class="text-muted small mb-0">Transferencia y movilización de inventario entre tanques de una misma sede</p>
        </div>
        <div class="d-flex flex-wrap align-items-center justify-content-md-end gap-2">
            <a href="{{ route('combustibles.trasegados.index') }}" class="btn btn-sm btn-dark fw-bold text-uppercase shadow-sm px-3 d-inline-flex align-items-center" style="font-size: 12px; height: 32px;">
                <i class="fas fa-history me-1"></i> Volver al Historial
            </a>
        </div>
    </div>

    {{-- ALERTA DE SEGURIDAD OPERATIVA --}}
    <div class="alert bg-white shadow-sm d-flex align-items-center mb-4 py-3" style="border-left: 4px solid #ff6600; border-radius: 4px;">
        <i class="fas fa-shield-alt text-orange fa-lg me-3"></i>
        <div>
            <strong class="text-dark d-block" style="font-size: 14px;">Validación de Stock y Compatibilidad de Producto</strong>
            <span class="text-muted small">El sistema solo permitirá trasegados entre depósitos que compartan el mismo tipo de combustible para evitar contaminación de inventario.</span>
        </div>
    </div>

    {{-- CAPTURA DE ERRORES --}}
    @if ($errors->any() || session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <div class="fw-bold mb-1"><i class="fas fa-exclamation-triangle me-2"></i> Error al procesar el trasegado:</div>
            <ul class="mb-0 small">
                @if(session()->has('error'))
                    <li>{{ session('error') }}</li>
                @endif
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- FORMULARIO PRINCIPAL --}}
    <form action="{{ route('combustibles.trasegados.store') }}" method="POST">
        @csrf

        {{-- DATOS METADATA REQUERIDOS POR EL CONTROLADOR --}}
        <input type="hidden" name="tipo_trasegado" value="interno">
        <input type="hidden" name="sede_destino_id" id="hidden-sede-destino-id" value="{{ old('sede_destino_id') }}">
        <input type="hidden" name="tipo_combustible_id" id="hidden-tipo-combustible-id" value="{{ old('tipo_combustible_id') }}">
        
        {{-- Inputs corregidos con ID para manipulación dinámica del JS --}}
        <input type="hidden" name="bolsa_origen_tipo" id="hidden-bolsa-origen-tipo" value="{{ old('bolsa_origen_tipo') }}">
        <input type="hidden" name="bolsa_destino_tipo" id="hidden-bolsa-destino-tipo" value="{{ old('bolsa_destino_tipo') }}">

        <div class="row g-3 mb-4">
            {{-- BLOQUE DE DATOS OPERATIVOS --}}
            <div class="col-md-8">
                <div class="card shadow-sm border-0 h-100" style="border-left: 4px solid #ff6600;">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-black text-uppercase small text-dark">
                            <i class="fas fa-dolly-flatbed text-orange me-2"></i> Detalles de la Transferencia Interna
                        </h6>
                        {{-- BADGE DINÁMICO DE VISUALIZACIÓN DE COMBUSTIBLE --}}
                        <div id="badge-combustible-container" style="display: none;">
                            <span class="badge bg-dark text-warning text-uppercase px-3 py-2 fw-black" id="badge-combustible-nombre" style="font-size: 11px; letter-spacing: 0.5px;">
                                <i class="fas fa-gas-pump me-1"></i> Detectado: ---
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            
                            {{-- UBICACIÓN LOGÍSTICA --}}
                            <div class="col-12 col-md-6 mb-2">
                                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Sede de Operación</label>
                                <select name="sede_origen_id" id="select-sede" class="form-select fw-bold text-dark" style="font-size: 14px;" required>
                                    <option value="">-- SELECCIONE LA SEDE --</option>
                                    @foreach($sedes as $sede)
                                        <option value="{{ $sede->id }}" {{ old('sede_origen_id') == $sede->id ? 'selected' : '' }}>
                                            {{ $sede->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <hr class="my-3 text-muted opacity-20">

                            {{-- EMISOR --}}
                            <div class="col-md-6">
                                <span class="badge bg-dark text-white text-uppercase px-2 py-1 mb-2" style="font-size: 10px; letter-spacing: 0.5px;">1. Tanque Emisor (Origen)</span>
                                <label class="small fw-bold text-uppercase text-muted mb-1 d-block" style="font-size: 12px;">Depósito de Salida</label>
                                <select name="deposito_origen_id" id="select-tanque-origen" class="form-select fw-bold text-dark" style="font-size: 14px;" required disabled>
                                    <option value="">-- SELECCIONE TANQUE ORIGEN --</option>
                                </select>
                            </div>

                            {{-- RECEPTOR --}}
                            <div class="col-md-6">
                                <span class="badge bg-orange text-white text-uppercase px-2 py-1 mb-2" style="font-size: 10px; letter-spacing: 0.5px;">2. Tanque Receptor (Destino)</span>
                                <label class="small fw-bold text-uppercase text-muted mb-1 d-block" style="font-size: 12px;">Depósito de Entrada</label>
                                <select name="deposito_destino_id" id="select-tanque-destino" class="form-select fw-bold text-dark" style="font-size: 14px;" required disabled>
                                    <option value="">-- SELECCIONE TANQUE DESTINO --</option>
                                </select>
                            </div>

                            <hr class="my-4 text-muted opacity-20">

                            {{-- VOLUMEN A TRASEGAR --}}
                            <div class="col-12">
                                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Volumen Neto a Trasegar</label>
                                <div class="input-group">
                                    <input type="number" 
                                           name="cantidad_litros" 
                                           class="form-control fw-black text-center" 
                                           step="0.01" 
                                           min="0.01" 
                                           value="{{ old('cantidad_litros') }}"
                                           placeholder="0.00"
                                           required 
                                           style="font-size: 16px; border-radius: 4px 0 0 4px;">
                                    <span class="input-group-text bg-light fw-bold text-muted" style="font-size: 12px;">LTS</span>
                                </div>
                            </div>

                            {{-- OBSERVACIONES / JUSTIFICACIÓN --}}
                            <div class="col-12 mt-3">
                                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Observaciones</label>
                                <textarea name="observaciones" class="form-control fw-bold" rows="2" style="font-size: 13px;" placeholder="Opcional...">{{ old('observaciones') }}</textarea>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            {{-- BLOQUE DE ACCIÓN Y CIERRE DE LOTE --}}
            <div class="col-md-4 d-flex align-items-stretch">
                <div class="card shadow-sm border-0 w-100 bg-dark text-white">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-4">
                        <i class="fas fa-file-signature text-orange fa-2x mb-2"></i>
                        <h5 class="fw-black text-uppercase mb-1" style="font-size: 14px; letter-spacing: 0.5px;">Confirmación de Trasegado</h5>
                        <p class="text-muted small mb-4">Al procesar esta acción, se descontará el volumen del inventario origen y se sumará al destino de forma inmediata en las bitácoras del sistema.</p>
                        
                        <button type="submit" class="btn w-100 fw-black text-uppercase py-2 shadow mb-2" style="color: #000; background-color: #ff6600; border-color: #ff6600; font-size: 13px;">
                            <i class="fas fa-check-circle me-1"></i> Ejecutar Movimiento
                        </button>
                        
                        <a href="{{ route('combustibles.dashboard') }}" class="btn btn-sm btn-outline-light w-100 text-uppercase fw-bold opacity-70" style="font-size: 11px;">
                            Cancelar Operación
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
    const selectTanqueOrigen = document.getElementById('select-tanque-origen');
    const selectTanqueDestino = document.getElementById('select-tanque-destino');
    const hiddenSedeDestino = document.getElementById('hidden-sede-destino-id');
    const hiddenTipoCombustible = document.getElementById('hidden-tipo-combustible-id');
    
    // Selectores de los inputs ocultos de las bolsas
    const hiddenBolsaOrigen = document.getElementById('hidden-bolsa-origen-tipo');
    const hiddenBolsaDestino = document.getElementById('hidden-bolsa-destino-tipo');
    
    // Elementos del Badge Dinámico
    const badgeContainer = document.getElementById('badge-combustible-container');
    const badgeNombre = document.getElementById('badge-combustible-nombre');

    // Mapeo de la colección de tanques desde el controlador
    const tanques = @json($tanques);

    // Captura de valores previos (old)
    const oldTanqueOrigen = "{{ old('deposito_origen_id') }}";
    const oldTanqueDestino = "{{ old('deposito_destino_id') }}";

    selectSede.addEventListener('change', function () {
        const sedeId = this.value;
        hiddenSedeDestino.value = sedeId; 
        
        badgeContainer.style.display = 'none';
        hiddenTipoCombustible.value = "";
        hiddenBolsaOrigen.value = "";
        hiddenBolsaDestino.value = "";

        renderizarTanquesOrigen(sedeId);
    });

    // 1. Renderiza los tanques de origen para la sede elegida
    function renderizarTanquesOrigen(sedeId) {
        selectTanqueOrigen.innerHTML = '<option value="">-- SELECCIONE TANQUE ORIGEN --</option>';
        selectTanqueDestino.innerHTML = '<option value="">-- SELECCIONE PRIMERO EL ORIGEN --</option>';
        selectTanqueDestino.disabled = true;

        if (!sedeId) {
            selectTanqueOrigen.disabled = true;
            return;
        }

        const filtrados = tanques.filter(t => String(t.id_sede) === String(sedeId));

        filtrados.forEach(tank => {
            const litrosActuales = tank.nivel_actual_litros ? parseFloat(tank.nivel_actual_litros) : 0;
            const nivelActual = litrosActuales.toLocaleString('es-VE', { minimumFractionDigits: 2 });
            const productoLabel = tank.combustible_nombre ? tank.combustible_nombre.toUpperCase() : 'N/A';
            
            // Definimos la etiqueta visual de la bolsa para que el operador sepa qué mueve
            const bolsaLabel = tank.llena_cupo_prepagado == 1 ? 'PREPAGADO' : 'GENERAL';
            
            const opt = document.createElement('option');
            opt.value = tank.id;
            opt.textContent = `[${productoLabel}] ${tank.serial} (${bolsaLabel}) — Stock: ${nivelActual} Lts`;
            opt.setAttribute('data-combustible-id', tank.tipo_combustible_id);
            opt.setAttribute('data-combustible-name', productoLabel);
            
            if (String(tank.id) === String(oldTanqueOrigen)) opt.selected = true;
            selectTanqueOrigen.appendChild(opt);
        });

        selectTanqueOrigen.disabled = false;

        if (selectTanqueOrigen.value) {
            selectTanqueOrigen.dispatchEvent(new Event('change'));
        }
    }

    // 2. Al seleccionar el Origen, leemos producto, definimos su bolsa y filtramos destinos
    selectTanqueOrigen.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (!selectedOption || selectedOption.value === "") {
            badgeContainer.style.display = 'none';
            hiddenTipoCombustible.value = "";
            hiddenBolsaOrigen.value = "";
            selectTanqueDestino.innerHTML = '<option value="">-- SELECCIONE PRIMERO EL ORIGEN --</option>';
            selectTanqueDestino.disabled = true;
            return;
        }

        const tanqueId = selectedOption.value;
        const combustibleId = selectedOption.getAttribute('data-combustible-id');
        const combustibleName = selectedOption.getAttribute('data-combustible-name');

        // Seteamos el tipo de combustible id
        hiddenTipoCombustible.value = combustibleId;

        // Buscamos el objeto del tanque para determinar dinámicamente el ENUM de la bolsa
        const tanqueSeleccionado = tanques.find(t => String(t.id) === String(tanqueId));
        if (tanqueSeleccionado) {
            hiddenBolsaOrigen.value = tanqueSeleccionado.llena_cupo_prepagado == 1 ? 'prepagado' : 'general';
        }

        // Encendemos el Badge de Seguridad
        badgeNombre.innerHTML = `<i class="fas fa-gas-pump me-1"></i> DETECTADO: ${combustibleName}`;
        badgeContainer.style.display = 'block';

        renderizarTanquesDestino(selectSede.value, combustibleId);
    });

    function renderizarTanquesDestino(sedeId, combustibleId) {
        selectTanqueDestino.innerHTML = '<option value="">-- SELECCIONE TANQUE DESTINO --</option>';

        const destinosCompatibles = tanques.filter(t => 
            String(t.id_sede) === String(sedeId) && 
            String(t.tipo_combustible_id) === String(combustibleId) &&
            String(t.id) !== String(selectTanqueOrigen.value)
        );

        destinosCompatibles.forEach(tank => {
            const capMaxima = tank.capacidad_maxima ? parseFloat(tank.capacidad_maxima) : 0;
            const litrosActuales = tank.nivel_actual_litros ? parseFloat(tank.nivel_actual_litros) : 0;
            const disponibleNum = Math.max(0, capMaxima - litrosActuales);
            const disponible = disponibleNum.toLocaleString('es-VE', { minimumFractionDigits: 2 });
            const productoLabel = tank.combustible_nombre ? tank.combustible_nombre.toUpperCase() : 'N/A';
            const bolsaLabel = tank.llena_cupo_prepagado == 1 ? 'PREPAGADO' : 'GENERAL';

            const opt = document.createElement('option');
            opt.value = tank.id;
            opt.textContent = `[${productoLabel}] ${tank.serial} (${bolsaLabel}) — Disp: ${disponible} Lts`;
            
            if (String(tank.id) === String(oldTanqueDestino)) opt.selected = true;
            selectTanqueDestino.appendChild(opt);
        });

        selectTanqueDestino.disabled = false;

        if (selectTanqueDestino.value) {
            selectTanqueDestino.dispatchEvent(new Event('change'));
        }
    }

    // 3. Al seleccionar el Destino, capturamos su tipo de bolsa
    selectTanqueDestino.addEventListener('change', function() {
        if (this.value === "") {
            hiddenBolsaDestino.value = "";
            return;
        }
        const tanqueSeleccionado = tanques.find(t => String(t.id) === String(this.value));
        if (tanqueSeleccionado) {
            hiddenBolsaDestino.value = tanqueSeleccionado.llena_cupo_prepagado == 1 ? 'prepagado' : 'general';
        }
    });

    // Mantener estado ante fallos de validación del Request de Laravel
    if (selectSede.value) {
        hiddenSedeDestino.value = selectSede.value;
        renderizarTanquesOrigen(selectSede.value);
    }
});
</script>

<style>
    .text-orange { color: #ff6600 !important; }
    .bg-orange { background-color: #ff6600 !important; }
    .fw-black { font-weight: 900; }
</style>
@endsection