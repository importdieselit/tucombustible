@extends('layouts.app')

@section('title', 'Guía de Distribución')
@push('styles')
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            margin: 20px;
        }
        .guia-container {
            width: 100%;
            max-width: 1080px;
            margin: 0 auto;
            border: 1px solid #000;
            padding: 10px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }
        .header-section, .logistica-section, .receptor-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 5px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        .header-info {
            flex-basis: 60%;
            font-size: 11pt;
        }
        .header-info p {
            margin: 0;
            line-height: 1.3;
        }
        .header-rif {
            flex-basis: 35%;
            text-align: right;
            border: 1px solid #000;
            padding: 5px;
            font-size: 12pt;
            font-weight: bold;
        }
        h1, h2, h3 { margin: 0; }
        .table-detalle {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .table-detalle th, .table-detalle td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
            vertical-align: top;
        }
        .table-detalle th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .table-detalle .cantidad {
            width: 15%;
            text-align: center;
        }
        .table-detalle .precio, .table-detalle .total {
            width: 15%;
            text-align: right;
        }
        .small-title {
            font-weight: bold;
            display: block;
            margin-top: 5px;
        }
        .footer-section {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .footer-field {
            flex-basis: 30%;
            border-bottom: 1px solid #000;
            padding-top: 15px;
            font-size: 9pt;
        }
        .print-only {
            text-align: center;
            margin-top: 20px;
        }
        @media print {
            .print-only {
                display: none;
            }
            .guia-container {
                box-shadow: none;
                border: none;
            }
        }

        .ui-autocomplete {
    z-index: 9999 !important; /* Asegura que esté por encima de todo */
    background: white;
    border: 1px solid #ccc;
    list-style: none;
    padding: 0;
    margin: 0;
    max-height: 200px;
    overflow-y: auto;
}
.ui-menu-item {
    padding: 8px 12px;
    cursor: pointer;
}
.ui-state-active {
    background-color: #007bff !important;
    color: white !important;
}
    </style>
@endpush
@section('content')

{{-- Cargar librerías necesarias --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<div class="control-panel" style="margin-bottom: 20px; padding: 15px; border: 1px solid #007bff; background-color: #e9f5ff;">
    <h4>📝 Edición y Selección Rápida (Fuera de Impresión)</h4>
    <form id="guia-editor-form">
        <div class="row mb-3">
            <div class="col-md-6">
                <label>Cliente / Razón Social</label>
                <input type="text" id="cliente_input" class="form-control hybrid-autocomplete" 
                    data-db-field="cliente_nombre" data-live-id="#live-cliente-nombre" 
                    value="{{ $viaje->despachos->first()->cliente->nombre ?? '' }}" placeholder="Escriba para buscar o crear...">
            </div>
            <div class="col-md-6">
                <label>Buque Embarcacion</label>
                <input type="text" id="buque_input" class="form-control autocomplete-field hybrid-autocomplete" 
                    data-db-field="buque" data-live-id="#live-buque" 
                    value="" placeholder="Escriba para buscar o crear...">
            </div>
            <div class="col-md-6">
                <label>Chuto</label>
                <input type="text" id="chuto_input" class="form-control autocomplete-field hybrid-autocomplete" 
                    data-db-field="chuto" data-live-id="#live-chuto" 
                    value="{{ $viaje->vehiculo->flota ?? 'N/A' }} ({{ $viaje->vehiculo->placa ?? 'N/A' }})" placeholder="Escriba para buscar o crear...">
            </div>
            <div class="col-md-6">
                <label>Cisterna</label>
                <input type="text" id="cisterna_input" class="form-control autocomplete-field hybrid-autocomplete" 
                    data-db-field="cisterna" data-live-id="#live-cisterna" 
                    value="{{ $viaje->cisterna->placa ?? 'N/A' }}" placeholder="Escriba para buscar o crear...">
            </div>
        </div>

        <div class="row mb-3">
             <div class="col-md-6">
                <label for="muelle_input" class="form-label">Destino/Muelle (Registro al Vuelo)</label>
                <input type="text" id="muelle_input" class="form-control autocomplete-field hybrid-autocomplete" 
                    data-db-field="muelle" data-live-id="#live-muelle" value="{{ $viaje->destino ?? 'Muelle de SIDOR' }}">
            </div>
             <div class="col-md-6">
                <label for="precintos_input" class="form-label">Nro. de Precintos</label>
                <input type="text" id="precintos_input" class="form-control autocomplete-field" 
                    data-db-field="precintos" data-live-id="#live-precintos" value="{{ $viaje->precintos ?? 'N/A' }}">
            </div>
        </div>
        
        <button type="button" id="update-guia-btn" class="btn btn-primary mt-2">
            <i class="bi bi-save"></i> Guardar Cambios y Recargar Guía
        </button>
        <button type="button" onclick="window.print()" class="btn btn-success mt-2">
            <i class="bi bi-printer"></i> Vista Previa de Impresión
        </button>
    </form>
</div>
<div class="guia-container">
        
        <div class="header-section row">
            <div class="header-info col-4">
                <img src="{{ asset('img/logo1.png') }}" alt="logo empresa" style="width: 250px">
                <p>Av. Principal de Boleíta entre Av. Francisco de Miranda <br>y la 1ra transversal, Qta Adela Nro S/N Urb Boleita, Caracas</p>
                <p>Petare Miranda, Zona Postal 1079 Telf: {{ '0414-3779488' }}</p>
            </div>
            <div class="header-rif col-4">
                GUÍA DE DISTRIBUCIÓN
            </div>
        </div>

        <div class="col-4" style="display: flex; justify-content: space-between; font-size: 9pt; border-bottom: 1px dashed #ccc; padding-bottom: 5px; margin-bottom: 5px;">
            <p><strong>Lugar de Emisión:</strong> Caracas </p>
            <p><strong>Fecha de Emisión:</strong> {{ \Carbon\Carbon::now()->format('d/m/Y') }}</p>
            <p><strong>Guía Nro:</strong> {{ $viaje->id }}</p>
        </div>

        <div class="receptor-section">
            <div style="flex-basis: 70%;">
                <p><strong>Nombre/Razón Social:</strong> <span id="live-cliente-nombre">{{ $viaje->despachos->first()->cliente->nombre ?? $viaje->despachos->first()->otro_cliente ?? 'N/A' }}</span> <strong>C.I./R.I.F.:</strong> <span id="live-cliente-rif">{{ $viaje->despachos->first()->cliente->rif ?? 'N/A' }}</span></p>
                <p><strong>Domicilio Fiscal:</strong> <span id="live-cliente-direccion">{{ $viaje->despachos->first()->cliente->direccion ?? 'N/A' }}</span>
                <strong>Condiciones de Pago:</strong></p>
            </div>
            <div style="flex-basis: 25%; text-align: right;">
                 <p style="margin-top: 10px;"><strong>Nro. Precintos:</strong> <span id="live-precintos">{{ $viaje->precintos ?? 'N/A' }}</span></p>
            </div>
        </div>
        
        <table class="table-detalle">
            <thead>
                <tr>
                    <th class="cantidad">CANTIDAD </th>
                    <th>CONCEPTO/DESCRIPCIÓN</th>
                    <th class="precio">PRECIO UNIT.</th>
                    <th class="total">TOTAL</th>
                    <th rowspan="12" width="20%" class="sello">SELLO</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($viaje->despachos as $despacho)
                    <tr>
                        <td class="cantidad">{{ number_format($despacho->litros, 2, ',', '.') }}</td>
                        <td>{{ $despacho->concepto ?? 'MARINE GASOIL (MGO)' }} </td>
                        <td class="precio">{{ number_format($despacho->precio_unitario ?? 0, 2, ',', '.') }}</td>
                        <td class="total">{{ number_format($despacho->total ?? ($despacho->litros * ($despacho->precio_unitario ?? 0)), 2, ',', '.') }}</td>
                        
                    </tr>
                @endforeach
                <tr>
                        <td></td>
                        <td><span class="small-title">Placa Chuto:</span> <span id="live-chuto">{{ $viaje->vehiculo->placa ?? 'N/A' }}</span></td>
                        <td ></td>
                        <td ></td>
                        
                </tr>
                <tr>
                        <td></td>
                        <td><span class="small-title">Placa Cisterna:</span> <span id="live-cisterna">{{ $viaje->cisterna->placa ?? 'N/A' }}</span></td>
                        <td ></td>
                        <td ></td>
                        
                </tr>

                <tr>
                        <td></td>
                        <td><span class="small-title">Ruta:</span> <span id="live-ruta">{{ $viaje->ruta ?? 'Boleíta Norte Caracas Puerto Ordaz Edo. Bolivar' }}</span></td>
                        <td ></td>
                        <td ></td>
                        
                </tr>
                <tr>
                        <td></td>
                        <td><span class="small-title">Destino:</span> <span id="live-destino">{{ $viaje->destino ?? 'Muelle de SIDOR' }}</span></td>
                        <td ></td>
                        <td ></td>
                        
                </tr>
                <tr>
                    <td></td>
                    <td><span class="small-title">BUQUE/EMBARCACIÓN: </span> <span id="live-buque">{{ $viaje->buque ?? 'N/A' }}</span></td>
                    <td ></td>
                    <td ></td>
                </tr>
                <tr>
                    <td></td>
                    <td><span class="small-title">Nro precintos: </span> <span id="live-precintos">{{ $viaje->precintos ?? 'N/A' }}</span></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                        <td></td>
                        <td><span class="small-title">CONDUCTOR: </span> <span id="live-conductor">{{ $viaje->chofer->persona->nombre ?? 'N/A' }}</span></td>
                        <td ></td>
                        <td ></td>
                </tr>
                <tr>
                        <td></td>
                        <td><span class="small-title">CEDULA: </span> <span id="live-cedula">{{ $viaje->chofer->persona->cedula ?? 'N/A' }}</span></td>
                        <td ></td>
                        <td ></td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align: right; font-weight: bold;">TOTAL A PAGAR</td>
                    <td class="total" style="font-weight: bold;">$ {{ number_format($viaje->despachos->sum('total'), 2, ',', '.') }}</td>
                    
                </tr>
                <tr>
                    <td>Recibido por:</td>
                    <td colspan="3" style="border-bottom: 1px solid #000;">&nbsp;</td>
                </tr>
                <tr>
                    <td>Cédula:</td>
                    <td colspan="3" style="border-bottom: 1px solid #000;">&nbsp;</td>
                </tr>
                <tr>
                    <td>Fecha:</td>
                    <td colspan="3" style="border-bottom: 1px solid #000;">&nbsp;</td>
                </tr>
                <tr>
                    <td>Firma y Sello:</td>
                    <td colspan="3" style="border-bottom: 1px solid #000;">&nbsp;</td>  
                </tr>
            </tbody>
        </table>

        
    </div>
    
    <div class="print-only">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 14pt; cursor: pointer;">
            Imprimir Guía / Guardar como PDF
        </button>
    </div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script>
   $(document).ready(function() {
    const viajeId = {{ $viaje->id }};
    let typingTimer;

    $('.hybrid-autocomplete').each(function() {
        const $input = $(this);
        const dbField = $input.data('db-field');
        const liveId = $input.data('live-id');

        $input.autocomplete({
            source: function(request, response) {
                $.getJSON("{{ route('api.search.generic') }}", {
                    term: request.term,
                    field: dbField
                }, response);
            },
            select: function(event, ui) {
                $input.val(ui.item.value);
                $(liveId).text(ui.item.value); // Actualiza vista previa
                saveToDatabase(dbField, ui.item.value); // Guarda porque fue una selección oficial
                return false;
            }
        });

        // REACTIVIDAD EN TIEMPO REAL (Sin guardar en BD aún)
        $input.on('input', function() {
            const currentVal = $(this).val();
            $(liveId).text(currentVal); // Esto es lo que querías: ver el cambio abajo YA
        });

        // GUARDADO AL PERDER EL FOCO (Blur)
        // Solo guarda si el usuario terminó de escribir algo nuevo
        $input.on('blur', function() {
            const finalVal = $(this).val();
            if(finalVal.trim() !== "") {
                saveToDatabase(dbField, finalVal);
            }
        });
    });

    function saveToDatabase(field, value) {
        $.ajax({
            url: `/api/viajes/${viajeId}/update-guia-data`,
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // Evita error 401/419
            },
            data: { field: field, value: value },
            success: function() { console.log("Sincronizado con BD"); }
        });
    }
});
</script>
@endpush