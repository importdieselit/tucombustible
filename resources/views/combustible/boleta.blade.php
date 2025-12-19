@extends('layouts.app')

@section('title', 'Boleta de Combustible Marino Entregado')
@push('styles')
<style>
    /* Estilos generales (reusados de guia.blade.php) */
    body { font-family: Arial, sans-serif; font-size: 10pt; margin: 20px; }
    .bunker-container { width: 100%; max-width: 900px; margin: 0 auto; border: 1px solid #000; padding: 15px; }
    .header-bunker {border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 10px; }
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px; }
    .info-grid div { border-bottom: 1px dashed #ccc; padding-bottom: 3px; }
    .info-grid strong { font-size: 9pt; display: block; }
    .quality-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 9pt; }
    .quality-table th, .quality-table td { border: 1px solid #000; padding: 4px 8px; text-align: left; }
    .quality-table th { background-color: #f0f0f0; font-weight: bold; }
    .signature-area { display: flex; justify-content: space-between; margin-top: 40px; font-size: 9pt; }
    .signature-area div { width: 45%; text-align: center; }
    .signature-line { border-top: 1px solid #000; margin-top: 50px; padding-top: 5px; }
    .print-only { text-align: center; margin-top: 20px; }
    @media print { .print-only, .control-panel { display: none; } .bunker-container { box-shadow: none; border: none; } }
</style>
@endpush
@section('content')

{{-- Cargar librerías necesarias --}}
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<div class="control-panel" style="margin-bottom: 20px; padding: 15px; border: 1px solid #007bff; background-color: #e9f5ff;">
    <h4>📝 Edición Boleta de Combustible (Fuera de Impresión)</h4>
    <form id="bunker-editor-form">
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="cliente_select" class="form-label">Cliente</label>
                
            </div>
            <div class="col-md-6">
                <label for="buque_select" class="form-label">Buque (Registro al Vuelo)</label>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="bandera" class="form-label">Puerto/Muelle (Registro al Vuelo)</label>
                <input type="text" id="bandera" class="form-control" value="{{ $guia->destino ?? 'MUELLE BAUXILUM' }}">
            </div>
            <div class="col-md-6">
                <label for="delivery_method" class="form-label"></label>
                
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

<div class="bunker-container">
    <div class="header-bunker mb-3" style="display: block; height: 100px; ;">
        <img src="{{ asset('img/logo1.png') }}" alt="logo empresa" style="width: 250px; float: left; margin-right: 10px;">
        <p class="mb-0" style="float: rigth; text-align: right; vertical-align:middle"><strong>Boleta de combustible marino entregado</strong> <br>(marine bunker delivery receipt)</p>
        
    </div>
    <table class="table table-bordered border-dark mb-3">
        <tr>
            <td colspan="6">
                <label for="">CLIENTE (client)</label>
                {{ $guia->cliente ?? 'Tepuy Marina' }}
            </td>
            <td colspan="3">
                <label for="">NOMINACION</label>
            </td>
            <td><label for="">FECHA(date)</label></td>
        </tr>
        <tr>
            <td colspan="5">
                <label for="">BUQUE (vessel)</label>
                {{ $guia->buque ?? 'GAMBOA' }}
            </td>
            <td>
                <label for="">IMO</label>
            </td>
            <td>
                <label for="">BANDERA (flag)</label>
            </td>
            <td colspan="3">
                <label for="">PUERTO (port)</label>
            </td>
        </tr>
        <tr>
            <td colspan="2"><label for="">METODO DE ENTREGA (delivery method)</label></td>
            <td colspan="8">
                <div class="row">
                    <div class="col-4"><i class="fa fa-solid fa-square"></i> CAMION (tank truck)</div>
                    <div class="col-4"><i class="fa fa-regular fa-square"></i> GABARRA (barge)</div>
                    <div class="col-4"><i class="fa fa-regular fa-square"></i> TUBERIA (pipeline)</div>
                </div>
            </td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    </table>
    <div class="info-grid">
        <div>
            <strong>CLIENTE (client):</strong> {{ $guia->cliente ?? 'Tepuy Marina' }}
        </div>
        <div>
            <strong>NOMINACIÓN:</strong> #########
        </div>
        <div>
            <strong>BUQUE (vessel):</strong> {{ $guia->buque ?? 'GAMBOA' }}
        </div>
        <div>
            <strong>FECHA (date):</strong> {{ \Carbon\Carbon::parse($guia->created_at)->format('d.m.Y') }}
        </div>
        <div>
            <strong>IMO:</strong> ####
        </div>
        <div>
            <strong>BANDERA (flag):</strong> #########
        </div>
        <div>
            <strong>PUERTO (port):</strong> {{ $guia->muelle ?? 'MUELLE BAUXILUM' }}
        </div>
        <div>
            <strong>MÉTODO DE ENTREGA (delivery method):</strong> 
            {{ $guia->metodo_entrega ?? 'CAMION (tank truck)' }}
        </div>
    </div>
    
    <h5 style="margin-bottom: 10px; border-bottom: 1px solid #000; padding-bottom: 5px;">DETALLE DE CALIDAD (QUALITY)</h5>

    <table class="quality-table">
        <thead>
            <tr>
                <th>PROPIEDAD</th><th>VALOR</th><th>PROPIEDAD</th><th>VALOR</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>GRAVEDAD API 60 °F (A. PI gravity at 60 °F)</td>
                <td>{{ '36,3' }}</td>
                <td>GRAVEDAD ESPECIFICA A 60 °F (specific gravity at 60 °F)</td>
                <td>{{ '0,8433' }}</td>
            </tr>
            <tr>
                <td>PUNTO DE INFLAMACIÓN (°C) (flash point)</td>
                <td>{{ '66' }}</td>
                <td>PUNTO DE FLUIDEZ (°C) (pour point)</td>
                <td>{{ '-6' }}</td>
            </tr>
            <tr>
                <td>VISCOSIDAD A 50° C (cSt.) (viscosity)</td>
                <td>{{  '38,5' }}</td>
                <td>AZUFRE (%PESO) (sulphur, wt%)</td>
                <td>{{ '0,438' }}</td>
            </tr>
            <tr>
                <td>AGUA Y SEDIMENTO (% VOL) (B.S & water)</td>
                <td>{{  '0,005' }}</td>
                <td>DENSIDAD (density)</td>
                <td>{{ '0,8428' }}</td>
            </tr>
            <tr>
                <td>PRODUCTO (product)</td>
                <td>M.G.O</td>
                <td>TEMP °C</td>
                <td>{{ '28' }}</td>
            </tr>
        </tbody>
    </table>

    <h5 style="margin-top: 15px; margin-bottom: 10px; border-bottom: 1px solid #000; padding-bottom: 5px;">CANTIDAD (QUANTITY)</h5>
    
    <table class="quality-table">
        <tr>
            <th width="30%">LITROS BRUTOS (gross litres)</th>
            <td width="20%">{{ number_format(($guia->cantidad ?? 0), 2, ',', '.') }}</td>
            <th width="30%">TONELADAS METRICAS</th>
            <td width="20%">{{ number_format(($guia->cantidad* 0.8428), 2, ',', '.') }}</td>
        </tr>
        <tr>
            <th>LITROS NETOS (net litres)</th>
            <td>{{ number_format($guia->cantidad, 2, ',', '.') }}</td>
            <th>FACTOR CORRECC (corr. Factor)</th>
            <td>{{  '0,998' }}</td>
        </tr>
    </table>

    <p style="margin-top: 15px; font-style: italic; font-size: 8pt; border-bottom: 1px solid #000; padding-bottom: 10px;">
        Remarks: The fuel supplied in this delivery in conformity with regulation 14(1) or (4)A and regulation 18(1) of annex VI Marpol 73/78
    </p>

    <div class="signature-area">
        <div>
            <p>POR EL BUQUE (by vessel)</p>
            <div class="signature-line">FIRMA (signature)</div>
            <div class="signature-line">NOMBRE (name)</div>
            <div class="signature-line">CAPITAN (master)</div>
        </div>
        <div>
            <p>POR DISTRIBUIDORA IMPORDIESEL</p>
            <div class="signature-line">FIRMA (signature)</div>
            <div class="signature-line">NOMBRE (name): {{ $guia->supervisor_nombre ?? 'YULIMAR CASTELLANOS' }}</div>
            <div class="signature-line">CARGO (ej. Supervisor)</div>
        </div>
    </div>
</div>

<div class="print-only">
    <button onclick="window.print()" style="padding: 10px 20px; font-size: 14pt; cursor: pointer;">
        Imprimir Boleta / Guardar como PDF
    </button>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        
        const viajeId = {{ $guia->id }};
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
                    url: `{{ url('api/viajes') }}/${viajeId}/update-guia-data`, // Asegúrate de que esta ruta maneje todos los campos
                    method: 'PUT',
                    data: {
                        _token: '{{ csrf_token() }}',
                        cliente_id: finalClienteId,
                        buque: selectedBuque,
                        destino: destino,
                        metodo_entrega: metodo_entrega,
                        // Aquí se podrían añadir los campos de calidad si son editables (api_gravity, flash_point, etc.)
                    },
                    success: function(response) {
                        alert('Boleta actualizada con éxito. Recargando vista previa...');
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