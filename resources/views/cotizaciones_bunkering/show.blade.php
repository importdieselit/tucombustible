@extends('layouts.app')


@section('content')
<style>
@media print {
    /* Ocultar todo el cuerpo por defecto */
    body * {
        visibility: hidden;
    }

    /* Mostrar únicamente el área imprimible y sus hijos */
    #printable-area, #printable-area * {
        visibility: visible;
    }

    /* Posicionar el área imprimible al inicio de la página impresa */
    #printable-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        box-shadow: none !important;
    }

    /* Ocultar elementos específicos marcados como no imprimibles */
    .no-print { 
        display: none !important; 
    }

    /* Ajustes visuales limpios para impresión */
    body { 
        background-color: white !important; 
        font-size: 12px !important;
    }

    .avoid-page-break {
        page-break-inside: avoid !important;
    }

    table {
        page-break-inside: auto;
    }

    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }
}
</style>
<div class="container my-4">
    <!-- Botones de Acción (Ocultos en Impresión) -->
    <div class="d-flex justify-content-between mb-3 no-print">
        <a href="{{ route('cotizaciones-bunkering.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Listado
        </a>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Imprimir / Guardar PDF
        </button>
    </div>

    <!-- Contenedor Estilo Formato Institucional Bunkering / STS -->
    <div class="card border-dark p-4 bg-white shadow-sm" id="printable-area">
        
        <!-- Encabezado Membrete Corporativo -->
        <div class="row border-bottom border-2 border-dark pb-3 mb-3 align-items-center">
            <div class="col-7">
                <img src="{{ asset('img/logo1.png') }}" alt="logo empresa" style="max-width: 240px; height: auto;" class="d-block mb-2">
                <small class="text-dark d-block fw-semibold">IMPORDIESEL, C.A. | RIF: J-35023074-86</small>
                <small class="text-muted d-block">Av. Principal Boleíta, Edificio Impordiesel, Piso 1, Caracas, Venezuela</small>
            </div>
            <div class="col-5 text-end">
                <div class="border border-dark p-2 rounded bg-light">
                    <span class="d-block text-muted small fw-bold">OFFICIAL QUOTATION / ORDER NUMBER</span>
                    <strong class="text-danger h4 d-block m-0">{{ $cotizacion->numero_pedido }}</strong>
                    <span class="d-block text-dark small fw-semibold mt-1">DATE: {{ $cotizacion->created_at->format('d/m/Y') }}</span>
                    
                    @if($cotizacion->nivel_precio_cliente)
                    <span class="badge bg-secondary no-print mt-1">
                        Selector: {{ $cotizacion->nivel_precio_cliente == 4 ? '0% (Base)' : 'Nivel ' . $cotizacion->nivel_precio_cliente }}
                    </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Tabla de Datos del Buque y Logística Comercial -->
        <table class="table table-bordered table-sm mb-4 align-middle" style="border: 1px solid #000;">
            <tbody>
                <tr>
                    <td style="width: 20%; background-color: #f2f2f2;" class="fw-bold text-uppercase">COMPAÑÍA / BUYER</td>
                    <td style="width: 30%;">{{ $cotizacion->pagador ?? 'N/A' }}</td>
                    <td style="width: 20%; background-color: #f2f2f2;" class="fw-bold text-uppercase">F. ENTREGA / DELIVERY DATE</td>
                    <td style="width: 30%;">{{ optional($cotizacion->fecha_entrega)->format('d-M-Y') ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="background-color: #f2f2f2;" class="fw-bold text-uppercase">BUQUE / VESSEL</td>
                    <td><strong>{{ $cotizacion->nombre_buque }}</strong></td>
                    <td style="background-color: #f2f2f2;" class="fw-bold text-uppercase">BANDERA / FLAG</td>
                    <td>{{ $cotizacion->bandera_buque ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="background-color: #f2f2f2;" class="fw-bold text-uppercase">IMO NO.</td>
                    <td>{{ $cotizacion->imo_buque ?? 'N/A' }}</td>
                    <td style="background-color: #f2f2f2;" class="fw-bold text-uppercase">AGENCIA NAVIERA</td>
                    <td>{{ $cotizacion->agencia_naviera ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="background-color: #f2f2f2;" class="fw-bold text-uppercase">PUERTO / PORT</td>
                    <td>{{ $cotizacion->punto_entrega }}</td>
                    <td style="background-color: #f2f2f2;" class="fw-bold text-uppercase">LUGAR DE ENTREGA</td>
                    <td>{{ $cotizacion->lugar_entrega ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td style="background-color: #f2f2f2;" class="fw-bold text-uppercase">METODO DE ENTREGA</td>
                    <td colspan="3" class="fw-bold text-primary">{{ $cotizacion->metodo_despacho ?? 'N/A' }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Tabla Réplica del Excel Corporativo (2 renglones por producto) -->
        <table class="table table-bordered mb-4 align-middle" style="border: 2px solid #000;">
            <thead class="text-center" style="border-bottom: 2px solid #000; background-color: #e9ecef;">
                <tr>
                    <th style="width: 35%; border: 1px solid #000;">PRODUCTO / PRODUCT</th>
                    <th style="width: 15%; border: 1px solid #000;">VOLUMEN MT</th>
                    <th style="width: 15%; border: 1px solid #000;">USD$ / TM</th>
                    <th style="width: 15%; border: 1px solid #000;">DELIVERY COST USD</th>
                    <th style="width: 20%; border: 1px solid #000;">TOTAL COST USD</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cotizacion->detalles as $detalle)
                    <!-- Renglón 1: Producto -->
                    <tr>
                        <td class="fw-bold text-uppercase" style="border: 1px solid #000;">
                            {{ $detalle->tipo_producto }}
                            @if(!empty($detalle->obs))
                                <small class="d-block text-muted fst-italic text-capitalize fw-normal">({{ $detalle->obs }})</small>
                            @endif
                        </td>
                        <td class="text-center fw-semibold" style="border: 1px solid #000;">{{ number_format($detalle->toneladas_metricas, 3, ',', '.') }}</td>
                        <td class="text-end fw-semibold" style="border: 1px solid #000;">${{ number_format($detalle->precio_unitario_tm, 2, ',', '.') }}</td>
                        <td class="text-center" style="border: 1px solid #000;">-</td>
                        <td class="text-end fw-bold" style="border: 1px solid #000;">${{ number_format($detalle->subtotal_producto, 2, ',', '.') }}</td>
                    </tr>
                    
                    <!-- Renglón 2: Delivery Fee Independiente -->
                    <tr class="bg-light">
                        <td class="ps-4 text-muted fst-italic fw-bold" style="border: 1px solid #000;">DELIVERY FEE</td>
                        <td class="text-center text-muted" style="border: 1px solid #000;">-</td>
                        <td class="text-center text-muted" style="border: 1px solid #000;">-</td>
                        <td class="text-center fw-bold text-dark" style="border: 1px solid #000;">
                            @if(in_array(strtoupper(trim($detalle->tipo_producto)), ['VLSFO', 'HSFO', 'LSMGO']))
                                @if($detalle->delivery_tarifa_completa > 0)
                                    ${{ number_format($detalle->delivery_tarifa_completa, 2, ',', '.') }}
                                @elseif($detalle->delivery_costo_tm > 0)
                                    ${{ number_format($detalle->delivery_costo_tm, 2, ',', '.') }} x TM
                                @else
                                    $0,00
                                @endif
                            @else
                                {{ $detalle->toneladas_metricas < 890 ? '$8.000' : '$9 x TM' }}
                            @endif
                        </td>
                        <td class="text-end fw-bold text-dark" style="border: 1px solid #000;">${{ number_format($detalle->costo_delivery, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="table-secondary">
                    <td colspan="4" class="text-end fw-bold text-uppercase" style="border: 1px solid #000;">TOTAL COST USD:</td>
                    <td class="text-end fw-bold text-success h5 mb-0" style="border: 1px solid #000;">${{ number_format($cotizacion->total_general, 2, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        <!-- Observaciones Generales de la Operación -->
        @if($cotizacion->observaciones)
        <div class="mb-4">
            <strong class="text-uppercase small">Observaciones / Remarks:</strong>
            <p class="border border-secondary p-2 bg-light rounded mt-1 mb-0 small text-secondary" style="white-space: pre-line;">{{ $cotizacion->observaciones }}</p>
        </div>
        @endif

        <!-- Bloque Institucional de Firmas y Sellos -->
        <div class="row mt-5 pt-3 avoid-page-break">
            <div class="col-6">
                <div class="border border-dark p-3 text-center" style="min-height: 120px;">
                    <span class="d-block text-muted mb-4 small text-uppercase">For and on behalf of Supplier</span>
                    <hr class="w-75 mx-auto border-dark my-2">
                    <small class="fw-bold">Impordiesel Representative Stamp & Signature</small>
                </div>
            </div>
         
        </div>

    </div>
</div>

<!-- Estilos Específicos para Impresión Limpia y Guardado PDF -->
<style>
@media print {
    .no-print { 
        display: none !important; 
    }
    body { 
        background-color: white !important; 
        font-size: 12px !important;
    }
    .container {
        max-width: 100% !important;
        width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .card { 
        border: none !important; 
        box-shadow: none !important; 
        padding: 0 !important;
    }
    .avoid-page-break {
        page-break-inside: avoid !important;
    }
    table {
        page-break-inside: auto;
    }
    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }
}
</style>
@endsection