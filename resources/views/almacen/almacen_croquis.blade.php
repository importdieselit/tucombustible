@extends('layouts.app')

@section('content')
<style>
    .grid-almacen {
        display: grid;
        grid-template-columns: repeat({{ $almacen->total_columnas_grid }}, 1fr);
        gap: 6px;
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }
    .celda-mapa {
        aspect-ratio: 1 / 1;
        min-width: 60px;
        border: 1px dashed #cbd5e1;
        border-radius: 4px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        font-weight: bold;
        transition: all 0.15s;
        background-color: #ffffff;
    }
    /* Estados de arrastre */
    .celda-preview-valida {
        background-color: #e0f2fe !important;
        border: 2px solid #0284c7 !important;
        color: #0369a1;
    }
    .celda-preview-invalida {
        background-color: #fee2e2 !important;
        border: 2px dashed #ef4444 !important;
    }
    /* Ficha Drag del panel lateral */
    .token-draggable {
        cursor: grab;
        padding: 15px;
        border-radius: 6px;
        border: 2px dashed #0d6efd;
        background-color: #ecf3fe;
        text-align: center;
        font-weight: bold;
        transition: transform 0.2s;
    }
    .token-draggable:active {
        cursor: grabbing;
        transform: scale(0.95);
    }

    /* Estilos para las celdas internas de la matriz del Rack */
.slot-rack {
    cursor: pointer;
    font-size: 11px;
    font-weight: bold;
    padding: 18px 10px !important;
    transition: all 0.2s ease;
    border: 2px solid #343a40 !important;
}
.slot-rack:hover {
    transform: scale(0.96);
    filter: brightness(1.2);
    box-shadow: inset 0 0 10px rgba(0,0,0,0.3);
}
.slot-libre {
    background-color: #198754 !important; /* Verde Corporativo */
    color: #ffffff !important;
}
.slot-ocupado {
    background-color: #FFFFFF !important; /* Rojo Alerta */
    color: #000000 !important;
}
.slot-seleccionado {
    border: 2px solid #ffffff !important;
    outline: 3px solid #0d6efd;
}

.progress {
    background-color: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}

/* Contenedor del track (fondo) */
.modern-progress-track {
    background-color: #e2e8f0; /* Gris muy claro */
    height: 8px;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 6px;
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
}

/* Barra de progreso (progreso real) */
.modern-progress-bar {
    height: 100%;
    border-radius: 10px;
    transition: width 0.6s cubic-bezier(0.22, 1, 0.36, 1); /* Animación suave */
    background: linear-gradient(90deg, #3b82f6, #60a5fa); /* Gradiente azul profesional */
}

/* Variaciones de color según nivel */
.bg-danger-gradient { background: linear-gradient(90deg, #ef4444, #f87171); }
.bg-warning-gradient { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.bg-success-gradient { background: linear-gradient(90deg, #10b981, #34d399); }

/* Estado cuando el Modo Reubicación está encendido */
.modo-edicion-activo .celda-mapa[data-codigo] {
    cursor: grab !important;
    animation: pulseBorder 2s infinite ease-in-out;
}
.modo-edicion-activo .celda-mapa[data-codigo]:active {
    cursor: grabbing !important;
}
.modo-edicion-activo .celda-mapa[data-codigo]:hover {
    filter: brightness(1.15);
    outline: 2px dashed #ffc107 !important;
}

#gridContenedorPrincipal {
    user-select: none; /* Evita selección de texto al hacer doble clic */
}
.modo-edicion-activo .celda-mapa[data-codigo] {
    cursor: grab !important;
    box-shadow: inset 0 0 0 2px rgba(255, 193, 7, 0.5); /* Resalta qué puedes mover */
}
.modo-edicion-activo .celda-mapa[data-codigo]:active {
    cursor: grabbing !important;
}

@keyframes pulseBorder {
    0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4); }
    70% { box-shadow: 0 0 0 6px rgba(255, 193, 7, 0); }
    100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
}
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-3">
            <div class="card shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-dark text-white text-uppercase py-3">
                    <h6 class="mb-0"><i class="fas fa-tools me-2"></i> Constructor WMS</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label font-weight-bold small">1. Tipo de Bloque</label>
                        <select class="form-select form-select-sm" id="cfgTipo">
                            <option value="ESTANTE">Estante / Rack Estructural</option>
                            <option value="GRANEL_LUBRICANTE">Zona Lubricantes (Granel)</option>
                            <option value="PISO_PALLET">Área de Suelo / Pallet (1x1)</option>
                            <option value="PASILLO">⚠️ Borrador / Pasillo Vacío</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-weight-bold small">2. Identificador Base</label>
                        <input type="text" class="form-control form-control-sm text-uppercase" id="cfgCodigo" value="EST-A" placeholder="Ej: RAC-1, TANQ-B">
                    </div>

                    <div id="propiedadesEstante">
                        <div class="mb-3">
                            <label class="form-label font-weight-bold small">3. Niveles de Altura (Stack 3D)</label>
                            <input type="number" class="form-control form-control-sm" id="cfgNiveles" min="1" value="3">
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-weight-bold small">4. Cantidad de Módulos (Largo en Suelo)</label>
                            <input type="number" class="form-control form-control-sm" id="cfgLargo" min="1" value="3">
                            <span class="text-muted d-block mt-1" style="font-size:10px;">Ocupará esta cantidad de cuadros en la grilla.</span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-weight-bold small">5. Orientación Física</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="cfgOrientacion" id="oriH" value="H" checked>
                                <label class="btn btn-outline-secondary btn-sm" for="oriH"><i class="fas fa-arrows-alt-h me-1"></i> Horizontal</label>
                                
                                <input type="radio" class="btn-check" name="cfgOrientacion" id="oriV" value="V">
                                <label class="btn btn-outline-secondary btn-sm" for="oriV"><i class="fas fa-arrows-alt-v me-1"></i> Vertical</label>
                            </div>
                        </div>
                    </div>

                    <div class="border-top pt-4 mt-3">
                        <label class="form-label d-block text-center font-weight-bold small text-primary mb-2">¡Arrastra este objeto al mapa!</label>
                        <div id="dragToken" class="token-draggable text-primary" draggable="true">
                            <i class="fas fa-hand-rock me-2"></i> <span id="lblTokenText">EST-A (Largo: 3)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 text-dark font-weight-bold"><i class="fas fa-warehouse me-2"></i> Distribución de Planta: {{ $almacen->nombre }}</h5>
                    <button class="btn btn-sm btn-outline-secondary font-weight-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalRedimensionar">
                        <i class="fas fa-expand-arrows-alt me-1"></i> Redimensionar
                    </button>
                    <div class="d-flex align-items-center gap-3">
                        <div class="form-check form-switch d-inline-block ms-4 bg-light border px-3 py-1 rounded shadow-sm">
                            <input class="form-check-input" type="checkbox" id="switchModoOperacion" style="cursor: pointer;">
                            <label class="form-check-label small font-weight-bold text-dark ms-1" for="switchModoOperacion" style="cursor: pointer;">
                                <i class="fas fa-pencil-ruler text-warning me-1"></i> Modo Edición / Mover
                            </label>
                        </div>
                        <span class="badge bg-secondary py-2 px-3">Cuadrícula: {{ $almacen->total_filas_grid }} x {{ $almacen->total_columnas_grid }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="grid-almacen" id="gridContenedorPrincipal">
                        @for ($y = 1; $y <= $almacen->total_filas_grid; $y++)
                            @for ($x = 1; $x <= $almacen->total_columnas_grid; $x++)
                                @php 
                                    $key = "$y-$x";
                                    $existe = $estructuras->has($key);
                                    $bloque = $existe ? $estructuras->get($key) : null;
                                    
                                    $claseColor = '';
                                    if($bloque) {
                                        $claseColor = match($bloque->tipo_estructura) {
                                            'ESTANTE' => 'bg-primary text-white border-solid',
                                            'GRANEL_LUBRICANTE' => 'bg-warning text-dark border-solid',
                                            'PISO_PALLET' => 'bg-info text-white border-solid',
                                            default => ''
                                        };
                                    }
                                @endphp

                                <div class="celda-mapa {{ $claseColor }}" 
                                    id="cell-{{ $y }}-{{ $x }}"
                                    data-x="{{ $x }}" 
                                    data-y="{{ $y }}"
                                    @if($bloque)
                                        data-codigo="{{ $bloque->codigo_bloque }}"
                                        data-tipo="{{ $bloque->tipo_estructura }}"
                                        data-niveles="{{ $bloque->cantidad_niveles }}"
                                    @endif>
                                    <span class="txt-codigo" style="font-size:11px;">{{ $bloque ? $bloque->codigo_bloque : '' }}</span>
                                    <small class="text-muted" style="font-size: 8px; opacity:0.5;">[{{$y}},{{$x}}]</small>
                                </div>
                            @endfor
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalInspector" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-top border-primary border-4 shadow-lg">
            <div class="modal-header bg-light py-3">
                <h5 class="modal-title text-dark font-weight-bold">
                    <i class="fas fa-th text-primary Corporate-icon me-2"></i> Vista Frontal Estructural: Estante <span id="insTituloEstante" class="text-primary"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="row h-100">
                    <div class="col-md-3 border-end bg-white p-3 d-flex flex-column" style="max-height: 70vh;">
                        <h6 class="text-uppercase font-weight-bold text-dark small mb-3"><i class="fas fa-search me-1"></i> Asignar Ítems</h6>
                        
                        <input type="text" id="inpBuscarItemModal" class="form-control form-control-sm mb-3" placeholder="Buscar código o descripción...">
                        
                        <div id="listaResultadosItems" class="flex-grow-1 overflow-auto mb-3 px-1">
                            <div class="text-center text-muted small mt-4" id="msgAyudaBuscar">
                                Escribe para buscar stock global disponible.
                            </div>
                            </div>

                        <div class="mt-auto">
                            <div id="zonaPapeleraLogistica" class="card border-danger text-danger text-center p-3" style="border: 2px dashed #dc3545 !important; background: #fff5f5;">
                                <i class="fas fa-trash-box fa-2x mb-2"></i>
                                <div class="small font-weight-bold text-uppercase">Papelera de Retorno</div>
                                <span style="font-size: 9px;" class="text-muted">Arrastra un slot aquí para vaciarlo. El stock volverá al sistema.</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-9 p-3 overflow-auto" style="max-height: 70vh;">
                        <h6 class="text-uppercase font-weight-bold text-secondary mb-3 small" style="letter-spacing: 0.5px;">Mapa de Ocupación (Elevación Frontal)</h6>
                        
                        <div class="table-responsive bg-dark p-3 rounded shadow-inner mb-4">
                            <table class="table table-bordered text-center align-middle mb-0 text-white border-secondary" id="tablaMatrizEstante">
                                </table>
                        </div>

                        <div class="d-flex gap-3 justify-content-end mb-4" style="font-size: 11px;">
                            <span><i class="fas fa-square text-success me-1"></i> Disponible (Arrastra ítems aquí)</span>
                            <span><i class="fas fa-square text-danger me-1"></i> Ocupado (Arrastra a otro slot o papelera)</span>
                        </div>

                        <div class="card bg-white border-0 shadow-sm mt-auto">
                            <div class="card-body p-3">
                                <h6 class="text-uppercase font-weight-bold text-dark mb-2 small"><i class="fas fa-barcode me-1"></i> Detalle del Slot Seleccionado</h6>
                                <div id="panelDetalleSlot">
                                    <p class="text-muted mb-0 py-2 italic font-weight-light" style="font-size: 12px;">
                                        <i class="fas fa-hand-pointer me-1"></i> Seleccione o arrastre sobre las celdas de arriba.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar Inspector</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRedimensionar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-top border-secondary border-4 shadow">
            <div class="modal-header bg-light py-2">
                <h6 class="modal-title font-weight-bold text-dark"><i class="fas fa-vector-square me-2 text-secondary"></i> Medidas del Plano</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formRedimensionar">
                <div class="modal-body bg-light-50">
                    <div class="alert alert-warning py-2 mb-3" style="font-size: 11px; line-height: 1.2;">
                        <i class="fas fa-exclamation-triangle"></i> Para <strong>reducir</strong> el tamaño, las celdas que van a ser recortadas deben estar completamente vacías (Pasillos).
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small font-weight-bold mb-1">Alto (Filas)</label>
                            <input type="number" class="form-control form-control-sm text-center font-weight-bold text-primary" id="inpRedimFilas" value="{{ $almacen->total_filas_grid }}" min="1">
                        </div>
                        <div class="col-6">
                            <label class="form-label small font-weight-bold mb-1">Ancho (Columnas)</label>
                            <input type="number" class="form-control form-control-sm text-center font-weight-bold text-primary" id="inpRedimColumnas" value="{{ $almacen->total_columnas_grid }}" min="1">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btnProcesarRedim">Aplicar Ajuste</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
 window.addEventListener('load', function() {
    
    // --- ESTADOS GLOBALES ---
    let modoReubicacion = false;
    let configArrastreActual = null; // Ayuda a previsualizar la sombra del tamaño exacto

    // --- 1. CONTROL DEL MODO DE OPERACIÓN (Switch) ---
    $('#switchModoOperacion').on('change', function() {
        modoReubicacion = $(this).is(':checked');
        
        if (modoReubicacion) {
            $('#gridContenedorPrincipal').addClass('modo-edicion-activo');
            $('.celda-mapa[data-codigo]').attr('draggable', 'true');
        } else {
            $('#gridContenedorPrincipal').removeClass('modo-edicion-activo');
            $('.celda-mapa[data-codigo]').attr('draggable', 'false');
            limpiarPreviews();
        }
    });

    // --- 2. LÓGICA DEL PANEL LATERAL (Configuración) ---
    $('#cfgTipo').on('change', function() {
        const val = $(this).val();
        
        if (val === 'PASILLO') {
            $('#propiedadesEstante').addClass('d-none');
            $('#lblTokenText').text('BORRAR ESPACIO');
            $('#dragToken').removeClass('text-primary text-info').addClass('text-danger');
        } else if (val === 'PISO_PALLET') {
            // Bloqueamos a 1x1
            $('#propiedadesEstante').addClass('d-none');
            $('#cfgNiveles').val(1);
            $('#cfgLargo').val(1);
            actualizarTokenTexto();
            $('#dragToken').removeClass('text-danger text-primary').addClass('text-info');
        } else {
            $('#propiedadesEstante').removeClass('d-none');
            actualizarTokenTexto();
            $('#dragToken').removeClass('text-danger text-info').addClass('text-primary');
        }
    });

    $('#cfgCodigo, #cfgLargo').on('input change', actualizarTokenTexto);
    $('input[name="cfgOrientacion"]').on('change', actualizarTokenTexto);

    function actualizarTokenTexto() {
        const cod = $('#cfgCodigo').val().toUpperCase() || 'S/C';
        const largo = $('#cfgLargo').val() || 1;
        const ori = $('input[name="cfgOrientacion"]:checked').val();
        $('#lblTokenText').text(`${cod} (${ori === 'H' ? 'Horiz' : 'Vert'} x${largo})`);
    }

    // --- 3. EVENTOS DRAG & DROP (Origen Lateral y Origen Mapa) ---
    const dragToken = document.getElementById('dragToken');
    
    // 3A. Arrastrar desde el panel lateral (NUEVO BLOQUE)
    dragToken.addEventListener('dragstart', function(e) {
        const config = {
            tipo: $('#cfgTipo').val(),
            codigo: $('#cfgCodigo').val(),
            niveles: $('#cfgNiveles').val(),
            largo: $('#cfgTipo').val() === 'PASILLO' ? 1 : $('#cfgLargo').val(),
            orientacion: $('input[name="cfgOrientacion"]:checked').val()
        };
        configArrastreActual = config;
        e.dataTransfer.setData('text/plain', JSON.stringify(config));
    });

    // 3B. Arrastrar un bloque YA EXISTENTE en el mapa (REUBICAR)
    $(document).on('dragstart', '.celda-mapa', function(e) {
        if (!modoReubicacion) { e.preventDefault(); return; }
        
        const codigo = $(this).attr('data-codigo');
        if (!codigo) { e.preventDefault(); return; }

        // Calcular la geometría del estante analizando sus celdas
        let celdas = $(`.celda-mapa[data-codigo="${codigo}"]`);
        let minX = 999, minY = 999, maxX = 0, maxY = 0;
        let tipo = $(this).attr('data-tipo');
        let niveles = $(this).attr('data-niveles');

        celdas.each(function() {
            let cx = parseInt($(this).data('x'));
            let cy = parseInt($(this).data('y'));
            if (cx < minX) minX = cx;
            if (cy < minY) minY = cy;
            if (cx > maxX) maxX = cx;
            if (cy > maxY) maxY = cy;
        });

        let orientacion = (minY === maxY) ? 'H' : 'V';
        let largo = (orientacion === 'H') ? (maxX - minX + 1) : (maxY - minY + 1);

        const config = {
            tipo: tipo,
            codigo: codigo,
            niveles: niveles,
            largo: largo,
            orientacion: orientacion
        };
        
        configArrastreActual = config; // Guarda para el preview exacto
        e.originalEvent.dataTransfer.setData('text/plain', JSON.stringify(config));
    });

    const totalFilas = {{ $almacen->total_filas_grid }};
    const totalColumnas = {{ $almacen->total_columnas_grid }};

    $('.celda-mapa').on('dragover', function(e) { e.preventDefault(); });

    // 3C. Dibujar la sombra previa antes de soltar (Usa configArrastreActual)
    $('.celda-mapa').on('dragenter', function(e) {
        e.preventDefault();
        limpiarPreviews();

        const startX = parseInt($(this).data('x'));
        const startY = parseInt($(this).data('y'));
        
        // Detectar si estamos moviendo uno existente o uno nuevo del panel
        const largo = configArrastreActual ? parseInt(configArrastreActual.largo) : 1;
        const ori = configArrastreActual ? configArrastreActual.orientacion : 'H';

        let esValido = true;
        let celdasDestino = [];

        for (let i = 0; i < largo; i++) {
            let x = (ori === 'H') ? (startX + i) : startX;
            let y = (ori === 'V') ? (startY + i) : startY;

            if (x > totalColumnas || y > totalFilas) {
                esValido = false;
            } else {
                celdasDestino.push(`#cell-${y}-${x}`);
            }
        }

        celdasDestino.forEach(selector => {
            $(selector).addClass(esValido ? 'celda-preview-valida' : 'celda-preview-invalida');
        });
    });

    // 3D. Soltar y Guardar
    $('.celda-mapa').on('drop', function(e) {
        e.preventDefault();
        limpiarPreviews();

        const startX = $(this).data('x');
        const startY = $(this).data('y');
        
        try {
            const data = JSON.parse(e.originalEvent.dataTransfer.getData('text/plain'));
            ejecutarTransaccionLayout(startX, startY, data);
            configArrastreActual = null; // Reset
        } catch (err) {
            console.error("Error procesando drop", err);
        }
    });

    // --- 4. EVENTO DOBLE CLIC (Rotar 90 Grados) ---
    $(document).on('dblclick', '.celda-mapa', function() {
        if (!modoReubicacion) return; // Solo rota si estamos en modo edición

        const codigo = $(this).attr('data-codigo');
        if (!codigo) return;

        let celdas = $(`.celda-mapa[data-codigo="${codigo}"]`);
        let minX = 999, minY = 999, maxX = 0, maxY = 0;
        let tipo = $(this).attr('data-tipo');
        let niveles = $(this).attr('data-niveles');

        celdas.each(function() {
            let cx = parseInt($(this).data('x'));
            let cy = parseInt($(this).data('y'));
            if (cx < minX) minX = cx;
            if (cy < minY) minY = cy;
            if (cx > maxX) maxX = cx;
            if (cy > maxY) maxY = cy;
        });

        let orientacionActual = (minY === maxY) ? 'H' : 'V';
        let nuevaOrientacion = (orientacionActual === 'H') ? 'V' : 'H';
        let largo = (orientacionActual === 'H') ? (maxX - minX + 1) : (maxY - minY + 1);

        const dataRotacion = {
            tipo: tipo,
            codigo: codigo,
            niveles: niveles,
            largo: largo,
            orientacion: nuevaOrientacion
        };

        // Gira usando la esquina superior izquierda del bloque actual
        ejecutarTransaccionLayout(minX, minY, dataRotacion);
    });

    // --- 5. FUNCIÓN CORE: GUARDADO AJAX UNIFICADO ---
    function ejecutarTransaccionLayout(x, y, data) {
        $.ajax({
            url: "{{ route('almacen.layout.guardar_drag') }}",
            method: "POST",
            data: {
                almacen_id: "{{ $almacen->id }}",
                start_x: x,
                start_y: y,
                tipo_estructura: data.tipo,
                codigo_bloque: data.codigo,
                cantidad_niveles: data.niveles,
                largo_secciones: data.largo,
                orientacion: data.orientacion
            },
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    
                    // A. LIMPIEZA ABSOLUTA Y ESTÁNDAR DE RASTROS VIEJOS
                    // 1. Si el controlador envió celdas explícitas para borrar, las limpiamos primero
                    if (response.celdas_borradas && response.celdas_borradas.length > 0) {
                        response.celdas_borradas.forEach(celda => {
                            const selectorBorrar = `#cell-${celda.y}-${celda.x}`;
                            $(selectorBorrar).removeClass('bg-primary bg-warning bg-info text-white text-dark');
                            $(selectorBorrar).find('.txt-codigo').text('');
                            $(selectorBorrar).removeAttr('data-codigo data-tipo data-niveles').attr('draggable', 'false');
                        });
                    }

                    // 2. Limpieza por código de bloque: buscamos todas las celdas que tenían este código antes del movimiento
                    $(`.celda-mapa[data-codigo="${response.codigo}"]`).each(function() {
                        $(this).removeClass('bg-primary bg-warning bg-info text-white text-dark');
                        $(this).find('.txt-codigo').text('');
                        $(this).removeAttr('data-codigo data-tipo data-niveles').attr('draggable', 'false');
                    });


                    // B. PINTAR E INDEXAR EN LA NUEVA UBICACIÓN (Garantizando consistencia)
                    response.celdas.forEach(celda => {
                        const selector = `#cell-${celda.y}-${celda.x}`;
                        
                        // Limpieza preventiva sobre la celda destino antes de pintar
                        $(selector).removeClass('bg-primary bg-warning bg-info text-white text-dark');
                        
                        if (response.tipo === 'ESTANTE') {
                            $(selector).addClass('bg-primary text-white');
                            $(selector).find('.txt-codigo').text(response.codigo);
                            $(selector).attr('data-codigo', response.codigo).attr('data-tipo', response.tipo).attr('data-niveles', data.niveles);
                            if (modoReubicacion) $(selector).attr('draggable', 'true');
                        } 
                        else if (response.tipo === 'GRANEL_LUBRICANTE') {
                            $(selector).addClass('bg-warning text-dark');
                            $(selector).find('.txt-codigo').text(response.codigo);
                            $(selector).attr('data-codigo', response.codigo).attr('data-tipo', response.tipo).attr('data-niveles', data.niveles);
                            if (modoReubicacion) $(selector).attr('draggable', 'true');
                        } 
                        else if (response.tipo === 'PISO_PALLET') {
                            $(selector).addClass('bg-info text-dark');
                            $(selector).find('.txt-codigo').text(response.codigo);
                            // Los pallets en base de datos e interfaz gráfica se configuran con nivel 1
                            $(selector).attr('data-codigo', response.codigo).attr('data-tipo', response.tipo).attr('data-niveles', 1);
                            if (modoReubicacion) $(selector).attr('draggable', 'true');
                        } 
                        else {
                            // En caso de pasar a PASILLO o limpieza
                            $(selector).find('.txt-codigo').text('');
                            $(selector).removeAttr('data-codigo data-tipo data-niveles').attr('draggable', 'false');
                        }
                    });
                }
            },
            error: function(xhr) {
                alert('Movimiento inválido: ' + (xhr.responseJSON?.error || 'Límites excedidos del plano o colisión.'));
            }
        });
    }

    function limpiarPreviews() {
        $('.celda-mapa').removeClass('celda-preview-valida celda-preview-invalida');
    }

    // --- 6. MODO AUDITORÍA: CLIC SIMPLE PARA VER EL ESTANTE ---
    let cacheUbicacionesEstante = {};

    $(document).on('click', '.celda-mapa', function(e) {
        // BLINDAJE: Si el modo edición está activo, no abre el modal
        if (modoReubicacion) {
            e.preventDefault();
            return;
        }

        const cell = $(this);
        const x = cell.data('x');
        const y = cell.data('y');

        if (!cell.find('.txt-codigo').text().trim()) return;

        const modalIns = new bootstrap.Modal(document.getElementById('modalInspector'));

        $.ajax({
            url: "{{ route('almacen.layout.inspeccionar') }}",
            method: "POST",
            data: { almacen_id: "{{ $almacen->id }}", coord_x: x, coord_y: y },
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            beforeSend: function() {
                $('#panelDetalleSlot').html('<div class="text-muted py-2"><i class="fas fa-spinner fa-spin me-1"></i> Consultando estante completo en planta...</div>');
                $('#tablaMatrizEstante').html('');
            },
            success: function(response) {
                if (!response.success) return;

                $('#insTituloEstante').text(response.estante);
                cacheUbicacionesEstante = response.matriz; 

                let tablaHtml = '<thead><tr><th class="bg-secondary text-light small" style="width:100px;">Nivel \\ Posición</th>';
                response.posiciones.forEach(p => {
                    tablaHtml += `<th class="bg-secondary text-light small">Posición ${p}</th>`;
                });
                tablaHtml += '</tr></thead><tbody>';

                response.niveles.forEach(n => {
                    tablaHtml += `<tr><td class="bg-dark text-light font-weight-bold small">Nivel ${n}</td>`;
    
                        response.posiciones.forEach(p => {
                            const key = `${n}-${p}`;
                            const slot = cacheUbicacionesEstante[key];
                            
                            if (slot) {
                                const ocupado = slot.ocupado;
                                const claseEstado = ocupado ? 'slot-ocupado' : 'slot-libre';
                                
                                // Lógica de visualización de capacidad dinámica
                                let barraHtml = '';
                                let infoAdicional = '';
                                
                                if (ocupado && slot.inventario) {
                                    // Si tienes el campo capacidad_asignada en tu base de datos
                                    const capacidad = slot.inventario.capacidad_asignada || slot.total_articulos; 
                                    const porcentaje = (slot.total_articulos / capacidad) * 100;

                                    //const color = porcentaje > 90 ? 'bg-danger' : (porcentaje > 70 ? 'bg-warning' : 'bg-success');
                                    const colorClass = porcentaje > 90 ? 'bg-danger-gradient' : (porcentaje > 70 ? 'bg-warning-gradient' : 'bg-success-gradient');

                                    barraHtml = `
                                        <div class="modern-progress-track">
                                            <div class="modern-progress-bar ${colorClass}" style="width: ${Math.min(porcentaje, 100)}%"></div>
                                        </div>
                                        <div class="d-flex justify-content-between mt-1">
                                            <span style="font-size: 8px; font-weight: 600; color: #64748b;">OCUPACIÓN</span>
                                            <span style="font-size: 8px; font-weight: 700; color: #1e293b;">${Math.round(porcentaje)}%</span>
                                        </div>
                                    `;
                                    
                                    infoAdicional = `
                                        <div class="small text-truncate" style="font-size:9px;" title="${slot.inventario.producto}">
                                            ${slot.inventario.sku}
                                        </div>
                                        <div class="font-weight-bold" style="font-size:10px;">${slot.total_articulos} / ${capacidad}</div>
                                    `;
                                } else {
                                    infoAdicional = '<small style="font-size:9px;" class="text-muted">Libre</small>';
                                }

                                tablaHtml += `
                                    <td class="slot-rack slot-receptor-drop ${claseEstado}" 
                                        data-key="${key}" 
                                        data-ubicacion-id="${slot.id}"
                                        ${ocupado ? 'draggable="true"' : ''}>
                                        N${n}-P${p}
                                        ${infoAdicional}
                                        ${barraHtml}
                                    </td>
                                `;
                            } else {
                                tablaHtml += '<td class="bg-dark text-muted text-center" style="opacity:0.3;">-</td>';
                            }
                        });
                        tablaHtml += '</tr>';
                    });
                tablaHtml += '</tbody>';

                $('#tablaMatrizEstante').html(tablaHtml);
                modalIns.show();
            },
            error: function() {
                alert('Error crítico al procesar la radiografía del estante.');
            }
        });
    });

    $(document).on('click', '.slot-rack', function() {
        $('.slot-rack').removeClass('slot-seleccionado');
        $(this).addClass('slot-seleccionado');

        const key = $(this).data('key');
        const slot = cacheUbicacionesEstante[key];

        if (!slot) return;

        let htmlDetalle = `
            <div class="p-3 bg-white border rounded shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                    <span class="font-weight-bold text-dark" style="font-size:13px;">Código Único: <span class="text-primary">${slot.codigo_completo}</span></span>
                    <span class="badge ${slot.ocupado ? 'bg-danger' : 'bg-success'} py-1 px-3">${slot.ocupado ? 'Ocupado' : 'Disponible'}</span>
                </div>
        `;

        if (slot.ocupado && slot.total_articulos > 0) {
            htmlDetalle += '<div class="table-responsive"><table class="table table-sm table-striped mb-0 align-middle" style="font-size:12px;">';
            htmlDetalle += '<thead class="table-light"><tr><th>SKU / Referencia</th><th>Descripción del Artículo</th><th>Lote</th><th class="text-end">Cant.</th></tr></thead><tbody>';
            
            //slot.inventario.forEach(item => {
              
            htmlDetalle += `
                    <tr>
                        <td><strong class="text-dark">${slot.inventario.sku}</strong></td>
                        <td>${slot.inventario.producto}</td>
                        <td><span class="badge bg-light text-dark border">${slot.inventario.lote}</span></td>
                        <td class="text-end font-weight-bold text-primary">${slot.inventario.cantidad}</td>
                    </tr>
                `;
            //});
            htmlDetalle += '</tbody></table></div>';
        } else {
            htmlDetalle += `
                <div class="text-center py-3 text-success">
                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                    <p class="mb-0 font-weight-bold" style="font-size:13px;">Espacio 100% Disponible</p>
                    <small class="text-muted">Apto para recepciones, transferencias o almacenamiento en lote.</small>
                </div>
            `;
        }
        htmlDetalle += '</div>';
        $('#panelDetalleSlot').html(htmlDetalle);
    });

    $('#formRedimensionar').on('submit', function(e) {
        e.preventDefault();
        
        const btnSubmit = $('#btnProcesarRedim');
        const filas = $('#inpRedimFilas').val();
        const columnas = $('#inpRedimColumnas').val();

        $.ajax({
            url: "{{ route('almacen.layout.redimensionar') }}",
            method: "POST",
            data: {
                almacen_id: "{{ $almacen->id }}",
                filas: filas,
                columnas: columnas
            },
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            beforeSend: function() {
                btnSubmit.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
            },
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                }
            },
            error: function(xhr) {
                btnSubmit.prop('disabled', false).html('Aplicar Ajuste');
                alert('Aviso del Sistema:\n\n' + (xhr.responseJSON?.error || 'Error de conexión.'));
            }
        });
    });

    // ==========================================
    // MÓDULO: TRÁFICO LOGÍSTICO E INVENTARIO
    // ==========================================
    let slotActivoContexto = null; // Guarda temporalmente la celda clickeada en el croquis

    // 1. Buscador en Vivo
    let typingTimer;
    $('#inpBuscarItemModal').on('keyup', function () {
        clearTimeout(typingTimer);
        const termino = $(this).val();
        if (termino.length >= 2) {
            typingTimer = setTimeout(() => {
                $.post("{{ route('almacen.layout.buscar_items') }}", { termino: termino, _token: '{{ csrf_token() }}' }, function(res) {
                    $('#msgAyudaBuscar').hide();
                    $('#listaResultadosItems').empty();
                    res.items.forEach(item => {
                        $('#listaResultadosItems').append(`
                            <div class="item-resultado-drag p-2 mb-2 border border-primary rounded bg-light" 
                                 draggable="true" 
                                 data-id="${item.id}" 
                                 data-codigo="${item.codigo}" 
                                 data-stock="${item.existencia}"
                                 style="cursor: grab;">
                                <div class="fw-bold small text-primary">${item.codigo}</div>
                                <div style="font-size: 10px;" class="text-truncate" title="${item.descripcion}">${item.descripcion}</div>
                                <div style="font-size: 11px;" class="text-dark mt-1">Stock Disp: <b>${item.existencia}</b></div>
                            </div>
                        `);
                    });
                });
            }, 500);
        }
    });

    // 2. Eventos Drag & Drop: Desde Panel Lateral a Slot
    $(document).on('dragstart', '.item-resultado-drag', function(e) {
        const payload = { tipo: 'NUEVO_ITEM', id: $(this).data('id'), stock: $(this).data('stock') };
        e.originalEvent.dataTransfer.setData('text/plain', JSON.stringify(payload));
    });

    // 3. Eventos Drag & Drop: Desde Slot a Slot o Papelera
    $(document).on('dragstart', '.slot-ocupado', function(e) {
        const payload = { tipo: 'REUBICAR_SLOT', ubicacion_id: $(this).data('ubicacion-id') };
        e.originalEvent.dataTransfer.setData('text/plain', JSON.stringify(payload));
    });

    // 4. Recepción en los Slots (Estantes)
    $(document).on('dragover', '.slot-receptor-drop', function(e) {
        e.preventDefault();
        $(this).css('border', '2px dashed #ffc107');
    });

    $(document).on('dragleave', '.slot-receptor-drop', function(e) {
        $(this).css('border', '');
    });

    $(document).on('drop', '.slot-receptor-drop', function(e) {
        e.preventDefault();
        $(this).css('border', '');
        
        const ubicacionDestinoId = $(this).data('ubicacion-id');
        const data = JSON.parse(e.originalEvent.dataTransfer.getData('text/plain'));

        if (data.tipo === 'NUEVO_ITEM') {
            // Pedimos cantidad a asignar
            let cant = prompt(`¿Cuántas unidades desea asignar a esta ubicación? (Máximo disponible: ${data.stock})`);
            if (cant && parseFloat(cant) > 0) {
                $.post("{{ route('almacen.layout.asignar_item') }}", {
                    _token: '{{ csrf_token() }}',
                    ubicacion_id: ubicacionDestinoId,
                    inventario_id: data.id,
                    cantidad: cant
                }, function(res) {
                    if(res.success) {
                        recargarMatrizActual();
                        if(res.alerta) alert(`Alerta de Capacidad: ${res.alerta}. Ocupación al ${res.porcentaje}%`);
                    }
                }).fail(err => alert(err.responseJSON.error));
            }
        } else if (data.tipo === 'REUBICAR_SLOT') {
            if (data.ubicacion_id === ubicacionDestinoId) return; // Mismo slot
            $.post("{{ route('almacen.layout.reubicar_item') }}", {
                _token: '{{ csrf_token() }}',
                origen_id: data.ubicacion_id,
                destino_id: ubicacionDestinoId
            }, function(res) {
                if(res.success) recargarMatrizActual();
            }).fail(err => alert("Error al reubicar."));
        }
    });

    // 5. Recepción en la Papelera
    const zonaPapelera = document.getElementById('zonaPapeleraLogistica');
    
    zonaPapelera.addEventListener('dragover', e => { e.preventDefault(); $(zonaPapelera).addClass('bg-danger text-white'); });
    zonaPapelera.addEventListener('dragleave', e => { $(zonaPapelera).removeClass('bg-danger text-white'); });
    
    zonaPapelera.addEventListener('drop', function(e) {
        e.preventDefault();
        $(zonaPapelera).removeClass('bg-danger text-white');
        
        try {
            const data = JSON.parse(e.dataTransfer.getData('text/plain'));
            if (data.tipo === 'REUBICAR_SLOT') {
                if(confirm("¿Confirmas que deseas vaciar esta ubicación? Todo el stock retornará al inventario global.")) {
                    $.post("{{ route('almacen.layout.vaciar_slot') }}", {
                        _token: '{{ csrf_token() }}',
                        ubicacion_id: data.ubicacion_id
                    }, function(res) {
                        alert('vaciado con éxito.');
                        if(res.success) recargarMatrizActual();
                    });
                }
            } else {
                alert("Para vaciar un slot, debes arrastrar la celda ocupada desde la matriz de la derecha hacia esta papelera.");
            }
        } catch(err) { console.error(err); }
    });

    // Función auxiliar para refrescar el visual del modal en caliente
    function recargarMatrizActual() {
        // Obtenemos las coordenadas X y Y del título o variable temporal
        const titulo = $('#insTituloEstante').text(); // Ej: EST-A
        // Disparamos un click simulado en la grilla original para que el AJAX principal refresque todo
        $(`.celda-mapa[data-codigo="${titulo}"]`).first().trigger('click');
        $('#inpBuscarItemModal').trigger('keyup'); // Refresca stocks del buscador
    }

});
</script>
@endpush
@endsection
