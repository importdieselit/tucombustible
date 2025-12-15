@extends('layouts.app')

@section('title', 'Crear Solicitud de Compra de Combustible')
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
                <label for="destino_text" class="form-label">Destino/Muelle</label>
                <input type="text" id="destino_text" class="form-control" value="{{ $viaje->destino ?? 'Muelle de SIDOR' }}">
            </div>
             <div class="col-md-6">
                <label for="precintos_text" class="form-label">Nro. de Precintos</label>
                <input type="text" id="precintos_text" class="form-control" value="{{ $viaje->precintos ?? 'N/A' }}">
            </div>
        </div>
        
        <button type="button" id="update-guia-btn" class="btn btn-primary mt-2">
            <i class="bi bi-save"></i> Guardar Cambios e Imprimir
        </button>
        <button type="button" onclick="window.print()" class="btn btn-success mt-2">
            <i class="bi bi-printer"></i> Vista Previa de Impresión
        </button>
    </form>
</div>
<div class="guia-container">
        
        <div class="header-section">
            <div class="header-info">
                <img src="{{ asset('img/logo1.png') }}" alt="logo empresa" style="width: 250px">
                <p>Av. Principal de Boleíta entre Av. Francisco de Miranda <br>y la 1ra transversal, Qta Adela Nro S/N Urb Boleita, Caracas</p>
                <p>Petare Miranda, Zona Postal 1079 Telf: {{ '0414-3779488' }}</p>
            </div>
            <div class="header-rif">
                GUÍA DE DISTRIBUCIÓN
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; font-size: 9pt; border-bottom: 1px dashed #ccc; padding-bottom: 5px; margin-bottom: 5px;">
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
                 <p style="margin-top: 10px;"><strong>Nro. Precintos:</strong></p>
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
                        <td>{{ $despacho->concepto ?? 'MARINE GASOIL (MGO)' }} [cite: 14]</td>
                        <td class="precio">{{ number_format($despacho->precio_unitario ?? 0, 2, ',', '.') }}</td>
                        <td class="total">{{ number_format($despacho->total ?? ($despacho->litros * ($despacho->precio_unitario ?? 0)), 2, ',', '.') }}</td>
                        
                    </tr>
                @endforeach
                <tr>
                        <td></td>
                        <td><span class="small-title">Placa Chuto:</span> {{ $viaje->vehiculo->placa ?? 'N/A' }}</td>
                        <td ></td>
                        <td ></td>
                        
                </tr>
                <tr>
                        <td></td>
                        <td><span class="small-title">Placa Cisterna:</span> {{ $viaje->cisterna->placa ?? 'N/A' }}</td>
                        <td ></td>
                        <td ></td>
                        
                </tr>

                <tr>
                        <td></td>
                        <td><span class="small-title">Ruta:</span> {{ $viaje->ruta ?? 'Boleíta Norte Caracas Puerto Ordaz Edo. Bolivar' }}</td>
                        <td ></td>
                        <td ></td>
                        
                </tr>
                <tr>
                        <td></td>
                        <td><span class="small-title">Destino:</span> {{ $viaje->destino ?? 'Muelle de SIDOR' }}</td>
                        <td ></td>
                        <td ></td>
                        
                </tr>
                <tr>
                    <td></td>
                    <td><span class="small-title">BUQUE/EMBARCACIÓN:</span> {{ $viaje->buque ?? 'N/A' }}</td>
                    <td ></td>
                    <td ></td>
                </tr>
                <tr>
                    <td></td>
                    <td>Nro precintos:</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                        <td></td>
                        <td><span class="small-title">CONDUCTOR:</span> {{ $viaje->chofer->persona->nombre ?? 'N/A' }}</td>
                        <td ></td>
                        <td ></td>
                </tr>
                <tr>
                        <td></td>
                        <td><span class="small-title">CEDULA:</span> {{ $viaje->chofer->persona->cedula ?? 'N/A' }}</td>
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
                    <td colspan="3" style="text-align: right; font-weight: bold;">TOTAL A PAGAR [cite: 34]</td>
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
// ... al final de guia_distribucion.blade.php

<script>
    $(document).ready(function() {
        
        const viajeId = {{ $viaje->id }};
        const clienteSelect = $('#cliente_select');
        const buqueSelect = $('#buque_select');

        // =========================================================
        // 1. SELECT2: CLIENTES (Búsqueda y Creación al Vuelo)
        // =========================================================
        clienteSelect.select2({
            placeholder: 'Buscar o ingresar nuevo cliente',
            allowClear: true,
            tags: true, // Permite crear una opción que no existe
            ajax: {
                // Ruta para buscar clientes (debes definirla en Laravel)
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
            // Plantilla para la opción de creación al vuelo
            createTag: function (params) {
                if (params.term.trim() === '') {
                    return null;
                }
                return {
                    id: params.term,
                    text: params.term + ' (Nuevo Cliente)',
                    newTag: true // Flag para identificar que es nuevo
                };
            }
        });

        // =========================================================
        // 2. SELECT2: BUQUES (Registro al Vuelo, No asociado a una tabla por simplicidad)
        // =========================================================
        buqueSelect.select2({
            placeholder: 'Buscar o ingresar nuevo buque',
            tags: true, // Esto permite al usuario escribir un nuevo buque
            createTag: function (params) {
                return { id: params.term, text: params.term };
            }
        });
        
        // =========================================================
        // 3. ACTUALIZAR GUÍA / IMPRIMIR
        // =========================================================
        $('#update-guia-btn').on('click', function() {
            const btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

            const selectedClienteId = clienteSelect.val();
            const selectedClienteText = clienteSelect.find(':selected').text();
            const selectedBuque = buqueSelect.val();
            const destino = $('#destino_text').val();
            const precintos = $('#precintos_text').val();
            
            let clienteToUseId = selectedClienteId;
            let isNewCliente = clienteSelect.find(':selected').data('select2-tag') === true;


            // Función para manejar el registro del cliente o la actualización de la guía
            const handleUpdate = (finalClienteId) => {
                // 1. Actualizar datos del Viaje con el Cliente ID (y otros campos)
                $.ajax({
                    url: `{{ url('api/viajes') }}/${viajeId}/update-guia-data`,
                    method: 'PUT', // o POST
                    data: {
                        _token: '{{ csrf_token() }}',
                        cliente_id: finalClienteId,
                        buque: selectedBuque,
                        destino: destino,
                        precintos: precintos,
                        // Se pueden añadir más campos de la guía si son editables
                    },
                    success: function(response) {
                        alert('Guía actualizada con éxito.');
                        // Opcional: Recargar la vista previa de la guía con los datos actualizados
                        window.location.reload(); 
                    },
                    error: function() {
                        alert('Error al actualizar los datos del viaje.');
                    },
                    complete: function() {
                        btn.prop('disabled', false).html('<i class="bi bi-save"></i> Guardar Cambios e Imprimir');
                    }
                });
            };

            // 2. Si es un nuevo cliente, registrarlo primero
            if (isNewCliente) {
                $.ajax({
                    url: '{{ route('api.clientes.store-al-vuelo') }}', // Ruta para crear cliente
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        nombre: selectedClienteText,
                        // Aquí puedes añadir más campos necesarios para la creación
                    },
                    success: function(response) {
                        alert(`Nuevo Cliente "${response.cliente.nombre}" registrado exitosamente.`);
                        handleUpdate(response.cliente.id); // Continuar con la actualización de la guía
                    },
                    error: function(xhr) {
                        alert('Error al registrar el nuevo cliente: ' + (xhr.responseJSON.message || 'Error desconocido'));
                        btn.prop('disabled', false).html('<i class="bi bi-save"></i> Guardar Cambios e Imprimir');
                    }
                });
            } else {
                // Si el cliente ya existe, simplemente actualiza la guía
                handleUpdate(clienteToUseId);
            }

        });
    });
</script>
@endpush
