@extends('layouts.app')

@section('title', 'Gestión Integral de Documentos')

@push('styles')
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<style>
    /* Estilos generales reusados de tus formatos originales */
    body { font-family: Arial, sans-serif; font-size: 10pt; color: #000; }
    
    /* Contenedores originales */
    .guia-container { width: 100%; max-width: 1080px; margin: 0 auto; border: 1px solid #000; padding: 10px; background: #fff; }
    .bunker-container { width: 100%; max-width: 900px; margin: 0 auto; border: 1px solid #000; padding: 15px; background: #fff; }
    .auth-container { width: 100%; max-width: 900px; margin: 0 auto; border: 1px solid #000; padding: 15px; background: #fff; }

    /* Estilos específicos Guía */
    .header-section, .logistica-section, .receptor-section { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 5px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
    .header-info { flex-basis: 60%; font-size: 11pt; }
    .header-info p { margin: 0; line-height: 1.3; }
    .header-rif { flex-basis: 35%; text-align: right; border: 1px solid #000; padding: 5px; font-size: 12pt; font-weight: bold; }
    .table-detalle { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .table-detalle th, .table-detalle td { border: 1px solid #000; padding: 6px; text-align: left; vertical-align: top; }
    .table-detalle th { background-color: #f0f0f0; font-weight: bold; }
    .table-detalle .cantidad { width: 15%; text-align: center; }
    .table-detalle .precio, .table-detalle .total { width: 15%; text-align: right; }
    .small-title { font-weight: bold; display: block; margin-top: 5px; }

    /* Estilos específicos Boleta */
    .header-bunker { border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 10px; }
    .bunker-container .table td { background-color: #FFFFFF; padding-bottom: 3px; }
    .bunker-container .table strong { font-size: 8pt; display: block; }

    /* Estilos específicos Nominación */
    .auth-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 9pt; }
    .auth-table th, .auth-table td { border: 1px solid #000; padding: 6px; text-align: left; }
    .auth-table th { background-color: #f0f0f0; font-weight: bold; }
    .comentarios-box { border: 1px solid #000; padding: 10px; min-height: 100px; margin-top: 15px; font-size: 9pt; }

    /* Pestañas */
    .nav-tabs .nav-link.active { background-color: #212529 !important; color: #fff !important; font-weight: bold; }
    .nav-tabs .nav-link { color: #495057; }

    /* Control de Impresión por Pestaña Activa */
    @media print {
        .no-print, .control-panel, .nav-tabs, .navbar, footer { display: none !important; }
        body * { visibility: hidden; }
        .tab-pane.active, .tab-pane.active * { visibility: visible; }
        .tab-pane.active { position: absolute; left: 0; top: 0; width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .guia-container, .bunker-container, .auth-container { border: none !important; box-shadow: none !important; }
    }

    .ui-autocomplete { z-index: 9999 !important; background: white; border: 1px solid #ccc; list-style: none; padding: 0; margin: 0; max-height: 200px; overflow-y: auto; }
    .ui-menu-item { padding: 8px 12px; cursor: pointer; }
    .ui-state-active { background-color: #007bff !important; color: white !important; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4 my-4">

    <!-- ENCABEZADO Y BOTONERA GENERAL -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <div>
            <h1 class="h3 m-0">Gestión de Documentos: Viaje #{{ $viaje->id }}</h1>
            <p class="text-muted small m-0">Guía de Distribución, Boleta Bunkering y Nominación en formatos originales sincronizados</p>
        </div>
        <a href="{{ route('cotizaciones-bunkering.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Listado
        </a>
    </div>

    <!-- PANEL UNIFICADO DE EDICIÓN RÁPIDA (FUERA DE IMPRESIÓN) -->
    <div class="control-panel card shadow-sm mb-4 no-print border-primary">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-edit me-1"></i> Panel de Control y Edición Rápida (Sincronización en Vivo para los 3 formatos)
        </div>
        <div class="card-body bg-light">
            <form id="guia-editor-form">
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Cliente / Razón Social</label>
                        <input type="text" id="cliente_input" class="form-control hybrid-autocomplete" 
                            data-db-field="cliente" data-live-id="#live-cliente-nombre" 
                            value="{{ $guia->cliente ?? 'N/A' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">RIF</label>
                        <input type="text" id="cliente_rif_input" class="form-control hybrid-autocomplete" 
                            data-db-field="rif" data-live-id="#live-cliente-rif" 
                            value="{{ $guia->rif ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Dirección Fiscal</label>
                        <input type="text" id="cliente_direccion_input" class="form-control hybrid-autocomplete" 
                            data-db-field="direccion" data-live-id="#live-cliente-direccion" 
                            value="{{ $guia->direccion ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Muelle</label>
                        <input type="text" id="muelle_input" class="form-control hybrid-autocomplete" 
                            data-db-field="muelle" data-live-id="#live-muelle" 
                            value="{{ $guia->muelle ?? 'MUELLE BAUXILUM' }}">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Buque (Vessel)</label>
                        <input type="text" class="form-control hybrid-autocomplete" data-db-field="buque" data-live-id="#live-buque" id="buque_input" value="{{ $guia->buqueDet->nombre ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">IMO</label>
                        <input type="text" class="form-control hybrid-autocomplete" id="imo_input" data-db-field="imo" data-live-id="#live-imo" value="{{ $guia->buqueDet->imo ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Bandera (Flag)</label>
                        <input type="text" class="form-control hybrid-autocomplete " id="bandera_input" data-db-field="bandera" data-live-id="#live-bandera" value="{{ $guia->buqueDet->bandera ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Método de Despacho</label>
                        <select id="metodo_despacho_input" class="form-select" data-db-field="metodo_despacho">
                            <option value="TTS (Truck to Ship)" {{ ($guia->metodo_despacho ?? '') == 'TTS (Truck to Ship)' ? 'selected' : '' }}>TTS (Truck to Ship)</option>
                            <option value="STS (Ship to Ship)" {{ ($guia->metodo_despacho ?? '') == 'STS (Ship to Ship)' ? 'selected' : '' }}>STS (Ship to Ship)</option>
                            <option value="ALONGSITE" {{ ($guia->metodo_despacho ?? '') == 'ALONGSITE' ? 'selected' : '' }}>ALONGSITE</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Unidad que transporta</label>
                        <input type="text" id="chuto_input" class="form-control hybrid-autocomplete" data-db-field="chuto" data-live-id="#live-chuto" value="{{ $guia->unidad ?? 'N/A' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Cisterna</label>
                        <input type="text" id="cisterna_input" class="form-control hybrid-autocomplete" data-db-field="cisterna" data-live-id="#live-cisterna" value="{{ $guia->cisterna ?? 'N/A' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Cantidad Litros</label>
                        <input type="text" id="cantidad_input" class="form-control hybrid-autocomplete" data-db-field="cantidad" data-live-id="#live-cantidad" value="{{ $totalLitrosConsolidado ?? 0 }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Cantidad TM</label>
                        <input type="text" id="cantidad_tm_input" class="form-control hybrid-autocomplete" data-db-field="cantidad_tm" value="{{ number_format($totalTmConsolidado, 2, ',', '.') }}">
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- PESTAÑAS DE NAVEGACIÓN -->
    <ul class="nav nav-tabs mb-3 no-print" id="documentTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="guia-tab" data-bs-toggle="tab" data-bs-target="#guia-content" type="button" role="tab">
                <i class="fas fa-file-alt me-1"></i> Guía de Distribución (Original)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="boleta-tab" data-bs-toggle="tab" data-bs-target="#boleta-content" type="button" role="tab">
                <i class="fas fa-gas-pump me-1"></i> Boleta de Bunkering (Original)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="nominacion-tab" data-bs-toggle="tab" data-bs-target="#nominacion-content" type="button" role="tab">
                <i class="fas fa-file-invoice me-1"></i> Nominación / Autorización (Original)
            </button>
        </li>
    </ul>

    <!-- CONTENIDO DE LAS PESTAÑAS -->
    <div class="tab-content" id="documentTabsContent">

        <!-- ================= PESTAÑA 1: GUÍA DE DISTRIBUCIÓN ================= -->
        <div class="tab-pane fade show active" id="guia-content" role="tabpanel">
            <div class="d-flex justify-content-end mb-2 no-print">
                <button onclick="window.print()" class="btn btn-success btn-sm">
                    <i class="fas fa-print"></i> Imprimir Guía Actual
                </button>
            </div>
            
            <div class="guia-container">       
                <div class="header-section row">
                    <div class="header-info col-4">
                        <img src="{{ asset('img/logo1.png') }}" alt="logo empresa" style="width: 250px">
                        <p>Av. Principal de Boleíta entre Av. Francisco de Miranda <br>y la 1ra transversal, Qta Adela Nro S/N Urb Boleita, Caracas</p>
                        <p>Petare Miranda, Zona Postal 1079 Telf: 0414-3779488</p>
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
                        <p><strong>Teléfono:</strong> {{ $viaje->despachos->first()->cliente->telefono ?? 'N/A' }}</p>
                        <p><strong>Email:</strong> {{ $viaje->despachos->first()->cliente->email ?? 'N/A' }}</p>
                    </div>
                </div>
                
                <table class="table-detalle">
                    <thead>
                        <tr>
                            <th class="cantidad">CANTIDAD </th>
                            <th>CONCEPTO/DESCRIPCIÓN</th>
                            <th class="precio">PRECIO UNIT.</th>
                            <th class="total">TOTAL</th>
                            <th rowspan="12" width="20%" class="sello text-center align-middle">SELLO</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($viaje->despachos as $despacho)
                            <tr>
                                <td class="cantidad">{{ number_format($despacho->litros ?? 0, 2, ',', '.') }}</td>
                                <td>{{ $despacho->concepto ?? 'MARINE GASOIL (MGO)' }} </td>
                                <td class="precio">{{ number_format($despacho->precio_unitario ?? 0, 2, ',', '.') }}</td>
                                <td class="total">{{ number_format($despacho->total ?? ($despacho->litros * ($despacho->precio_unitario ?? 0)), 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td></td>
                            <td><span class="small-title">Placa Chuto:</span> <span id="live-chuto">{{ $viaje->vehiculo->placa ?? 'N/A' }}</span></td>
                            <td></td><td></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td><span class="small-title">Placa Cisterna:</span> <span id="live-cisterna">{{ $viaje->cisterna->placa ?? 'N/A' }}</span></td>
                            <td></td><td></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td><span class="small-title">Ruta:</span> <span id="live-ruta">{{ $guia->ruta ?? 'Boleíta Norte Caracas Puerto Ordaz Edo. Bolivar' }}</span></td>
                            <td></td><td></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td><span class="small-title">Destino:</span> <span id="live-muelle">{{ $guia->ruta ?? 'Muelle de SIDOR' }}</span></td>
                            <td></td><td></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td><span class="small-title">BUQUE/EMBARCACIÓN: </span> <span id="live-buque">{{ $guia->buqueDet->nombre ?? 'N/A' }}</span> <span id="live-imo" data-db-field="imo"></span> <span id="live-bandera" data-db-field="bandera"></span></td>
                            <td></td><td></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td><span class="small-title">Nro precintos: </span> <span id="live-precintos">{{ $viaje->precintos ?? 'N/A' }}</span></td>
                            <td></td><td></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td><span class="small-title">CONDUCTOR: </span> <span id="live-conductor">{{ $viaje->chofer->persona->nombre ?? 'N/A' }}</span></td>
                            <td></td><td></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td><span class="small-title">CEDULA: </span> <span id="live-cedula">{{ $viaje->chofer->persona->cedula ?? 'N/A' }}</span></td>
                            <td></td><td></td>
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
        </div>

        <!-- ================= PESTAÑA 2: BOLETA DE BUNKERING ================= -->
        <div class="tab-pane fade" id="boleta-content" role="tabpanel">
            <div class="d-flex justify-content-end mb-2 no-print">
                <button onclick="window.print()" class="btn btn-success btn-sm">
                    <i class="fas fa-print"></i> Imprimir Boleta Actual
                </button>
            </div>

            <div class="bunker-container">
                <div class="header-bunker mb-3" style="display: block; height: 100px;">
                    <img src="{{ asset('img/logo1.png') }}" alt="logo empresa" style="width: 250px; float: left; margin-right: 10px;">
                    <p class="mb-0" style="float: right; text-align: right; vertical-align: middle"><strong>Boleta de combustible marino entregado</strong> <br>(marine bunker delivery receipt)</p>
                </div>
                <table class="table table-bordered border-dark mb-3" style="font-size: 10pt">
                    <tr>
                        <td colspan="6">
                            <strong>CLIENTE (client)</strong>
                            <span id="live-nominacion-cliente">
                                {{ $guia->cliente ?? 'Tepuy Marina' }}
                            </span>
                        </td>
                        <td colspan="3">
                            <strong>NOMINACION</strong>
                            <span id="live-nominacion-numero-guia">
                                {{ $guia->numero_guia ?? 'N/A'}}
                            </span>
                        </td>
                        <td>
                            <strong>FECHA (date)</strong>
                            <span id="live-nominacion-fecha">
                                {{ \Carbon\Carbon::parse($guia->created_at)->format('d.m.Y') }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="4">
                            <strong>BUQUE (vessel)</strong>
                            <span id="live-nominacion-buque">
                                {{ $guia->buqueDet->nombre ?? 'GAMBOA' }}
                            </span>
                        </td>
                        <td colspan="2">
                            <strong>IMO</strong>
                            <span id="live-boleta-imo">
                                {{ $guia->buqueDet->imo ?? '####' }}
                            </span>
                        </td>
                        <td colspan="2">
                            <strong>BANDERA (flag)</strong>
                            <span id="live-boleta-bandera">
                                {{ $guia->buqueDet->bandera ?? '#########'}}
                            </span>
                        </td>
                        <td colspan="2">
                            <strong>PUERTO (port)</strong>
                            <span id="live-boleta-puerto">
                                {{ $guia->muelle ?? 'MUELLE BAUXILUM' }}
                            </span>
                        </td>
                    </tr>
                    <tr style="font-size: 8pt">
                        <td colspan="3">METODO DE ENTREGA (delivery method)</td>
                        <td colspan="7">
                            <div class="row">
                                <div class="col-4"><span id="live-metodo-despacho-boleta">{{ $guia->metodo_despacho ?? 'TTS (Truck to Ship)' }}</span></div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="8"></td>
                        <td rowspan="2"><strong>TEMP °C</strong></td>
                        <td rowspan="2"><strong>FACTOR CORREC (corr. Factor)</strong></td>
                    </tr>
                    <tr style="text-align: center">
                        <td rowspan="3" colspan="2"><strong>PRODUCTO (product)</strong> <br>M.G.O.</td>
                        <td colspan="3"><strong>INICIO (start)</strong></td>
                        <td colspan="3"><strong>FINAL (finish)</strong></td>
                    </tr>
                    <tr style="text-align: center">
                        <td><strong>FECHA</strong></td>
                        <td><strong>HORA</strong></td>
                        <td><strong>LITROS</strong></td>
                        <td><strong>FECHA</strong></td>
                        <td><strong>HORA</strong></td>
                        <td><strong>LITROS</strong></td>
                        <td rowspan="2">80 F <br> 28</td>
                        <td rowspan="2"><br>0,998</td>
                    </tr>
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($guia->created_at)->format('d.m.Y') }}</td>
                        <td></td><td></td>
                        <td>{{ \Carbon\Carbon::parse($guia->created_at)->format('d.m.Y') }}</td>
                        <td></td><td></td>
                    </tr>
                    <tr><td colspan="10" style="text-align: center; font-weight:bolder">CALIDAD (QUALITY)</td></tr>
                    <tr style="text-align: center">
                        <td colspan="3"><strong>GRAVEDAD API 60 °F (15.6 °C)</strong></td>
                        <td colspan="2">36,3</td>
                        <td colspan="3"><strong>GRAVEDAD ESPECIFICA A 60 °F</strong></td>
                        <td colspan="2">0,8433</td>
                    </tr>
                    <tr style="text-align: center">
                        <td colspan="3"><strong>PUNTO DE INFLAMACIÓN (°C)</strong></td>
                        <td colspan="2">66</td>
                        <td colspan="3"><strong>PUNTO DE FLUIDEZ (°C)</strong></td>
                        <td colspan="2">-6</td>
                    </tr>
                    <tr style="text-align: center">
                        <td colspan="3"><strong>VISCOSIDAD A 50° C (cSt.)</strong></td>
                        <td colspan="2">38,5</td>
                        <td colspan="3"><strong>AZUFRE (%PESO)</strong></td>
                        <td colspan="2">0,438</td>
                    </tr>
                    <tr style="text-align: center">
                        <td colspan="3"><strong>AGUA Y SEDIMENTO (% VOL)</strong></td>
                        <td colspan="2">0,005</td>
                        <td colspan="3"><strong>DENSIDAD</strong></td>
                        <td colspan="2">0,8428</td>
                    </tr>
                    <tr>
                        <td colspan="10" style="text-align: center; font-weight:bolder">CANTIDAD (QUANTITY)</td>
                    </tr>
                    <tr style="text-align: center">
                        <td colspan="4">
                            <strong>TONELADAS METRICAS</strong><br>
                            <span id="live-total-tm-boleta">{{ number_format($totalTmConsolidado, 2, ',', '.') }}</span> MT
                        </td>
                        <td colspan="3">
                            <strong>LITROS BRUTOS</strong><br><strong>(gross litres)</strong>
                            <span id="live-total-litros-boleta">{{ number_format($totalLitrosConsolidado, 2, ',', '.') }}</span> Lts
                        </td>
                        <td colspan="3">
                            <strong>LITROS NETOS</strong><br><strong>(net litres)</strong>
                            <span id="live-total-neto-boleta">{{ number_format($totalLitrosConsolidado * 0.998, 2, ',', '.') }}</span> Lts
                        </td>
                    </tr>
                    <tr>
                        <td colspan="10" style="text-align: center; font-weight:bolder">
                            <p style="margin-top: 15px; font-style: italic; font-size: 8pt; border-bottom: 1px solid #000; padding-bottom: 10px;">
                                Remarks: The fuel supplied in this delivery in conformity with regulation 14(1) or (4)A and regulation 18(1) of annex VI Marpol 73/78
                            </p>
                        </td>   
                    </tr>
                    <tr>
                        <td colspan="10" style="text-align: center; font-weight:bolder">
                            <br><p style="margin-top: 5px; font-size: 12pt; font-weight: bold;">INTERNACIONAL</p><br>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="6"><strong>POR EL BUQUE (by vessel)</strong></td>
                        <td colspan="4" rowspan="3">POR DISTRIBUIDORA IMPORDIESEL
                            <img src="{{ asset('img/logo1.png') }}" alt="logo empresa" style="width: 90%; margin-right: 10px;">
                            <br>FIRMA (signature) 
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" style="vertical-align: top; padding-bottom: 40px;">FIRMA (signature)</td>
                        <td colspan="3">FIRMA (signature)</td>
                    </tr>
                    <tr>
                        <td colspan="3">NOMBRE (name)</td>
                        <td colspan="3">NOMBRE (name)</td>
                    </tr>
                    <tr>
                        <td colspan="3"><br>CAPITAN (master)</td>
                        <td colspan="3"><br>PRIMER INGENIERO (chief master)</td>
                        <td colspan="4">NOMBRE (name)</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- ================= PESTAÑA 3: NOMINACIÓN ================= -->
        <div class="tab-pane fade" id="nominacion-content" role="tabpanel">
            <div class="d-flex justify-content-end mb-2 no-print">
                <button onclick="window.print()" class="btn btn-success btn-sm">
                    <i class="fas fa-print"></i> Imprimir Nominación Actual
                </button>
            </div>

            <div class="auth-container">
                <table class="table table-bordered" style="font-size: 10pt; width: 100%; border:solid 1px black;">
                    <tr>
                        <td colspan="4" style="text-align: center; font-size: 14pt; font-weight: bold;">
                            <img src="{{ asset('img/logo1.png') }}" alt="logo empresa" style="width: 250px; float: left; margin-right: 10px;">
                            <div style="float: right; text-align: right; vertical-align: middle">AUTORIZACIÓN / NOMINACIÓN DE COMBUSTIBLES Y LUBRICANTES</div>
                        </td>
                    </tr>
                    <tr>
                        <td width="20%"></td><td width="20%"></td><td width="30%"></td><td width="30%"></td>
                    </tr>
                    <tr style="border:solid 1px black;">
                        <td colspan="3" style="text-align:left; font-size: 12pt; font-weight: bold;">CLIENTE</td>
                        <td style="text-align: left; font-size: 10pt;">INFORMACION</td>
                    </tr>
                    <tr style="border:solid 1px black;">
                        <td colspan="3" style="text-align:left; font-size: 10pt;">
                            Facturar a: {{ $guia->cliente ?? 'Distribuidora Impordiesel C.A.' }} <br>
                            Direccion: {{ $guia->direccion ?? 'CR UD 524 LOCAL PARCELA...' }} <br>
                            Contacto: {{ $guia->contacto ?? 'Antonio Bertolo' }} <br>
                            Correo: {{ $guia->email ?? 'N/A' }} <br>
                            Telefono: {{ $guia->telefono ?? 'N/A' }}
                        </td>
                        <td style="text-align: left; font-size: 10pt; border:solid 1px black;">
                            Etiqueta <br>
                            N° Pedido: {{ $guia->numero_guia ?? 'N/A' }} <br>
                            Fecha Doc: {{ \Carbon\Carbon::parse($guia->created_at)->format('d/m/Y') }} <br>
                            Moneda: USD <br>
                            Orden de Compra: {{ $guia->cliente ?? 'N/A' }} <br>
                            Fecha O.C.: {{ \Carbon\Carbon::parse($guia->updated_at)->format('d/m/Y') }} <br>
                        </td>
                    </tr>
                    <tr style="border:solid 1px black;">
                        <td colspan="4"><br></td>
                    </tr>
                    <tr style="border:solid 1px black;">
                        <td colspan="3" style="text-align:left; font-size: 12pt;">
                            <span style="font-weight: bold;">Cuenta:</span> DISTRIBUIDORA DE COMBUSTIBLE IMPORDIESEL <br>
                            <span style="font-weight: bold;">Vendedor:</span> DISTRIBUIDORA DE COMBUSTIBLE IMPORDIESEL <br>
                            <span style="font-weight: bold;">Centro:</span> Planta de Dist. Boleíta <br>
                            <span style="font-weight: bold;">Terminos de Pago:</span> PREPAGADO
                        </td>
                        <td style="text-align: left; font-size: 10pt; border:solid 1px black;">
                            <span style="font-weight: bold;">BUQUE :</span> <span id="live-nominacion-buque">{{ $guia->buqueDet->nombre ?? 'GAMBOA' }} </span> <br>
                            <span style="font-weight: bold;">Pto. Entrega:</span> <span id="live-nominacion-puerto">{{ $guia->muelle ?? 'MUELLE BAUXILUM' }}</span> <br>
                            <span style="font-weight: bold;">Fecha de Entrega:</span> <span id="live-nominacion-fecha-entrega">{{ \Carbon\Carbon::parse($guia->updated_at)->format('d/m/Y') }}</span> <br>
                            <span style="font-weight: bold;">Método de Entrega:</span> <span id="live-metodo-despacho-nominacion">{{ $guia->metodo_despacho ?? 'TTS (Truck to Ship)' }}</span>
                        </td>
                    </tr>
                    <tr style="text-align: center; font-weight: bold; border:solid 1px black;">
                        <td>Item</td><td>Codigo</td><td>Material</td><td>Cantidad</td>
                    </tr>
                    <tr style="text-align: center; border:solid 1px black;">
                        <td>1</td><td>401</td><td>MARINE GAS OIL (MGO)</td>
                        <td>
                            <strong id="live-total-tm-nominacion">{{ number_format($totalTmConsolidado, 2, ',', '.') }} TM</strong>
                            (<span id="live-total-litros-nominacion">{{ number_format($totalLitrosConsolidado, 0, ',', '.') }}</span> LTS)
                        </td>
                    </tr>
                    <tr style="border:solid 1px black;">
                        <td colspan="4" style="text-align: center">
                            <h5 style="margin-top: 15px; margin-bottom: 5px;">COMENTARIOS</h5>
                            <div class="comentarios-box">
                                <h3>PRODUCTO PARA SER DEPOSITADO EN LOS TANQUES DE SERVICIOS DE LA EMBARCACIÓN PARA CONSUMO PROPIO.</h3>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script>
   $(document).ready(function() {
    const viajeId = {{ $viaje->id }};

    // Sincronización en vivo y base de datos vía AJAX para los 3 formatos simultáneamente
    $('.hybrid-autocomplete').on('input', function() {
        const dbField = $(this).data('db-field');
        const currentVal = $(this).val();
        const targetSelectors = {
            'cliente': '#live-cliente-nombre, #live-boleta-cliente, #live-nominacion-cliente, #live-nominacion-cliente-oc',
            'rif': '#live-cliente-rif',
            'direccion': '#live-cliente-direccion, #live-nominacion-direccion',
            'buque': '#live-buque, #live-boleta-buque, #live-nominacion-buque',
            'imo': '#live-imo, #live-boleta-imo',
            'bandera': '#live-bandera, #live-boleta-bandera',
            'chuto': '#live-chuto',
            'cisterna': '#live-cisterna',
            'muelle': '#live-muelle, #live-boleta-puerto, #live-nominacion-puerto',
            'metodo_despacho': '#live-metodo-despacho-guia, #live-metodo-despacho-boleta, #live-metodo-despacho-nominacion'
        };

        // 2. Actualización instantánea usando el mapa
        if (targetSelectors[dbField]) {
            $(targetSelectors[dbField]).text(currentVal);
        }

        // Si se modifica la cantidad total en litros
        if (dbField === 'cantidad') {
            const litros = parseFloat(currentVal.replace(/\./g, '').replace(',', '.')) || 0;
            const tmCalculado = (litros * 0.85) / 1000;
            const netoCalculado = litros * 0.998;

            const fmt = new Intl.NumberFormat('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const fmtInt = new Intl.NumberFormat('es-VE', { minimumFractionDigits: 0, maximumFractionDigits: 0 });

            // Actualizar input TM
            $('#cantidad_tm_input').val(fmt.format(tmCalculado));

            // Actualizar valores consolidados en Boleta y Nominación
            $('#live-total-litros-boleta').text(fmt.format(litros));
            $('#live-total-tm-boleta').text(fmt.format(tmCalculado));
            $('#live-total-neto-boleta').text(fmt.format(netoCalculado));

            $('#live-total-litros-nominacion').text(fmtInt.format(litros) + ' LTS');
            $('#live-total-tm-nominacion').text(fmt.format(tmCalculado));
        }
    });

    $('#metodo_despacho_input').on('change', function() {
        const metodo = $(this).val();
        
        // Actualizar en tiempo real en los 3 formatos
        //$('#live-metodo-despacho-guia').text(metodo);
        $('#live-metodo-despacho-boleta').text(metodo);
        $('#live-metodo-despacho-nominacion').text(metodo);

        // Guardar en base de datos vía AJAX
        $.ajax({
            url: `/api/viajes/${viajeId}/update-guia-data`,
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { field: 'metodo_despacho', value: metodo },
            success: function() { console.log("Método de despacho sincronizado con BD"); }
        });
    });

    $('.hybrid-autocomplete').on('blur', function() {
        const dbField = $(this).data('db-field');
        const finalVal = $(this).val();
        if(finalVal.trim() !== "") {
            $.ajax({
                url: `/api/viajes/${viajeId}/update-guia-data`,
                method: 'PUT',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: { field: dbField, value: finalVal },
                success: function() { console.log("Sincronizado con BD correctamente"); }
            });
        }
    });
});
</script>
@endpush