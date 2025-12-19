@extends('layouts.app')

@section('title', 'Autorización / Nominación de Combustibles')
@push('styles')
<style>
    /* Estilos generales (reusados de guia.blade.php) */
    body { font-family: Arial, sans-serif; font-size: 10pt; margin: 20px; }
    .auth-container { width: 100%; max-width: 900px; margin: 0 auto; border: 1px solid #000; padding: 15px; }
    .auth-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 10px; }
    .auth-info { margin-bottom: 15px; border: 1px solid #000; padding: 10px; font-size: 9pt; }
    .auth-info div { margin-bottom: 5px; }
    .auth-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 9pt; }
    .auth-table th, .auth-table td { border: 1px solid #000; padding: 6px; text-align: left; }
    .auth-table th { background-color: #f0f0f0; font-weight: bold; }
    .comentarios-box { border: 1px solid #000; padding: 10px; min-height: 100px; margin-top: 15px; font-size: 9pt; }
    .print-only { text-align: center; margin-top: 20px; }
    @media print { .print-only, .control-panel { display: none; } .auth-container { box-shadow: none; border: none; } }
</style>
@endpush
@section('content')

{{-- Cargar librerías necesarias --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<div class="control-panel" style="margin-bottom: 20px; padding: 15px; border: 1px solid #007bff; background-color: #e9f5ff;">
    <h4>📝 Edición Nominación (Fuera de Impresión)</h4>
    <form id="nominacion-editor-form">
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="cliente_select" class="form-label">Cliente (Facturar a)</label>
                <select id="cliente_select" class="form-control" style="width: 100%;">
                    @if ($guia->despachos->first()->cliente)
                        <option value="{{ $guia->despachos->first()->cliente->id }}" selected>{{ $guia->despachos->first()->cliente->nombre }}</option>
                    @endif
                </select>
            </div>
            <div class="col-md-6">
                <label for="buque_select" class="form-label">Buque (Registro al Vuelo)</label>
                <select id="buque_select" class="form-control" style="width: 100%;">
                    @if ($guia->buque)
                        <option value="{{ $guia->buque }}" selected>{{ $guia->buque }}</option>
                    @endif
                </select>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="destino_text" class="form-label">Puerto/Muelle (Registro al Vuelo)</label>
                <input type="text" id="destino_text" class="form-control" value="{{ $guia->destino ?? 'MUELLE BAUXILUM' }}">
            </div>
            <div class="col-md-6">
                <label for="delivery_method" class="form-label">Método de Entrega</label>
                <select id="delivery_method" class="form-control" style="width: 100%;">
                    <option value="Truck" {{ ($guia->metodo_entrega ?? 'Truck') == 'Truck' ? 'selected' : '' }}>Truck</option>
                    <option value="Barge" {{ ($guia->metodo_entrega ?? '') == 'Barge' ? 'selected' : '' }}>Barge</option>
                    <option value="Pipeline" {{ ($guia->metodo_entrega ?? '') == 'Pipeline' ? 'selected' : '' }}>Pipeline</option>
                </select>
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

<div class="auth-container printableArea row" style="width: 8.5in; heigth:11in;">
    <table class="table table-bordered" style="font-size: 10pt; width: 100%;">
        <tr>
            <td colspan="4" style="text-align: center; font-size: 14pt; font-weight: bold;">
                <img src="{{ asset('img/logo1.png') }}" alt="logo empresa" style="width: 250px; float: left; margin-right: 10px;">
                AUTORIZACIÓN / NOMINACIÓN DE COMBUSTIBLES Y LUBRICANTES
            </td>
        </tr>
        <tr style="margin-top: none; padding: none; line-height: none; height: none;">
            <td width="20%" style="margin: none"></td>
            <td width="20%" style="margin: none"></td>
            <td width="30%" style="margin: none"></td>
            <td width="30%" style="margin: none"></td>
        </tr>
        <tr>
            <td colspan="3" style="text-align:left; font-size: 12pt; font-weight: bold;">
                CLIENTE
            </td>
            <td style="text-align: right; font-size: 10pt;">INFORMACION</td>
        </tr>
        <tr>
            <td colspan="3" style="text-align:left; font-size: 10pt;">
                Facturar a: {{ $guia->->cliente ?? 'Distribuidora Impordiesel C.A.' }} <br>
                Direccion: {{ $guia->direccion ?? 'CR UD 524 LOCAL PARCELA 524-01-02...' }} <br>
                Contacto: {{ $guia->contacto ?? 'Antonio Bertolo' }} <br>
                Correo: electronico: {{ $guia->email ?? 'Navuera@tepuymarina.com' }} <br>
                Telefono: {{ $guia->telefono ?? '0286-9231278' }}
            </td>
            <td style="text-align: right; font-size: 10pt;">
                Etiqueta  <br>
                N° Pedido: {{ $guia->id }} <br>
                Fecha Doc: {{ \Carbon\Carbon::parse($guia->fecha_salida)->format('d/m/Y') }} <br>
                Moneda: VESM Miles de Bolívares <br>
                Orden de Compra: {{ $guia->cliente ?? 'Distribuidora Impordiesel C.A.' }} <br>
                Fecha O.C.: {{ \Carbon\Carbon::parse($guia->updated_at)->format('d/m/Y') }} <br>
            </td>
        </tr>
        <tr>
            <td colspan="4"><br><br></td>
        </tr>
        <tr>
            <td colspan="3" style="text-align:left; font-size: 12pt; font-weight: bold;">
                Cuenta: DISTRIBUIDORA DE COMBUSTIBLE IMPORDIESEL <br>
                Vendedor: DISTRIBUIDORA DE COMBUSTIBLE IMPORDIESEL <br>
                Agente: <br>
                Corredor: <br>
                Centro: Planta de Dist. Boleíta <br>
                Terminos de Pago: {{  'PREPAGADO' }}

            </td>
            <td style="text-align: right; font-size: 10pt;">
                BUQUE : {{ $guia->buque ?? 'GAMBOA' }} <br>
                Pto. Entrega: {{ $guia->destino ?? 'MUELLE BAUXILUM' }} <br>
                Fecha de Entrega: {{ \Carbon\Carbon::parse($guia->updated_at)->format('d/m/Y') }} <br>
                Método de Entrega: {{  'Truck' }}
            </td>
        </tr>
        <tr style="text-align: center">
            <td style="border: none">Item</td>
            <td style="border: none">Codigo</td>
            <td style="border: none">Material</td>
            <td style="border: none">Cantidad</td>
        </tr>
        <tr>
            <td>1</td>
            <td>401</td>
            <td>MARINE GAS OIL (MGO)</td>
            <td>{{ number_format($guia->cantidad, 0) }} LTS</td>
        </tr>
        <tr>
            <td colspan="4" style="text-align: center">
            <h5 style="margin-top: 15px; margin-bottom: 5px;">COMENTARIOS</h5>
            <div class="comentarios-box">
                {{  'PRODUCTO PARA SER DEPOSITADO EN LOS TANQUES DE SERVICIOS DE LA EMBARCACIÓN PARA CONSUMO PROPIO.' }}
            </div>
            </td>
        </tr>

    </table>
    <div class="header-bunker mb-3 col-12" style="display: block; height: 100px; ;">
        <p class="mb-0" style="float: rigth; text-align: right; vertical-align:middle"><strong>AUTORIZACION / NOMINACION DE COMBUSTIBLES Y LUBRICANTES</strong></p>
        
    </div>

    <div class="auth-info">
        <div class="row">
            <div class="col-6"><strong>Cliente (Facturar a):</strong> {{ $guia->despachos->first()->cliente->nombre ?? 'Distribuidora Impordiesel C.A.' }}</div>
            <div class="col-6"><strong>Contacto del Cliente:</strong> {{ $guia->despachos->first()->cliente->contacto ?? 'Antonio Bertolo' }}</div>
        </div>
        <div><strong>Dirección:</strong> {{ $guia->despachos->first()->cliente->direccion ?? 'CR UD 524 LOCAL PARCELA 524-01-02...' }}</div>
        <div class="row">
            <div class="col-6"><strong>Teléfono:</strong> {{ $guia->despachos->first()->cliente->telefono ?? '0286-9231278' }}</div>
            <div class="col-6"><strong>Correo Electrónico:</strong> {{ $guia->despachos->first()->cliente->email ?? 'Navuera@tepuymarina.com' }}</div>
        </div>
    </div>
    
    <h5 style="margin-bottom: 10px; border-bottom: 1px solid #000; padding-bottom: 5px;">INFORMACIÓN / ETIQUETA</h5>

    <div class="auth-info">
        <div class="row">
            <div class="col-4"><strong>No. de Pedido:</strong> {{ $guia->id }} Guía de s</div>
            <div class="col-4"><strong>Fecha Doc:</strong> {{ \Carbon\Carbon::parse($guia->fecha_salida)->format('d/m/Y') }}</div>
            <div class="col-4"><strong>Moneda:</strong> VESM Miles de Bolívares</div>
        </div>
        <div class="row">
            <div class="col-4"><strong>Orden de Compra:</strong> {{ $guia->orden_compra ?? 'Distribuidora Impordiesel C.A.' }}</div>
            <div class="col-4"><strong>Fecha O.C.:</strong> {{ \Carbon\Carbon::parse($guia->fecha_salida)->format('d/m/Y') }}</div>
            <div class="col-4"><strong>Términos de Pago:</strong> {{ $guia->terminos_pago ?? 'PREPAGADO' }}</div>
        </div>
        <div class="row">
            <div class="col-4"><strong>Vendedor:</strong> DISTRIBUIDORA DE COMBUSTIBLE IMPORDIESEL</div>
            <div class="col-4"><strong>Centro:</strong> Planta de Dist. Boleíta</div>
            <div class="col-4"><strong>Buque:</strong> {{ $guia->buque ?? 'GAMBOA' }}</div>
        </div>
        <div class="row">
            <div class="col-4"><strong>Pto. Entrega:</strong> {{ $guia->destino ?? 'MUELLE BAUXILUM' }}</div>
            <div class="col-4"><strong>Fecha de Entrega:</strong> {{ \Carbon\Carbon::parse($guia->fecha_salida)->format('d/m/Y') }}</div>
            <div class="col-4"><strong>Método de Entrega:</strong> {{ $guia->metodo_entrega ?? 'Truck' }}</div>
        </div>
    </div>

    <h5 style="margin-bottom: 10px; border-bottom: 1px solid #000; padding-bottom: 5px;">MATERIALES</h5>

    <table class="auth-table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Código</th>
                <th>Material</th>
                <th>Cantidad</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($guia->despachos as $index => $despacho)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>401</td>
                <td>{{ $despacho->concepto ?? 'MARINE GAS OIL (MGO)' }}</td>
                <td>{{ number_format($despacho->litros, 0) }} LTS</td>
            </tr>
            @endforeach
            @for ($i = $guia->despachos->count(); $i < 3; $i++)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
            @endfor
        </tbody>
    </table>

    <div class="row">
        <div class="col-6">
            <h5 style="margin-top: 15px; margin-bottom: 5px;">COMENTARIOS</h5>
            <div class="comentarios-box">
                {{ $guia->comentarios_nominacion ?? 'PRODUCTO PARA SER DEPOSITADO EN LOS TANQUES DE SERVICIOS DE LA EMBARCACIÓN PARA CONSUMO PROPIO.' }}
            </div>
        </div>
        <div class="col-6">
            <h5 style="margin-top: 15px; margin-bottom: 5px;">TM</h5>
            <div class="comentarios-box" style="text-align: right; font-weight: bold; font-size: 14pt;">
                {{ $guia->toneladas_metricas ?? '32,59' }}
            </div>
        </div>
    </div>
    
</div>

<div class="print-only">
    <button onclick="window.print()" style="padding: 10px 20px; font-size: 14pt; cursor: pointer;">
        Imprimir Nominación / Guardar como PDF
    </button>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // La lógica de Select2 y Actualización es idéntica a la Boleta/Guía anterior
        // Se asume que las rutas API (api.clientes.search, api.clientes.store-al-vuelo, api/guias/{id}/update-guia-data) están definidas.
        
        const guiaId = {{ $guia->id }};
        const clienteSelect = $('#cliente_select');
        const buqueSelect = $('#buque_select');
        const deliveryMethod = $('#delivery_method');
        
        // =========================================================
        // 1. SELECT2: CLIENTES, BUQUES (Configuración de Búsqueda y Creación al Vuelo)
        // =========================================================
        clienteSelect.select2({
            placeholder: 'Buscar o ingresar nuevo cliente',
            allowClear: true, tags: true, 
            ajax: { url: '{{ route('api.clientes.search') }}', dataType: 'json', delay: 250, 
                processResults: data => ({ results: data.map(c => ({ id: c.id, text: c.nombre })) }), cache: true },
            createTag: params => (params.term.trim() === '') ? null : { id: params.term, text: params.term + ' (Nuevo Cliente)', newTag: true }
        });

        buqueSelect.select2({
            placeholder: 'Buscar o ingresar nuevo buque', tags: true, 
            createTag: params => ({ id: params.term, text: params.term })
        });
        
        // =========================================================
        // 2. ACTUALIZAR GUÍA / IMPRIMIR
        // =========================================================
        $('#update-guia-btn').on('click', function() {
            const btn = $(this);
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');

            const selectedClienteId = clienteSelect.val();
            const selectedClienteText = clienteSelect.find(':selected').text();
            const selectedBuque = buqueSelect.val();
            const destino = $('#destino_text').val();
            const metodo_entrega = deliveryMethod.val();
            
            let isNewCliente = clienteSelect.find(':selected').data('select2-tag') === true;

            const handleUpdate = (finalClienteId) => {
                $.ajax({
                    url: `{{ url('api/guias') }}/${guiaId}/update-guia-data`, 
                    method: 'PUT',
                    data: {
                        _token: '{{ csrf_token() }}',
                        cliente_id: finalClienteId,
                        buque: selectedBuque,
                        destino: destino,
                        metodo_entrega: metodo_entrega,
                    },
                    success: function(response) {
                        alert('Nominación actualizada con éxito. Recargando vista previa...');
                        window.location.reload(); 
                    },
                    error: function(xhr) {
                        alert('Error al actualizar los datos del guia: ' + (xhr.responseJSON.message || 'Error desconocido'));
                    },
                    complete: function() {
                        btn.prop('disabled', false).html('<i class="bi bi-save"></i> Guardar Cambios y Recargar Guía');
                    }
                });
            };

            // Lógica para registrar cliente al vuelo
            if (isNewCliente) {
                $.ajax({
                    url: '{{ route('api.clientes.store-al-vuelo') }}', 
                    method: 'POST',
                    data: { _token: '{{ csrf_token() }}', nombre: selectedClienteText },
                    success: function(response) {
                        handleUpdate(response.cliente.id); 
                    },
                    error: function(xhr) {
                        alert('Error al registrar el nuevo cliente.');
                        btn.prop('disabled', false).html('<i class="bi bi-save"></i> Guardar Cambios y Recargar Guía');
                    }
                });
            } else {
                handleUpdate(selectedClienteId);
            }
        });
    });
</script>
@endpush
@endsection