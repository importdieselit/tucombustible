<div class="modal-header bg-dark text-white py-3">
    <h6 class="modal-title fw-black text-uppercase">
        <i class="fas fa-search me-2 text-orange"></i> 
        Planificación V-{{ str_pad($viaje->id, 5, '0', STR_PAD_LEFT) }}
    </h6>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body p-4">
    <div class="row g-3">
        {{-- BLOQUE 1: DATOS COMUNES --}}
        <div class="col-md-4">
            <label class="small text-muted fw-bold d-block text-uppercase">Vehículo / Cisterna</label>
            <span class="fw-bold">{{ $viaje->vehiculo->placa ?? $viaje->vehiculo_externo ?? 'S/A' }}</span>
            
            {{-- Cambiamos el acceso directo por la relación --}}
            @if($viaje->cisterna || $viaje->cisterna_externo)
                <br>
                <small class="text-muted">
                    Cisterna: {{ $viaje->cisternaAcoplada->placa ?? $viaje->cisterna_externo ?? 'S/R' }}
                </small>
            @endif
        </div>
        <div class="col-md-4">
            <label class="small text-muted fw-bold d-block text-uppercase">Chofer</label>
            <span class="fw-bold">
                {{-- Acceso directo a la relación persona -> nombre --}}
                {{ $viaje->chofer->persona->nombre ?? $viaje->chofer_externo ?? 'N/A' }}
            </span>
        </div>
        <div class="col-md-4 text-end">
            <label class="small text-muted fw-bold d-block text-uppercase">Ayudante</label>
            <span class="fw-bold">
                {{-- Usamos getRelation para evitar la colisión con la columna 'ayudante' (int) de la tabla viajes --}}
                {{ $viaje->getRelation('ayudante')->persona->nombre ?? $viaje->ayudante_externo ?? 'N/A' }}
            </span>
        </div>

        <hr class="my-3">

        {{-- BLOQUE 2: LÓGICA POR TIPO --}}
        
        {{-- DIESEL Y MGO --}}
        @if(in_array($viaje->tipo_planificacion, [1, 2]))
            <div class="col-12">
                <div class="d-flex justify-content-between mb-2">
                    <h6 class="fw-black text-orange small text-uppercase">Destinos de Despacho</h6>
                    <span class="badge bg-light text-dark border">Sede: {{ $viaje->sede->nombre ?? 'N/A' }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="bg-light small text-uppercase">
                            <tr>
                                <th>Cliente / RIF</th>
                                <th>Dirección</th>
                                <th class="text-center">Litros</th>
                                @if($viaje->tipo_planificacion == 2) 
                                    <th>Buque / IMO / Bandera</th> 
                                @endif
                            </tr>
                        </thead>
                        <tbody class="small">
                            @foreach($viaje->detalles as $detalle)
                            <tr>
                                <td>
                                    <strong>{{ $detalle->cliente->nombre ?? $viaje->nombre_cliente_externo }}</strong><br>
                                    <small class="text-muted">{{ $detalle->cliente->rif ?? 'S/R' }}</small>
                                </td>
                                <td>{{ $detalle->direccion_despacho ?? $detalle->cliente->direccion_operativa }}</td>
                                <td class="text-center fw-bold">{{ number_format($detalle->litros_despachados ?? $detalle->litros ?? 0, 0) }} L</td>
                                
                                @if($viaje->tipo_planificacion == 2)
                                    <td>
                                        <div class="x-small">
                                            {{-- Prioridad: 1. Relación 'buques', 2. Nombre manual --}}
                                            <strong>Buque:</strong> {{ $detalle->buques->nombre ?? $detalle->buque_nombre_manual ?? 'N/A' }}<br>
                                            <strong>IMO:</strong> {{ $detalle->imo ?? 'N/A' }}<br>
                                            <strong>Bandera:</strong> {{ $detalle->bandera ?? 'N/A' }}
                                            @if($detalle->muelle_atraque)
                                                <br><strong>Muelle:</strong> {{ $detalle->muelle_atraque }}
                                            @endif
                                        </div>
                                    </td>
                                @endif
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        {{-- FLETES --}}
        @elseif($viaje->tipo_planificacion == 3)
            <div class="col-md-6">
                <label class="small text-muted fw-bold text-uppercase">Ruta de Flete</label>
                <p class="mb-1"><strong>Salida:</strong> {{ $viaje->punto_salida }}</p>
                <p><strong>Destino:</strong> {{ $viaje->punto_llegada }}</p>
            </div>
            <div class="col-md-6 text-end">
                <label class="small text-muted fw-bold text-uppercase">Carga</label>
                <p class="mb-1"><strong>Producto:</strong> {{ $viaje->producto->nombre ?? 'Combustible' }}</p>
                <p><strong>Litros:</strong> {{ number_format($viaje->litros, 0) }} L</p>
            </div>

        {{-- COMPRAS --}}
        @elseif($viaje->tipo_planificacion == 4)
            @php 
                // Obtenemos la relación de la tabla compras_combustible
                $compra = $viaje->compraCombustible->first(); 
            @endphp
            <div class="col-md-6">
                <label class="small text-muted fw-bold text-uppercase">Proveedor y SAP</label>
                {{-- Ajuste: Consultamos el proveedor desde la relación en compras_combustible según lo solicitado --}}
                <p class="mb-1">
                    <strong>Proveedor:</strong> 
                    {{ $compra->proveedor->nombre ?? $viaje->proveedor->nombre ?? $viaje->nombre_cliente_externo ?? 'No asignado' }}
                </p>
                <p><strong>Código SAP:</strong> <span class="badge bg-info text-dark">{{ $viaje->codigo_sap ?? 'N/A' }}</span></p>
            </div>
            <div class="col-md-6 text-end">
                <label class="small text-muted fw-bold text-uppercase">Destino e Inventario</label>
                <p class="mb-1"><strong>Sede Destino:</strong> {{ $viaje->sede->nombre ?? 'N/A' }}</p>
                <p><strong>Cantidad Compra:</strong> 
                    {{ $compra ? number_format($compra->cantidad_litros, 0) : number_format($viaje->litros, 0) }} L
                </p>
            </div>
        @endif

        {{-- OBSERVACIONES (Lógica Multi-fuente) --}}
        <div class="col-12 mt-3 bg-light p-3 rounded">
            <label class="small text-muted fw-bold text-uppercase d-block mb-1">Observaciones</label>
            <p class="mb-0 small italic">
                @php
                    // 1. Intentar obtener de la tabla 'despachos_viajes' (Para Diesel/MGO)
                    $obs = $viaje->detalles->pluck('observacion')->filter()->first();

                    // 2. Si es una Compra (Tipo 4), intentar obtener de la tabla 'compras_combustible'
                    if (!$obs && $viaje->tipo_planificacion == 4) {
                        // Buscamos en la relación que definiste en el modelo Viaje
                        $obs = $viaje->compraCombustible->first()->observaciones ?? null;
                    }

                    // 3. Fallback Final: La tabla 'viajes' 
                    // (Aquí es donde caerán los Fletes y lo que acabas de arreglar en el Service)
                    if (!$obs) {
                        $obs = $viaje->observacion;
                    }
                @endphp

                {{ $obs ?? 'Sin observaciones registradas.' }}
            </p>
        </div>
    </div>
</div>

<div class="modal-footer bg-light">
    <div class="d-flex w-100 justify-content-between align-items-center">
        <span class="badge bg-{{ $viaje->status == 'CANCELADO' ? 'danger' : 'success' }}">{{ $viaje->status }}</span>
        <button type="button" class="btn btn-dark fw-bold text-uppercase small" data-bs-dismiss="modal">Cerrar</button>
    </div>
</div>