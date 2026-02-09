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
            
        </div>
        
        <button type="button" id="update-guia-btn" class="btn btn-primary mt-2">
            <i class="bi bi-save"></i> Guardar Cambios y Recargar Guía
        </button>
        <button type="button" id="print" class="btn btn-success mt-2">
            <i class="bi bi-printer"></i> Vista Previa de Impresión
        </button>
    </form>
</div>

<div class="auth-container printableArea row" style="width: 8.5in; heigth:11in;">
    <table class="table table-bordered" style="font-size: 10pt; width: 95%; margin: 1cm;border:solid 1px black; ">
        <tr>
            <td colspan="4" style="text-align: center; font-size: 14pt; font-weight: bold;">
                <img src="{{ asset('img/logo1.png') }}" alt="logo empresa" style="width: 250px; float: left; margin-right: 10px;">
                <div style="float: rigth; text-align: right; vertical-align:middle">AUTORIZACIÓN / NOMINACIÓN DE COMBUSTIBLES Y LUBRICANTES</div>
            </td>
        </tr>
        <tr style="margin-top: none; padding: none; line-height: none; height: none;">
            <td width="20%" style="margin: none"></td>
            <td width="20%" style="margin: none"></td>
            <td width="30%" style="margin: none"></td>
            <td width="30%" style="margin: none"></td>
        </tr>
        <tr style="border:solid 1px black;">
            <td colspan="3" style="text-align:left; font-size: 12pt; font-weight: bold;">
                CLIENTE
            </td>
            <td style="text-align: left; font-size: 10pt;">INFORMACION</td>
        </tr>
        <tr style="border:solid 1px black;">
            <td colspan="3" style="text-align:left; font-size: 10pt;">
                Facturar a: {{ $guia->cliente ?? 'Distribuidora Impordiesel C.A.' }} <br>
                Direccion: {{ $guia->direccion ?? 'CR UD 524 LOCAL PARCELA 524-01-02...' }} <br>
                Contacto: {{ $guia->contacto ?? 'Antonio Bertolo' }} <br>
                Correo: electronico: {{ $guia->email ?? 'Navuera@tepuymarina.com' }} <br>
                Telefono: {{ $guia->telefono ?? '0286-9231278' }}
            </td>
            <td style="text-align: left; font-size: 10pt; border:solid 1px black;">
                Etiqueta  <br>
                N° Pedido: {{ $guia->numero_guia ?? 'N/A' }} <br>
                Fecha Doc: {{ \Carbon\Carbon::parse($guia->fecha_salida)->format('d/m/Y') }} <br>
                Moneda: VESM Miles de Bolívares <br>
                Orden de Compra: {{ $guia->cliente ?? 'Distribuidora Impordiesel C.A.' }} <br>
                Fecha O.C.: {{ \Carbon\Carbon::parse($guia->updated_at)->format('d/m/Y') }} <br>
            </td>
        </tr>
        <tr style="border:solid 1px black;">
            <td colspan="4"><br><br></td>
        </tr>
        <tr style="border:solid 1px black;">
            <td colspan="3" style="text-align:left; font-size: 12pt; ">
                <span style="font-weight: bold;">Cuenta:</span> DISTRIBUIDORA DE COMBUSTIBLE IMPORDIESEL <br>
                <span style="font-weight: bold;">Vendedor:</span> DISTRIBUIDORA DE COMBUSTIBLE IMPORDIESEL <br>
                <span style="font-weight: bold;">Agente:</span> <br>
                <span style="font-weight: bold;">Corredor:</span> <br>
                <span style="font-weight: bold;">Centro:</span> Planta de Dist. Boleíta <br>
                <span style="font-weight: bold;">Terminos de Pago:</span> {{  'PREPAGADO' }}

            </td>
            <td style="text-align: left; font-size: 10pt; border:solid 1px black;">
                <span style="font-weight: bold;">BUQUE :</span> {{ $guia->buque ?? 'GAMBOA' }} <br>
                <span style="font-weight: bold;">Pto. Entrega:</span> {{ $guia->destino ?? 'MUELLE BAUXILUM' }} <br>
                <span style="font-weight: bold;">Fecha de Entrega:</span> {{ \Carbon\Carbon::parse($guia->updated_at)->format('d/m/Y') }} <br>
                <span style="font-weight: bold;">Método de Entrega:</span> {{  'Truck' }}
            </td>
        </tr>
        <tr style="text-align: center ; font-weight: bold; border:solid 1px black;">
            <td style="border: none">Item</td>
            <td style="border: none">Codigo</td>
            <td style="border: none">Material</td>
            <td style="border: none">Cantidad</td>
        </tr>
        <tr style="text-align: center; border:solid 1px black;">
            <td>1</td>
            <td>401</td>
            <td>MARINE GAS OIL (MGO)</td>
            <td>{{ number_format($guia->cantidad, 0) }} LTS</td>
        </tr>
        <tr style="border:solid 1px black;">
            <td colspan="4" style="text-align: center">
            <h5 style="margin-top: 15px; margin-bottom: 5px;">COMENTARIOS</h5>
            <div class="comentarios-box"> <br><br>
                <h3>{{  'PRODUCTO PARA SER DEPOSITADO EN LOS TANQUES DE SERVICIOS DE LA EMBARCACIÓN PARA CONSUMO PROPIO.' }}</h3>
                <br>
                <br>
            </div>
            </td>
        </tr>

    </table>

</div>

<div class="print-only">
    <button onclick="window.print()" style="padding: 10px 20px; font-size: 14pt; cursor: pointer;">
        Imprimir Nominación / Guardar como PDF
    </button>
</div>

@push('scripts')
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="{{asset('js/jquery.PrintArea.js')}}" defer></script>
@endpush
@endsection