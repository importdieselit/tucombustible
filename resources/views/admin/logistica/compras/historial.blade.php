@extends('layouts.app')
@section('title', 'Histórico de Factura')

@section('content')
<div class="container-fluid py-4 px-4">

    {{-- ENCABEZADO --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-black text-dark text-uppercase mb-1">
                <i class="fas fa-history text-orange me-2"></i> Histórico de Factura
            </h2>
            <p class="text-muted small mb-0">Trazabilidad de modificaciones de factura para la Compra #C-{{ str_pad($compra->id, 5, '0', STR_PAD_LEFT) }}.</p>
        </div>
        <a href="{{ route('logistica.compras.index') }}" class="btn btn-outline-secondary btn-sm fw-bold text-uppercase">
            <i class="fas fa-arrow-left me-1"></i> Volver a Compras
        </a>
    </div>

    {{-- RESUMEN DE LA COMPRA ACTUAL --}}
    <div class="card shadow-sm border-0 mb-4" style="border-left: 4px solid #ff6600;">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-black text-uppercase small"><i class="fas fa-info-circle text-orange me-2"></i> Estado Actual de la Compra</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <span class="text-muted small fw-bold text-uppercase d-block">Fecha:</span>
                    <strong class="text-dark">{{ \Carbon\Carbon::parse($compra->fecha)->format('d/m/Y') }}</strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small fw-bold text-uppercase d-block">Volumen:</span>
                    <strong class="text-orange fw-black">{{ number_format($compra->cantidad_litros, 0, ',', '.') }} L</strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small fw-bold text-uppercase d-block">N° Factura Actual:</span>
                    <strong class="text-dark">{{ $compra->numero_factura ?? 'N/A' }}</strong>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small fw-bold text-uppercase d-block">Sporte Actual:</span>
                    @if($compra->factura_path)
                        <a href="{{ Storage::url($compra->factura_path) }}" target="_blank" class="fw-bold text-primary small text-decoration-none">
                            <i class="fas fa-external-link-alt me-1"></i> Ver Documento
                        </a>
                    @else
                        <span class="text-muted small">Sin Archivo</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- TABLA DE HISTORIAL DE CAMBIOS --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-black text-uppercase small"><i class="fas fa-list me-2"></i> Registro Auditado de Cambios</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="text-uppercase text-muted" style="font-size: 11px;">
                            <th class="ps-4">Fecha y Hora</th>
                            <th>Usuario Responsable</th>
                            <th>N° Factura Anterior</th>
                            <th>N° Factura Nuevo</th>
                            <th>Soporte Anterior</th>
                            <th class="text-center">Soporte Nuevo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($compra->historialFacturas as $historial)
                            <tr>
                                <td class="ps-4 fw-bold text-dark" style="font-size: 12px;">
                                    <i class="far fa-clock text-secondary me-1"></i>
                                    {{ $historial->created_at->format('d/m/Y h:i A') }}
                                </td>
                                <td>
                                    <span class="fw-bold text-dark" style="font-size: 12px;">
                                        {{ $historial->usuario->name ?? 'Usuario ID ' . $historial->usuario_id }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-muted fw-bold" style="font-size: 12px;">
                                        {{ $historial->numero_factura_anterior ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    <strong class="text-success" style="font-size: 12px;">
                                        {{ $historial->numero_factura_nuevo }}
                                    </strong>
                                </td>
                                <td>
                                    @if($historial->factura_path_anterior)
                                        <a href="{{ Storage::url($historial->factura_path_anterior) }}" target="_blank" class="btn btn-sm btn-light border fw-bold text-secondary" style="font-size: 10px;">
                                            <i class="fas fa-file-pdf me-1"></i> Archivo Previo
                                        </a>
                                    @else
                                        <span class="text-muted small">Ninguno</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ Storage::url($historial->factura_path_nuevo) }}" target="_blank" class="btn btn-sm btn-light border shadow-sm fw-bold text-primary" style="font-size: 10px;">
                                        <i class="fas fa-file-pdf text-danger me-1"></i> Ver Reemplazo
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="fas fa-check-circle text-success fa-2x mb-2 opacity-50"></i>
                                    <p class="text-muted fw-bold mb-0 text-uppercase small">Esta compra conserva su registro inicial sin ediciones posteriores.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .text-orange { color: #ff6600 !important; }
    .fw-black { font-weight: 900; }
</style>
@endsection