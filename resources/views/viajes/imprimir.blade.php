<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planificación V-{{ str_pad($viaje->id, 5, '0', STR_PAD_LEFT) }}</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700;900&display=swap');
        
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f1f5f9;
            margin: 0;
            padding: 0;
        }
        /* Garantizar el renderizado exacto de clases de peso pesado */
        .fw-black { 
            font-weight: 900 !important; 
        }
        .text-orange { 
            color: #fd7e14 !important; 
        }
        /* Forzar que el fondo no sufra recortes extraños en la captura */
        #areaCapturaViaje {
            max-width: 1100px;
            margin: 0 auto;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }
    </style>
</head>
<body>

<div id="areaCapturaViaje" class="p-4" style="background-color: #ffffff;">
    
    {{-- 📊 LÓGICA DE MOVIMIENTO Y PRODUCTO --}}
    @php
        // 1. Identificamos el tipo de movimiento general
        $movimiento = match($viaje->tipo_planificacion) {
            1, 2    => ['text' => 'DESPACHO', 'color' => 'bg-success'],
            3       => ['text' => 'FLETE', 'color' => 'bg-secondary'],
            4       => ['text' => 'COMPRA', 'color' => 'bg-info'],
            default => ['text' => 'OTRO', 'color' => 'bg-light text-dark']
        };

        // 2. Identificamos el producto o combustible específico
        $productoEspecifico = match($viaje->tipo_planificacion) {
            2       => 'DIESEL',
            1       => 'MGO',
            3       => $viaje->producto_flete ?? 'Sin Especificar',
            4       => match((int)$viaje->tipo) {
                        2 => 'DIESEL',
                        1 => 'MGO',
                        default => 'Sin Especificar'
                       },
            default => 'Sin especificar'
        };
    @endphp

    {{-- 🏢 MEMBRETE CORPORATIVO OPTIMIZADO PARA CAPTURAS --}}
    <div class="row align-items-center border-bottom pb-3 mb-4">
        <div class="col-6">
            <img src="{{ asset('img/logo1.png') }}" alt="Logo Empresa" style="max-width: 220px; height: auto;">
        </div>
        <div class="col-6 text-end">
            <span class="text-uppercase text-muted fw-bold d-block" style="font-size: 13px; letter-spacing: 1px;">Control de Operaciones</span>
            <div class="d-block mt-1">
                <span class="badge bg-dark text-white fw-bold px-2 py-1 text-uppercase" style="font-size: 13px; display: inline-block;">
                    ID: V-{{ str_pad($viaje->id, 5, '0', STR_PAD_LEFT) }}
                </span>
                <span class="badge {{ $movimiento['color'] }} text-white fw-bold px-2 py-1 text-uppercase ms-1" style="font-size: 13px; display: inline-block; vertical-align: middle;">
                    {{ $movimiento['text'] }}
                </span>
            </div>

            <span class="d-block text-dark fw-black text-uppercase mt-1" style="font-size: 14px; letter-spacing: 0.5px;">
                <i class="fas fa-gas-pump text-orange me-1"></i> {{ $productoEspecifico }}
            </span>

            <span class="d-block text-dark fw-black small mt-1" style="font-size: 13px;">
                <i class="far fa-calendar-alt text-muted me-1"></i> {{ \Carbon\Carbon::parse($viaje->fecha_salida)->format('d/m/Y') }} 
                <i class="far fa-clock text-orange ms-2 me-1"></i> {{ \Carbon\Carbon::parse($viaje->fecha_salida)->format('h:i A') }}
            </span>
        </div>
    </div>

    <div class="row g-3">
        {{-- 🎯 BLOQUE 1: DATOS COMUNES --}}
        
        {{-- Tarjeta: Vehículo / Cisterna --}}
        <div class="col-md-3">
            <div class="p-3 rounded shadow-sm h-100" style="background-color: #f8fafc; border-left: 4px solid #64748b;">
                <label class="small text-muted fw-bold d-block text-uppercase mb-1" style="font-size: 11px; letter-spacing: 0.5px;">Vehículo / Unidad</label>
                <span class="fs-5 fw-black text-dark d-block" style="line-height: 1.2;">
                    <i class="fas fa-truck text-secondary me-1"></i>
                    {{ $viaje->vehiculo->placa ?? $viaje->vehiculo_externo ?? 'S/A' }}
                </span>
                
                @if($viaje->cisterna || $viaje->cisterna_externo)
                    <div class="mt-2 pt-2 border-top border-white" style="border-top-width: 2px !important;">
                        <small class="text-danger fw-bold text-uppercase d-block" style="font-size: 9px; letter-spacing: 0.3px;">Cisterna / Acople</small>
                        <span class="fw-bold text-dark small">
                            <i class="fas fa-trailer text-danger me-1"></i>
                            {{ $viaje->cisternaAcoplada->placa ?? $viaje->cisterna_externo ?? 'S/R' }}
                        </span>
                    </div>
                @endif
            </div>
        </div>
        
        {{-- Tarjeta: Chofer --}}
        <div class="col-md-3">
            <div class="p-3 rounded shadow-sm h-100" style="background-color: #f0fdf4; border-left: 4px solid #16a34a;">
                <label class="small fw-bold d-block text-uppercase mb-1" style="font-size: 11px; letter-spacing: 0.5px; color: #16a34a;">Chofer Asignado</label>
                <span class="fs-5 fw-black d-block" style="line-height: 1.2; color: #145a32;">
                    <i class="fas fa-id-card me-1 text-success"></i>
                    {{ $viaje->chofer->persona->nombre ?? $viaje->chofer_externo ?? 'N/A' }}
                </span>
            </div>
        </div>
        
        {{-- Tarjeta: Ayudante --}}
        <div class="col-md-3">
            <div class="p-3 rounded shadow-sm h-100" style="background-color: #f5f3ff; border-left: 4px solid #6366f1;">
                <label class="small fw-bold d-block text-uppercase mb-1" style="font-size: 11px; letter-spacing: 0.5px; color: #4f46e5;">Ayudante de Ruta</label>
                <span class="fs-5 fw-black d-block" style="line-height: 1.2; color: #312e81;">
                    <i class="fas fa-user me-1 text-indigo"></i>
                    {{ $viaje->getRelation('ayudante')->persona->nombre ?? $viaje->ayudante_externo ?? 'N/A' }}
                </span>
            </div>
        </div>

        {{-- Tarjeta: Destinos --}}
        <div class="col-md-3">
            <div class="p-3 rounded shadow-sm h-100" style="background-color: #fff7ed; border-left: 4px solid #ea580c;">
                <label class="small fw-bold d-block text-uppercase mb-1" style="font-size: 11px; letter-spacing: 0.5px; color: #c2410c;">Destinos</label>
                @if(!empty($viaje->destino_ciudad))
                    <div class="d-block mt-2">
                        @foreach(explode(', ', $viaje->destino_ciudad) as $ciudad)
                            <span class="badge text-white fw-bold text-uppercase px-2 py-1 shadow-sm d-inline-block me-1 mb-1" style="background-color: #fd7e14; font-size: 10px; letter-spacing: 0.5px; vertical-align: middle;">
                                <i class="fas fa-map-marker-alt me-1"></i> {{ $ciudad }}
                            </span>
                        @endforeach
                    </div>
                @else
                    <span class="text-muted small fw-bold d-block mt-1">S/D</span>
                @endif
            </div>
        </div>

        <div class="col-12 my-2">
            <hr class="text-muted opacity-25">
        </div>

        {{-- ⚙️ BLOQUE 2: LOGICA POR TIPO --}}
        
        {{-- DIESEL Y MGO --}}
        @if(in_array($viaje->tipo_planificacion, [1, 2]))
            <div class="col-12">
                <div class="d-flex justify-content-between mb-2 align-items-center">
                    <h6 class="fw-black text-orange small text-uppercase mb-0">
                        <i class="fas fa-gas-pump me-1"></i> Destinos de Despacho
                    </h6>
                    <span class="badge bg-light text-dark border fw-bold">Sede: {{ $viaje->sede->nombre ?? 'N/A' }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="bg-light small text-uppercase fw-bold text-secondary">
                            <tr>
                                <th class="ps-3">Cliente / RIF</th>
                                <th>Dirección</th>
                                <th>Persona de Contacto</th>
                                <th>Persona Alternativa</th>
                                <th class="text-center" style="width: 15%;">Litros</th>
                                @if($viaje->tipo_planificacion == 1) 
                                    <th style="width: 30%;">Buque / IMO / Bandera</th> 
                                @endif
                            </tr>
                        </thead>
                        <tbody class="small">
                            @foreach($viaje->detalles as $detalle)
                            <tr class="align-middle">
                                <td class="ps-3">
                                    <strong>{{ $detalle->cliente->nombre ?? $viaje->nombre_cliente_externo ?? $detalle->otro_cliente ?? 'N/A'}}</strong><br>
                                    <small class="text-muted">{{ $detalle->cliente->rif ?? 'S/R' }}</small>
                                </td>
                                <td>{{ $detalle->direccion_despacho ?? $detalle->cliente->direccion_operativa ?? 'S/D' }}</td>
                                <td>{{ $detalle->cliente->contacto ?? 'N/A' }} {{ $detalle->cliente->telefono ?? 'N/A' }}</td>
                                <td>{{ $detalle->cliente->contacto_alt ?? 'N/A' }} {{ $detalle->cliente->telefono_alt ?? 'N/A' }}</td>
                                <td class="text-center fw-black text-dark fs-6">{{ number_format($detalle->litros_despachados ?? $detalle->litros ?? 0, 0) }} L</td>
                                
                                @if($viaje->tipo_planificacion == 1)
                                    <td>
                                        <div class="x-small p-1" style="font-size: 11px; line-height: 1.3;">
                                            <strong>Buque:</strong> {{ $detalle->buques->nombre ?? $detalle->buque_nombre_manual ?? 'N/A' }}<br>
                                            <strong>IMO:</strong> {{ $detalle->buques->imo ?? $detalle->imo ?? 'N/A' }}<br>
                                            <strong>Bandera:</strong> {{ $detalle->buques->bandera ?? $detalle->bandera ?? 'N/A' }}
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
                <label class="small text-muted fw-bold text-uppercase d-block mb-1">Ruta de Flete</label>
                <div class="bg-light p-2 rounded">
                    <p class="mb-1 text-dark"><strong>Salida:</strong> {{ $viaje->punto_salida }}</p>
                    <p class="mb-0 text-dark"><strong>Destino:</strong> {{ $viaje->punto_llegada }}</p>
                </div>
            </div>
            <div class="col-md-6 text-end">
                <label class="small text-muted fw-bold text-uppercase d-block mb-1">Carga</label>
                <div class="bg-light p-2 rounded">
                    <p class="mb-0 text-dark"><strong>Producto:</strong> {{ $viaje->producto_flete ?? 'No especificado' }}</p>
                </div>
            </div>

            @if($viaje->cliente_id && $viaje->cliente)
                <div class="col-12 mt-3">
                    <h6 class="fw-black text-orange small text-uppercase mb-2">
                        <i class="fas fa-user-tie me-1"></i> Cliente Asociado y Contactos
                    </h6>
                    <div class="p-3 rounded border shadow-sm" style="background-color: #f8fafc; border-left: 4px solid #fd7e14 !important;">
                        <div class="row g-2">
                            <div class="col-md-4 border-end border-2">
                                <small class="text-muted fw-bold text-uppercase d-block mb-1" style="font-size: 10px; letter-spacing: 0.5px;">Cliente / Empresa</small>
                                <strong class="text-dark d-block text-uppercase" style="font-size: 13px;">{{ $viaje->cliente->nombre }}</strong>
                                <small class="text-muted fw-bold">RIF: {{ $viaje->cliente->rif }}</small>
                            </div>
                            
                            <div class="col-md-4 border-end border-2 ps-md-3">
                                <small class="fw-bold text-uppercase d-block mb-1" style="font-size: 10px; letter-spacing: 0.5px; color: #16a34a;">Contacto Principal</small>
                                <span class="text-dark fw-bold d-block small">
                                    <i class="fas fa-user text-success me-1"></i> {{ $viaje->cliente->contacto ?? 'S/N' }}
                                </span>
                                <span class="text-muted small">
                                    <i class="fas fa-phone text-muted me-1"></i> {{ $viaje->cliente->telefono ?? 'S/T' }}
                                </span>
                            </div>

                            <div class="col-md-4 ps-md-3">
                                <small class="fw-bold text-uppercase d-block mb-1" style="font-size: 10px; letter-spacing: 0.5px; color: #4f46e5;">Contacto Alternativo</small>
                                <span class="text-dark fw-bold d-block small">
                                    <i class="far fa-user text-indigo me-1"></i> {{ $viaje->cliente->contacto_alt ?? 'S/N' }}
                                </span>
                                <span class="text-muted small">
                                    <i class="fas fa-phone text-muted me-1"></i> {{ $viaje->cliente->telefono_alt ?? 'S/T' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        {{-- COMPRAS --}}
        @elseif($viaje->tipo_planificacion == 4)
            @php 
                $compra = $viaje->compraCombustible->first(); 
            @endphp
            <div class="col-md-6">
                <label class="small text-muted fw-bold text-uppercase d-block mb-1">Planta y SAP</label>
                <div class="bg-light p-2 rounded">
                    <p class="mb-1 text-dark">
                        <strong>Planta:</strong> 
                        {{ $compra->planta->nombre ?? $viaje->planta->nombre ?? $viaje->nombre_cliente_externo ?? 'No asignado' }}
                    </p>
                    <p class="mb-0"><strong>Código SAP:</strong> <span class="badge bg-info text-dark fw-bold">{{ $viaje->codigo_sap ?? 'N/A' }}</span></p>
                </div>
            </div>
            <div class="col-md-6 text-end">
                <label class="small text-muted fw-bold text-uppercase d-block mb-1">Destino e Inventario</label>
                <div class="bg-light p-2 rounded">
                    <p class="mb-1 text-dark"><strong>Sede Destino:</strong> {{ $viaje->sede->nombre ?? 'N/A' }}</p>
                    <p class="mb-0 text-dark"><strong>Cantidad Compra:</strong> 
                        <strong class="text-primary">{{ $compra ? number_format($compra->cantidad_litros, 0) : number_format($viaje->litros, 0) }} L</strong>
                    </p>
                </div>
            </div>
        @endif

        {{-- 💬 OBSERVACIONES --}}
        <div class="col-12 mt-2">
            <div class="p-3 rounded border" style="background-color: #f8fafc;">
                <label class="small text-muted fw-bold text-uppercase d-block mb-1" style="font-size: 15px; letter-spacing: 0.5px;">Observaciones del Viaje</label>
                <p class="mb-0 small text-secondary" style="font-style: italic;">
                    @php
                        $obs = $viaje->detalles->pluck('observacion')->filter()->first();

                        if (!$obs && $viaje->tipo_planificacion == 4) {
                            $obs = $viaje->compraCombustible->first()->observaciones ?? null;
                        }

                        if (!$obs) {
                            $obs = $viaje->observacion;
                        }
                    @endphp

                    {{ $obs ?? 'Sin observaciones registradas.' }}
                </p>
            </div>
        </div>
    </div>
</div>

</body>
</html>