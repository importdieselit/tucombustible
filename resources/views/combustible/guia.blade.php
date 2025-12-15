@extends('layouts.app')

@section('title', 'Guía de Distribución')
@push('styles')
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
                <label for="cliente_select" class="form-label">Cliente (Registro al Vuelo)</label>
                <select id="cliente_select" class="form-control" style="width: 100%;" data-current-id="{{ $viaje->despachos->first()->cliente_id ?? '' }}">
                    @if ($viaje->despachos->first()->cliente)
                        <option value="{{ $viaje->despachos->first()->cliente->id }}" selected>
                            {{ $viaje->despachos->first()->cliente->nombre }}
                        </option>
                    @endif
                </select>
            </div>
            
            <div class="col-md-6">
                <label for="buque_select" class="form-label">Buque/Embarcación (Registro al Vuelo)</label>
                <select id="buque_select" class="form-control" style="width: 100%;" data-current-buque="{{ $viaje->buque ?? '' }}">
                    @if ($viaje->buque)
                        <option value="{{ $viaje->buque }}" selected>{{ $viaje->buque }}</option>
                    @endif
                </select>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="chuto_select" class="form-label">Chuto/Vehículo (Placa/Flota)</label>
                <select id="chuto_select" class="form-control" style="width: 100%;" data-current-id="{{ $viaje->vehiculo_id ?? '' }}">
                    @if ($viaje->vehiculo)
                        <option value="{{ $viaje->vehiculo->id }}" selected>
                            {{ $viaje->vehiculo->flota ?? 'N/A' }} ({{ $viaje->vehiculo->placa ?? 'N/A' }})
                        </option>
                    @endif
                </select>
            </div>
            <div class="col-md-6">
                <label for="cisterna_select" class="form-label">Cisterna (Placa)</label>
                <select id="cisterna_select" class="form-control" style="width: 100%;" data-current-id="{{ $viaje->cisterna_id ?? '' }}">
                    @if ($viaje->cisterna)
                        <option value="{{ $viaje->cisterna->id }}" selected>
                            {{ $viaje->cisterna->placa ?? 'N/A' }}
                        </option>
                    @endif
                </select>
            </div>
        </div>


        <div class="row mb-3">
             <div class="col-md-6">
                <label for="destino_text" class="form-label">Destino/Muelle (Registro al Vuelo)</label>
                <input type="text" id="destino_text" class="form-control" value="{{ $viaje->destino ?? 'Muelle de SIDOR' }}">
            </div>
             <div class="col-md-6">
                <label for="precintos_text" class="form-label">Nro. de Precintos</label>
                <input type="text" id="precintos_text" class="form-control" value="{{ $viaje->precintos ?? 'N/A' }}">
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
                <p><strong>Nombre/Razón Social:</strong> {{ $viaje->despachos->first()->cliente->nombre ?? $viaje->despachos->first()->otro_cliente ?? 'N/A' }} <strong>C.I./R.I.F.:</strong> {{ $viaje->despachos->first()->cliente->rif ?? 'N/A' }}</p>
                <p><strong>Domicilio Fiscal:</strong> {{ $viaje->despachos->first()->cliente->direccion ?? 'N/A' }}
                <strong>Condiciones de Pago:</strong></p>
            </div>
            <div style="flex-basis: 25%; text-align: right;">
                 <p style="margin-top: 10px;"><strong>Nro. Precintos:</strong> {{ $viaje->precintos ?? 'N/A' }}</p>
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
                        <td><span class="small-title">Placa Chuto:{{ $viaje->vehiculo->placa ?? 'N/A' }} </span></td>
                        <td ></td>
                        <td ></td>
                        
                </tr>
                <tr>
                        <td></td>
                        <td><span class="small-title">Placa Cisterna: {{ $viaje->cisterna->placa ?? 'N/A' }}</span></td>
                        <td ></td>
                        <td ></td>
                        
                </tr>

                <tr>
                        <td></td>
                        <td><span class="small-title">Ruta: {{ $viaje->ruta ?? 'Boleíta Norte Caracas Puerto Ordaz Edo. Bolivar' }}</span></td>
                        <td ></td>
                        <td ></td>
                        
                </tr>
                <tr>
                        <td></td>
                        <td><span class="small-title">Destino: {{ $viaje->destino ?? 'Muelle de SIDOR' }}</span></td>
                        <td ></td>
                        <td ></td>
                        
                </tr>
                <tr>
                    <td></td>
                    <td><span class="small-title">BUQUE/EMBARCACIÓN: {{ $viaje->buque ?? 'N/A' }}</span></td>
                    <td ></td>
                    <td ></td>
                </tr>
                <tr>
                    <td></td>
                    <td><span class="small-title">Nro precintos: {{ $viaje->precintos ?? 'N/A' }}</span></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                        <td></td>
                        <td><span class="small-title">CONDUCTOR: {{ $viaje->chofer->persona->nombre ?? 'N/A' }}</span></td>
                        <td ></td>
                        <td ></td>
                </tr>
                <tr>
                        <td></td>
                        <td><span class="small-title">CEDULA: {{ $viaje->chofer->persona->cedula ?? 'N/A' }}</span></td>
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
<script>
    $(document).ready(function() {
        
        const viajeId = {{ $viaje->id }};
        const clienteSelect = $('#cliente_select');
        const buqueSelect = $('#buque_select');
        const chutoSelect = $('#chuto_select'); 
        const cisternaSelect = $('#cisterna_select'); 

        // =========================================================
        // 1. SELECT2: CLIENTES (Búsqueda y Creación al Vuelo)
        // =========================================================
        clienteSelect.select2({
            placeholder: 'Buscar o ingresar nuevo cliente',
            allowClear: true,
            tags: true, 
            ajax: {
                // RUTA: Debes definir `route('api.clientes.search')` en routes/api.php
                url: '{{ route('api.clientes.search') }}', 
                dataType: 'json',
                delay: 250,
                processResults: function (data) {
                    return {
                        results: data.map(cliente => ({
                            id: cliente.id,
                            text: cliente.nombre
                        }))
                    };
                },
                cache: true
            },
            createTag: function (params) {
                if (params.term.trim() === '') { return null; }
                return { id: params.term, text: params.term + ' (Nuevo Cliente)', newTag: true };
            }
        });

        // =========================================================
        // 2. SELECT2: VEHÍCULOS (Búsqueda y Creación al Vuelo)
        // =========================================================
        chutoSelect.select2({
            placeholder: 'Buscar o ingresar nuevo chuto (Flota o Placa)',
            allowClear: true,
            tags: true, // Permite escribir si no existe
            ajax: {
                // RUTA: Debes definir `route('api.vehiculos.search')`
                url: '{{ route('api.vehiculos.search') }}', 
                dataType: 'json',
                delay: 250,
                processResults: function (data) {
                    return {
                        results: data.map(vehiculo => ({
                            id: vehiculo.id,
                            text: `${vehiculo.flota} (${vehiculo.placa})`
                        }))
                    };
                },
                cache: true
            },
            // Prefijo para distinguir la entrada manual de un ID
            createTag: function (params) {
                if (params.term.trim() === '') { return null; }
                return { id: 'NEW_CHUTO:' + params.term, text: params.term + ' (Nuevo/Manual)', newTag: true };
            }
        });

        cisternaSelect.select2({
            placeholder: 'Buscar o ingresar nueva cisterna (Placa)',
            allowClear: true,
            tags: true, // Permite escribir si no existe
            ajax: {
                // RUTA: Debes definir `route('api.cisternas.search')`
                url: '{{ route('api.cisternas.search') }}', 
                dataType: 'json',
                delay: 250,
                processResults: function (data) {
                    return {
                        results: data.map(cisterna => ({
                            id: cisterna.id,
                            text: cisterna.placa
                        }))
                    };
                },
                cache: true
            },
            // Prefijo para distinguir la entrada manual de un ID
            createTag: function (params) {
                if (params.term.trim() === '') { return null; }
                return { id: 'NEW_CISTERNA:' + params.term, text: params.term + ' (Nueva/Manual)', newTag: true };
            }
        });


        // =========================================================
        // 3. SELECT2: BUQUES (Registro al Vuelo Simplificado)
        // =========================================================
        buqueSelect.select2({
            placeholder: 'Buscar o ingresar nuevo buque',
            tags: true, 
            createTag: function (params) {
                return { id: params.term, text: params.term };
            }
        });
        
        // =========================================================
        // 4. ACTUALIZAR GUÍA / IMPRIMIR (handleUpdate modificado)
        // =========================================================
        $('#update-guia-btn').on('click', function() {
            const btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

            // Recolección de datos
            const selectedClienteId = clienteSelect.val();
            const selectedClienteText = clienteSelect.find(':selected').text();
            
            const selectedChuto = chutoSelect.val(); 
            const selectedCisterna = cisternaSelect.val(); 
            
            const selectedBuque = buqueSelect.val();
            const destino = $('#destino_text').val();
            const precintos = $('#precintos_text').val();
            
            let clienteToUseId = selectedClienteId;
            let isNewCliente = clienteSelect.find(':selected').data('select2-tag') === true;


            const handleUpdate = (finalClienteId) => {
                
                // Procesar Chuto/Cisterna: Si el valor comienza con NEW_*, se envía solo el texto.
                let chutoData = selectedChuto;
                let cisternaData = selectedCisterna;

                if (String(selectedChuto).startsWith('NEW_CHUTO:')) {
                    chutoData = selectedChuto.replace('NEW_CHUTO:', '');
                }
                if (String(selectedCisterna).startsWith('NEW_CISTERNA:')) {
                    cisternaData = selectedCisterna.replace('NEW_CISTERNA:', '');
                }

                // 1. Actualizar datos del Viaje con todos los campos
                $.ajax({
                    // RUTA: Debes definir un método PUT/POST para `api/viajes/{viajeId}/update-guia-data`
                    url: `{{ url('api/viajes') }}/${viajeId}/update-guia-data`,
                    method: 'PUT',
                    data: {
                        _token: '{{ csrf_token() }}',
                        cliente_id: finalClienteId,
                        vehiculo_data: chutoData,
                        cisterna_data: cisternaData,
                        buque: selectedBuque,
                        destino: destino,
                        precintos: precintos,
                    },
                    success: function(response) {
                        alert('Guía actualizada con éxito. Recargando vista previa...');
                        window.location.reload(); 
                    },
                    error: function(xhr) {
                        alert('Error al actualizar los datos del viaje: ' + (xhr.responseJSON.message || 'Error desconocido'));
                    },
                    complete: function() {
                        btn.prop('disabled', false).html('<i class="bi bi-save"></i> Guardar Cambios y Recargar Guía');
                    }
                });
            };

            // 2. Lógica para registrar cliente al vuelo
            if (isNewCliente) {
                $.ajax({
                    url: '{{ route('api.clientes.store-al-vuelo') }}', 
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        nombre: selectedClienteText,
                    },
                    success: function(response) {
                        handleUpdate(response.cliente.id); 
                    },
                    error: function(xhr) {
                        alert('Error al registrar el nuevo cliente: ' + (xhr.responseJSON.message || 'Error desconocido'));
                        btn.prop('disabled', false).html('<i class="bi bi-save"></i> Guardar Cambios y Recargar Guía');
                    }
                });
            } else {
                handleUpdate(clienteToUseId);
            }

        });
    });
</script>
@endpush