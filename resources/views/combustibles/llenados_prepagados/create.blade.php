@extends('layouts.app')
@section('title', 'Registrar Llenado Prepagado')

@section('content')
<div class="container-fluid py-4 px-4">

    {{-- ENCABEZADO --}}
    <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h2 class="h4 fw-black text-dark text-uppercase mb-1">
                <i class="fas fa-gas-pump text-orange me-2"></i> Nuevo Llenado de Vehículo
            </h2>
            <p class="text-muted small mb-0">Modalidad especial para clientes con despacho directo en sedes de Impordiesel</p>
        </div>
        <div class="d-flex flex-wrap align-items-center justify-content-md-end gap-2">
            <a href="{{ route('combustibles.llenados_prepagados.index') }}" class="btn btn-sm btn-dark fw-bold text-uppercase shadow-sm px-3 d-inline-flex align-items-center" style="font-size: 12px; height: 32px;">
                <i class="fas fa-history me-1"></i> Volver al Historial
            </a>
        </div>
    </div>

    {{-- ALERTA DE SEGURIDAD OPERATIVA --}}
    <div class="alert bg-white shadow-sm d-flex align-items-center mb-4 py-3" style="border-left: 4px solid #ff6600; border-radius: 4px;">
        <i class="fas fa-shield-alt text-orange fa-lg me-3"></i>
        <div>
            <strong class="text-dark d-block" style="font-size: 14px;">Validación de Cupo en Tiempo Real</strong>
            <span class="text-muted small">Si el despacho es de Diésel, el sistema comprobará y descontará automáticamente el disponible del Cupo Gasco del cliente.</span>
        </div>
    </div>

    {{-- CAPTURA DE ERRORES DE VALIDACIÓN O EXCEPCIONES DEL SERVICE --}}
    @if ($errors->any() || Session::has('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <div class="fw-bold mb-1"><i class="fas fa-exclamation-triangle me-2"></i> Error al procesar la solicitud:</div>
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

    {{-- FORMULARIO PRINCIPAL --}}
    <form action="{{ route('combustibles.llenados_prepagados.store') }}" method="POST">
        @csrf

        <div class="row g-3 mb-4">
            {{-- BLOQUE DE DATOS OPERATIVOS --}}
            <div class="col-md-8">
                <div class="card shadow-sm border-0 h-100" style="border-left: 4px solid #ff6600;">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-black text-uppercase small text-dark">
                            <i class="fas fa-edit text-orange me-2"></i> Datos del Despacho
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            
                            {{-- SELECCIÓN DE CLIENTE --}}
                            <div class="col-12">
                                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Cliente Corporativo</label>
                                <select name="cliente_id" id="select-cliente" class="form-select fw-bold text-dark" style="font-size: 14px;" required>
                                    <option value="">-- SELECCIONE EL CLIENTE --</option>
                                    @foreach($clientes as $cliente)
                                        <option value="{{ $cliente->id }}" {{ old('cliente_id') == $cliente->id ? 'selected' : '' }}>
                                            {{ $cliente->nombre }} (RIF: {{ $cliente->rif }}) — Disp: {{ number_format($cliente->disponible, 2, ',', '.') }} Lts
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- SELECCIÓN DE CHOFER AUTORIZADO (Dependiente del Cliente) --}}
                            <div class="col-md-6">
                                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Chofer Autorizado</label>
                                <select name="chofer_cliente_id" id="select-chofer" class="form-select fw-bold text-dark" style="font-size: 14px;" required disabled>
                                    <option value="">-- SELECCIONE CHOFER --</option>
                                    <option value="nuevo" {{ old('chofer_cliente_id') == 'nuevo' ? 'selected' : '' }} class="text-orange fw-bold">
                                        [+] REGISTRAR NUEVO CHOFER
                                    </option>
                                    @foreach($choferes as $chofer)
                                        <option value="{{ $chofer->id }}" data-cliente="{{ $chofer->cliente_id }}" {{ old('chofer_cliente_id') == $chofer->id ? 'selected' : '' }} style="display: none;">
                                            {{ $chofer->nombre_completo }} (CI: {{ $chofer->cedula }})
                                        </option>
                                    @endforeach
                                </select>

                                {{-- CAMPOS CONDICIONALES PARA NUEVO CHOFER --}}
                                <div id="div-nuevo-chofer" class="mt-2 p-3 bg-light border rounded row g-2" style="display: none;">
                                    <div class="col-12">
                                        <span class="badge bg-orange text-white text-uppercase mb-2" style="font-size: 10px;">Nuevo Registro</span>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="small fw-bold text-muted mb-1">Nombre Completo</label>
                                        <input type="text" name="nuevo_chofer_nombre" id="input-chofer-nombre" class="form-control form-control-sm text-uppercase" value="{{ old('nuevo_chofer_nombre') }}" placeholder="Ej. Juan Pérez">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="small fw-bold text-muted mb-1">Cédula de Identidad</label>
                                        <input type="text" name="nuevo_chofer_cedula" id="input-chofer-cedula" class="form-control form-control-sm" value="{{ old('nuevo_chofer_cedula') }}" placeholder="Ej. 12345678" maxlength="8" inputmode="numeric">                                    </div>
                                </div>
                            </div>

                            {{-- SELECCIÓN DE PLACA DEL VEHÍCULO (Dependiente del Cliente) --}}
                            <div class="col-md-6">
                                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Vehículo / Placa</label>
                                <select name="placa_vehiculo_id" id="select-placa" class="form-select fw-bold text-dark" style="font-size: 14px;" required disabled>
                                    <option value="">-- SELECCIONE PLACA --</option>
                                    <option value="nuevo" {{ old('placa_vehiculo_id') == 'nuevo' ? 'selected' : '' }} class="text-orange fw-bold">
                                        [+] REGISTRAR NUEVA PLACA
                                    </option>
                                    @foreach($placas as $placa)
                                        <option value="{{ $placa->id }}" data-cliente="{{ $placa->cliente_id }}" {{ old('placa_vehiculo_id') == $placa->id ? 'selected' : '' }} style="display: none;">
                                            {{ $placa->placa }}
                                        </option>
                                    @endforeach
                                </select>

                                {{-- CAMPOS CONDICIONALES PARA NUEVA PLACA --}}
                                <div id="div-nueva-placa" class="mt-2 p-3 bg-light border rounded" style="display: none;">
                                    <span class="badge bg-orange text-white text-uppercase mb-2" style="font-size: 10px;">Nuevo Registro</span>
                                    <div>
                                        <label class="small fw-bold text-muted mb-1">Número de Placa</label>
                                        <input type="text" name="nueva_placa_numero" id="input-placa-numero" class="form-control form-control-sm text-uppercase" value="{{ old('nueva_placa_numero') }}" placeholder="Ej. AB123CD">
                                    </div>
                                </div>
                            </div>

                            {{-- SELECCIÓN DE SEDE EMISORA --}}
                            <div class="col-md-6">
                                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Sede de Despacho</label>
                                <select name="id_sede" id="select-sede" class="form-select fw-bold text-dark" style="font-size: 14px;" required>
                                    <option value="">-- SELECCIONE LA SEDE --</option>
                                    @foreach($sedes as $sede)
                                        <option value="{{ $sede->id }}" {{ old('id_sede') == $sede->id ? 'selected' : '' }}>
                                            {{ $sede->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- SELECCIÓN DE TANQUE AUTORIZADO (Dependiente de la Sede) --}}
                            <div class="col-md-6">
                                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Tanque Emisor</label>
                                <select name="id_deposito" id="select-tanque" class="form-select fw-bold text-dark" style="font-size: 14px;" required disabled>
                                    <option value="">-- SELECCIONE TANQUE --</option>
                                    @foreach($tanques as $tanque)
                                        <option value="{{ $tanque->id }}" data-sede="{{ $tanque->id_sede }}" {{ old('id_deposito') == $tanque->id ? 'selected' : '' }} style="display: none;">
                                            {{ $tanque->serial }} — Stock Actual: {{ number_format($tanque->nivel_actual_litros, 2, ',', '.') }} Lts
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- VOLUMEN A SURTIR --}}
                            <div class="col-12">
                                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Litros a Despachar</label>
                                <div class="input-group">
                                    <input type="number" 
                                           name="litros" 
                                           class="form-control fw-black text-center" 
                                           step="0.01" 
                                           min="0.01" 
                                           value="{{ old('litros') }}"
                                           placeholder="0.00"
                                           required 
                                           style="font-size: 16px; border-radius: 4px 0 0 4px;">
                                    <span class="input-group-text bg-light fw-bold text-muted" style="font-size: 12px;">LTS</span>
                                </div>
                            </div>

                            {{-- OBSERVACIONES --}}
                            <div class="col-12">
                                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Observaciones</label>
                                <textarea name="observaciones" 
                                        class="form-control fw-bold text-dark" 
                                        rows="2" 
                                        style="font-size: 13px;" 
                                        placeholder="Notas u observaciones adicionales">{{ old('observaciones') }}</textarea>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            {{-- BLOQUE DE FIRMA Y DE ACCIÓN --}}
            <div class="col-md-4 d-flex align-items-stretch">
                <div class="card shadow-sm border-0 w-100 bg-dark text-white">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-4">
                        <i class="fas fa-file-signature text-orange fa-2x mb-2"></i>
                        <h5 class="fw-black text-uppercase mb-1" style="font-size: 14px; letter-spacing: 0.5px;">Cierre de Lote Seguro</h5>
                        <p class="text-muted small mb-4">Al procesar, la transacción restará inventario físico y afectará el Cupo Gasco de manera irreversible para auditorías ministeriales.</p>
                        
                        <button type="submit" class="btn btn-warning w-100 fw-black text-uppercase py-2 shadow mb-2" style="color: #000; font-size: 13px; background-color: #ff6600; border-color: #ff6600;">
                            <i class="fas fa-check-circle me-1"></i> Autorizar y Surtir
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
    const selectTanque = document.getElementById('select-tanque');
    const optionsTanque = Array.from(selectTanque.options);

    const selectCliente = document.getElementById('select-cliente');
    const selectChofer = document.getElementById('select-chofer');
    const selectPlaca = document.getElementById('select-placa');
    
    const optionsChofer = Array.from(selectChofer.options);
    const optionsPlaca = Array.from(selectPlaca.options);

    const divNuevoChofer = document.getElementById('div-nuevo-chofer');
    const inputChoferNombre = document.getElementById('input-chofer-nombre');
    const inputChoferCedula = document.getElementById('input-chofer-cedula');

    const divNuevaPlaca = document.getElementById('div-nueva-placa');
    const inputPlacaNumero = document.getElementById('input-placa-numero');

    function filtrarTanques() {
        const sedeId = selectSede.value;

        if (!sedeId) {
            selectTanque.value = "";
            selectTanque.disabled = true;
            optionsTanque.forEach(opt => {
                if (opt.value !== "") opt.style.display = 'none';
            });
            return;
        }

        selectTanque.disabled = false;
        let coincidenciaEncontrada = false;

        optionsTanque.forEach(opt => {
            if (opt.value === "") {
                opt.style.display = 'block';
            } else if (opt.getAttribute('data-sede') === sedeId) {
                opt.style.display = 'block';
                if (opt.value === selectTanque.value) {
                    coincidenciaEncontrada = true;
                }
            } else {
                opt.style.display = 'none';
            }
        });

        if (!coincidenciaEncontrada && selectTanque.value !== "") {
            selectTanque.value = "";
        }
    }

    function filtrarDatosCliente() {
        const clienteId = selectCliente.value;

        if (!clienteId) {
            selectChofer.value = "";
            selectPlaca.value = "";
            selectChofer.disabled = true;
            selectPlaca.disabled = true;
            
            optionsChofer.forEach(opt => { if (opt.value !== "") opt.style.display = 'none'; });
            optionsPlaca.forEach(opt => { if (opt.value !== "") opt.style.display = 'none'; });
            
            toggleNuevoChofer();
            toggleNuevaPlaca();
            return;
        }

        selectChofer.disabled = false;
        selectPlaca.disabled = false;

        let choferCoincide = false;
        let placaCoincide = false;

        optionsChofer.forEach(opt => {
            if (opt.value === "" || opt.value === "nuevo") {
                opt.style.display = 'block';
                if (opt.value === selectChofer.value) choferCoincide = true;
            } else if (opt.getAttribute('data-cliente') === clienteId) {
                opt.style.display = 'block';
                if (opt.value === selectChofer.value) choferCoincide = true;
            } else {
                opt.style.display = 'none';
            }
        });

        optionsPlaca.forEach(opt => {
            if (opt.value === "" || opt.value === "nuevo") {
                opt.style.display = 'block';
                if (opt.value === selectPlaca.value) placaCoincide = true;
            } else if (opt.getAttribute('data-cliente') === clienteId) {
                opt.style.display = 'block';
                if (opt.value === selectPlaca.value) placaCoincide = true;
            } else {
                opt.style.display = 'none';
            }
        });

        if (!choferCoincide && selectChofer.value !== "") selectChofer.value = "";
        if (!placaCoincide && selectPlaca.value !== "") selectPlaca.value = "";

        toggleNuevoChofer();
        toggleNuevaPlaca();
    }

    function toggleNuevoChofer() {
        if (selectChofer.value === 'nuevo') {
            divNuevoChofer.style.display = 'flex';
            inputChoferNombre.required = true;
            inputChoferCedula.required = true;
        } else {
            divNuevoChofer.style.display = 'none';
            inputChoferNombre.required = false;
            inputChoferNombre.value = '';
            inputChoferCedula.required = false;
            inputChoferCedula.value = '';
        }
    }

    // Muestra u oculta la sección para tipear la placa
    function toggleNuevaPlaca() {
        if (selectPlaca.value === 'nuevo') {
            divNuevaPlaca.style.display = 'block';
            inputPlacaNumero.required = true;
        } else {
            divNuevaPlaca.style.display = 'none';
            inputPlacaNumero.required = false;
            inputPlacaNumero.value = '';
        }
    }

    selectSede.addEventListener('change', filtrarTanques);
    selectCliente.addEventListener('change', filtrarDatosCliente);
    selectChofer.addEventListener('change', toggleNuevoChofer);
    selectPlaca.addEventListener('change', toggleNuevaPlaca);

    if (selectSede.value) filtrarTanques();
    if (selectCliente.value) filtrarDatosCliente();

    // Limitar el input de cédula estrictamente a números en tiempo real
    inputChoferCedula.addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
});
</script>

<style>
    .text-orange { color: #ff6600 !important; }
    .bg-orange { background-color: #ff6600 !important; }
    .fw-black { font-weight: 900; }
</style>
@endsection