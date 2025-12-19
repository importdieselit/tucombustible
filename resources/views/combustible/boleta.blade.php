@extends('layouts.app')

@section('title', 'Boleta de Combustible Marino Entregado')
@push('styles')
<style>
    /* Estilos generales (reusados de guia.blade.php) */
    body { font-family: Arial, sans-serif; font-size: 10pt; margin: 20px; }
    .bunker-container { width: 100%; max-width: 900px; margin: 0 auto; border: 1px solid #000; padding: 15px; }
    .header-bunker {border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 10px; }
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px; }
    .table td { border-bottom: 1px dashed #ccc; padding-bottom: 3px; }
    .table strong { font-size: 8pt; display: block; }
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
        <button type="button" class="btn btn-success mt-2" id="print">
            <i class="bi bi-printer"></i> Vista Previa de Impresión
        </button>
    </form>
</div>

<div class="bunker-container printableArea">
    <div class="header-bunker mb-3" style="display: block; height: 100px; ;">
        <img src="{{ asset('img/logo1.png') }}" alt="logo empresa" style="width: 250px; float: left; margin-right: 10px;">
        <p class="mb-0" style="float: rigth; text-align: right; vertical-align:middle"><strong>Boleta de combustible marino entregado</strong> <br>(marine bunker delivery receipt)</p>
        
    </div>
    <table class="table table-bordered border-dark mb-3" style="font-size: 10pt">
        <tr>
            <td colspan="6">
                <strong>CLIENTE (client)</strong>
                {{ $guia->cliente ?? 'Tepuy Marina' }}
            </td>
            <td colspan="3">
                <strong>NOMINACION</strong>
                #########
            </td>
            <td>
                <strong>FECHA (date)</strong>
                {{ \Carbon\Carbon::parse($guia->created_at)->format('d.m.Y') }}
            </td>
        </tr>
        <tr >
            <td colspan="4">
                <strong>BUQUE (vessel)</strong>
                {{ $guia->buque ?? 'GAMBOA' }}
            </td>
            <td colspan="2">
                <strong>IMO</strong>
                {{ $guia->buque->imo ?? '####' }}
            </td>
            <td colspan="2">
                <strong>BANDERA (flag)</strong>
                {{ $guia->buque->bandera ?? '#########'}}
            </td>
            <td colspan="2">
                <strong>PUERTO (port)</strong>
                {{ $guia->muelle ?? 'MUELLE BAUXILUM' }}
            </td>
        </tr>
        <tr>
            <td colspan="3" style="font-size: 8pt">METODO DE ENTREGA (delivery method)</td>
            <td colspan="7">
                <div class="row">
                    <div class="col-4"><i class="fa fa-solid fa-square"></i> CAMION (tank truck)</div>
                    <div class="col-4"><i class="fa fa-regular fa-square"></i> GABARRA (barge)</div>
                    <div class="col-4"><i class="fa fa-regular fa-square"></i> TUBERIA (pipeline)</div>
                </div>
            </td>
        </tr>
        <tr style="height:1px; border: none">
            <td width="10%" style="border: none"></td>
            <td width="10%" style="border: none"></td>
            <td width="10%" style="border: none"></td>
            <td width="10%" style="border: none"></td>
            <td width="10%" style="border: none"></td>
            <td width="10%" style="border: none"></td>
            <td width="10%" style="border: none"></td>
            <td width="10%" style="border: none"></td>
            <td width="10%" style="border: none"></td>
            <td width="10%" style="border: none"></td>
        </tr>
        <tr>
            <td colspan="8" ></td>
            <td rowspan="2"><strong>TEMP °C</strong></td>
            <td rowspan="2">
                <strong>FACTOR CORREC (corr. Factor)</strong>
            </td>
        </tr>
        <tr style="text-align: center">
            <td rowspan="3" colspan="2"><strong>PRODUCTO (product)</strong> <br>M.G.O.</td>
            <td colspan="3"><strong>INICIO (start)</strong></td>
            <td colspan="3"><strong>FINAL (finish)</strong></td>
        </tr>
        <tr style="text-align: center">
            <td><strong>FECHA (date)</strong></td>
            <td><strong>HORA (time)</strong></td>
            <td><strong>LITROS (litres)</strong></td>
            <td><strong>FECHA (date)</strong></td>
            <td><strong>HORA (time)</strong></td>
            <td><strong>LITROS (litres)</strong></td>
            <td rowspan="2">80 F <br> 28</td>
            <td rowspan="2"><br>0,998</td>
        </tr>
        <tr>
            <td>{{ \Carbon\Carbon::parse($guia->created_at)->format('d.m.Y') }}</td>
            <td></td>
            <td></td>
            <td>{{ \Carbon\Carbon::parse($guia->created_at)->format('d.m.Y') }}</td>
            <td></td>
            <td></td>
        </tr>
        <tr><td colspan="10" style="text-align: center; font-weight:bolder">CALIDAD (QUALITY)</td></tr>
        <tr style="text-align: center">
            <td colspan="3"> <strong>GRAVEDAD API 60 °F (15.6 °C) (A. PI gravity at 60 °F)</strong></td>
            <td colspan="2">36,3</td>
            <td colspan="3"><strong>GRAVEDAD ESPECIFICA A 60 °F (specific gravity at 60 °F)</strong></td>
            <td colspan="2">0,8433</td>
        </tr>
        <tr style="text-align: center">
            <td colspan="3"><strong>PUNTO DE INFLAMACIÓN (°C) (flash point)</strong></td>
            <td colspan="2">66</td>
            <td colspan="3"><strong>PUNTO DE FLUIDEZ (°C) (pour point)</strong></td>
            <td colspan="2">-6</td>
        </tr>
        <tr style="text-align: center">
            <td colspan="3"> <strong>VISCOSIDAD A 50° C (cSt.) (viscosity)</strong>td>
            <td colspan="2"> 38,5</td>
            <td colspan="3"> <strong>AZUFRE (%PESO) (sulphur, wt%)</strong>td>
            <td colspan="2"> 0,438</td>
        </tr>
        <tr style="text-align: center">
            <td colspan="3"> <strong>AGUA Y SEDIMENTO (% VOL) (B.S & water)</strong>td>
            <td colspan="2"> 0,005</td>
            <td colspan="3"> <strong>DENSIDAD (density)</strong></td>
            <td colspan="2"> 0,8428</td>
        </tr>
        <tr>
            <td colspan="10" style="text-align: center; font-weight:bolder" > CANTIDAD (QUANTITY)</td>
        </tr>
        <tr style="text-align: center">
            <td colspan="4">
                <strong>TONELADAS METRICAS</strong>
                {{ number_format(($guia->cantidad* 0.85/1000), 2, ',', '.') }}
            </td>
            <td colspan="3">
                <strong>LITROS BRUTOS (gross litres)</strong>
                {{ number_format(($guia->cantidad ?? 0), 2, ',', '.') }}
            </td>
            <td colspan="3">
                <strong>litros NETOS (net litres)</strong>
                {{ number_format(($guia->cantidad ?? 0)*0.998, 2, ',', '.') }}

            </td>
        </tr>
        <tr>
            <td colspan="10" style="text-align: center; font-weight:bolder"><p style="margin-top: 15px; font-style: italic; font-size: 8pt; border-bottom: 1px solid #000; padding-bottom: 10px;">
        Remarks: The fuel supplied in this delivery in conformity with regulation 14(1) or (4)A and regulation 18(1) of annex VI Marpol 73/78
         </p>
        </td>   
        </tr>
        <tr>
            <td colspan="10" style="text-align: center; font-weight:bolder">
                <br>
                <p style="margin-top: 15px; font-size: 12pt; font-weight: bold;  padding-bottom: 10px;">INTERNACIONAL</p>
                <br>
            </td>
        </tr>
        <tr>
            <td colspan="6"><strong>POR EL BUQUE (by vessel)</strong></td>
            <td colspan="4" rowspan="3" style="border-bottom: 1px">POR DISTRIBUIDORA IMPORDIESEL
                <img src="{{ asset('img/logo1.png') }}" alt="logo empresa" style="width: 90% ; margin-right: 10px;">
                <br>FIRMA (signature) 
            </td>
        </tr>
        <tr>
            <td colspan="3"  style="vertical-align: top; padding-bottom: 40px;">FIRMA (signature)</td>
            <td colspan="3" >FIRMA (signature)</td>
        </tr>
        <tr style="border-bottom: none">
            <td colspan="3" style="border-bottom: none" >
                NOMBRE (name)
            </td>
            <td colspan="3" style="border-bottom: none">
                NOMBRE (name) <br>
            </td>
        <tr style="border-top: none">
            <td colspan="3" style="border-top: none">
                <br>
                CAPITAN (master)
            </td>
            <td colspan="3" style="border-top: none">
                <br>
                PRIMER INGENIERO (chief master)</td>
            <td colspan="4">NOMBRE (name) YULIMAR CASTELLANOS</td>
        </tr>

    </table>
</div>

<div class="print-only">
    <button type="button" id="print" style="padding: 10px 20px; font-size: 14pt; cursor: pointer;">
        Imprimir Boleta / Guardar como PDF
    </button>
</div>

@push('scripts')

<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script src="{{asset('js/jquery.PrintArea.js')}}" defer></script>

@endpush
@endsection