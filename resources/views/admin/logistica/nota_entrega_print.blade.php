<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nota de Entrega - Viaje #{{ $viaje->id }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #000; background: #fff; }
        .header-title { font-weight: bold; font-size: 14px; text-transform: uppercase; }
        .table-bordered th, .table-bordered td { border-color: #000 !important; padding: 4px 6px; }
        .box-section { border: 1px solid #000; padding: 8px; margin-bottom: 12px; }
        
        /* Control de salto de página independiente por cada despacho */
        .nota-entrega-page {
            page-break-after: always;
            break-after: page;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .nota-entrega-page:last-child {
            page-break-after: avoid;
            break-after: avoid;
            margin-bottom: 0;
        }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; margin: 0; }
            .container { max-width: 100% !important; width: 100% !important; padding: 0 !important; }
        }
    </style>
</head>
<body class="py-4">

<div class="container" style="max-width: 800px;">
    
    <!-- Botón global de impresión (Se oculta al imprimir) -->
    <div class="d-flex justify-content-between align-items-center mb-4 no-print bg-light p-3 rounded shadow-sm">
        <div>
            <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm">&larr; Volver</a>
            <span class="ms-2 fw-bold text-dark">Tipo de Viaje: 
                <span class="badge {{ $viaje->tipo == 1 ? 'bg-primary' : 'bg-success' }}">
                    {{ $viaje->tipo == 1 ? 'MARÍTIMO (BUNKER)' : 'INDUSTRIAL' }}
                </span>
            </span>
        </div>
        <button onclick="window.print()" class="btn btn-primary btn-sm fw-bold">
            <i class="fas fa-print me-1"></i> Imprimir Notas de Entrega ({{ $viaje->despachos->count() }})
        </button>
    </div>

    <!-- Bucle que genera una hoja por cada despacho del viaje -->
    @forelse($viaje->despachos as $index => $despacho)
        <div class="nota-entrega-page">
            
            <!-- Encabezado Corporativo -->
            <div class="row border-bottom pb-3 mb-3 align-items-center">
                <div class="col-8">
                    <img src="{{ asset('img/logo1.png') }}" alt="logo empresa" style="width: 320px; margin-right: 10px;" onerror="this.style.display='none'">
                    <p class="mb-0 text-muted small mt-1">{{ $empresa['direccion'] }}</p>
                    <p class="mb-0 text-muted small"><strong>Telf:</strong> {{ $empresa['telefono'] }} | <strong>Email:</strong> {{ $empresa['email'] }}</p>
                </div>
                <div class="col-4 text-end">
                    <div class="border border-dark p-2 rounded bg-light">
                        <span class="text-muted d-block small fw-bold">
                            {{ $viaje->tipo == 1 ? 'BUNKER DELIVERY NOTE' : 'NOTA DE ENTREGA INDUSTRIAL' }}
                        </span>
                        <span class="header-title text-danger">DOC NO: {{ str_pad($viaje->id, 4, '0', STR_PAD_LEFT) }}-{{ $index + 1 }}</span>
                        <span class="d-block small">Fecha: {{ \Carbon\Carbon::parse($viaje->fecha_salida ?? now())->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>

            <!-- Datos Generales (Varía si es Marítimo o Industrial) -->
            <div class="box-section bg-light bg-opacity-10">
                <div class="row g-2">
                    @if($viaje->tipo == 1)
                        <!-- DATOS ESPECÍFICOS PARA VIAJE MARÍTIMO -->
                        <div class="col-6">
                            <strong>Customer / Client:</strong> {{ $despacho->cliente->nombre ?? $despacho->otro_cliente ?? 'N/A' }}<br>
                            <strong>Receiving Vessel Name:</strong> {{ $despacho->buques->nombre ?? $despacho->buque_nombre_manual ?? 'N/A' }}<br>
                            <strong>IMO / Bandera:</strong> {{ $despacho->imo ?? 'N/A' }} / {{ $despacho->bandera ?? 'N/A' }}<br>
                        </div>
                        <div class="col-6">
                            <strong>Bunker Tanker (Bureta):</strong> {{ $viaje->vehiculo->placa ?? 'N/A' }}<br>
                            <strong>Master / Operator:</strong> {{ optional($viaje->chofer)->persona ? $viaje->chofer->persona->nombre . ' ' . $viaje->chofer->persona->apellidos : 'N/A' }}<br>
                            <strong>Muelle / Atraque:</strong> {{ $despacho->muelle_atraque ?? 'N/A' }}
                        </div>
                    @else
                        <!-- DATOS ESPECÍFICOS PARA VIAJE INDUSTRIAL -->
                        <div class="col-6">
                            <strong>Cliente / Empresa:</strong> {{ $despacho->cliente->nombre ?? $despacho->otro_cliente ?? 'N/A' }}<br>
                            <strong>Planta / Destino:</strong> {{ $despacho->buque_nombre_manual ?? $despacho->muelle_atraque ?? 'Planta Principal / Industrial' }}<br>
                            <strong>N° de Pedido:</strong> {{ $despacho->pedido->id ?? 'N/A' }}<br>
                        </div>
                        <div class="col-6">
                            <strong>Unidad / Cisterna:</strong> {{ $viaje->vehiculo->placa ?? 'N/A' }}<br>
                            <strong>Conductor:</strong> {{ optional($viaje->chofer)->persona ? $viaje->chofer->persona->nombre . ' ' . $viaje->chofer->persona->apellidos : 'N/A' }}<br>
                            <strong>Fecha de Despacho:</strong> {{ \Carbon\Carbon::parse($viaje->fecha_salida ?? now())->format('d/m/Y H:i') }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Tabla de Producto y Cantidades -->
            <h6 class="fw-bold text-uppercase small mb-2">1. Detalle del Producto y Volúmenes Suministrados</h6>
            <table class="table table-bordered text-center align-middle mb-3">
                <thead class="table-dark small">
                    <tr>
                        <th>Descripción del Producto</th>
                        <th>Volumen / Litros</th>
                        @if($viaje->tipo == 1)
                            <th>Approx. Barrels (Bbls)</th>
                            <th>Volume (m3)</th>
                        @endif
                        <th>Cantidad (Metric Tons / MT)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-start fw-bold">{{ optional($viaje->producto)->nombre ?? 'DIESEL / COMBUSTIBLE' }}</td>
                        <td>{{ number_format($despacho->litros ?? 0, 2, ',', '.') }} Lts</td>
                        @if($viaje->tipo == 1)
                            <td>{{ number_format(($despacho->litros ?? 0) / 158.987, 3) }}</td>
                            <td>{{ number_format(($despacho->litros ?? 0) / 1000, 3) }}</td>
                        @endif
                        <td class="fw-bold text-primary">{{ number_format((($despacho->litros ?? 0) * 0.85) / 1000, 3) }} MT</td>
                    </tr>
                </tbody>
            </table>

            @if($despacho->observacion)
                <div class="p-2 border bg-light mb-3 small">
                    <strong>Observaciones:</strong> {{ $despacho->observacion }}
                </div>
            @endif

            <!-- Especificaciones de Calidad (Opcional o simplificado para industrial) -->
            @if($viaje->tipo == 1)
                <h6 class="fw-bold text-uppercase small mb-2">2. Fuel Characteristics (ISO 8217)</h6>
                <table class="table table-bordered text-center align-middle mb-4">
                    <thead class="table-secondary small">
                        <tr>
                            <th>Density @ 15°C (kg/m3)</th>
                            <th>Sulphur Content (% m/m)</th>
                            <th>Viscosity (mm2/s)</th>
                            <th>Water Content (% V/V)</th>
                            <th>Flash Point (°C)</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <tr>
                            <td>959.2</td>
                            <td><span class="badge bg-dark">0.5%</span></td>
                            <td>376.4</td>
                            <td>0.20</td>
                            <td>>110</td>
                        </tr>
                    </tbody>
                </table>
            @endif

            <!-- BLOQUE DE FIRMAS: CONDICIONAL SEGÚN EL TIPO -->
            @if($viaje->tipo == 1)
                <!-- FORMATO MARÍTIMO (MARPOL / SELLO DE BUQUE / MUESTRAS) -->
                <div class="row g-3">
                    <div class="col-6">
                        <div class="border p-2 h-100">
                            <p class="fw-bold small mb-1">SUPPLIER'S CONFIRMATION</p>
                            <p class="text-muted" style="font-size: 9px;">
                                Declaration that bunker fuel supplied conforms with MARPOL Annex VI regulation 18.3.<br>
                                <input type="checkbox" style="vertical-align: bottom" checked> the purchaser's specified limit value of 0.5% (m/m).
                            </p>
                            <div class="mt-4 pt-2 text-center border-top">
                                <span class="small d-block">For and on behalf of Supplier</span>
                                <br><br>
                                <strong>______________________________________</strong><br>
                                <span class="small text-muted">Representative Stamp & Signature</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border p-2 h-100">
                            <p class="fw-bold small mb-1">MASTER'S / CHIEF ENGINEER'S ACKNOWLEDGEMENT</p>
                            <p class="text-muted" style="font-size: 10px;">
                                We acknowledge receipt of the above product and confirm that samples were jointly taken.
                            </p>
                            <div class="pt-1 text-center row g-1">
                                <div class="col-12 text-start">
                                    <span class="small d-block" style="font-size: 10px;">Seal No: _________________________</span>
                                </div>
                                <div class="col-3 text-start small">Vessel:</div>
                                <div class="col-9"><strong>__________________________________</strong></div>
                                <div class="col-12 mt-3">
                                    Received Acknowledged by:<br>
                                    <strong>______________________________________</strong><br>
                                    <span class="small text-muted">Signature of Master / Chief Engineer & Vessel Stamp</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <!-- FORMATO INDUSTRIAL (RECIBIDO CONFORME EN PLANTA) -->
                <div class="row g-3 mt-2">
                    <div class="col-6">
                        <div class="border p-3 h-100 text-center">
                            <p class="fw-bold small mb-1 text-start">DESPACHADO POR (PROVEEDOR)</p>
                            <br><br><br>
                            <strong>______________________________________</strong><br>
                            <span class="small text-muted">Firma y Sello del Conductor / Empresa</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border p-3 h-100 text-center">
                            <p class="fw-bold small mb-1 text-start">RECIBIDO CONFORME (CLIENTE / PLANTA)</p>
                            <p class="text-muted small text-start mb-2">Nombre: _____________________ Cédula: ____________</p>
                            <br><br>
                            <strong>______________________________________</strong><br>
                            <span class="small text-muted">Firma y Sello de Recepción en Planta</span>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    @empty
        <div class="alert alert-warning text-center">
            Este viaje no posee despachos registrados para emitir notas de entrega.
        </div>
    @endforelse

</div>

</body>
</html>