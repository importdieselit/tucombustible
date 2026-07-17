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
            <p class="text-muted small mb-0">Transferencia y movilización de inventario entre tanques de Impordiesel</p>
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
            <strong class="text-dark d-block" style="font-size: 14px;">Validación de Stock en Tiempo Real</strong>
            <span class="text-muted small">El sistema comprobará la disponibilidad física del tanque emisor y actualizará simultáneamente ambos depósitos de forma irreversible.</span>
        </div>
    </div>

    {{-- CAPTURA DE ERRORES DE VALIDACIÓN O EXCEPCIONES --}}
    @if ($errors->any() || Session::has('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <div class="fw-bold mb-1"><i class="fas fa-exclamation-triangle me-2"></i> Error al procesar el trasegado:</div>
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
    <form action="{{ route('combustibles.trasegados.create_interno') }}" method="POST">
        @csrf

        <div class="row g-3 mb-4">
            {{-- BLOQUE DE DATOS OPERATIVOS --}}
            <div class="col-md-8">
                <div class="card shadow-sm border-0 h-100" style="border-left: 4px solid #ff6600;">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-black text-uppercase small text-dark">
                            <i class="fas fa-dolly-flatbed text-orange me-2"></i> Detalles de la Transferencia Interna
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            
                            {{-- SECCIÓN ORIZONTE / ORIGEN --}}
                            <div class="col-12">
                                <span class="badge bg-dark text-white text-uppercase px-2 py-1 mb-2" style="font-size: 10px; letter-spacing: 0.5px;">1. Depósito de Origen (Emisor)</span>
                            </div>

                            <div class="col-md-6">
                                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Sede de Origen</label>
                                <select name="id_sede_origen" id="select-sede-origen" class="form-select fw-bold text-dark" style="font-size: 14px;" required>
                                    <option value="">-- SELECCIONE SEDE ORIGEN --</option>
                                    @foreach($sedes as $sede)
                                        <option value="{{ $sede->id }}" {{ old('id_sede_origen') == $sede->id ? 'selected' : '' }}>
                                            {{ $sede->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Tanque Emisor</label>
                                <select name="id_deposito_origen" id="select-tanque-origen" class="form-select fw-bold text-dark" style="font-size: 14px;" required disabled>
                                    <option value="">-- SELECCIONE TANQUE ORIGEN --</option>
                                    @foreach($tanques as $tanque)
                                        <option value="{{ $tanque->id }}" data-sede="{{ $tanque->id_sede }}" {{ old('id_deposito_origen') == $tanque->id ? 'selected' : '' }} style="display: none;">
                                            {{ $tanque->serial }} — Stock: {{ number_format($tanque->nivel_actual_litros, 2, ',', '.') }} Lts
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <hr class="my-4 text-muted opacity-20">

                            {{-- SECCIÓN DESTINO --}}
                            <div class="col-12">
                                <span class="badge bg-orange text-white text-uppercase px-2 py-1 mb-2" style="font-size: 10px; letter-spacing: 0.5px;">2. Depósito de Destino (Receptor)</span>
                            </div>

                            <div class="col-md-6">
                                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Sede de Destino</label>
                                <select name="id_sede_destino" id="select-sede-destino" class="form-select fw-bold text-dark" style="font-size: 14px;" required>
                                    <option value="">-- SELECCIONE SEDE DESTINO --</option>
                                    @foreach($sedes as $sede)
                                        <option value="{{ $sede->id }}" {{ old('id_sede_destino') == $sede->id ? 'selected' : '' }}>
                                            {{ $sede->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Tanque Receptor</label>
                                <select name="id_deposito_destino" id="select-tanque-destino" class="form-select fw-bold text-dark" style="font-size: 14px;" required disabled>
                                    <option value="">-- SELECCIONE TANQUE DESTINO --</option>
                                    @foreach($tanques as $tanque)
                                        <option value="{{ $tanque->id }}" data-sede="{{ $tanque->id_sede }}" {{ old('id_deposito_destino') == $tanque->id ? 'selected' : '' }} style="display: none;">
                                            {{ $tanque->serial }} — Cap. Disponible: {{ number_format(($tanque->capacidad_maxima ?? 0) - $tanque->nivel_actual_litros, 2, ',', '.') }} Lts
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <hr class="my-4 text-muted opacity-20">

                            {{-- VOLUMEN A TRASEGAR --}}
                            <div class="col-12">
                                <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 12px;">Volumen Neto a Trasegar</label>
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

            {{-- BLOQUE DE ACCIÓN Y CIERRE DE LOTE --}}
            <div class="col-md-4 d-flex align-items-stretch">
                <div class="card shadow-sm border-0 w-100 bg-dark text-white">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center text-center p-4">
                        <i class="fas fa-file-signature text-orange fa-2x mb-2"></i>
                        <h5 class="fw-black text-uppercase mb-1" style="font-size: 14px; letter-spacing: 0.5px;">Confirmación de Trasegado</h5>
                        <p class="text-muted small mb-4">Al procesar esta acción, se descontará el volumen del inventario origen y se sumará al destino de forma inmediata en las bitácoras del sistema.</p>
                        
                        <button type="submit" class="btn btn-warning w-100 fw-black text-uppercase py-2 shadow mb-2" style="color: #000; background-color: #ff6600; border-color: #ff6600; font-size: 13px;">
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
    const selectSedeOrigen = document.getElementById('select-sede-origen');
    const selectTanqueOrigen = document.getElementById('select-tanque-origen');
    const optionsTanqueOrigen = Array.from(selectTanqueOrigen.options);

    const selectSedeDestino = document.getElementById('select-sede-destino');
    const selectTanqueDestino = document.getElementById('select-tanque-destino');
    const optionsTanqueDestino = Array.from(selectTanqueDestino.options);

    function filtrarTanques(selectSede, selectTanque, optionsTanque) {
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

    selectSedeOrigen.addEventListener('change', () => filtrarTanques(selectSedeOrigen, selectTanqueOrigen, optionsTanqueOrigen));
    selectSedeDestino.addEventListener('change', () => filtrarTanques(selectSedeDestino, selectTanqueDestino, optionsTanqueDestino));

    if (selectSedeOrigen.value) filtrarTanques(selectSedeOrigen, selectTanqueOrigen, optionsTanqueOrigen);
    if (selectSedeDestino.value) filtrarTanques(selectSedeDestino, selectTanqueDestino, optionsTanqueDestino);
});
</script>

<style>
    .text-orange { color: #ff6600 !important; }
    .bg-orange { background-color: #ff6600 !important; }
    .fw-black { font-weight: 900; }
</style>
@endsection