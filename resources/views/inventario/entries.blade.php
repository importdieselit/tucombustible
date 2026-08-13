@extends('layouts.app')

@section('title', 'Control de Inventario y Almacenes')

@push('styles')
<style>
    /* Estándares Corporativos */
    .bg-navy { background-color: #002855 !important; }
    .bg-orange { background-color: #ff6600 !important; }
    .text-navy { color: #002855 !important; }
    .text-orange { color: #ff6600 !important; }
    .border-orange { border-color: #ff6600 !important; }
    
    .card-kpi { border: none; border-radius: 8px; transition: transform 0.2s; }
    .card-kpi:hover { transform: translateY(-5px); }
    .stats-number { font-size: 1.8rem; font-weight: 800; line-height: 1; }
    .stats-label { font-size: 0.7rem; text-uppercase; font-weight: 700; color: #6c757d; letter-spacing: 0.5px; }
    
    .table-alerts thead { font-size: 0.7rem; background: #f8f9fa; }
    .badge-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 5px; }

    /* Estilos de navegación interna */
    .nav-pills .nav-link.active { background-color: #002855 !important; color: #fff !important; }
    .nav-pills .nav-link { color: #495057; font-weight: 600; font-size: 0.85rem; }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    
    {{-- SELECTOR MAESTRO DE ARTÍCULO --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body bg-navy rounded shadow-sm text-white p-4">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <h5 class="mb-2 mb-md-0 fw-bold"><i class="fas fa-boxes me-2 text-orange"></i> Gestión Operativa de Stock</h5>
                </div>
                <div class="col-md-8">
                    <select id="master_articulo_id" class="form-select form-select-lg border-0 shadow-sm" style="border-radius: 6px;">
                        <option value="">🔎 Busque o seleccione un artículo para inicializar el módulo...</option>
                        @foreach($articulos as $art)
                            <option value="{{ $art->id }}">{{ $art->codigo }} - {{ $art->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- ÁREA DE TRABAJO DINÁMICA (Oculta hasta seleccionar un ítem) --}}
    <div id="wrapper-operaciones" style="display: none;">
        <div class="row g-4">
            
            {{-- COLUMNA IZQUIERDA: DIAGNÓSTICO Y UBICACIONES DEL ITEM --}}
            <div class="col-xl-5 col-lg-6">
                <!-- Ficha del Item -->
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-body">
                        <span class="stats-label">Artículo Seleccionado</span>
                        <h4 id="txt_item_nombre" class="fw-bold text-navy mt-1 mb-0">-</h4>
                        <small id="txt_item_sku" class="text-muted font-monospace d-block mb-3">-</small>
                        
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="p-3 bg-light rounded border-start border-primary border-3">
                                    <span class="stats-label d-block">Disponibilidad General</span>
                                    <div id="kpi_stock_general" class="stats-number text-dark mt-1">0.00</div>
                                    <small class="text-muted x-small">Sin asignar a slots</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 bg-light rounded border-start border-orange border-3">
                                    <span class="stats-label d-block">Total en Almacén</span>
                                    <div id="kpi_stock_total" class="stats-number text-orange mt-1">0.00</div>
                                    <small class="text-muted x-small">Suma total de ubicaciones</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selector de Ubicaciones Poseídas -->
                <div class="card shadow-sm border-0 border-start border-orange border-3">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 text-navy fw-bold"><i class="fas fa-map-marker-alt text-orange me-2"></i> Stock Actual por Slot</h6>
                    </div>
                    <div class="card-body">
                        <label class="form-label text-muted small fw-bold">Slots donde este ítem posee existencias:</label>
                        <select id="selector_slots_poseidos" class="form-select mb-3">
                            <!-- Se llena vía AJAX -->
                        </select>
                        
                        <div class="d-flex align-items-center justify-content-between p-3 rounded bg-navy text-white shadow-sm">
                            <span class="fw-bold text-uppercase small" style="letter-spacing: 0.5px;">Existencia en el Slot:</span>
                            <span id="txt_cantidad_slot" class="fs-4 fw-black font-monospace">0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- COLUMNA DERECHA: PANEL DE ACCIONES E INDEXACIÓN DE MOVIMIENTOS --}}
            <div class="col-xl-7 col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white border-bottom-0 pt-3">
                        <ul class="nav nav-pills card-header-pills" id="pills-tab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="tab-compra" data-bs-toggle="pill" data-bs-target="#panel-compra" type="button" role="tab"><i class="fas fa-shopping-cart me-2"></i>1. Entrada (Compra)</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab-mover" data-bs-toggle="pill" data-bs-target="#panel-mover" type="button" role="tab"><i class="fas fa-exchange-alt me-2"></i>2. Mover entre Slots</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tab-asignar" data-bs-toggle="pill" data-bs-target="#panel-asignar" type="button" role="tab"><i class="fas fa-th-large me-2"></i>3. Asignar Stock General</button>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="card-body tab-content" id="pills-tabContent">
                        
                        {{-- PANEL 1: ENTRADA POR COMPRA --}}
                        <div class="tab-pane fade show active" id="panel-compra" role="tabpanel">
                            <form id="form-entrada-compra" class="row g-3">
                                @csrf
                                <input type="hidden" name="articulo_id" class="target-articulo-id">
                                <div class="col-12 bg-light p-2 rounded small text-muted border-start border-3 border-primary mb-2">
                                    Registra el ingreso físico de mercancía directo a un compartimiento/slot.
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Slot Destino</label>
                                    <select name="slot_id" class="form-select selector-todos-slots" required></select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">ID Orden de Compra (Opcional)</label>
                                    <input type="number" name="compra_id" class="form-control" placeholder="Ej: 1045">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-bold text-orange">Cantidad a Ingresar</label>
                                    <input type="number" step="0.01" name="cantidad" class="form-control form-control-lg border-orange font-monospace" required min="0.01">
                                </div>
                                <div class="col-12 pt-2">
                                    <button type="submit" class="btn bg-navy text-white fw-bold w-100 py-2"><i class="fas fa-save me-2"></i>Procesar Entrada Física</button>
                                </div>
                            </form>
                        </div>

                        {{-- PANEL 2: MOVER ENTRE SLOTS --}}
                        <div class="tab-pane fade" id="panel-mover" role="tabpanel">
                            <form id="form-movimiento-interno" class="row g-3">
                                @csrf
                                <input type="hidden" name="articulo_id" class="target-articulo-id">
                                <div class="col-12 bg-light p-2 rounded small text-muted border-start border-3 border-warning mb-2">
                                    Transfiere mercancía de una ubicación física a otra dentro del almacén.
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Slot Origen</label>
                                    <select name="slot_origen_id" id="mov_slot_origen" class="form-select" required></select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-navy">Slot Destino</label>
                                    <select name="slot_destino_id" class="form-select selector-todos-slots" required></select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-bold">Cantidad a Transferir</label>
                                    <input type="number" step="0.01" name="cantidad" id="mov_cantidad" class="form-control font-monospace" required min="0.01">
                                    <div class="form-text text-danger d-none" id="error-exceso-mov">La cantidad supera el stock del slot origen.</div>
                                </div>
                                <div class="col-12 pt-2">
                                    <button type="submit" id="btn-submit-mover" class="btn btn-warning fw-bold w-100 py-2 text-navy"><i class="fas fa-dolly me-2"></i>Ejecutar Reubicación</button>
                                </div>
                            </form>
                        </div>

                        {{-- PANEL 3: ASIGNAR DESDE DISPONIBILIDAD GENERAL --}}
                        <div class="tab-pane fade" id="panel-asignar" role="tabpanel">
                            <form id="form-asignar-general" class="row g-3">
                                @csrf
                                <input type="hidden" name="articulo_id" class="target-articulo-id">
                                <div class="col-12 bg-light p-2 rounded small text-muted border-start border-3 border-success mb-2">
                                    Toma existencias del "Stock General" (artículos globalizados sin ubicación fija) y ubícalos en un slot.
                                </div>
                                <div class="col-md-7">
                                    <label class="form-label fw-bold">Slot a Ubicar</label>
                                    <select name="slot_id" class="form-select selector-todos-slots" required></select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label fw-bold text-success">Cantidad a Indexar</label>
                                    <input type="number" step="0.01" name="cantidad" id="asig_cantidad" class="form-control font-monospace border-success" required min="0.01">
                                    <div class="form-text text-danger d-none" id="error-exceso-asig">La cantidad supera el stock general disponible.</div>
                                </div>
                                <div class="col-12 pt-2">
                                    <button type="submit" id="btn-submit-asignar" class="btn btn-success fw-bold w-100 py-2"><i class="fas fa-network-wired me-2"></i>Asignar e Indexar Slot</button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let currentItemData = null;

    // 1. ESCUCHA CAMBIO MAESTRO DE ARTÍCULO
    $('#master_articulo_id').on('change', function() {
        let articulo_id = $(this).val();
        if(!articulo_id) {
            $('#wrapper-operaciones').slideUp();
            return;
        }
        cargarDataArticulo(articulo_id);
    });

    // 2. FUNCIÓN DE CARGA ASÍNCRONA DESDE EL SERVIDOR
    function cargarDataArticulo(articulo_id) {
        $.ajax({
            url: `/inventario/articulos/${articulo_id}/detalles`, // Ajusta tu ruta según el router
            method: 'GET',
            success: function(res) {
                if(res.success) {
                    currentItemData = res;
                    
                    // Seteamos inputs ocultos de los formularios
                    $('.target-articulo-id').val(articulo_id);

                    // Pintamos Ficha Básica del Ítem
                    $('#txt_item_nombre').text(res.articulo.nombre);
                    $('#txt_item_sku').text(`SKU/CÓDIGO: ${res.articulo.codigo || 'N/A'}`);
                    $('#kpi_stock_general').text(parseFloat(res.stock_general).toFixed(2));
                    $('#kpi_stock_total').text(parseFloat(res.stock_total).toFixed(2));

                    // Llenamos el selector de Slots Poseídos (Donde hay stock actualmente)
                    let selectPoseidos = $('#selector_slots_poseidos').empty();
                    let selectOrigenMover = $('#mov_slot_origen').empty();
                    
                    if(res.slots_poseidos.length > 0) {
                        res.slots_poseidos.forEach(item => {
                            let opt = `<option value="${item.slot_id}" data-cant="${item.cantidad}">${item.codigo_posicion} (Actual: ${item.cantidad})</option>`;
                            selectPoseidos.append(opt);
                            selectOrigenMover.append(opt);
                        });
                        // Disparamos cálculo de la primera opción por defecto
                        selectPoseidos.trigger('change');
                    } else {
                        selectPoseidos.append('<option value="">[Sin ubicaciones asignadas actualmente]</option>');
                        selectOrigenMover.append('<option value="">[No hay stock que mover]</option>');
                        $('#txt_cantidad_slot').text('0.00');
                    }

                    // Llenamos los Selectores de Todos los Slots del Almacén (Destinos)
                    let selectDestinos = $('.selector-todos-slots').empty();
                    selectDestinos.append('<option value="">Seleccione ubicación destino...</option>');
                    res.todos_los_slots.forEach(slot => {
                        selectDestinos.append(`<option value="${slot.id}">${slot.codigo_posicion}</option>`);
                    });

                    // Mostramos la interfaz de trabajo limpia
                    $('#wrapper-operaciones').slideDown();
                }
            },
            error: function() { alert('Error crítico al recopilar métricas del artículo.'); }
        });
    }

    // 3. CAMBIO EN SELECTOR DE UBICACIÓN ACTUAL (Pinta cantidad en pantalla)
    $('#selector_slots_poseidos').on('change', function() {
        let selectedOpt = $(this).find('option:selected');
        let cant = parseFloat(selectedOpt.data('cant')) || 0;
        $('#txt_cantidad_slot').text(cant.toFixed(2));
    });

    // 4. VALIDACIONES DE VOLUMEN ON-TIME (Previene submits erróneos antes del controller)
    $('#mov_cantidad').on('input', function() {
        let disponible = parseFloat($('#mov_slot_origen').find('option:selected').data('cant')) || 0;
        let solicitado = parseFloat($(this).val()) || 0;
        if(solicitado > disponible) {
            $('#error-exceso-mov').removeClass('d-none');
            $('#btn-submit-mover').attr('disabled', true);
        } else {
            $('#error-exceso-mov').addClass('d-none');
            $('#btn-submit-mover').attr('disabled', false);
        }
    });

    $('#asig_cantidad').on('input', function() {
        let disponible = parseFloat(currentItemData?.stock_general) || 0;
        let solicitado = parseFloat($(this).val()) || 0;
        if(solicitado > disponible) {
            $('#error-exceso-asig').removeClass('d-none');
            $('#btn-submit-asignar').attr('disabled', true);
        } else {
            $('#error-exceso-asig').addClass('d-none');
            $('#btn-submit-asignar').attr('disabled', false);
        }
    });

    // 5. SUBMITS VIA AJAX (Post funcionales e independientes)
    
    // Formulario 1: Entrada por Compra
    $('#form-entrada-compra').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: "{{ route('inventario.entry') }}",
            method: "POST",
            data: $(this).serialize(),
            success: function(res) {
                alert(res.message);
                cargarDataArticulo($('#master_articulo_id').val()); // Recarga asíncrona
                $('#form-entrada-compra')[0].reset();
            },
            error: function() { alert('Error al procesar la entrada por compra.'); }
        });
    });

    // Formulario 2: Movimiento entre slots
    $('#form-movimiento-interno').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: "/inventario/movimiento-interno", // Define tu ruta en el web.php
            method: "POST",
            data: $(this).serialize(),
            success: function(res) {
                alert(res.message);
                cargarDataArticulo($('#master_articulo_id').val());
                $('#form-movimiento-interno')[0].reset();
            },
            error: function() { alert('Error al ejecutar reubicación.'); }
        });
    });

    // Formulario 3: Asignar Stock General
    $('#form-asignar-general').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: "/inventario/asignar-general", // Define tu ruta en el web.php
            method: "POST",
            data: $(this).serialize(),
            success: function(res) {
                alert(res.message);
                cargarDataArticulo($('#master_articulo_id').val());
                $('#form-asignar-general')[0].reset();
            },
            error: function() { alert('Error al asignar inventario general.'); }
        });
    });
});
</script>
@endpush