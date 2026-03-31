@extends('layouts.app')

@section('title', 'Hoja Técnica - Orden #' . $orden->nro_orden)

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    .table-success-light {
        background-color: rgba(40, 167, 69, 0.08) !important;
        transition: all 0.3s ease;
    }
    
    .x-small {
        font-size: 0.65rem !important;
        letter-spacing: 0.5px;
        padding: 0.35em 0.6em !important;
    }

    .text-done {
        text-decoration: line-through;
        color: #6c757d !important;
    }

    .table .form-control-sm {
        padding: 0.2rem 0.4rem;
        font-size: 0.85rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">
    {{-- Alerta de Habilitación Especial --}}
@if($orden->estatus == 2 && $orden->vehiculoBelong->estatus == 1)
    <div class="alert alert-warning border-orange shadow-sm d-flex align-items-center animate__animated animate__fadeIn" role="alert">
        <i class="fas fa-exclamation-triangle fs-4 me-3 text-orange"></i>
        <div>
            <strong class="text-uppercase">Unidad en Habilitación Especial:</strong> 
            Esta unidad ha sido marcada como <b>DISPONIBLE</b> para operaciones, pero mantiene esta Orden de Trabajo abierta por tareas no críticas.
            <br><small class="text-muted">Motivo: {{ $orden->ultimo_motivo_habilitacion ?? 'No especificado' }}</small>
        </div>
    </div>
@endif
    {{-- BARRA DE HERRAMIENTAS DINÁMICA --}}
    <div class="d-flex justify-content-between align-items-center mb-4 no-print bg-white p-3 rounded shadow-sm">
        <div>
            <a href="{{ route('ordenes.list') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <button onclick="window.print();" class="btn btn-sm btn-dark ms-2">
                <i class="fas fa-print"></i> Imprimir
            </button>
        </div>
        <div class="d-flex gap-2">
            @if($orden->estatus == 'ABIERTA' || $orden->estatus == 2 || $orden->estatus == 3)
                <a href="{{ route('ordenes.edit', $orden->id) }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-edit"></i> Editar
                </a>

                @if($orden->estatus == 2)
                    <button id="cerrar-orden" class="btn btn-sm btn-success fw-bold">
                        <i class="fas fa-check-double"></i> CERRAR
                    </button>
                    <button id="habilitar-unidad" class="btn btn-sm btn-outline-warning fw-bold">
                        <i class="fas fa-truck-loading"></i> HABILITAR UNIDAD
                    </button>
                @endif
                <button id="anular-orden" class="btn btn-sm btn-danger">
                    <i class="fas fa-times-circle"></i> CANCELAR
                </button>
            @endif
            @if($orden->estatus == 'CERRADA' || $orden->estatus == 4 || $orden->estatus == 1 || $orden->estatus == 3)
                @php($texto= $orden->estatus == 3? 'INICIAR' : 'REACTIVAR')
                <button id="reactivar-orden" class="btn btn-sm btn-warning fw-bold">
                    <i class="fas fa-undo"></i> {{ $texto }} ORDEN
                </button>
            @endif
            @if($orden->estatus == 'ANULADA' || $orden->estatus == 4)
                <button id="eliminar-orden" class="btn btn-sm btn-outline-danger fw-bold delete-item" data-type="orden" data-id="{{ $orden->id }}"
                    <i class="fas fa-trash"></i> ELIMINAR DEFINITIVAMENTE
                </button>
            @endif
        </div>
    </div>

    {{-- ENCABEZADO DE ORDEN --}}
    <div class="card card-step border-orange shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-8">
                    <h2 class="fw-bold text-uppercase mb-0">Hoja Técnica de Servicio</h2>
                    <p class="text-muted small mb-0">Impordiesel - Gestión de Mantenimiento de Flota</p>
                </div>
                <div class="col-4 text-end">
                    <div class="bg-corporate text-white p-2 rounded">
                        <span class="d-block small text-uppercase">Orden de Trabajo</span>
                        <span class="fs-4 fw-bold">#{{ $orden->nro_orden }}</span>
                    </div>
                    <div class="mt-1">
                        <span class="badge bg-{{ $estatusData->css }}">
                            ESTADO: <i class="fa {{ $estatusData->icon_orden }}"></i> {{ $estatusData->orden ?? 'Desconocido' }}
                        </span>
                    </div>
                </div>
            </div>

            <hr>
            
            <div class="row mt-3">
                <div class="col-md-4">
                    <label class="form-label-corp d-block text-uppercase small fw-bold">Vehículo / Unidad</label>
                    <span class="fw-bold fs-5 text-orange">{{ $orden->vehiculoBelong->flota ?? 'N/A' }}</span> <br>
                    <span class="text-muted small">Placa: {{ $orden->vehiculoBelong->placa ?? 'N/A' }}</span>
                </div>
                <div class="col-md-2">
                    <label class="form-label-corp d-block text-uppercase small fw-bold">Kilometraje</label>
                    <span class="fw-bold">{{ number_format($orden->kilometraje) }} KM</span>
                </div>
                <div class="col-md-3">
                    <label class="form-label-corp d-block text-uppercase small fw-bold">Tipo de Servicio</label>
                    <span class="badge bg-secondary">{{ $orden->tipo }}</span>
                </div>
                <div class="col-md-3 text-end">
                    <label class="form-label-corp d-block text-uppercase small fw-bold">Fecha Apertura</label>
                    <span class="fw-bold">{{ \Carbon\Carbon::parse($orden->fecha_in)->format('d/m/Y H:i') }}</span>
                </div>
            </div>
        </div>
        
    </div>
    <div class="card card-step shadow-sm">
                <div class="card-body">
                    <label class="form-label-corp fw-bold text-uppercase small">Observaciones Técnicas:</label>
                    <p class="mt-2 p-3 bg-light rounded border" style="min-height: 100px;">
                        {!! $orden->descripcion ?: 'Sin observaciones adicionales.' !!}
                    </p>
                </div>
            </div>
    <div class="row">
        {{-- COLUMNA TRABAJOS --}}
        <div class="col-md-6">
            <div class="card card-step shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-inline">
                    <h6 class="m-0 fw-bold text-uppercase small d-inline"><i class="fas fa-tools text-orange me-2"></i>Trabajos Ejecutados</h6>
                    <button class="btn btn-sm btn-corporate ms-2 shadow-sm d-inline float-end" data-bs-toggle="modal" data-bs-target="#modalTrabajo">
                        <i class="fas fa-plus"></i> Agregar
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light small text-uppercase">
                            <tr>
                                <th class="ps-3">Servicio</th>
                                <th>Mecánicos</th>
                                <th>Mano de Obra</th>
                                <th class="text-end pe-3">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php($totalManoObra = 0)
                            @forelse($trabajos as $trabajo)

                            @php($totalManoObra += $trabajo->costo)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold">{{ $trabajo->descripcion }}</div>
                                    <div class="text-muted x-small text-uppercase">{{ $trabajo->tempario->categoria->categoria ?? 'General' }}</div>
                                    @if(!is_null($trabajo->fecha_fin))
                                        <span class="badge bg-success x-small">
                                            <i class="fas fa-clock"></i> Terminado en: {{ $trabajo->tiempo_ejecucion }}
                                        </span>
                                    @else
                                        <span class="badge bg-warning text-dark x-small animate__animated animate__flash animate__infinite">
                                            En proceso...
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @foreach($trabajo->mecanicos_lista as $mec)
                                        <span class="badge border text-dark fw-normal bg-light">{{ $mec->persona->nombre ?? 'N/A' }}</span>
                                    @endforeach
                                </td>
                                <td>${{ number_format($trabajo->costo, 2) }}</td>
                                <td class="text-end pe-3 no-print">
                                    @if($orden->estatus == 2 || $orden->estatus == 'ABIERTA')
                                            @if(is_null($trabajo->fecha_fin))
                                                <button class="btn btn-sm btn-outline-success finish-work" data-id="{{ $trabajo->id_trabajo }}" title="Finalizar Trabajo">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                            @endif
                                            
                                        <button class="btn btn-sm btn-link text-danger p-0 delete-item" data-type="trabajo" data-id="{{ $trabajo->id_trabajo }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-4 text-muted">No hay trabajos registrados.</td></tr>
                            @endforelse
                            @if(count($trabajos) > 0)  
                            <tr class="total-row">
                                <td colspan="2" class="text-end ps-3">TOTAL SERVICIOS</td>
                                <td colspan="2" class="text-end pe-3 text-orange">${{ number_format($totalManoObra, 2) }}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- COLUMNA DE TRABAJOS EXT --}}
            <div class="card card-step shadow-sm mb-4">
                    <div class="card-header bg-white py-3 d-inline">
                        <h6 class="m-0 fw-bold text-uppercase small d-inline"><i class="fas fa-shredder text-orange me-2"></i>Trabajos Externos</h6>
                        <button type="button" class="btn btn-sm btn-corporate ms-2 shadow-sm d-inline float-end" data-bs-toggle="modal" data-bs-target="#modalTrabajoExterno">
                            <i class="fa-solid fa-plus me-1"></i> agregar
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="x-small border-0">FECHA</th>
                                    <th class="x-small border-0">PROVEEDOR</th>
                                    <th class="x-small border-0">DESCRIPCIÓN DEL TRABAJO</th>
                                    <th class="x-small border-0 text-end">COSTO FACTURADO</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php($totalExterno = 0)
                                @forelse($orden->trabajosExternos as $trabajo)
                                    @php($totalExterno += $trabajo->costo)
                                    <tr>
                                        <td class="small text-muted">{{ \Carbon\Carbon::parse($trabajo->fecha)->format('d/m/Y') }}</td>
                                        <td class="small fw-bold text-dark">{{ $trabajo->proveedor->nombre ?? 'N/A' }}</td>
                                        <td class="small text-secondary">{{ $trabajo->descripcion }}</td>
                                        <td class="small fw-bold text-end text-primary font-monospace">${{ number_format($trabajo->costo, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted small italic">
                                            <i class="bi bi-info-circle me-1"></i> No se han registrado servicios externos para esta orden.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if($totalExterno > 0)
                            <tfoot class="bg-light-primary">
                                <tr>
                                    <td colspan="3" class="text-end fw-bold x-small text-uppercase">Subtotal Servicios Externos:</td>
                                    <td class="text-end fw-bold text-primary font-monospace">${{ number_format($totalExterno, 2) }}</td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
            </div>
            
            
        </div>

        {{-- COLUMNA INSUMOS --}}
        <div class="col-md-6">
            <div class="card card-step shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-uppercase small d-inline"><i class="fas fa-box-open text-orange me-2"></i>Insumos y Repuestos</h6>
                    <button class="btn btn-sm btn-info d-inline float-end text-white" data-bs-toggle="modal" data-bs-target="#modalInsumo">
                        <i class="fas fa-box"></i> + Insumo
                    </button>
                    <button type="button" class="btn btn-sm d-inline float-end btn-corporate ms-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#manualSupplyModal">
                        <i class="fa-solid fa-cart-plus me-1"></i> Solicitar a Compras
                    </button>
                    
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="selected-supplies-table">
                        <thead class="bg-light">
                            <tr class="small text-uppercase">
                                <th width="80" class="text-center">Cant.</th>
                                <th>Descripción</th>
                                <th width="120" class="text-end">P. Unit</th>
                                <th width="120" class="text-end">Subtotal</th>
                                <th width="100" class="text-center no-print">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php($totalGeneral = 0)
                            @php($hasItems = false)

                            {{-- --- BLOQUE 1: SUMINISTROS DE INVENTARIO --- --}}
                            @foreach($suministros as $item)
                                @php($hasItems = true)
                                @php($subtotal = $item->cantidad * $item->precio_unitario)
                                @php($totalGeneral += $subtotal)
                                @php($isRecibido = $item->estatus == 1)

                                <tr class="small {{ $isRecibido ? 'table-success-light' : '' }}">
                                    <td class="text-center fw-bold">{{ $item->cantidad }}</td>
                                    <td>
                                        <div class="fw-bold text-orange">Inventario</div>
                                        {{ $item->inventario->descripcion }}
                                        @if($isRecibido)
                                            <span class="badge bg-success x-small ms-2"><i class="fas fa-check"></i> RECIBIDO</span>
                                        @endif
                                    </td>
                                    <td class="text-end text-muted">${{ number_format($item->precio_unitario, 2) }}</td>
                                    <td class="text-end fw-bold">${{ number_format($subtotal, 2) }}</td>
                                    <td class="text-center no-print">
                                        @if(!$isRecibido && ($orden->estatus == 2 || $orden->estatus == 'ABIERTA'))
                                            <button type="button" class="btn btn-sm btn-outline-success mark-recibido" 
                                                    data-id="{{ $item->id_inventario_suministro }}" data-type="supplies">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-link text-danger p-0 delete-item" data-type="insumo" data-id="{{ $trabajo->id_trabajo }}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @else
                                            <i class="fas fa-check-circle text-success fs-5"></i>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach

                            {{-- --- BLOQUE 2: REQUERIMIENTOS / COMPRAS --- --}}
                            @foreach ($requerimientos as $req)
                                @foreach ($req->detalles as $detalle)
                                    @php($hasItems = true)
                                    @php($subtotalDetalle = $detalle->cantidad_solicitada * ($detalle->costo_unitario_aprobado ?? 0))
                                    @php($totalGeneral += $subtotalDetalle)
                                    {{-- Asumiendo que el detalle tiene un estatus de recepción, si no, usa el del requerimiento --}}
                                    @php($isRecibidoReq = $detalle->estatus == 1 || $req->estatus == 'RECIBIDO')
                                    @php($isSolicitadoReq = $detalle->estatus == 2 || $req->estatus == 'SOLICITADO')
                                    @php($isAprobadoReq = $detalle->estatus == 3 || $req->estatus == 'APROBADO')

                                    <tr class="small {{ $isRecibidoReq ? 'table-success-light' : ($isAprobadoReq ? 'table-info-light' : '') }}">
                                        <td class="text-center fw-bold">{{ $detalle->cantidad_solicitada }}</td>
                                        <td>
                                            <div class="fw-bold text-info">Req: #{{ $req->nro_requerimiento }}</div>
                                            {{ $detalle->descripcion }}
                                            
                                           
                                        </td>
                                        <td class="text-end text-muted">${{ number_format($detalle->costo_unitario_aprobado ?? 0, 2) }}</td>
                                        <td class="text-end fw-bold">${{ number_format($subtotalDetalle, 2) }}</td>
                                        
                                        <td class="text-center no-print">
                                            @if($isRecibidoReq)
                                                {{-- ESTADO 1: Ya recibido --}}
                                                <i class="fas fa-check-circle text-success fs-5" title="Recibido"></i>
                                            
                                            @elseif($isAprobadoReq)
                                                {{-- ESTADO 3: Aprobado (Listo para marcar como recibido) --}}
                                                <button type="button" class="btn btn-sm btn-outline-success mark-recibido" 
                                                        data-id="{{ $detalle->id }}" data-type="compras" title="Marcar como Recibido">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-sm btn-link text-danger p-0 delete-item" data-type="compra" data-id="{{ $detalle->id }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                
                                            @elseif($isSolicitadoReq)
                                                {{-- ESTADO 2: Solo solicitado (En espera de aprobación) --}}
                                                <i class="fas fa-clock text-warning fs-5" title="Pendiente de aprobación/compra"></i>
                                                <button class="btn btn-sm btn-link text-danger p-0 delete-item" data-type="compra" data-id="{{ $detalle->id }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @else
                                                {{-- Cualquier otro estado --}}

                                                <span class="text-muted small">Pte.</span>
                                                <button class="btn btn-sm btn-link text-danger p-0 delete-item" data-type="compra" data-id="{{ $detalle->id }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                             {{-- Badge dinámico de estado --}}
                                            {{-- @if($isRecibidoReq)
                                                <span class="badge bg-success x-small ms-2"><i class="fas fa-check"></i> RECIBIDO</span>
                                            @elseif($isSolicitadoReq)
                                                <span class="badge bg-warning text-dark x-small ms-2"><i class="fas fa-clock"></i> SOLICITADO</span>
                                            @elseif($isAprobadoReq)
                                                <span class="badge bg-info x-small ms-2"><i class="fas fa-thumbs-up"></i> APROBADO</span>
                                            @endif --}}
                                        </td> 
                                    </tr>
                                @endforeach
                            @endforeach

                            {{-- --- MENSAJE DE TABLA VACÍA --- --}}
                            @if(!$hasItems)
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="fas fa-box-open d-block mb-2 fs-4"></i>
                                        Sin insumos ni compras asignadas a esta orden.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                        <tfoot>
                            <tr class="bg-light">
                                <td colspan="3" class="text-end fw-bold text-uppercase">Total General (Insumos + Compras)</td>
                                <td class="text-end pe-3 fw-bold text-orange fs-5">${{ number_format($totalGeneral, 2) }}</td>
                                <td class="no-print"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
                      

            <div class="card bg-corporate text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2 small">
                        <span>SUBTOTAL SERVICIOS:</span>
                        <span>${{ number_format($totalManoObra, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span>SUBTOTAL TRABAJOS EXTERNOS:</span>
                        <span>${{ number_format($totalExterno, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span>SUBTOTAL REPUESTOS:</span>
                        <span>${{ number_format($totalGeneral, 2) }}</span>
                    </div>
                    <hr class="bg-white my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-uppercase">TOTAL GENERAL:</span>
                        <span class="fs-3 fw-bold text-orange">${{ number_format($totalManoObra + $totalExterno + $totalGeneral, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- BLOQUE DE EVIDENCIA FOTOGRÁFICA --}}
<div class="card card-step shadow-sm mt-4 no-print">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 fw-bold text-uppercase small"><i class="fas fa-camera text-orange me-2"></i>Evidencia Fotográfica</h6>
        @if($orden->estatus == 2 || $orden->estatus == 'ABIERTA')
            <button class="btn btn-sm btn-orange" data-bs-toggle="modal" data-bs-target="#modalFoto">
                <i class="fas fa-plus"></i> Añadir Foto
            </button>
        @endif
    </div>
    <div class="card-body">
        <div class="row g-2">
            @forelse($fotos as $foto)
                <div class="col-md-4 col-6 position-relative mb-2">
                    <a href="{{ asset('storage/' . $foto->ruta_archivo) }}" target="_blank">
                        <img src="{{ asset('storage/' . $foto->ruta_archivo) }}"  class="img-fluid rounded shadow-sm border"  style="height: 200px; width: 100%; object-fit: cover;">
                    </a>
                    @if($orden->estatus == 2 || $orden->estatus == 'ABIERTA')
                        <button class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 delete-item" data-type="foto" data-id="{{ $foto->id }}">
                            <i class="fas fa-times"></i>
                        </button>
                    @endif
                </div>
            @empty
                <div class="col-12 text-center py-3 text-muted small">
                    <i class="fas fa-images d-block mb-2 fa-2x"></i>
                    No hay fotos registradas.
                </div>
            @endforelse
        </div>
    </div>
</div>
{{-- Sección de Ubicación Geográfica (Solo si hay coordenadas) --}}
@if($orden->latitud && $orden->longitud)
    <div class="card shadow-sm mb-4 no-print border-orange">
        <div class="card-header bg-white py-2">
            <h6 class="mb-0 text-uppercase fw-bold text-orange">
                <i class="fas fa-map-marked-alt me-2"></i>Ubicación del Servicio
            </h6>
        </div>
        <div class="card-body p-0">
            <div id="map-show" style="height: 250px; width: 100%;"></div>
            <div class="p-2 bg-light border-top">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i> Punto georeferenciado al momento de la creación.
                </small>
            </div>
        </div>
    </div>
@endif
    {{-- FIRMAS --}}
    <div class="row mt-5 justify-content-around">
        <div class="col-auto">
            <div class="signature-box">
                <span class="d-block fw-bold text-uppercase"></span>
                Jefe de Taller / Autorizado
            </div>
        </div>
        <div class="col-auto">
            <div class="signature-box">
                <br>
                Técnico Responsable
            </div>
        </div>
        <div class="col-auto">
            <div class="signature-box">
                <br>
                Chofer / Recepción
            </div>
        </div>
    </div>
</div>

{{-- MODALES --}}

<div class="modal fade" id="modalTrabajo" data-bs-backdrop="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"> {{-- Cambiado a lg para mejor visión en móvil --}}
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title small text-uppercase">Registrar Trabajo Realizado</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <label class="small fw-bold">Categoría</label>
                              
                                <select id="select-categoria" name="id_categoria" class="form-select form-select-sm select2 s2">
                                    <option value="">Seleccione...</option>
                                    @foreach ($categorias_tempario as $cat)

                                        
                                        <option value="{{ $cat->id_tempario_categoria }}">[{{ $cat->codigo }}] {{ $cat->categoria }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="small fw-bold">Servicio / Trabajo</label>
                                <select id="select-servicio" name="id_servicio" class="form-select form-select-sm select2 s2">
                                    <option value="">Seleccione categoría primero</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="small fw-bold">Mecánico(s)</label>
                                <select id="select-mecanicos" name="mecanicos[]" class="form-select form-select-sm s2" multiple>
                                    @foreach ($personal as $p)
                                        <option value="{{ $p->id_personal }}">{{ $p->persona->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                </div>
                
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success btn-sm fw-bold" id="btn-guardar-todo">
                    <i class="fas fa-save me-1"></i> GUARDAR CAMBIOS
                </button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="manualSupplyModal" data-bs-backdrop="false" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title fw-bold">SUMINISTRO NO CATALOGADO</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="mb-3">
                    <label class="form-label">Descripción del Repuesto</label>
                    <input type="text" class="form-control" name="descripcion" id="manual-descripcion" placeholder="Ej: Tornillo grado 8 1/2 pulgada">
                </div>
                <div class="mb-3">
                    <label class="form-label">Cantidad Requerida</label>
                    <input type="number" class="form-control text-end fw-bold" name="cantidad" id="manual-cantidad" value="1" min="1">
                </div>
                <div class="mb-3">
                    <label class="form-label">Precio Unitario</label>
                    <input type="number" class="form-control text-end fw-bold" name="costo" id="manual-costo" value="1" min="0.1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success w-100 fw-bold"  id="addManualSupplyBtn">AÑADIR A LA LISTA</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalInsumo" data-bs-backdrop="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('ordenes.addInsumo', $orden->id) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-corporate text-white">
                    <h5 class="modal-title small text-uppercase fw-bold">Cargar Insumo al Inventario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Seleccionar Artículo</label>
                            <select name="id_inventario" class="form-select select2-insumos" required style="width: 100%">
                                <option value="">Escriba para buscar...</option>
                                @foreach($inventario as $item)
                                    <option value="{{ $item->id }}" @if($item->existencia <= $item->existencia_minima) class="text-danger" @endif @if($item->existencia == 0) class="text-muted" disabled @endif>
                                        {{$item->codigo}} - {{ $item->descripcion }} (Stock: {{ $item->existencia }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Cantidad</label>
                            <input type="number" name="cantidad" class="form-control form-control-lg" min="1" value="1" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-orange text-white fw-bold">AGREGAR INSUMO</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalFoto" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h6 class="modal-title text-uppercase small">Añadir Evidencia Fotográfica</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="form-fotos" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Seleccione una o más imágenes</label>
                        <input type="file" name="fotos_orden[]" id="input-fotos" class="form-control form-control-sm" accept="image/*" multiple>
                        <div class="form-text x-small">Formatos permitidos: JPG, PNG. Máximo 4MB por foto.</div>
                    </div>
                    
                    <div id="preview-container" class="d-flex flex-wrap gap-2 mb-2"></div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-orange btn-sm fw-bold" id="btn-subir-evidencia">
                    <i class="fas fa-upload me-1"></i> SUBIR Y ENVIAR
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTrabajoExterno" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form id="formTrabajoExterno" action="{{ route('ordenes.addTrabajoExterno', $orden->id) }}" method="POST">
                @csrf
                <input type="hidden" name="id_orden" value="{{ $orden->id }}">
                <input type="hidden" name="id_usuario" value="{{ auth()->id() }}">

                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold text-uppercase small">Registrar Gasto Externo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="fw-bold small text-muted text-uppercase mb-1">Proveedor / Taller Externo</label>
                        <div class="input-group">
                            <select class="form-select select2-proveedor" name="id_proveedor" id="select-proveedor" required>
                                <option value="">Seleccione un proveedor...</option>
                                @foreach($proveedores as $prov)
                                    <option value="{{ $prov->id }}">{{ $prov->nombre_proveedor }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-outline-primary" type="button" id="btn-nuevo-proveedor" title="Agregar nuevo proveedor">
                                <i class="bi bi-plus-circle-fill"></i>
                            </button>
                        </div>
                    </div>

                    <div id="wrapper-nuevo-proveedor" class="mb-3 d-none animate__animated animate__fadeIn">
                        <label class="fw-bold small text-primary text-uppercase mb-1">Nombre del Nuevo Proveedor</label>
                        <input type="text" name="nuevo_proveedor_nombre" class="form-control form-control-sm border-primary" placeholder="Ej: Rectificadora Caracas C.A.">
                    </div>

                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="fw-bold small text-muted text-uppercase mb-1">Fecha del Servicio</label>
                            <input type="date" name="fecha" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-5">
                            <label class="fw-bold small text-muted text-uppercase mb-1">Costo Facturado ($)</label>
                            <input type="number" name="costo" step="0.01" class="form-control form-control-sm font-monospace fw-bold text-primary" placeholder="0.00" required>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="fw-bold small text-muted text-uppercase mb-1">Descripción del Trabajo Realizado</label>
                        <textarea name="descripcion" class="form-control form-control-sm" rows="3" placeholder="Detalle el servicio técnico realizado..." required></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm">
                        <i class="bi bi-save me-1"></i>Guardar Registro
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
@php(
    
    $dataTrabajos = $trabajos->map(function($t) {
                return [
                    'id_trabajo' => $t->id_trabajo,
                    'id_tempario' => $t->id_tempario_servicio,
                    'id_categoria' => $t->id_categoria ?? null,
                    'concepto' => $t->descripcion,
                    'mecanicos' => $t->mecanicos_lista->pluck('id_personal')->toArray(),
                    'mecanicos_nombres' => $t->mecanicos_lista->pluck('persona.nombre')->join(', ')
                ];
            })
)

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    /**
     * Estandarización de procesos: Función maestra para peticiones AJAX/Fetch
     */
    async function apiCall(url, method = 'POST', body = null) {
        try {
            const options = {
                method: method,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            };

            if (body) {
                options.headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(body);
            }

            const response = await fetch(url, options);
            const res = await response.json();

            if (res.success || response.ok) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Hecho!',
                    text: res.message || 'Operación exitosa',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => window.location.reload());
            } else {
                Swal.fire('Error', res.message || 'Error en el servidor', 'error');
            }
        } catch (error) {
            console.error(error);
            Swal.fire('Error', 'No se pudo procesar la solicitud', 'error');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const orderId = '{{ $orden->id }}';

        // --- 1. INICIALIZACIÓN DE COMPONENTES ---
        $('.select2-insumos').select2({ dropdownParent: $('#modalInsumo'), width: '100%' });
        $('#select-mecanicos').select2({ dropdownParent: $('#modalTrabajo'), width: '100%', placeholder: "Seleccione..." });
        $('#select-categoria, #select-servicio').select2({ dropdownParent: $('#modalTrabajo'), width: '100%' });

        // --- 2. GESTIÓN DE TRABAJOS (MODAL) ---
        $('#select-categoria').on('change', function() {
            const catId = $(this).val();
            if (!catId) return;
            $.post('{{ route("get.tempario_servicios") }}', { catemp: catId }, function(data) {
                $('#select-servicio').html(data).trigger('change');
            });
        });

        // Botón "Guardar" del Modal de Trabajo (Directo a DB)
        $('#btn-guardar-todo').on('click', function() {
            const data = {
                id_tempario: $('#select-servicio').val(),
                id_categoria: $('#select-categoria').val(),
                mecanicos: $('#select-mecanicos').val(),
                descripcion: $('#select-servicio option:selected').text(),
                costo: 0 // El controlador lo calcula por Eager Loading si lo configuraste
            };

            if (!data.id_tempario || !data.mecanicos || data.mecanicos.length === 0) {
                return Swal.fire('Atención', 'Seleccione servicio y mecánicos responsables.', 'warning');
            }

            apiCall(`/ordenes/${orderId}/trabajos/add`, 'POST', data);
        });

        // Finalizar Trabajo (Botón en tabla)
        $(document).on('click', '.finish-work', function() {
            const id = $(this).data('id');
            apiCall(`/ordenes/trabajo/${id}/finalizar`, 'POST');
        });

        // --- 3. GESTIÓN DE INSUMOS Y COMPRAS ---
        // Solicitar Insumo Manual
        $('#addManualSupplyBtn').on('click', function() {
            const data = {
                descripcion: $('#manual-descripcion').val(),
                cantidad: $('#manual-cantidad').val(),
                costo: $('#manual-costo').val()
            };

            if (!data.descripcion || data.cantidad <= 0) {
                return Swal.fire('Error', 'Ingrese descripción y cantidad válida.', 'error');
            }

            apiCall(`/ordenes/${orderId}/add-manual-supply`, 'POST', data);
        });

        // Marcar como Recibido (Insumos o Compras)
        $(document).on('click', '.mark-recibido', function() {
            const id = $(this).data('id');
            const type = $(this).data('type'); // 'supplies' o 'compras'
            const url = type === 'supplies' 
                ? `/ordenes/supplies/receive/${id}` 
                : `/ordenes/compras/receive/${id}`;
            
            apiCall(url, 'POST');
        });

        // --- 4. ACCIONES DE ELIMINACIÓN ---
        $(document).on('click', '.delete-item', function() {
            const id = $(this).data('id');
            const type = $(this).data('type');
            let url = '';

            if(type === 'trabajo') url = `/ordenes/trabajos/${id}/delete`;
            if(type === 'insumo')  url = `/ordenes/supplies/${id}/delete`;
            if(type === 'compra')  url = `/ordenes/purchase/${id}/delete`;
            if(type === 'foto')    url = `/ordenes/fotos/${id}/delete`;
            if(type === 'orden')   url = `/ordenes/${id}`;

            Swal.fire({
                title: '¿Confirmar eliminación?',
                text: "Esta acción borrará el registro de la base de datos.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'SÍ, ELIMINAR'
            }).then((result) => {
                if (result.isConfirmed) apiCall(url, 'DELETE');
            });
        });

        // --- 5. CIERRE Y HABILITACIÓN ---
        $('#cerrar-orden').on('click', function() {
            // Validación de trabajos pendientes (opcional si el controlador ya lo hace)
            const pendientes = $('.badge.bg-warning.animate__flash').length;
            if (pendientes > 0) {
                return Swal.fire('Atención', 'No puede cerrar la orden con trabajos pendientes.', 'error');
            }

            Swal.fire({
                title: '¿Cerrar Orden Técnica?',
                text: "Se generará el reporte final y se notificará a gerencia.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'SÍ, CERRAR ORDEN'
            }).then((res) => {
                if (res.isConfirmed) apiCall(`/ordenes/${orderId}/cerrar`, 'POST');
            });
        });

         $('#reactivar-orden').on('click', function() {
            // Validación de trabajos pendientes (opcional si el controlador ya lo hace)
            const texto = '{{ $orden->estatus }}' === '3' 
                ? "La Orden Trabajo se Iniciará y la unidad estará fuera de servicio." 
                : "La Orden se Activará nuevamente y la unidad estará fuera de servicio nuevamente.";
            const titulo = '{{ $orden->estatus }}' === '3' 
                ? "Iniciar Orden Trabajo" 
                : "Reactivar Orden Trabajo";
            const buttonText = '{{ $orden->estatus }}' === '3' 
                ? "SÍ, INICIAR ORDEN" 
                : "SÍ, REACTIVAR ORDEN";

            Swal.fire({
                title: titulo,
                text: texto,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: buttonText
            }).then((res) => {
                if (res.isConfirmed) apiCall(`/ordenes/${orderId}/reactivar`, 'POST');
            });
        });

        $('#anular-orden').on('click', function() {
            // Validación de trabajos pendientes (opcional si el controlador ya lo hace)

            Swal.fire({
                title: 'Anular Orden Técnica?',
                text: "La Orden se Anulara y la unidad estara Operativa",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'SÍ, ANULAR ORDEN'
            }).then((res) => {
                if (res.isConfirmed) apiCall(`/ordenes/${orderId}/anular`, 'POST');
            });
        });

        $('#habilitar-unidad').on('click', function() {
            Swal.fire({
                title: 'Habilitación de Unidad',
                text: 'Indique el motivo o condición de salida:',
                input: 'textarea',
                showCancelButton: true,
                confirmButtonText: 'HABILITAR VEHÍCULO',
                inputValidator: (value) => {
                    if (!value) return '¡Debe indicar un motivo!';
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    apiCall(`/ordenes/${orderId}/habilitar-unidad`, 'POST', { motivo: result.value });
                }
            });
        });

        // --- Lógica para Subida de Fotos ---
        $('#btn-subir-evidencia').on('click', async function() {
            const input = document.getElementById('input-fotos');
            if (input.files.length === 0) {
                return Swal.fire('Error', 'Seleccione al menos una imagen.', 'error');
            }

            const formData = new FormData();
            for (let i = 0; i < input.files.length; i++) {
                formData.append('fotos_orden[]', input.files[i]);
            }

            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');

            try {
                const response = await fetch(`/ordenes/{{ $orden->id }}/fotos/add`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: formData
                });

                const res = await response.json();
                if (res.success) {
                    Swal.fire('¡Éxito!', res.message, 'success').then(() => window.location.reload());
                } else {
                    throw new Error(res.message);
                }
            } catch (error) {
                Swal.fire('Error', error.message, 'error');
                btn.prop('disabled', false).html('<i class="fas fa-upload me-1"></i> SUBIR Y ENVIAR');
            }
        });

        $('#modalTrabajoExterno').on('shown.bs.modal', function () {
            $('.select2-proveedor').select2({
                dropdownParent: $('#modalTrabajoExterno'),
                width: '100%',
                placeholder: "Buscar proveedor registrado..."
            });
        });

        // Toggle para agregar nuevo proveedor
        $('#btn-nuevo-proveedor').on('click', function() {
            const wrapper = $('#wrapper-nuevo-proveedor');
            const select = $('#select-proveedor');
            
            if(wrapper.hasClass('d-none')) {
                wrapper.removeClass('d-none');
                select.val('').trigger('change').prop('required', false);
                $(this).removeClass('btn-outline-primary').addClass('btn-primary');
                wrapper.find('input').prop('required', true).focus();
            } else {
                wrapper.addClass('d-none');
                select.prop('required', true);
                $(this).removeClass('btn-primary').addClass('btn-outline-primary');
                wrapper.find('input').prop('required', false).val('');
            }
        });

        // Envío del Formulario
        $('#formTrabajoExterno').on('submit', function(e) {
            e.preventDefault();
            const form = $(this);
            const btn = form.find('button[type=\"submit\"]');
            
            btn.prop('disabled', true).html('<i class=\"fas fa-spinner fa-spin\"></i> Guardando...');

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: form.serialize(),
                success: function(res) {
                    if(res.success) {
                        Swal.fire('¡Éxito!', 'El trabajo externo ha sido registrado correctamente.', 'success')
                            .then(() => window.location.reload());
                    }
                },
                error: function(err) {
                    Swal.fire('Error', 'No se pudo registrar el trabajo. Verifique los datos.', 'error');
                    btn.prop('disabled', false).html('<i class=\"bi bi-save me-1\"></i>Guardar Registro');
                }
            });
        });

        // --- 6. MAPA ---
        @if($orden->latitud && $orden->longitud)
            const mapShow = L.map('map-show', {
                center: [{{ $orden->latitud }}, {{ $orden->longitud }}],
                zoom: 16,
                dragging: false,
                scrollWheelZoom: false,
                attributionControl: false
            });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mapShow);
            L.marker([{{ $orden->latitud }}, {{ $orden->longitud }}]).addTo(mapShow);
        @endif


    });
</script>
@endpush