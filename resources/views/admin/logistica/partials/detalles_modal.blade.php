<div class="modal-header bg-dark text-white py-3">
    <h6 class="modal-title fw-black text-uppercase">
        <i class="fas fa-info-circle me-2 text-orange"></i> Detalles de Planificación V-{{ str_pad($viaje->id, 5, '0', STR_PAD_LEFT) }}
    </h6>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body p-4">
    <div class="row g-3">
        {{-- INFO GENERAL (COMÚN A TODOS) --}}
        <div class="col-md-4">
            <label class="small text-muted fw-bold text-uppercase d-block">Vehículo / Cisterna</label>
            <span class="fw-black">{{ $viaje->vehiculo->placa ?? $viaje->vehiculo_externo }}</span>
            @if($viaje->cisterna) <br><small class="text-muted">Cisterna: {{ $viaje->cisterna }}</small> @endif
        </div>
        <div class="col-md-4">
            <label class="small text-muted fw-bold text-uppercase d-block">Personal</label>
            <span class="fw-black">Chofer: {{ $viaje->chofer->nombre ?? 'N/A' }}</span><br>
            <small class="text-muted">Ayudante: {{ $viaje->ayudante->nombre ?? 'N/A' }}</small>
        </div>
        <div class="col-md-4 text-end">
            <label class="small text-muted fw-bold text-uppercase d-block">Estatus</label>
            <span class="badge bg-{{ $viaje->status == 'CANCELADO' ? 'danger' : 'success' }}">{{ $viaje->status }}</span>
        </div>

        <hr>

        {{-- LÓGICA POR TIPO --}}
        @if($viaje->tipo_planificacion == 1 || $viaje->tipo_planificacion == 2) {{-- DIESEL O MGO --}}
            <div class="col-12">
                <h6 class="fw-black text-uppercase small text-orange mb-3">Destinos de Despacho ({{ $viaje->tipo_planificacion == 1 ? 'Diesel' : 'MGO' }})</h6>
                <table class="table table-sm table-bordered">
                    <thead class="bg-light">
                        <tr class="small text-uppercase">
                            <th>Cliente / RIF</th>
                            <th>Dirección</th>
                            <th class="text-center">Litros</th>
                            @if($viaje->tipo_planificacion == 2) <th>Buque / IMO</th> @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($viaje->detalles as $detalle)
                        <tr>
                            <td>
                                <span class="d-block fw-bold">{{ $detalle->cliente->nombre }}</span>
                                <small class="text-muted">{{ $detalle->cliente->rif }}</small>
                            </td>
                            <td class="small">{{ $detalle->cliente->direccion_operativa }}</td>
                            <td class="text-center fw-black">{{ number_format($detalle->litros_despachados, 0) }}</td>
                            @if($viaje->tipo_planificacion == 2)
                                <td class="small">{{ $viaje->buque }} ({{ $viaje->imo }})</td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        @elseif($viaje->tipo_planificacion == 3) {{-- FLETES --}}
            <div class="col-md-6">
                <label class="small text-muted fw-bold text-uppercase d-block">Ruta</label>
                <p class="small mb-1"><strong>Desde:</strong> {{ $viaje->punto_salida }}</p>
                <p class="small"><strong>Hasta:</strong> {{ $viaje->punto_llegada }}</p>
            </div>
            <div class="col-md-6">
                <label class="small text-muted fw-bold text-uppercase d-block">Detalles Carga</label>
                <p class="small mb-1"><strong>Producto:</strong> {{ $viaje->producto }}</p>
                <p class="small"><strong>Volumen:</strong> {{ number_format($viaje->litros, 0) }} Lts</p>
            </div>

        @elseif($viaje->tipo_planificacion == 4) {{-- COMPRAS --}}
            <div class="col-md-6">
                <label class="small text-muted fw-bold text-uppercase d-block">Proveedor / SAP</label>
                <span class="fw-black">{{ $viaje->proveedor->nombre ?? 'N/A' }}</span><br>
                <small class="text-info fw-bold">SAP: {{ $viaje->codigo_sap }}</small>
            </div>
            <div class="col-md-6 text-end">
                <label class="small text-muted fw-bold text-uppercase d-block">Volumen Compra</label>
                <span class="h4 fw-black">{{ number_format($viaje->litros, 0) }} L</span>
            </div>
        @endif

        <div class="col-12 mt-3 bg-light p-3 rounded">
            <label class="small text-muted fw-bold text-uppercase d-block mb-1">Observaciones</label>
            <p class="mb-0 small italic">{{ $viaje->observaciones ?? 'Sin observaciones registradas.' }}</p>
        </div>
    </div>
</div>
<div class="modal-footer bg-light">
    <button type="button" class="btn btn-dark fw-bold text-uppercase small" data-bs-dismiss="modal">Cerrar</button>
</div>