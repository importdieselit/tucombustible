@extends('layouts.app')
@section('title', 'Auditoría de Transacción #' . $transaccion->id)

@section('content')
<div class="container-fluid py-4 px-4">

    {{-- ENCABEZADO Y ACCIONES --}}
    <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <a href="{{ route('combustibles.transacciones.index') }}" class="btn btn-sm btn-link text-dark fw-bold text-uppercase p-0 mb-2 text-decoration-none" style="font-size: 11px;">
                <i class="fas fa-arrow-left me-1"></i> Volver al Ledger
            </a>
            <h2 class="h4 fw-black text-dark text-uppercase mb-1">
                <i class="fas fa-receipt text-orange me-2"></i> Transacción #{{ str_pad($transaccion->id, 6, '0', STR_PAD_LEFT) }}
            </h2>
            <p class="text-muted small mb-0">Comprobante digital de auditoría interna de inventario</p>
        </div>
        <div>
            <button onclick="window.print();" class="btn btn-sm btn-outline-dark fw-bold text-uppercase px-3" style="font-size: 11px; height: 32px;">
                <i class="fas fa-print me-1"></i> Imprimir Comprobante
            </button>
        </div>
    </div>

    @php
        // Configuración visual según la naturaleza del movimiento
        $cardBorder = '#ff6600'; // Default naranja
        $badgeClass = 'bg-secondary text-white';
        $tipoLabel = str_replace('_', ' ', $transaccion->tipo_movimiento);
        $signo = '';
        $litrosColor = 'text-dark';

        switch($transaccion->tipo_movimiento) {
            case 'compra':
            case 'ingreso':
                $cardBorder = '#2ecc71'; // Verde
                $badgeClass = 'bg-success text-white';
                $signo = '+';
                $litrosColor = 'text-success';
                break;
            case 'despacho':
                $cardBorder = '#3498db'; // Azul
                $badgeClass = 'bg-primary text-white';
                $signo = '-';
                $litrosColor = 'text-danger';
                break;
            case 'despacho_prepagado':
                $cardBorder = '#f1c40f'; // Amarillo/Naranja
                $badgeClass = 'bg-warning text-dark';
                $signo = '-';
                $litrosColor = 'text-danger';
                break;
            case 'ajuste_positivo':
                $cardBorder = '#1abc9c'; // Turquesa
                $badgeClass = 'bg-info text-dark';
                $signo = '+';
                $litrosColor = 'text-success';
                break;
            case 'ajuste_negativo':
                $cardBorder = '#e74c3c'; // Rojo
                $badgeClass = 'bg-danger text-white';
                $signo = '-';
                $litrosColor = 'text-danger';
                break;
        }
    @endphp

    <div class="row g-4">
        {{-- COLUMNA PRINCIPAL: DETALLES DEL MOVIMIENTO --}}
        <div class="col-lg-8">
            {{-- TARJETA PRINCIPAL DEL MOVIMIENTO --}}
            <div class="card shadow-sm border-0 mb-4" style="border-left: 4px solid {{ $cardBorder }} !important;">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-black text-uppercase small text-dark">
                        <i class="fas fa-info-circle text-orange me-2"></i> Datos del Asiento
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        {{-- Bloque de Litros --}}
                        <div class="col-md-6 border-end">
                            <span class="small fw-bold text-uppercase text-muted d-block mb-1" style="font-size: 11px;">Volumen Afectado</span>
                            <h1 class="display-5 fw-black {{ $litrosColor }} mb-0 font-monospace">
                                {{ $signo }} {{ number_format($transaccion->cantidad_litros, 2, ',', '.') }} <span class="h3 fw-bold">Lts</span>
                            </h1>
                            <span class="badge {{ $badgeClass }} text-uppercase fw-bold mt-2" style="font-size: 11px; padding: 6px 10px; letter-spacing: 0.5px;">
                                {{ $tipoLabel }}
                            </span>
                        </div>

                        {{-- Ubicación física --}}
                        <div class="col-md-6 ps-md-4">
                            <span class="small fw-bold text-uppercase text-muted d-block mb-2" style="font-size: 11px;">Ubicación del Inventario</span>
                            
                            <div class="mb-3">
                                <small class="text-muted d-block mb-1">Sede:</small>
                                <span class="badge bg-light text-secondary border text-uppercase fw-bold" style="font-size: 12px; padding: 5px 10px;">
                                    <i class="fas fa-map-marker-alt me-1 text-danger"></i> {{ $transaccion->sede->nombre ?? 'N/A' }}
                                </span>
                            </div>

                            <div>
                                <small class="text-muted d-block mb-1">Tanque / Depósito:</small>
                                <span class="fw-black text-dark" style="font-size: 15px;">
                                    <i class="fas fa-database text-muted me-1"></i> {{ $transaccion->deposito->serial ?? 'N/A' }}
                                </span>
                                @if($transaccion->deposito->descripcion ?? null)
                                    <small class="text-muted d-block font-italic">({{ $transaccion->deposito->descripcion }})</small>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- INFORMACIÓN DEL TERCERO / ASOCIADO --}}
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-black text-uppercase small text-dark">
                        <i class="fas fa-handshake text-orange me-2"></i> Tercero Asociado
                    </h6>
                </div>
                <div class="card-body p-4">
                    @if($transaccion->cliente)
                        <div class="row">
                            <div class="col-md-6">
                                <span class="small fw-bold text-uppercase text-muted d-block mb-1" style="font-size: 11px;">Cliente / Razón Social</span>
                                <span class="fw-black text-dark d-block h5 mb-1">{{ $transaccion->cliente->nombre }}</span>
                                <span class="badge bg-dark text-white font-monospace text-uppercase p-2" style="font-size: 11px;">
                                    RIF: {{ $transaccion->cliente->rif }}
                                </span>
                            </div>
                            <div class="col-md-6 border-start ps-md-4 mt-3 mt-md-0">
                                <span class="small fw-bold text-uppercase text-muted d-block mb-1" style="font-size: 11px;">Contacto / Dirección</span>
                                <span class="text-secondary small d-block mb-1">
                                    <i class="fas fa-envelope me-1"></i> {{ $transaccion->cliente->email ?? 'No registrado' }}
                                </span>
                                <span class="text-secondary small d-block">
                                    <i class="fas fa-phone me-1"></i> {{ $transaccion->cliente->telefono ?? 'No registrado' }}
                                </span>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-exchange-alt fa-2x mb-2 text-muted" style="opacity: 0.5;"></i>
                            <p class="mb-0 small fw-bold">Este movimiento no posee un cliente directo asociado.</p>
                            <small class="text-muted">Es un movimiento de uso interno, inventario inicial o ajuste de volumen.</small>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ASOCIACIONES DE LOGÍSTICA (SI APLICA) --}}
            @if($transaccion->viaje)
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white border-bottom py-3">
                        <h6 class="mb-0 fw-black text-uppercase small text-dark">
                            <i class="fas fa-truck text-orange me-2"></i> Relación con Logística / Distribución
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-4">
                                <span class="small fw-bold text-uppercase text-muted d-block mb-1" style="font-size: 11px;">ID Viaje Asociado</span>
                                <span class="fw-black text-dark font-monospace h5">#{{ $transaccion->viaje->id }}</span>
                            </div>
                            <div class="col-md-4 border-start ps-md-4">
                                <span class="small fw-bold text-uppercase text-muted d-block mb-1" style="font-size: 11px;">Estatus del Viaje</span>
                                <span class="badge bg-light text-secondary border text-uppercase fw-bold mt-1" style="font-size: 11px;">
                                    {{ $transaccion->viaje->estatus ?? 'N/A' }}
                                </span>
                            </div>
                            <div class="col-md-4 border-start ps-md-4">
                                <span class="small fw-bold text-uppercase text-muted d-block mb-1" style="font-size: 11px;">Ruta</span>
                                <span class="text-dark fw-bold small d-block mt-1">
                                    <i class="fas fa-road text-muted me-1"></i> {{ $transaccion->viaje->ruta->nombre ?? 'N/A' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- COLUMNA LATERAL: METADATOS Y AUDITORÍA --}}
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-black text-uppercase small text-dark">
                        <i class="fas fa-shield-alt text-orange me-2"></i> Trazabilidad de Auditoría
                    </h6>
                </div>
                <div class="card-body p-4">
                    {{-- Tipo de Combustible --}}
                    <div class="mb-4">
                        <span class="small fw-bold text-uppercase text-muted d-block mb-1" style="font-size: 11px;">Tipo de Combustible</span>
                        @if(($transaccion->tipoCombustible->id ?? null) == 2 || ($transaccion->tipo_combustible_id ?? null) == 2)
                            <span class="badge bg-warning text-dark fw-bold text-uppercase w-100 py-2" style="font-size: 12px; background-color: #ffa500 !important;">DIESEL</span>
                        @else
                            <span class="badge bg-info text-white fw-bold text-uppercase w-100 py-2" style="font-size: 12px; background-color: #00a8ff !important;">MGO</span>
                        @endif
                    </div>

                    <hr class="text-muted opacity-25">

                    {{-- Registro Cronológico --}}
                    <div class="mb-3">
                        <span class="small fw-bold text-uppercase text-muted d-block mb-1" style="font-size: 11px;">Fecha de Registro</span>
                        <div class="text-dark fw-bold font-monospace" style="font-size: 13px;">
                            <i class="far fa-calendar-alt text-muted me-1"></i> {{ $transaccion->created_at->format('d/m/Y') }}
                        </div>
                        <div class="text-muted font-monospace small">
                            <i class="far fa-clock text-muted me-1"></i> {{ $transaccion->created_at->format('h:i:s A') }}
                        </div>
                    </div>

                    {{-- Operador Responsable --}}
                    <div class="mb-3">
                        <span class="small fw-bold text-uppercase text-muted d-block mb-1" style="font-size: 11px;">Registrado Por</span>
                        <span class="text-dark fw-bold" style="font-size: 13px;">
                            <i class="fas fa-user-shield text-muted me-1"></i> {{ $transaccion->user->name ?? 'Sistema' }}
                        </span>
                        <small class="text-muted d-block font-monospace" style="font-size: 11px;">{{ $transaccion->user->email ?? '' }}</small>
                    </div>

                    {{-- Fecha de Actualización (si aplica) --}}
                    @if($transaccion->updated_at != $transaccion->created_at)
                        <div class="mb-3">
                            <span class="small fw-bold text-uppercase text-muted d-block mb-1" style="font-size: 11px;">Última Modificación</span>
                            <div class="text-muted small font-monospace">
                                <i class="fas fa-edit me-1"></i> {{ $transaccion->updated_at->format('d/m/Y h:i A') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- INFORMACIÓN DE NOTAS / OBSERVACIONES --}}
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-black text-uppercase small text-dark">
                        <i class="fas fa-comment-alt text-orange me-2"></i> Observaciones
                    </h6>
                </div>
                <div class="card-body p-4">
                    <p class="text-secondary mb-0 small" style="line-height: 1.6;">
                        {{ $transaccion->observaciones ?? 'No se registraron observaciones ni comentarios adicionales para este movimiento de inventario.' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .text-orange { color: #ff6600 !important; }
    .fw-black { font-weight: 900; }
    
    /* Optimización de estilos para impresión del comprobante */
    @media print {
        header, footer, nav, .btn, .btn-link {
            display: none !important;
        }
        .container-fluid {
            padding: 0 !important;
        }
        .card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
        }
    }
</style>
@endsection