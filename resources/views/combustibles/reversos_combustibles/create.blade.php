@extends('layouts.app')
@section('title', 'Registrar Reverso de Combustible')

@section('content')
<div class="container-fluid py-4 px-4">

    {{-- ENCABEZADO --}}
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h2 class="h4 fw-black text-dark text-uppercase mb-1">
                <i class="fas fa-undo text-orange me-2"></i> Registrar Reverso de Combustible
            </h2>
            <p class="text-muted small mb-0">Abono de producto al inventario global y acreditación de saldo al cliente</p>
        </div>
        <a href="{{ route('combustibles.reversos_combustibles.index') }}" class="btn btn-sm btn-outline-secondary fw-bold text-uppercase shadow-sm px-3" style="font-size: 12px; height: 32px;">
            <i class="fas fa-arrow-left me-1"></i> Volver al Historial
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
    <div class="card shadow-sm border-0 max-w-2xl" style="border-left: 4px solid #ff6600;">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-black text-uppercase small text-dark">
                <i class="fas fa-edit text-orange me-2"></i> Datos de la Operación de Reverso
            </h6>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('combustibles.reversos_combustibles.store') }}" method="POST">
                @csrf

                <div class="row g-3">
                    {{-- SEDE --}}
                    <div class="col-md-6">
                        <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Sede Operativa *</label>
                        <select name="sede_id" class="form-select form-select-sm fw-bold text-dark @error('sede_id') is-invalid @enderror" style="font-size: 13px;" required>
                            <option value="">-- SELEACCIONE SEDE --</option>
                            @foreach($sedes as $sede)
                                <option value="{{ $sede->id }}" {{ old('sede_id') == $sede->id ? 'selected' : '' }}>
                                    {{ $sede->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- CLIENTE --}}
                    <div class="col-md-6">
                        <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Cliente Receptor del Saldo *</label>
                        <select name="cliente_id" class="form-select form-select-sm fw-bold text-dark @error('cliente_id') is-invalid @enderror" style="font-size: 13px;" required>
                            <option value="">-- SELEACCIONE CLIENTE --</option>
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id }}" {{ old('cliente_id') == $cliente->id ? 'selected' : '' }}>
                                    {{ $cliente->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- TIPO DE COMBUSTIBLE --}}
                    <div class="col-md-6">
                        <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Tipo de Combustible *</label>
                        <select name="tipo_combustible_id" class="form-select form-select-sm fw-bold text-dark @error('tipo_combustible_id') is-invalid @enderror" style="font-size: 13px;" required>
                            <option value="">-- SELEACCIONE PRODUCTO --</option>
                            @foreach($tiposCombustible as $tipo)
                                <option value="{{ $tipo->id }}" {{ old('tipo_combustible_id') == $tipo->id ? 'selected' : '' }}>
                                    {{ $tipo->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- CANTIDAD EN LITROS --}}
                    <div class="col-md-6">
                        <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Cantidad (Litros) *</label>
                        <div class="input-group input-group-sm">
                            <input type="number" step="0.01" name="cantidad_litros" class="form-control fw-bold text-dark @error('cantidad_litros') is-invalid @enderror" style="font-size: 13px;" placeholder="0.00" value="{{ old('cantidad_litros') }}" required>
                            <span class="input-group-text fw-bold text-muted" style="font-size: 11px;">LTS</span>
                        </div>
                    </div>

                    {{-- MOTIVO DE REVERSO --}}
                    <div class="col-12">
                        <label class="small fw-bold text-uppercase text-muted mb-1" style="font-size: 11px;">Motivo / Justificación del Reverso</label>
                        <textarea name="motivo_reverso" rows="3" class="form-control fw-bold text-dark" style="font-size: 13px;" placeholder="Ej: Retorno parcial por tanque saturado en despacho, excedente acreditado...">{{ old('motivo_reverso') }}</textarea>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('combustibles.reversos_combustibles.index') }}" class="btn btn-sm btn-light fw-bold text-uppercase px-4" style="font-size: 11px;">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-sm btn-warning fw-black text-uppercase px-4" style="font-size: 11px; color: #000; background-color: #ff6600; border-color: #ff6600;">
                        <i class="fas fa-save me-1"></i> Procesar Reverso y Abonar Saldo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .text-orange { color: #ff6600 !important; }
    .fw-black { font-weight: 900; }
</style>
@endsection