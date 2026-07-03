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
                                <select name="cliente_id" class="form-select fw-bold text-dark" style="font-size: 14px;" required>
                                    <option value="">-- SELECCIONE EL CLIENTE --</option>
                                    @foreach($clientes as $cliente)
                                        <option value="{{ $cliente->id }}" {{ old('cliente_id') == $cliente->id ? 'selected' : '' }}>
                                            {{ $cliente->nombre }} (RIF: {{ $cliente->rif }}) — Disp: {{ number_format($cliente->disponible, 2, ',', '.') }} Lts
                                        </option>
                                    @endforeach
                                </select>
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

                            {{-- SELECCIÓN DE TANQUE AUTORIZADO --}}
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
                        
                        <a href="{{ route('combustibles.llenados_prepagados.index') }}" class="btn btn-sm btn-outline-light w-100 text-uppercase fw-bold opacity-70" style="font-size: 11px;">
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

    function filtrarTanques() {
        const sedeId = selectSede.value;

        // Si no hay sede seleccionada, deshabilitamos el selector de tanques y lo reseteamos
        if (!sedeId) {
            selectTanque.value = "";
            selectTanque.disabled = true;
            optionsTanque.forEach(opt => {
                if (opt.value !== "") opt.style.display = 'none';
            });
            return;
        }

        // Habilitamos el selector de tanques
        selectTanque.disabled = false;
        let coincidenciaEncontrada = false;

        optionsTanque.forEach(opt => {
            if (opt.value === "") {
                opt.style.display = 'block'; // La opción por defecto siempre visible
            } else if (opt.getAttribute('data-sede') === sedeId) {
                opt.style.display = 'block'; // Muestra tanques de esta sede
                if (opt.value === selectTanque.value) {
                    coincidenciaEncontrada = true;
                }
            } else {
                opt.style.display = 'none'; // Oculta tanques ajenos
            }
        });

        // Si el tanque previamente seleccionado (por el old()) no pertenece a la nueva sede elegida, reseteamos el select
        if (!coincidenciaEncontrada && selectTanque.value !== "") {
            selectTanque.value = "";
        }
    }

    // Escuchar el cambio de sede
    selectSede.addEventListener('change', filtrarTanques);

    // Disparar al cargar la página por si hay datos de validación fallida (old())
    if (selectSede.value) {
        filtrarTanques();
    }
});
</script>

<style>
    .text-orange { color: #ff6600 !important; }
    .fw-black { font-weight: 900; }
</style>
@endsection