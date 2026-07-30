<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de Trabajo #{{ $orden->nro_orden }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        /* CONFIGURACIÓN CRÍTICA PARA HOJA CARTA ÚNICA */
        @page {
            size: letter;
            margin: 8mm 10mm 8mm 10mm; /* Márgenes ajustados para maximizar área */
        }
        
        body {
            font-size: 11px !important;
            color: #000;
            background-color: #fff !important;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }

        .text-orange { color: #fd7e14 !important; }
        .border-orange { border-color: #fd7e14 !important; }
        .bg-corporate { background-color: #212529 !important; color: white !important; }
        
        /* Forzar compactación de elementos */
        .card {
            border: 1px solid #dee2e6 !important;
            margin-bottom: 6px !important;
            box-shadow: none !important;
        }
        .card-body { padding: 8px !important; }
        hr { margin: 6px 0 !important; opacity: 0.15; }
        .table th, .table td {
            padding: 4px 6px !important;
            font-size: 10.5px !important;
        }
        
        .signature-box {
            border-top: 1px solid #000;
            text-align: center;
            font-size: 10px;
            padding-top: 5px;
            margin-top: 35px; /* Espacio controlado para la firma */
            width: 180px;
        }

        /* Asegurar consistencia de fondos en impresión */
        @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .bg-light { background-color: #f8f9fa !important; }
            .table-success-light { background-color: #e6f4ea !important; }
            .table-info-light { background-color: #e8f0fe !important; }
        }
    </style>
</head>
<body>

<div class="container-fluid p-0">
    
    {{-- ENCABEZADO ULTRA COMPACTO --}}
    <div class="card border-orange">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-7">
                    <h4 class="fw-bold text-uppercase mb-0" style="letter-spacing: -0.5px;">Hoja Técnica de Servicio</h4>
                    <p class="text-muted small mb-0">Impordiesel - Gestión de Mantenimiento de Flota</p>
                </div>
                <div class="col-5 text-end">
                    <div class="d-inline-block bg-corporate text-white p-2 rounded text-center" style="min-width: 140px;">
                        <span class="d-block x-small text-uppercase lh-1" style="font-size: 9px;">Orden de Trabajo</span>
                        <span class="fs-5 fw-bold">#{{ $orden->nro_orden }}</span>
                    </div>
                    <div class="mt-1">
                        <span class="badge border text-dark bg-light" style="font-size: 10px;">
                            ESTADO: {{ $estatusData->orden ?? 'Desconocido' }}
                        </span>
                    </div>
                </div>
            </div>

            <hr>
            
            {{-- DATOS MÁSTER --}}
            <div class="row text-uppercase" style="font-size: 10px;">
                <div class="col-4">
                    <span class="text-muted d-block">Vehículo / Unidad</span>
                    <strong class="text-orange fs-6">{{ $orden->vehiculoBelong->flota ?? 'N/A' }}</strong>
                    <span class="text-muted d-block">Placa: {{ $orden->vehiculoBelong->placa ?? 'N/A' }}</span>
                </div>
                <div class="col-2">
                    <span class="text-muted d-block">Kilometraje</span>
                    <strong class="d-block mt-1">{{ number_format($orden->kilometraje) }} KM</strong>
                </div>
                <div class="col-3">
                    <span class="text-muted d-block">Tipo de Servicio</span>
                    <span class="badge bg-secondary mt-1">{{ $orden->tipo }}</span>
                </div>
                <div class="col-3 text-end">
                    <span class="text-muted d-block">Fecha Apertura</span>
                    <strong class="d-block mt-1">{{ \Carbon\Carbon::parse($orden->fecha_in)->format('d/m/Y H:i') }}</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- OBSERVACIONES TÉCNICAS --}}
    <div class="card">
        <div class="card-body py-2">
            <strong class="text-uppercase text-muted" style="font-size: 9px;">Observaciones Técnicas:</strong>
            <div class="mt-1 p-2 bg-light rounded border text-secondary" style="min-height: 40px; font-size: 10.5px; line-height: 1.3;">
                {!! $orden->descripcion ?: 'Sin observaciones adicionales.' !!}
            </div>
        </div>
    </div>

    {{-- GRILLA CENTRAL EN DOS COLUMNAS (Ahorro crítico de espacio vertical) --}}
    <div class="row g-2">
        
        {{-- COLUMNA IZQUIERDA: TRABAJOS (INTERNOS + EXTERNOS) --}}
        <div class="col-6">
            {{-- TRABAJOS EJECUTADOS --}}
            <div class="card h-100 mb-0">
                <div class="card-header bg-white py-1 border-bottom-0">
                    <strong class="text-uppercase text-muted" style="font-size: 9px;"><i class="fas fa-tools text-orange me-1"></i> Trabajos Ejecutados</strong>
                </div>
                <table class="table table-sm table-bordered align-middle mb-2">
                    <thead class="bg-light text-uppercase" style="font-size: 9px;">
                        <tr>
                            <th>Servicio</th>
                            <th class="text-end">Mano Obra</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php($totalManoObra = 0)
                        @forelse($trabajos as $trabajo)
                            @php($totalManoObra += $trabajo->costo)
                            <tr>
                                <td>
                                    <div class="fw-bold text-truncate" style="max-width: 190px;">{{ $trabajo->descripcion }}</div>
                                    <span class="text-muted x-small text-uppercase" style="font-size: 8.5px;">
                                        {{ $trabajo->tiempo_ejecucion ? 'Terminado en: '.$trabajo->tiempo_ejecucion : 'En proceso' }}
                                    </span>
                                </td>
                                <td class="text-end font-monospace">${{ number_format($trabajo->costo, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-muted py-2">Sin trabajos internos.</td></tr>
                        @endforelse
                        @if($totalManoObra > 0)
                            <tr class="bg-light fw-bold">
                                <td class="text-end text-uppercase" style="font-size: 9px;">Subtotal:</td>
                                <td class="text-end font-monospace">${{ number_format($totalManoObra, 2) }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>

                {{-- TRABAJOS EXTERNOS --}}
                <div class="card-header bg-white py-1 border-top border-bottom-0">
                    <strong class="text-uppercase text-muted" style="font-size: 9px;"><i class="fas fa-external-link-alt text-orange me-1"></i> Trabajos Externos</strong>
                </div>
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="bg-light text-uppercase" style="font-size: 9px;">
                        <tr>
                            <th>Proveedor / Detalles</th>
                            <th class="text-end">Costo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php($totalExterno = 0)
                        @forelse($orden->trabajosExternos as $trabajo)
                            @php($totalExterno += $trabajo->costo)
                            <tr>
                                <td>
                                    <div class="fw-bold text-truncate" style="max-width: 190px;">{{ $trabajo->proveedor->nombre ?? 'N/A' }}</div>
                                    <div class="text-muted" style="font-size: 9px;">{{ $trabajo->descripcion }}</div>
                                </td>
                                <td class="text-end font-monospace">${{ number_format($trabajo->costo, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-muted py-2">Sin servicios externos.</td></tr>
                        @endforelse
                        @if($totalExterno > 0)
                            <tr class="bg-light fw-bold">
                                <td class="text-end text-uppercase" style="font-size: 9px;">Subtotal:</td>
                                <td class="text-end font-monospace">${{ number_format($totalExterno, 2) }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        {{-- COLUMNA DER: INSUMOS Y REPUESTOS (INVENTARIO + REQUERIMIENTOS) --}}
        <div class="col-6">
            <div class="card h-100 mb-0">
                <div class="card-header bg-white py-1 border-bottom-0">
                    <strong class="text-uppercase text-muted" style="font-size: 9px;"><i class="fas fa-box-open text-orange me-1"></i> Insumos y Repuestos</strong>
                </div>
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="bg-light text-uppercase" style="font-size: 9px;">
                        <tr>
                            <th width="35" class="text-center">Cant</th>
                            <th>Descripción / Origen</th>
                            <th width="65" class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php($totalGeneral = 0)
                        @php($hasItems = false)

                        {{-- INVENTARIO --}}
                        @foreach($suministros as $item)
                            @php($hasItems = true)
                            @php($subtotal = $item->cantidad_solicitada * $item->inventario->precio_unitario)
                            @php($totalGeneral += $subtotal)
                            <tr>
                                <td class="text-center fw-bold">{{ $item->cantidad_solicitada }}</td>
                                <td>
                                    <span class="text-orange fw-bold" style="font-size: 8.5px;">[INV]</span> 
                                    <span class="text-truncate d-inline-block align-middle" style="max-width: 140px;">{{ $item->inventario->descripcion }}</span>
                                </td>
                                <td class="text-end font-monospace">${{ number_format($subtotal, 2) }}</td>
                            </tr>
                        @endforeach

                        {{-- COMPRAS --}}
                        @foreach ($requerimientos as $req)
                            @foreach ($req->detalles as $detalle)
                                @php($hasItems = true)
                                @php($subtotalDetalle = $detalle->cantidad_solicitada * ($detalle->costo_unitario_aprobado ?? 0))
                                @php($totalGeneral += $subtotalDetalle)
                                <tr>
                                    <td class="text-center fw-bold">{{ $detalle->cantidad_solicitada }}</td>
                                    <td>
                                        <span class="text-info fw-bold" style="font-size: 8.5px;">[REQ #{{ $req->nro_requerimiento }}]</span> 
                                        <span class="text-truncate d-inline-block align-middle" style="max-width: 140px;">{{ $detalle->descripcion }}</span>
                                    </td>
                                    <td class="text-end font-monospace">${{ number_format($subtotalDetalle, 2) }}</td>
                                </tr>
                            @endforeach
                        @endforeach

                        @if(!$hasItems)
                            <tr><td colspan="3" class="text-center text-muted py-4">Sin insumos asignados.</td></tr>
                        @endif
                        
                        @if($totalGeneral > 0)
                            <tr class="bg-light fw-bold">
                                <td colspan="2" class="text-end text-uppercase" style="font-size: 9px;">Subtotal Repuestos:</td>
                                <td class="text-end font-monospace">${{ number_format($totalGeneral, 2) }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- RESUMEN DE TOTALES CONSOLIDADOS --}}
    <div class="card mt-2 border-dark bg-light">
        <div class="card-body py-1 px-3">
            <div class="row align-items-center text-uppercase fw-bold" style="font-size: 9.5px;">
                <div class="col-3 text-muted">
                    Mano Obra: <span class="text-dark font-monospace">${{ number_format($totalManoObra, 2) }}</span>
                </div>
                <div class="col-3 text-muted">
                    Externos: <span class="text-dark font-monospace">${{ number_format($totalExterno, 2) }}</span>
                </div>
                <div class="col-3 text-muted">
                    Repuestos: <span class="text-dark font-monospace">${{ number_format($totalGeneral, 2) }}</span>
                </div>
                <div class="col-3 text-end fs-6">
                    TOTAL GENERAL: <span class="text-orange font-monospace">${{ number_format($totalManoObra + $totalExterno + $totalGeneral, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- BLOQUE FIJO DE FIRMAS --}}
    <div class="row justify-content-between mx-1">
        <div class="col-auto">
            <div class="signature-box">
                Jefe de Taller / Autorizado
            </div>
        </div>
        <div class="col-auto">
            <div class="signature-box">
                Técnico Responsable
            </div>
        </div>
        <div class="col-auto">
            <div class="signature-box">
                Chofer / Recepción
            </div>
        </div>
    </div>

</div>

{{-- Disparador automático de impresión al renderizar --}}
<script>
    window.onload = function() {
        window.print();
    }
</script>
</body>
</html>