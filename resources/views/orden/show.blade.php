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
            @if($orden->estatus == 'ABIERTA' || $orden->estatus == 2)
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTrabajo">
                    <i class="fas fa-tools"></i> + Trabajo
                </button>
                <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#modalInsumo">
                    <i class="fas fa-box"></i> + Insumo
                </button>
                <button type="button" class="btn btn-sm btn-corporate ms-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#manualSupplyModal">
                    <i class="fa-solid fa-cart-plus me-1"></i> Solicitar a Compras
                </button>
                <a href="{{ route('ordenes.edit', $orden->id) }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <button id="cerrar-orden" class="btn btn-sm btn-success fw-bold">
                    <i class="fas fa-check-double"></i> CERRAR
                </button>
                <button id="habilitar-unidad" class="btn btn-sm btn-outline-warning fw-bold">
                    <i class="fas fa-truck-loading"></i> HABILITAR UNIDAD
                </button>
                <button id="anular-orden" class="btn btn-sm btn-danger">
                    <i class="fas fa-times-circle"></i> CANCELAR
                </button>
            @elseif($orden->estatus == 'CERRADA' || $orden->estatus == 4 || $orden->estatus == 1)
                <button id="reactivar-orden" class="btn btn-sm btn-warning fw-bold">
                    <i class="fas fa-undo"></i> REACTIVAR ORDEN
                </button>
            @endif
            @if($orden->estatus == 'ANULADA' || $orden->estatus == 4)
                <button id="eliminar-orden" class="btn btn-sm btn-outline-danger fw-bold">
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
    <div class="row">
        {{-- COLUMNA TRABAJOS --}}
        <div class="col-md-6">
            <div class="card card-step shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-uppercase small"><i class="fas fa-tools text-orange me-2"></i>Trabajos Ejecutados</h6>
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

            <div class="card card-step shadow-sm">
                <div class="card-body">
                    <label class="form-label-corp fw-bold text-uppercase small">Observaciones Técnicas:</label>
                    <p class="mt-2 p-3 bg-light rounded border" style="min-height: 100px;">
                        {!! $orden->descripcion ?: 'Sin observaciones adicionales.' !!}
                    </p>
                </div>
            </div>
        </div>

        {{-- COLUMNA INSUMOS --}}
        <div class="col-md-6">
            <div class="card card-step shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 fw-bold text-uppercase small"><i class="fas fa-box-open text-orange me-2"></i>Insumos y Repuestos</h6>
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
                                                
                                            @elseif($isSolicitadoReq)
                                                {{-- ESTADO 2: Solo solicitado (En espera de aprobación) --}}
                                                <i class="fas fa-clock text-warning fs-5" title="Pendiente de aprobación/compra"></i>
                                                
                                            @else
                                                {{-- Cualquier otro estado --}}
                                                <span class="text-muted small">Pte.</span>
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
                        <span>SUBTOTAL REPUESTOS:</span>
                        <span>${{ number_format($totalGeneral, 2) }}</span>
                    </div>
                    <hr class="bg-white my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-uppercase">TOTAL GENERAL:</span>
                        <span class="fs-3 fw-bold text-orange">${{ number_format($totalManoObra + $totalGeneral, 2) }}</span>
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
    <div class="modal-dialog">
        <form action="{{ route('ordenes.addTrabajo', $orden->id) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title small text-uppercase">Registrar Trabajo Realizado</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
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
                            <div class="col-12 text-end mt-2">
                                <button type="button" class="btn btn-orange btn-sm px-4 fw-bold" id="btn-agregar-trabajo">
                                    <i class="fas fa-plus me-1"></i> AGREGAR TRABAJO
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Guardar Trabajo</button>
                </div>
            </div>
        </form>
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
                                    <option value="{{ $item->id }}">
                                        {{ $item->descripcion }} (Stock: {{ $item->stock }}) - ${{ $item->precio_venta }}
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
    document.addEventListener('DOMContentLoaded', function () {
        const orderId = '{{ $orden->id }}';

        let trabajosAsignados = {};

        @if(isset($item->trabajos))
            @foreach($item->trabajos as $trabajo)
                trabajosAsignados["{{ $trabajo->id_tempario_servicio }}"] = {
                    id_servicio: "{{ $trabajo->id_tempario_servicio }}",
                    servicio_texto: "{{ $trabajo->servicio->descripcion }}",
                    categoria_texto: "{{ $trabajo->servicio->categoria->categoria }}",
                    estatus: {{ $trabajo->estatus ?? 1 }},
                    personal: @json($trabajo->personal_ids ?? []), // Ajustar según tu relación
                    personal_nombres: @json($trabajo->personal_nombres ?? [])
                };
            @endforeach
        @endif

        // Ahora la variable se renderiza sin conflictos
        trabajosAsignados = {!! $dataTrabajos->toJson() !!};

        // Inicializar Select2
        $('.select2-insumos').select2({ dropdownParent: $('#modalInsumo') });
        $('.select2-mecanicos').select2({ dropdownParent: $('#modalTrabajo') });

        // Función genérica para Fetch
        async function apiCall(url, method = 'POST', body = null) {
            try {
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: body ? JSON.stringify(body) : null
                });
                const data = await response.json();
                if (data.success) {
                    Swal.fire('¡Éxito!', 'Operación realizada.', 'success').then(() => window.location.reload());
                } else {
                    Swal.fire('Error', data.message || 'Error en el servidor', 'error');
                }
            } catch (error) {
                Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
            }
        }

        // Eventos de Botones de Estado
        // --- VALIDACIÓN PARA CERRAR ORDEN ---
        if(document.getElementById('cerrar-orden')){
            document.getElementById('cerrar-orden').addEventListener('click', () => {
                // Contamos trabajos que NO tienen fecha_fin (usando el badge de 'En proceso' como referencia)
                const trabajosPendientes = document.querySelectorAll('.badge.bg-warning.animate__flash').length;

                if (trabajosPendientes > 0) {
                    Swal.fire({
                        title: '<span style="color: #d33;">TRABAJOS PENDIENTES</span>',
                        html: `No puedes cerrar la orden porque aún hay <b>${trabajosPendientes}</b> tarea(s) en proceso.<br><br>Finaliza todos los trabajos antes de proceder.`,
                        icon: 'error',
                        confirmButtonColor: '#e67e22',
                        confirmButtonText: 'ENTENDIDO'
                    });
                    return;
                }

                // Si no hay pendientes, procede al cierre normal
                Swal.fire({
                    title: '¿Confirmar Cierre Técnico?',
                    text: "Se generará el histórico y la unidad quedará disponible.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#198754',
                    cancelButtonColor: '#343a40',
                    confirmButtonText: 'SÍ, CERRAR ORDEN'
                }).then((r) => r.isConfirmed && apiCall(`/ordenes/${orderId}/cerrar`));
            });
        }

        // --- LÓGICA DE HABILITACIÓN ESPECIAL ---
        if(document.getElementById('habilitar-unidad')){
            document.getElementById('habilitar-unidad').addEventListener('click', () => {
                Swal.fire({
                    title: '<span style="color: #e67e22;">HABILITACIÓN ESPECIAL</span>',
                    html: `Esta acción permite que el vehículo <b>{{ $orden->vehiculoBelong->flota }}</b> pueda ser asignado a rutas aunque esta orden siga abierta.`,
                    input: 'textarea',
                    inputPlaceholder: 'Indique el motivo de la habilitación (ej: Repuesto en camino, trabajo no crítico)...',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e67e22',
                    cancelButtonColor: '#343a40',
                    confirmButtonText: 'CONFIRMAR HABILITACIÓN',
                    cancelButtonText: 'CANCELAR',
                    inputValidator: (value) => {
                        if (!value) return '¡Es obligatorio indicar un motivo!';
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        apiCall(`/ordenes/${orderId}/habilitar-unidad`, 'POST', { motivo: result.value });
                    }
                });
            });
        }

        if(document.getElementById('anular-orden')){
            document.getElementById('anular-orden').addEventListener('click', () => {
                Swal.fire({
                    title: 'Anular Orden',
                    text: 'Escriba el motivo (se devolverán los insumos al stock):',
                    input: 'text',
                    showCancelButton: true,
                    inputValidator: (v) => !v && '¡Motivo obligatorio!'
                }).then((r) => r.isConfirmed && apiCall(`/ordenes/${orderId}/anular`, 'POST', { anulacion: r.value }));
            });
        }

        if(document.getElementById('reactivar-orden')){
            document.getElementById('reactivar-orden').addEventListener('click', () => {
                apiCall(`/ordenes/${orderId}/reactivar`);
            });
        }

        if(document.getElementById('eliminar-orden')){
            document.getElementById('eliminar-orden').addEventListener('click', () => {
                Swal.fire({
                    title: '¿Eliminar de la DB?',
                    icon: 'error',
                    showCancelButton: true
                }).then((r) => r.isConfirmed && apiCall(`/ordenes/${orderId}/destroy`, 'DELETE'));
            });
        }

            // 1. CARGA DINÁMICA DE TEMPARIO
        $('#select-categoria').on('change', function() {
            const catId = $(this).val();
            if (!catId) return;

            $.post('{{ route("get.tempario_servicios") }}', { catemp: catId }, function(data) {
                $('#select-servicio').html(data);
            });
        });

        // 2. AGREGAR TRABAJO A LA LISTA
        $('#btn-agregar-trabajo').on('click', function() {
            const servicioId = $('#select-servicio').val();
            const categoriaId = $('#select-categoria').val();
            const servicioTexto = $('#select-servicio option:selected').text();
            const mecanicosIds = $('#select-mecanicos').val();
            const mecanicosNombres = $('#select-mecanicos option:selected').map(function(){ return $(this).text(); }).get();

            if (!servicioId || mecanicosIds.length === 0) {
                Swal.fire('Error', 'Debe seleccionar un servicio y al menos un mecánico', 'error');
                return;
            }

            const nuevoTrabajo = {
                id_tempario: servicioId,
                id_categoria: categoriaId,
                concepto: servicioTexto,
                mecanicos: mecanicosIds,
                mecanicos_nombres: mecanicosNombres.join(', ')
            };

            trabajosAsignados.push(nuevoTrabajo);
            renderTrabajos();
            
            // Limpiar selectores
            $('#select-mecanicos').val(null).trigger('change');
        });

        function renderTrabajos() {
            let html = '';
            if (trabajosAsignados.length === 0) {
                html = '<tr><td colspan="3" class="text-center text-muted py-4 small">No hay trabajos asignados</td></tr>';
            } else {
                trabajosAsignados.forEach((t, index) => {
                    html += `
                    <tr class="small">
                        <td class="ps-3 fw-bold">${t.concepto}</td>
                        <td><span class="text-muted">${t.mecanicos_nombres}</span></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-link btn-sm text-danger btn-remove-trabajo" data-index="${index}">
                                <i class="fas fa-times-circle"></i>
                            </button>
                        </td>
                    </tr>`;
                });
            }
            $('#tabla-trabajos-body').html(html);
            $('#trabajos_json').val(JSON.stringify(trabajosAsignados));
        }

        $(document).on('click', '.btn-remove-trabajo', function() {
            const index = $(this).data('index');
            trabajosAsignados.splice(index, 1);
            renderTrabajos();
        });

        $('#btn-limpiar-trabajos').on('click', function() {
            if(confirm('¿Desea limpiar todos los trabajos?')) {
                trabajosAsignados = [];
                renderTrabajos();
            }
        });


        function renderSuppliesTable() {
            const tbody = document.querySelector('#selected-supplies-table tbody');
            tbody.innerHTML = '';
            let total = 0;

            Object.values(selectedSupplies).forEach(item => {
                total += item.subtotal;
                const isRecibido = item.estatus == 2;
                
                tbody.innerHTML += `
                    <tr class="${isRecibido ? 'table-success-light' : ''}">
                        <td class="small fw-bold text-orange">${item.codigo}</td>
                        <td class="small">
                            ${item.descripcion}
                            <br>
                            <span class="badge ${isRecibido ? 'bg-success' : 'bg-warning text-dark'} x-small">
                                ${isRecibido ? '<i class="fas fa-check-circle me-1"></i>RECIBIDO' : '<i class="fas fa-clock me-1"></i>SOLICITADO'}
                            </span>
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm qty-input" 
                                data-id="${item.id}" value="${item.cantidad}" min="1" ${isRecibido ? 'readonly' : ''}>
                        </td>
                        <td><input type="number" step="0.01" class="form-control form-control-sm price-input" 
                                data-id="${item.id}" value="${item.precio}" ${isRecibido ? 'readonly' : ''}></td>
                        <td class="fw-bold">$ ${item.subtotal.toFixed(2)}</td>
                        <td class="text-center">
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm ${isRecibido ? 'btn-success' : 'btn-outline-success'}" 
                                        onclick="toggleStatusInsumo('${item.id}')" title="Marcar como recibido">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-supply" data-id="${item.id}">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <input type="hidden" name="supplies[${item.id}][id]" value="${item.id}">
                            <input type="hidden" name="supplies[${item.id}][cantidad]" value="${item.cantidad}">
                            <input type="hidden" name="supplies[${item.id}][precio]" value="${item.precio}">
                            <input type="hidden" name="supplies[${item.id}][estatus]" value="${item.estatus}">
                        </td>
                    </tr>
                `;
            });
            document.getElementById('total-amount').innerText = `$ ${total.toFixed(2)}`;
        }

        window.toggleStatusInsumo = function(id) {
            selectedSupplies[id].estatus = (selectedSupplies[id].estatus == 1) ? 2 : 1;
            renderSuppliesTable();
        };

        
    });

    async function apiCall(url, method = 'POST', body = null) {
        try {
            const response = await fetch(url, {
                method: method,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: body ? JSON.stringify(body) : null
            });
            
            const data = await response.json();
            
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: data.message || 'Operación realizada.',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => window.location.reload());
            } else {
                Swal.fire('Error', data.message || 'Error en el servidor', 'error');
            }
        } catch (error) {
            console.error('Error API:', error);
            Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
        }
    }

    // --- LÓGICA PARA SOLICITUD MANUAL A COMPRAS ---
document.getElementById('addManualSupplyBtn').addEventListener('click', function() {
    const orderId = '{{ $orden->id }}';
    const descripcion = document.getElementById('manual-descripcion').value;
    const cantidad = document.getElementById('manual-cantidad').value;

    // Validación básica
    if (!descripcion || descripcion.trim() === "") {
        Swal.fire('Error', 'Debe ingresar una descripción del repuesto.', 'error');
        return;
    }

    if (cantidad <= 0) {
        Swal.fire('Error', 'La cantidad debe ser mayor a 0.', 'error');
        return;
    }

    apiCall(`/ordenes/${orderId}/add-manual-supply`, 'POST', {
        descripcion: descripcion,
        cantidad: cantidad
    });
});

    // Dentro de tu sección de scripts
    $(document).on('click', '.mark-recibido', function() {
        const btn = $(this);
        const id = btn.data('id');
        const tipo = btn.data('type'); 
        const $fila = btn.closest('tr'); 
        const $celdaAccion = btn.closest('td'); 
        
        const routes = {
            'supplies': "{{ route('ordenes.supplies.receive', ':id') }}",
            'compras': "{{ route('ordenes.compras.receive', ':id') }}"
        };

        const url = routes[tipo].replace(':id', id);
        console.log(id);
        Swal.fire({
            title: '¿Confirmar recepción?',
            text: "Se marcará este ítem como entregado a la orden.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            confirmButtonText: 'Sí, recibir',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Deshabilitar botón para evitar doble click
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                $.ajax({
                    url:url,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        id: id,
                        tipo: tipo
                    },
                    success: function(response) {
                        if(response.success) {
                            // 1. Notificación de éxito
                            Swal.fire({
                                icon: 'success',
                                title: '¡Recibido!',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            });

                            // 2. Actualización visual de la fila
                            $fila.addClass('table-success-light');
                            
                            // 3. Reemplazar el botón por el icono de check estático
                            $celdaAccion.html('<i class="fas fa-check-circle text-success fs-5 animate__animated animate__bounceIn"></i>');
                            
                            // 4. Opcional: Añadir el badge de "RECIBIDO" en la descripción si no existe
                            if ($fila.find('.badge-recibido-dinamico').length === 0) {
                                $fila.find('td:nth-child(2)').append('<span class="badge bg-success x-small ms-2 badge-recibido-dinamico"><i class="fas fa-check"></i> RECIBIDO</span>');
                            }
                        } else {
                            // Error controlado desde el servidor
                            Swal.fire('Error', response.message || 'No se pudo procesar la recepción.', 'error');
                            btn.prop('disabled', false).html('<i class="fas fa-check"></i>');
                        }
                    },
                    error: function(xhr) {
                        // Error de conexión o 500
                        Swal.fire('Error de sistema', 'Hubo un problema de conexión con el servidor.', 'error');
                        btn.prop('disabled', false).html('<i class="fas fa-check"></i>');
                    }
                });
            }
        });
    });

    $(document).ready(function() {
        @if($orden->latitud && $orden->longitud)
            const lat = {{ $orden->latitud }};
            const lng = {{ $orden->longitud }};

            // Inicialización con todas las interacciones desactivadas
            const mapShow = L.map('map-show', {
                center: [lat, lng],
                zoom: 40,
                dragging: false,        // No se puede arrastrar el mapa
                zoomControl: true,     // No hay botones de zoom
                scrollWheelZoom: true, // No zoom con el mouse
                doubleClickZoom: false, // No zoom con doble click
                boxZoom: false,
                touchZoom: false,
                attributionControl: false // Limpieza visual (tu marca corporativa)
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(mapShow);

            // Marcador Naranja Corporativo (Estático)
            const orangeIcon = L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-orange.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41]
            });

            L.marker([lat, lng], { icon: orangeIcon }).addTo(mapShow);
        @endif
    });
</script>
@endpush