@extends('layouts.app')
@section('title', 'Facturación de Compras')

@section('content')
<div class="container-fluid py-4 px-4">

    {{-- ALERTAS DE SESIÓN --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert" style="border-left: 4px solid #198754 !important;">
            <i class="fas fa-check-circle me-2 text-success"></i>
            <span class="fw-bold text-dark small">{{ session('success') }}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show shadow-sm border-0" role="alert" style="border-left: 4px solid #ffc107 !important;">
            <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
            <span class="fw-bold text-dark small">{{ session('warning') }}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- ENCABEZADO --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-black text-dark text-uppercase mb-1">
                <i class="fas fa-file-invoice-dollar text-orange me-2"></i> Facturación de Compras
            </h2>
            <p class="text-muted small mb-0">Gestión y control de facturas asociadas a la compra de combustible de ImporDiesel.</p>
        </div>
        <a href="{{ route('logistica.index') }}" class="btn btn-outline-secondary btn-sm fw-bold text-uppercase">
            <i class="fas fa-arrow-left me-1"></i> Volver a Logística
        </a>
    </div>

    {{-- BARRA DE FILTROS --}}
    <form action="{{ route('logistica.compras.index') }}" method="GET" class="row g-2 align-items-end mb-4">
        <div class="col-md-3">
            <label class="small fw-bold text-uppercase text-muted mb-1">Buscar Factura / SAP</label>
            <input type="text" name="search" value="{{ request('search') }}" 
                class="form-control form-control-sm fw-bold uppercase" placeholder="N° Factura o SAP...">
        </div>

        <div class="col-md-2">
            <label class="small fw-bold text-uppercase text-muted mb-1">Estatus</label>
            <select name="estatus" class="form-select form-select-sm fw-bold">
                <option value="">TODOS</option>
                <option value="EN TRANSITO" {{ request('estatus') == 'EN TRANSITO' ? 'selected' : '' }}>EN TRANSITO</option>
                <option value="COMPLETADO" {{ request('estatus') == 'COMPLETADO' ? 'selected' : '' }}>COMPLETADO</option>
            </select>
        </div>

        <div class="col-md-2">
            <label class="small fw-bold text-uppercase text-muted mb-1">Desde</label>
            <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}" class="form-control form-control-sm fw-bold">
        </div>

        <div class="col-md-2">
            <label class="small fw-bold text-uppercase text-muted mb-1">Hasta</label>
            <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}" class="form-control form-control-sm fw-bold">
        </div>

        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-dark btn-sm fw-bold text-uppercase w-100">
                <i class="fas fa-search me-1"></i> Filtrar
            </button>
            <a href="{{ route('logistica.compras.index') }}" class="btn btn-outline-secondary btn-sm w-100 fw-bold">
                Limpiar
            </a>
        </div>
    </form>

    {{-- TABLA DE COMPRAS --}}
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-black text-uppercase small"><i class="fas fa-list me-2"></i> Registro de Compras de Combustible</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 550px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light sticky-top" style="z-index: 10;">
                        <tr class="text-uppercase text-muted" style="font-size: 11px;">
                            <th class="ps-3">Fecha</th>
                            <th>Tipo Combustible</th>
                            <th>ID Viaje</th>
                            <th>Volumen (L)</th>
                            <th>Código SAP</th>
                            <th>Estatus</th>
                            <th>N° Factura</th>
                            <th>Monto Total</th>
                            <th>Documento</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($compras as $compra)
                            <tr>
                                <td class="ps-3">
                                    <small class="text-muted fw-bold d-block" style="font-size: 11px;">
                                        <i class="far fa-calendar-alt text-secondary me-1"></i>{{ \Carbon\Carbon::parse($compra->fecha)->format('d/m/Y') }}
                                    </small>
                                </td>
                                <td>
                                    @php
                                        $tipoNombre = match((string)$compra->tipo) {
                                            '1' => 'MGO',
                                            '2' => 'DIESEL',
                                            default => $compra->tipo ?? 'N/A',
                                        };
                                    @endphp
                                    <span class="badge bg-dark text-uppercase" style="font-size: 10px;">{{ $tipoNombre }}</span>
                                </td>
                                <td>
                                    @if($compra->viaje_id)
                                        <span class="fw-bold text-dark d-block" style="font-size: 12px;">{{ $compra->viaje_id }}</span>
                                    @else
                                        <span class="text-muted small fw-bold">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-black text-orange" style="font-size: 13px;">
                                        {{ number_format($compra->cantidad_litros, 0, ',', '.') }} L
                                    </span>
                                </td>
                                <td>
                                    <small class="fw-bold text-dark d-block">{{ $compra->sap ?? 'N/A' }}</small>
                                </td>
                                <td>
                                    @php
                                        $statusColor = match($compra->estatus) {
                                            'COMPLETADO'  => 'success',
                                            'EN TRANSITO' => 'warning',
                                            'CANCELADO'  => 'danger',
                                            default      => 'secondary'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusColor }} text-uppercase" style="font-size: 10px;">{{ $compra->estatus }}</span>
                                </td>
                                <td>
                                    @if($compra->numero_factura)
                                        <span class="fw-black text-dark" style="font-size: 12px;">{{ $compra->numero_factura }}</span>
                                    @else
                                        <span class="text-muted small fw-bold"><i class="fas fa-clock me-1"></i> Pendiente</span>
                                    @endif
                                </td>
                                <td>
                                    @if($compra->monto_usd || $compra->monto_bs)
                                        <span class="fw-black text-dark d-block" style="font-size: 12px;">
                                            ${{ number_format($compra->monto_usd, 2, ',', '.') }}
                                        </span>
                                        <small class="text-muted fw-bold d-block" style="font-size: 11px;">
                                            Bs. {{ number_format($compra->monto_bs, 2, ',', '.') }}
                                        </small>
                                    @else
                                        <span class="text-muted small fw-bold">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($compra->factura_path)
                                        <a href="{{ Storage::url($compra->factura_path) }}" target="_blank" class="btn btn-sm btn-light border shadow-sm fw-bold text-primary" style="font-size: 11px;">
                                            <i class="fas fa-file-pdf text-danger me-1"></i> Ver Adjunto
                                        </a>
                                    @else
                                        <span class="text-muted small">Sin soporte</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        @if($compra->estatus === 'COMPLETADO')
                                            <button class="btn btn-sm {{ $compra->numero_factura ? 'btn-outline-warning' : 'btn-dark' }} fw-bold text-uppercase shadow-sm"
                                                    style="font-size: 10px;"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalFactura{{ $compra->id }}">
                                                <i class="fas {{ $compra->numero_factura ? 'fa-edit' : 'fa-plus-circle' }} me-1"></i>
                                                {{ $compra->numero_factura ? 'Editar' : 'Facturar' }}
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-light border text-muted fw-bold text-uppercase" style="font-size: 10px;" disabled title="Debe estar COMPLETADO para registrar factura">
                                                <i class="fas fa-lock me-1"></i> Facturar
                                            </button>
                                        @endif

                                        @if($compra->historial_facturas_count > 0)
                                            <a href="{{ route('logistica.compras.historial', $compra->id) }}" class="btn btn-sm btn-light border shadow-sm fw-bold text-dark" style="font-size: 10px;" title="Ver trazabilidad">
                                                <i class="fas fa-history text-orange me-1"></i> Histórico ({{ $compra->historial_facturas_count }})
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            <!-- MODAL DE CARGA / EDITAR FACTURA -->
                            @if($compra->estatus === 'COMPLETADO')
                            <div class="modal fade" id="modalFactura{{ $compra->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow-lg">
                                        <form action="{{ route('logistica.compras.guardar_factura', $compra->id) }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <div class="modal-header bg-dark text-white py-3">
                                                <h6 class="modal-title fw-black text-uppercase mb-0">
                                                    <i class="fas fa-file-invoice text-orange me-2"></i>
                                                    {{ $compra->numero_factura ? 'Modificar Factura' : 'Cargar Factura' }} - Compra #C-{{ str_pad($compra->id, 5, '0', STR_PAD_LEFT) }}
                                                </h6>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body p-4 text-start">
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold text-uppercase text-muted">Número de Factura <span class="text-danger">*</span></label>
                                                    <input type="text" name="numero_factura" class="form-control fw-bold uppercase" value="{{ old('numero_factura', $compra->numero_factura) }}" placeholder="EJ: F-00012345" required>
                                                </div>

                                                {{-- INPUTS DE MONTOS TOTALES --}}
                                                <div class="row g-2 mb-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label small fw-bold text-uppercase text-muted">Monto Total ($ USD) <span class="text-danger">*</span></label>
                                                        <div class="input-group">
                                                            <span class="input-group-text fw-bold">$</span>
                                                            <input type="number" step="0.01" min="0" name="monto_usd" class="form-control fw-bold" value="{{ old('monto_usd', $compra->monto_usd) }}" placeholder="0.00" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small fw-bold text-uppercase text-muted">Monto Total (Bs) <span class="text-danger">*</span></label>
                                                        <div class="input-group">
                                                            <span class="input-group-text fw-bold">Bs</span>
                                                            <input type="number" step="0.01" min="0" name="monto_bs" class="form-control fw-bold" value="{{ old('monto_bs', $compra->monto_bs) }}" placeholder="0.00" required>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold text-uppercase text-muted">Archivo de la Factura (PDF, PNG, JPG) <span class="text-danger">*</span></label>
                                                    <input type="file" name="factura" class="form-control fw-bold" accept=".pdf,.png,.jpg,.jpeg" {{ $compra->factura_path ? '' : 'required' }}>
                                                    <small class="text-muted d-block mt-1" style="font-size: 10px;">Límite de tamaño soportado: 5 MB.</small>
                                                </div>
                                            </div>
                                            <div class="modal-footer bg-light">
                                                <button type="button" class="btn btn-secondary btn-sm fw-bold text-uppercase" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-dark btn-sm fw-bold text-uppercase">
                                                    <i class="fas fa-save me-1 text-orange"></i> Guardar Factura
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @endif
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <i class="fas fa-folder-open text-muted fa-2x mb-2 opacity-50"></i>
                                    <p class="text-muted fw-bold mb-0 text-uppercase small">No hay registro de compras asociadas con estos criterios.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-light border-top">
            {{ $compras->links() }}
        </div>
    </div>
</div>

<style>
    .text-orange { color: #ff6600 !important; }
    .bg-orange { background-color: #ff6600 !important; }
    .fw-black { font-weight: 900; }
</style>
@endsection