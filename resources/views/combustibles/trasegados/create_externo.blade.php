@extends('layouts.app')
@section('title', 'Registrar Trasegado Externo')

@section('content')
<div class="container-fluid py-4 px-4">

    {{-- ENCABEZADO --}}
    <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h2 class="h4 fw-black text-dark text-uppercase mb-1">
                <i class="fas fa-external-link-alt text-orange me-2"></i> Nuevo Trasegado Externo
            </h2>
            <p class="text-muted small mb-0">Prestamos con Aliados Comerciales</p>
        </div>
        <div class="d-flex flex-wrap align-items-center justify-content-md-end gap-2">
            <a href="{{ route('combustibles.trasegados.index') }}" class="btn btn-sm btn-dark fw-bold text-uppercase shadow-sm px-3 d-inline-flex align-items-center" style="font-size: 12px; height: 32px;">
                <i class="fas fa-history me-1"></i> Volver al Historial
            </a>
        </div>
    </div>

    {{-- ALERTA DE SEGURIDAD OPERATIVA --}}
    <div class="alert bg-white shadow-sm d-flex align-items-center mb-4 py-3" style="border-left: 4px solid #ff6600; border-radius: 4px;">
        <i class="fas fa-truck-loading text-orange fa-lg me-3"></i>
        <div>
            <strong class="text-dark d-block" style="font-size: 14px;">Operación de Inventario con Fuente / Destino Externo</strong>
            <span class="text-muted small">Seleccione el sentido del movimiento. Una <strong>Salida Externa</strong> descontará stock de un tanque propio, mientras que una <strong>Entrada Externa</strong> incrementará el inventario del tanque receptor.</span>
        </div>
    </div>

    {{-- CAPTURA DE ERRORES --}}
    @if ($errors->any() || session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <div class="fw-bold mb-1"><i class="fas fa-exclamation-triangle me-2"></i> Error al procesar el trasegado externo:</div>
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
    <form action="{{ route('combustibles.trasegados.store') }}" method="POST" id="form-trasegado-externo">
        @csrf

        {{-- METADATA REQUERIDA --}}
        <input type="hidden" name="tipo_trasegado" value="externo">
        <input type="hidden" name="tipo_combustible_id" id="hidden-tipo-combustible-id" value="{{ old('tipo_combustible_id') }}">
        
        <input type="hidden" name="bolsa_origen_tipo" id="hidden-bolsa-origen-tipo" value="{{ old('bolsa_origen_tipo') }}">
        <input type="hidden" name="bolsa_destino_tipo" id="hidden-bolsa-destino-tipo" value="{{ old('bolsa_destino_tipo') }}">

        <div class="row g-3 mb-4">
            
            {{-- BLOQUE PRINCIPAL DE CAPTURA --}}
            <div class="col-md-8">
                <div class="card shadow-sm border-0 h-100" style="border-left: 4px solid #ff6600;">
                    
                    {{-- CABECERA CARD CON BADGE DINÁMICO --}}
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-black text-uppercase small text-dark">
                            <i class="fas fa-sliders-h text-orange me-2"></i> Parámetros del Trasegado Externo
                        </h6>
                        <div id="badge-combustible-container" style="display: none;">
                            <span class="badge bg-dark text-warning text-uppercase px-3 py-2 fw-black" id="badge-combustible-nombre" style="font-size: 11px; letter-spacing: 0.5px;">
                                <i class="fas fa-gas-pump me-1"></i> Detectado: ---
                            </span>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <div class="row g-3">

                            {{-- 1. SELECCIÓN DE SENTIDO DE LA OPERACIÓN --}}
                            <div class="col-12 mb-2">
                                <label class="small fw-bold text-uppercase text-muted mb-2 d-block" style="font-size: 12px;">Sentido de la Operación</label>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <input type="radio" class="btn-check" name="direccion_movimiento" id="direccion-salida" value="salida" {{ old('direccion_movimiento', 'salida') === 'salida' ? 'checked' : '' }} autocomplete="off">
                                        <label class="btn btn-outline-dark w-100 fw-bold text-uppercase p-3 text-start d-flex align-items-center justify-content-between" for="direccion-salida" style="border-radius: 6px; font-size: 13px;">
                                            <div>
                                                <i class="fas fa-arrow-up-from-bracket text-orange fa-lg me-2"></i>
                                                <span>Salida Externa</span>
                                                <small class="d-block text-muted text-transform-none mt-1 fw-normal" style="font-size: 11px;">Despacho / Resta stock de tanque interno</small>
                                            </div>
                                            <i class="fas fa-check-circle check-icon"></i>
                                        </label>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="radio" class="btn-check" name="direccion_movimiento" id="direccion-entrada" value="entrada" {{ old('direccion_movimiento') === 'entrada' ? 'checked' : '' }} autocomplete="off">
                                        <label class="btn btn-outline-dark w-100 fw-bold text-uppercase p-3 text-start d-flex align-items-center justify-content-between" for="direccion-entrada" style="border-radius: 6px; font-size: 13px;">
                                            <div>
                                                <i class="fas fa-arrow-down-to-bracket text-success fa-lg me-2"></i>
                                                <span>Entrada Externa</span>
                                                <small class="d-block text-muted text-transform-none mt-1 fw-normal" style="font-size: 11px;">Recepción / Suma stock a tanque interno</small>
                                            </div>
                                            <i class="fas fa-check-circle check-icon"></i>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-3 text-muted opacity-20">

                            {{-- 2. SECCIÓN DINÁMICA: TANQUE INTERNO INVOLUCRADO --}}
                            
                            {{-- BLOQUE ORIGEN (Si es Salida Externa) --}}
                            <div id="bloque-origen" class="col-12">
                                <span class="badge bg-dark text-white text-uppercase px-2 py-1 mb-2" style="font-size: 10px; letter-spacing: 0.5px;">Tanque Emisor (Origen Interno)</span>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Sede Origen</label>
                                        <select name="sede_origen_id" id="select-sede-origen" class="form-select fw-bold text-dark" style="font-size: 14px;">
                                            <option value="">-- SELECCIONE SEDE ORIGEN --</option>
                                            @foreach($sedes as $sede)
                                                <option value="{{ $sede->id }}" {{ old('sede_origen_id') == $sede->id ? 'selected' : '' }}>
                                                    {{ $sede->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Depósito de Salida</label>
                                        <select name="deposito_origen_id" id="select-tanque-origen" class="form-select fw-bold text-dark" style="font-size: 14px;" disabled>
                                            <option value="">-- SELECCIONE PRIMERO LA SEDE --</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- BLOQUE DESTINO (Si es Entrada Externa) --}}
                            <div id="bloque-destino" class="col-12" style="display: none;">
                                <span class="badge bg-orange text-white text-uppercase px-2 py-1 mb-2" style="font-size: 10px; letter-spacing: 0.5px;">Tanque Receptor (Destino Interno)</span>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Sede Destino</label>
                                        <select name="sede_destino_id" id="select-sede-destino" class="form-select fw-bold text-dark" style="font-size: 14px;">
                                            <option value="">-- SELECCIONE SEDE DESTINO --</option>
                                            @foreach($sedes as $sede)
                                                <option value="{{ $sede->id }}" {{ old('sede_destino_id') == $sede->id ? 'selected' : '' }}>
                                                    {{ $sede->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Depósito de Entrada</label>
                                        <select name="deposito_destino_id" id="select-tanque-destino" class="form-select fw-bold text-dark" style="font-size: 14px;" disabled>
                                            <option value="">-- SELECCIONE PRIMERO LA SEDE --</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-3 text-muted opacity-20">

                            {{-- 3. DATOS DE LA ENTIDAD EXTERNA Y VOLUMEN --}}
                            <div class="col-md-6">
                                <label class="small fw-bold text-uppercase text-muted mb-1" id="label-entidad-externa" style="font-size: 12px;">Entidad / Cisterna Externa</label>

                                {{-- SELECT CON ALIADOS COMERCIALES REGISTRADOS --}}
                                <select name="cliente_id" id="select-aliado-comercial" class="form-select fw-bold text-dark mb-2" style="font-size: 14px;">
                                    <option value="">-- SELECCIONE ALIADO REGISTRADO (OPCIONAL) --</option>
                                    @if(isset($aliadosComerciales))
                                        @foreach($aliadosComerciales as $aliado)
                                            <option value="{{ $aliado->id }}" data-nombre="{{ $aliado->nombre }}" {{ old('cliente_id') == $aliado->id ? 'selected' : '' }}>
                                                {{ $aliado->nombre }}
                                            </option>
                                        @endforeach
                                    @elseif(isset($aliados))
                                        @foreach($aliados as $aliado)
                                            <option value="{{ $aliado->id }}" data-nombre="{{ $aliado->nombre }}" {{ old('cliente_id') == $aliado->id ? 'selected' : '' }}>
                                                {{ $aliado->nombre }}
                                            </option>
                                        @endforeach
                                    @endif
                                    <option value="OTRO" {{ old('cliente_id') === 'OTRO' ? 'selected' : '' }}>✏️ INGRESE NOMBRE</option>
                                </select>

                                {{-- CAMPO DE TEXTO MANUAL (MANTIENE SU COMPORTAMIENTO Y ESTILO) --}}
                                <div class="input-group">
                                    <span class="input-group-text bg-light text-muted"><i class="fas fa-building"></i></span>
                                    <input type="text" 
                                           name="entidad_externa" 
                                           id="input-entidad-externa"
                                           class="form-control fw-bold" 
                                           value="{{ old('entidad_externa') }}"
                                           placeholder="Ej: Bolipuertos"
                                           style="font-size: 14px;">
                                </div>
                            </div>

                            <div class="col-md-6">
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

                            {{-- OBSERVACIONES / DETALLES ADICIONALES --}}
                            <div class="col-12 mt-3">
                                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Observaciones</label>
                                <textarea name="observaciones" class="form-control fw-bold" rows="2" style="font-size: 13px;" placeholder="Opcional...">{{ old('observaciones') }}</textarea>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            {{-- BLOQUE DE ACCIÓN Y CONFIRMACIÓN --}}
            <div class="col-md-4 d-flex align-items-stretch">
                <div class="card shadow-sm border-0 w-100 bg-dark text-white">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-4">
                        <i class="fas fa-file-signature text-orange fa-2x mb-2"></i>
                        <h5 class="fw-black text-uppercase mb-1" style="font-size: 14px; letter-spacing: 0.5px;">Confirmación Externa</h5>
                        <p class="text-muted small mb-4">Esta acción afectará el inventario del tanque interno seleccionado de forma inmediata. Verifique los datos físicamente antes de procesar.</p>
                        
                        <button type="submit" class="btn w-100 fw-black text-uppercase py-2 shadow mb-2" style="color: #000; background-color: #ff6600; border-color: #ff6600; font-size: 13px;">
                            <i class="fas fa-check-circle me-1"></i> Registrar Operación
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
    const radiosDireccion = document.getElementsByName('direccion_movimiento');
    
    const bloqueOrigen = document.getElementById('bloque-origen');
    const bloqueDestino = document.getElementById('bloque-destino');

    const selectSedeOrigen = document.getElementById('select-sede-origen');
    const selectTanqueOrigen = document.getElementById('select-tanque-origen');
    
    const selectSedeDestino = document.getElementById('select-sede-destino');
    const selectTanqueDestino = document.getElementById('select-tanque-destino');

    const labelEntidadExterna = document.getElementById('label-entidad-externa');
    const inputEntidadExterna = document.getElementById('input-entidad-externa');
    const selectAliadoComercial = document.getElementById('select-aliado-comercial');

    const hiddenTipoCombustible = document.getElementById('hidden-tipo-combustible-id');
    const hiddenBolsaOrigen = document.getElementById('hidden-bolsa-origen-tipo');
    const hiddenBolsaDestino = document.getElementById('hidden-bolsa-destino-tipo');

    const badgeContainer = document.getElementById('badge-combustible-container');
    const badgeNombre = document.getElementById('badge-combustible-nombre');

    const tanques = @json($tanques);

    const oldTanqueOrigen = "{{ old('deposito_origen_id') }}";
    const oldTanqueDestino = "{{ old('deposito_destino_id') }}";

    // Event listener para auto-rellenar o limpiar la Entidad Externa al cambiar el Select
    if (selectAliadoComercial) {
        selectAliadoComercial.addEventListener('change', function () {
            const selectedOpt = this.options[this.selectedIndex];
            const nombreAliado = selectedOpt.getAttribute('data-nombre');
            
            if (nombreAliado) {
                inputEntidadExterna.value = nombreAliado;
            } else if (this.value === 'OTRO') {
                inputEntidadExterna.value = '';
                inputEntidadExterna.focus();
            }
        });
    }

    // Obtenemos la dirección seleccionada ('salida' o 'entrada')
    function getDireccion() {
        let val = 'salida';
        radiosDireccion.forEach(r => { if (r.checked) val = r.value; });
        return val;
    }

    // Switchear vista entre Salida Externa y Entrada Externa
    function actualizarDireccionUI() {
        const dir = getDireccion();

        if (dir === 'salida') {
            bloqueOrigen.style.display = 'block';
            bloqueDestino.style.display = 'none';

            // Activar Origen, desactivar y limpiar Destino
            selectSedeOrigen.disabled = false;
            selectSedeDestino.disabled = true;
            selectSedeDestino.value = "";
            selectTanqueDestino.innerHTML = '<option value="">-- SELECCIONE PRIMERO LA SEDE --</option>';
            selectTanqueDestino.disabled = true;

            hiddenBolsaDestino.value = "";
            labelEntidadExterna.textContent = "Entidad / Destino Externo (Receptor)";
            inputEntidadExterna.placeholder = "Ej: Bolipuertos";

            if (selectSedeOrigen.value) renderizarTanquesOrigen(selectSedeOrigen.value);

        } else {
            bloqueOrigen.style.display = 'none';
            bloqueDestino.style.display = 'block';

            // Activar Destino, desactivar y limpiar Origen
            selectSedeDestino.disabled = false;
            selectSedeOrigen.disabled = true;
            selectSedeOrigen.value = "";
            selectTanqueOrigen.innerHTML = '<option value="">-- SELECCIONE PRIMERO LA SEDE --</option>';
            selectTanqueOrigen.disabled = true;

            hiddenBolsaOrigen.value = "";
            labelEntidadExterna.textContent = "Entidad / Origen Externo (Proveedor)";
            inputEntidadExterna.placeholder = "Ej: Cisterna PDVSA / Proveedor Externo / Guía #";

            if (selectSedeDestino.value) renderizarTanquesDestino(selectSedeDestino.value);
        }

        evaluarBadgeCombustible();
    }

    // Renderizar tanques para Origen (Salida)
    function renderizarTanquesOrigen(sedeId) {
        selectTanqueOrigen.innerHTML = '<option value="">-- SELECCIONE TANQUE ORIGEN --</option>';

        if (!sedeId) {
            selectTanqueOrigen.disabled = true;
            return;
        }

        const filtrados = tanques.filter(t => String(t.id_sede) === String(sedeId));

        filtrados.forEach(tank => {
            const litrosActuales = tank.nivel_actual_litros ? parseFloat(tank.nivel_actual_litros) : 0;
            const nivelActual = litrosActuales.toLocaleString('es-VE', { minimumFractionDigits: 2 });
            const productoLabel = tank.combustible_nombre ? tank.combustible_nombre.toUpperCase() : 'N/A';
            const bolsaLabel = tank.llena_cupo_prepagado == 1 ? 'PREPAGADO' : 'GENERAL';

            const opt = document.createElement('option');
            opt.value = tank.id;
            opt.textContent = `[${productoLabel}] ${tank.serial} (${bolsaLabel}) — Stock: ${nivelActual} Lts`;

            if (String(tank.id) === String(oldTanqueOrigen)) opt.selected = true;
            selectTanqueOrigen.appendChild(opt);
        });

        selectTanqueOrigen.disabled = false;
        procesarSeleccionTanqueOrigen();
    }

    // Renderizar tanques para Destino (Entrada)
    function renderizarTanquesDestino(sedeId) {
        selectTanqueDestino.innerHTML = '<option value="">-- SELECCIONE TANQUE DESTINO --</option>';

        if (!sedeId) {
            selectTanqueDestino.disabled = true;
            return;
        }

        const filtrados = tanques.filter(t => String(t.id_sede) === String(sedeId));

        filtrados.forEach(tank => {
            const litrosActuales = tank.nivel_actual_litros ? parseFloat(tank.nivel_actual_litros) : 0;
            const nivelActual = litrosActuales.toLocaleString('es-VE', { minimumFractionDigits: 2 });
            const productoLabel = tank.combustible_nombre ? tank.combustible_nombre.toUpperCase() : 'N/A';
            const bolsaLabel = tank.llena_cupo_prepagado == 1 ? 'PREPAGADO' : 'GENERAL';

            const opt = document.createElement('option');
            opt.value = tank.id;
            opt.textContent = `[${productoLabel}] ${tank.serial} (${bolsaLabel}) — Stock: ${nivelActual} Lts`;

            if (String(tank.id) === String(oldTanqueDestino)) opt.selected = true;
            selectTanqueDestino.appendChild(opt);
        });

        selectTanqueDestino.disabled = false;
        procesarSeleccionTanqueDestino();
    }

    // Setear metadata al seleccionar Tanque Origen
    function procesarSeleccionTanqueOrigen() {
        const tank = tanques.find(t => String(t.id) === String(selectTanqueOrigen.value));

        if (tank) {
            hiddenTipoCombustible.value = tank.tipo_combustible_id;
            hiddenBolsaOrigen.value = (tank.llena_cupo_prepagado == 1 || tank.llena_cupo_prepagado === true) ? 'prepagado' : 'general';
            mostrarBadgeCombustible(tank.combustible_nombre);
        } else {
            hiddenTipoCombustible.value = "";
            hiddenBolsaOrigen.value = "";
            ocultarBadgeCombustible();
        }
    }

    // Setear metadata al seleccionar Tanque Destino
    function procesarSeleccionTanqueDestino() {
        const tank = tanques.find(t => String(t.id) === String(selectTanqueDestino.value));

        if (tank) {
            hiddenTipoCombustible.value = tank.tipo_combustible_id;
            hiddenBolsaDestino.value = (tank.llena_cupo_prepagado == 1 || tank.llena_cupo_prepagado === true) ? 'prepagado' : 'general';
            mostrarBadgeCombustible(tank.combustible_nombre);
        } else {
            hiddenTipoCombustible.value = "";
            hiddenBolsaDestino.value = "";
            ocultarBadgeCombustible();
        }
    }

    function evaluarBadgeCombustible() {
        const dir = getDireccion();
        if (dir === 'salida') {
            procesarSeleccionTanqueOrigen();
        } else {
            procesarSeleccionTanqueDestino();
        }
    }

    function mostrarBadgeCombustible(nombre) {
        if (badgeNombre && badgeContainer) {
            badgeNombre.innerHTML = `<i class="fas fa-gas-pump me-1"></i> Detectado: ${nombre ? nombre.toUpperCase() : 'N/A'}`;
            badgeContainer.style.display = 'block';
        }
    }

    function ocultarBadgeCombustible() {
        if (badgeContainer) badgeContainer.style.display = 'none';
    }

    // EVENT LISTENERS
    radiosDireccion.forEach(r => r.addEventListener('change', actualizarDireccionUI));

    selectSedeOrigen.addEventListener('change', function () {
        renderizarTanquesOrigen(this.value);
    });

    selectSedeDestino.addEventListener('change', function () {
        renderizarTanquesDestino(this.value);
    });

    selectTanqueOrigen.addEventListener('change', procesarSeleccionTanqueOrigen);
    selectTanqueDestino.addEventListener('change', procesarSeleccionTanqueDestino);

    // INICIALIZACIÓN DE ESTADO Y RECUPERACIÓN DE OLD()
    actualizarDireccionUI();

    if (getDireccion() === 'salida' && selectSedeOrigen.value) {
        renderizarTanquesOrigen(selectSedeOrigen.value);
    } else if (getDireccion() === 'entrada' && selectSedeDestino.value) {
        renderizarTanquesDestino(selectSedeDestino.value);
    }
});
</script>

<style>
    .text-orange { color: #ff6600 !important; }
    .bg-orange { background-color: #ff6600 !important; }
    .fw-black { font-weight: 900; }
    .btn-check:checked + .btn-outline-dark {
        background-color: #1a1a1a !important;
        color: #ffffff !important;
        border-color: #ff6600 !important;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    .btn-check:checked + .btn-outline-dark .check-icon {
        color: #ff6600;
        display: inline-block !important;
    }
    .btn-check:not(:checked) + .btn-outline-dark .check-icon {
        display: none !important;
    }
</style>
@endsection