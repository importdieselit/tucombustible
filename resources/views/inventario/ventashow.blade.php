@extends('layouts.app')

@section('title', 'Detalle de Venta #' . $venta->nro_venta)

@push('styles')
<style>
    .border-orange { border-top: 3px solid #e67e22 !important; }
    .bg-corporate { background-color: #2c3e50 !important; color: white; }
    .text-orange { color: #e67e22 !important; }
    .table-detail thead th { background-color: #f8f9fa; text-transform: uppercase; font-size: 0.75rem; color: #555; }
    
    @media print {
        .no-print { display: none !important; }
        .card { border: none !important; shadow: none !important; }
        .border-orange { border-top: 2px solid #e67e22 !important; }
        body { background-color: white !important; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">

    {{-- BARRA DE HERRAMIENTAS --}}
    <div class="d-flex justify-content-between align-items-center mb-4 no-print bg-white p-3 rounded shadow-sm">
        <div>
            <a href="{{ route('ventas.list') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Volver al Listado
            </a>
            <button onclick="window.print();" class="btn btn-sm btn-dark ms-2">
                <i class="fas fa-print"></i> Imprimir Nota
            </button>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-success fs-6">VENTA PROCESADA</span>
        </div>
    </div>

    <div class="card shadow-sm border-orange">
        <div class="card-body p-4">
            {{-- CABECERA DE LA NOTA --}}
            <div class="row mb-4">
                <div class="col-6">
                    <h2 class="fw-bold text-orange mb-0">IMPORDERDIESEL</h2>
                    <p class="text-muted small">RIF: J-00000000-0<br>Sistema de Gestión de Almacén</p>
                </div>
                <div class="col-6 text-end">
                    <h4 class="fw-bold mb-0">NOTA DE DESPACHO</h4>
                    <h5 class="text-muted">{{ $venta->nro_venta }}</h5>
                    <p class="mb-0"><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($venta->fecha)->format('d/m/Y h:i A') }}</p>
                    @if($venta->nro_profit)
                        <p class="mb-0"><strong>Ref. Profit:</strong> {{ $venta->nro_profit }}</p>
                    @endif
                </div>
            </div>

            <hr>

            {{-- INFORMACIÓN DEL CLIENTE Y ALMACÉN --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="text-orange fw-bold text-uppercase small">Datos del Cliente</h6>
                    <div class="bg-light p-3 rounded border">
                        <p class="mb-1"><strong>Razón Social:</strong> {{ $venta->cliente->nombre }}</p>
                        <p class="mb-1"><strong>RIF:</strong> {{ $venta->cliente->rif }}</p>
                        <p class="mb-1"><strong>Teléfono:</strong> {{ $venta->cliente->telefono ?? 'N/A' }}</p>
                        <p class="mb-0"><strong>Correo:</strong> {{ $venta->cliente->correo ?? 'N/A' }}</p>
                    </div>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <h6 class="text-orange fw-bold text-uppercase small">Origen del Despacho</h6>
                    <div class="p-3">
                        <p class="mb-1"><strong>Almacén:</strong> {{ $venta->almacen->nombre ?? 'Principal' }}</p>
                        <p class="mb-1"><strong>Ubicación:</strong> Boleita Norte, Caracas</p>
                    </div>
                </div>
            </div>

            {{-- DETALLE DE ARTÍCULOS --}}
            <div class="table-responsive">
                <table class="table table-bordered table-detail align-middle">
                    <thead>
                        <tr>
                            <th width="5%" class="text-center">#</th>
                            <th width="15%">Código</th>
                            <th width="45%">Descripción del Artículo</th>
                            <th width="10%" class="text-center">Cant.</th>
                            <th width="10%" class="text-end">P. Unit ($)</th>
                            <th width="15%" class="text-end">Subtotal ($)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($venta->detalles as $index => $detalle)
                        <tr>
                            <td class="text-center text-muted small">{{ $index + 1 }}</td>
                            <td class="fw-bold">{{ $detalle->inventario->codigo }}</td>
                            <td>{{ $detalle->inventario->descripcion }}</td>
                            <td class="text-center">{{ number_format($detalle->cantidad, 0) }}</td>
                            <td class="text-end">{{ number_format($detalle->precio_unitario, 2, ',', '.') }}</td>
                            <td class="text-end fw-bold">{{ number_format($detalle->subtotal, 2, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="text-end fw-bold text-uppercase bg-light">Total General:</td>
                            <td class="text-end fw-bold fs-5 text-orange bg-light">
                                $ {{ number_format($venta->total_venta, 2, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- OBSERVACIONES --}}
            @if($venta->observaciones)
            <div class="mt-4">
                <h6 class="fw-bold small text-uppercase text-muted">Observaciones:</h6>
                <p class="border p-2 rounded bg-light small">{{ $venta->observaciones }}</p>
            </div>
            @endif

            {{-- FIRMAS (Solo visibles en impresión o al final) --}}
            <div class="row mt-5 pt-4 text-center">
                <div class="col-4">
                    <div class="border-top mx-3 pt-2 small">
                        <strong>Entregado por:</strong><br>
                        Almacén / Despacho
                    </div>
                </div>
                <div class="col-4">
                    {{-- <div class="border-top mx-3 pt-2 small">
                        <strong>Transporte:</strong><br>
                        Cédula / Placa
                    </div> --}}
                </div>
                <div class="col-4">
                    <div class="border-top mx-3 pt-2 small">
                        <strong>Recibido Conforme:</strong><br>
                        Firma Cliente / Sello
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- BOTÓN FLOTANTE VOLVER (Solo móvil no-print) --}}
    <div class="text-center mt-4 no-print">
        <p class="text-muted x-small">Documento generado por el Sistema de Gestión de Combustible & Flota</p>
    </div>
</div>
@endsection