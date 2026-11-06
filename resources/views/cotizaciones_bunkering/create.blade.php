@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Nueva Cotización Bunkering / STS</h1>
    
    <form action="{{ route('cotizacionesBunkering.store') }}" method="POST">
        @csrf

        <!-- Datos Generales y Selector de Costos -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">Datos Generales y Selector de Costos</div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">Compañía / Buyer</label>
                        <input type="text" name="pagador" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">Nombre del Buque</label>
                        <input type="text" name="nombre_buque" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">Bandera del Buque</label>
                        <input type="text" name="bandera_buque" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">IMO</label>
                        <input type="text" name="imo_buque" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">Agencia Naviera</label>
                        <input type="text" name="agencia_naviera" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">Selector de Costos (Aplica solo al Producto)</label>
                        <select name="selector_costos" id="selector_costos" class="form-select" required>
                            <option value="1">Costo 1 (+30%)</option>
                            <option value="2">Costo 2 (+20%)</option>
                            <option value="3">Costo 3 (+10%)</option>
                            <option value="4">Costo 4 (+0%)</option>
                            <option value="5">Personalizado</option>
                        </select>
                        <input type="number" step="1" name="selector_costos_personalizado" id="selector_costos_personalizado" class="form-control d-none mt-1" placeholder="Ingrese % de recargo personalizado">
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Centro de Suministro / Puerto</label>
                        <select name="punto_entrega" id="centro_suministro" class="form-control" required>
                            <option value="">Seleccione Centro de Suministro...</option>
                           @foreach($centrosSuministro as $centro)
                                @php
                                    $procs = is_array($centro->procedimiento) ? $centro->procedimiento : json_decode($centro->procedimiento ?? '[]', true);
                                @endphp
                                <option value="{{ $centro->nombre }}" data-procedimiento="{{ implode(',', $procs) }}">
                                    {{ $centro->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Lugar de Entrega Específico</label>
                        <input type="text" name="lugar_entrega" class="form-control" placeholder="Ej: Fondeadero / Muelle">
                    </div>
                    <div class="col-md-4">
                            <label class="form-label">Método de Entrega</label>
                            <select name="metodo_despacho" id="metodo_despacho" class="form-control" disabled required>
                                <option value="">Primero seleccione un centro...</option>
                            </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Fecha Entrega</label>
                        <input type="date" name="fecha_entrega" class="form-control">
                    </div>
                </div>
                <div class="row g-3 mb-3 mt-1">
                    <div class="col-md-12">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="3"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Productos y Delivery -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                <span>Productos y Tarifas de Delivery</span>
                <button type="button" class="btn btn-light btn-sm" id="btn-add-row"><i class="fas fa-plus"></i> Agregar Producto</button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="tabla-productos">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 30%;">PRODUCTO / PRODUCT</th>
                                <th style="width: 15%;">VOLUMEN MT</th>
                                <th style="width: 15%;">USD$ / TM (Ajustado)</th>
                                <th style="width: 20%;">DELIVERY COST USD</th>
                                <th style="width: 15%;">TOTAL COST USD</th>
                                <th style="width: 5%;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Fila Producto 0 -->
                            <tr class="row-producto" data-index="0">
                                <td>
                                    <select name="productos[0][tipo]" class="form-select select-producto" required>
                                        <option value="">Seleccione...</option>
                                        @foreach($productos as $producto)
                                            <option value="{{ $producto->nombre }}" data-precio="{{ $producto->precio }}">{{ $producto->nombre }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="productos[0][obs]" class="form-control mt-1" placeholder="Observación opcional del producto">
                                </td>
                                <td>
                                    <input type="number" step="0.001" name="productos[0][tm]" class="form-control input-tm" placeholder="0.00" required>
                                </td>
                                <td>
                                    <input type="number" step="0.01" name="productos[0][precio]" class="form-control input-precio" placeholder="0.00" required>
                                </td>
                                <td class="text-center align-middle">-</td>
                                <td>
                                    <input type="text" class="form-control bg-light text-end fw-bold input-subtotal-prod" readonly value="$0,00">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm" disabled><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <!-- Fila Delivery Fee Renglón Separado 0 -->
                            <tr class="row-delivery bg-light" data-index="0">
                                <td class="ps-4 text-muted fst-italic">
                                    DELIVERY FEE
                                    <div class="campos-abiertos d-none mt-1">
                                        <input type="number" step="0.01" name="productos[0][del_tm]" class="form-control form-control-sm input-del-tm mb-1" placeholder="Tarifa x TM">
                                        <input type="number" step="0.01" name="productos[0][del_comp]" class="form-control form-control-sm input-del-comp" placeholder="Tarifa Completa">
                                    </div>
                                </td>
                                <td class="text-center text-muted">-</td>
                                <td class="text-center text-muted">-</td>
                                <td class="text-center fw-bold text-warning input-delivery-rate">
                                    $8.000
                                </td>
                                <td>
                                    <input type="text" class="form-control bg-light text-end text-warning fw-bold input-delivery-monto" readonly value="$0,00">
                                </td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="card bg-light p-3 mt-3">
                    <div class="row text-end">
                        <div class="col-12">
                            <h4 class="mb-0 text-success">TOTAL GENERAL: <span id="lbl-total-general">$0,00</span></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-5">
            <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-save me-1"></i> Guardar y Generar Cotización</button>
        </div>
    </form>
</div>

<script>
let rowIndex = 1;

// Generar options de productos asegurando que se inyecten correctamente en JS
let productosOptions = `<option value="">Seleccione...</option>@foreach($productos as $producto)<option value="{{ $producto->nombre }}" data-precio="{{ $producto->precio }}">{{ $producto->nombre }}</option>@endforeach`;

// Control de dependencias para Centro de Suministro -> Método de Despacho
document.getElementById('centro_suministro').addEventListener('change', function() {
    let selectedOption = this.options[this.selectedIndex];
    let metodoDespachoSelect = document.getElementById('metodo_despacho');
    
    // Limpiar el select de método de despacho
    metodoDespachoSelect.innerHTML = '<option value="">Seleccione método...</option>';
    
    if (!this.value) {
        metodoDespachoSelect.setAttribute('disabled', 'disabled');
        return;
    }

    // Habilitar el select
    metodoDespachoSelect.removeAttribute('disabled');
    
    // Obtener los procedimientos permitidos desde el atributo data-procedimiento (ej: "TTS,STS,ALONGSITE")
    let rawProcedimientos = selectedOption.getAttribute('data-procedimiento') || '';
    
    if (rawProcedimientos.trim() !== '') {
        let procedimientosPermitidos = rawProcedimientos.split(',');

        // Mapeo opcional para mostrar nombres más amigables si lo deseas, o usar el valor directo de la BD
        let labelsProcedimiento = {
            'TTS': 'TRUCK TO SHIP (TTS)',
            'STS': 'SHIP TO SHIP (STS)',
            'ALONGSITE': 'ALONGSITE'
        };

        // Inyectar dinámicamente solo las opciones permitidas para este centro
        procedimientosPermitidos.forEach(proc => {
            let valorLimpio = proc.trim();
            if (valorLimpio !== '') {
                let textoMostrar = labelsProcedimiento[valorLimpio] || valorLimpio;
                metodoDespachoSelect.innerHTML += `<option value="${valorLimpio}">${textoMostrar}</option>`;
            }
        });
    }
});

// Selector Costos personalizado toggle
document.getElementById('selector_costos').addEventListener('change', function() {
    let customInput = document.getElementById('selector_costos_personalizado');
    if (this.value === '') {
        customInput.classList.remove('d-none');
    } else {
        customInput.classList.add('d-none');
        customInput.value = '';
    }
    calcularTotales();
});

// Agregar nueva fila de producto
document.getElementById('btn-add-row').addEventListener('click', function() {
    let tbody = document.querySelector('#tabla-productos tbody');
    let html = `
        <tr class="row-producto" data-index="${rowIndex}">
            <td>
                <select name="productos[${rowIndex}][tipo]" class="form-select select-producto" required>
                    ${productosOptions}
                </select>
                <input type="text" name="productos[${rowIndex}][obs]" class="form-control mt-1" placeholder="Observación opcional del producto">
            </td>
            <td>
                <input type="number" step="0.001" name="productos[${rowIndex}][tm]" class="form-control input-tm" placeholder="0.00" required>
            </td>
            <td>
                <input type="number" step="0.01" name="productos[${rowIndex}][precio]" class="form-control input-precio" placeholder="0.00" required>
            </td>
            <td class="text-center align-middle">-</td>
            <td>
                <input type="text" class="form-control bg-light text-end fw-bold input-subtotal-prod" readonly value="$0,00">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm btn-delete"><i class="fas fa-trash"></i></button>
            </td>
        </tr>
        <tr class="row-delivery bg-light" data-index="${rowIndex}">
            <td class="ps-4 text-muted fst-italic">
                DELIVERY FEE
                <div class="campos-abiertos d-none mt-1">
                    <input type="number" step="0.01" name="productos[${rowIndex}][del_tm]" class="form-control form-control-sm input-del-tm mb-1" placeholder="Tarifa x TM">
                    <input type="number" step="0.01" name="productos[${rowIndex}][del_comp]" class="form-control form-control-sm input-del-comp" placeholder="Tarifa Completa">
                </div>
            </td>
            <td class="text-center text-muted">-</td>
            <td class="text-center text-muted">-</td>
            <td class="text-center fw-bold text-warning input-delivery-rate">
                $8.000
            </td>
            <td>
                <input type="text" class="form-control bg-light text-end text-warning fw-bold input-delivery-monto" readonly value="$0,00">
            </td>
            <td></td>
        </tr>
    `;
    tbody.insertAdjacentHTML('beforeend', html);
    rowIndex++;
});

// Asignación automática de precio al seleccionar el producto (tanto estático como dinámico)
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('select-producto')) {
        let rowProd = e.target.closest('tr');
        let idx = rowProd.dataset.index;
        let rowDel = document.querySelector(`.row-delivery[data-index="${idx}"]`);
        let selectedOption = e.target.options[e.target.selectedIndex];
        let precioBase = selectedOption.getAttribute('data-precio');
        
        // Asignar precio por defecto del producto si existe
        if (precioBase) {
            rowProd.querySelector('.input-precio').value = precioBase;
        }

        let tipo = e.target.value;
        let divAbiertos = rowDel.querySelector('.campos-abiertos');

        if (tipo === 'VLSFO' || tipo === 'HSFO' || tipo === 'LSMGO') {
            divAbiertos.classList.remove('d-none');
        } else {
            divAbiertos.classList.add('d-none');
            rowDel.querySelector('.input-del-tm').value = '';
            rowDel.querySelector('.input-del-comp').value = '';
        }
        calcularTotales();
    }
});

document.addEventListener('input', function(e) {
    if (e.target.matches('.input-tm, .input-precio, .input-del-tm, .input-del-comp, #selector_costos_personalizado') || e.target.id === 'selector_costos') {
        calcularTotales();
    }
});

document.addEventListener('click', function(e) {
    if (e.target.closest('.btn-delete')) {
        let rowProd = e.target.closest('tr');
        let idx = rowProd.dataset.index;
        let rowDel = document.querySelector(`.row-delivery[data-index="${idx}"]`);
        rowProd.remove();
        rowDel.remove();
        calcularTotales();
    }
});

function calcularTotales() {
    let selectorVal = document.getElementById('selector_costos').value;
    let pctSelector = 20.0; // por defecto

    if (selectorVal === '') {
        pctSelector = parseFloat(document.getElementById('selector_costos_personalizado').value) || 0;
    } else {
        let s = parseInt(selectorVal);
        pctSelector = (s === 2) ? 15.0 : ((s === 4) ? 0.0 : 20.0);
    }

    let rowsProd = document.querySelectorAll('.row-producto');
    let totalGeneral = 0;

    rowsProd.forEach(rowProd => {
        let idx = rowProd.dataset.index;
        let rowDel = document.querySelector(`.row-delivery[data-index="${idx}"]`);

        let tipo = rowProd.querySelector('.select-producto').value;
        let tm = parseFloat(rowProd.querySelector('.input-tm').value) || 0;
        let precioBase = parseFloat(rowProd.querySelector('.input-precio').value) || 0;

        // El selector de costos aplica EXCLUSIVAMENTE al precio del producto
        let precioAjustado = precioBase * (1 + (pctSelector / 100));
        let subtotalProd = tm * precioAjustado;
        rowProd.querySelector('.input-subtotal-prod').value = '$' + subtotalProd.toLocaleString('de-DE', {minimumFractionDigits: 2, maximumFractionDigits: 2});

        // El Delivery Fee es independiente y NO recibe el recargo del selector
        let deliveryFinal = 0;
        let rateLabel = '-';

        if (tipo === 'VLSFO' || tipo === 'HSFO' || tipo === 'LSMGO') {
            let delComp = parseFloat(rowDel.querySelector('.input-del-comp').value) || 0;
            let delTm = parseFloat(rowDel.querySelector('.input-del-tm').value) || 0;

            if (delComp > 0) {
                deliveryFinal = delComp;
                rateLabel = '$' + delComp.toLocaleString('de-DE', {minimumFractionDigits: 2});
            } else if (delTm > 0) {
                deliveryFinal = tm * delTm;
                rateLabel = '$' + delTm.toLocaleString('de-DE', {minimumFractionDigits: 2}) + ' x TM';
            }
        } else {
            if (tm > 0) {
                if (tm < 890) {
                    deliveryFinal = 8000.00;
                    rateLabel = '$8.000';
                } else {
                    deliveryFinal = tm * 9.00;
                    rateLabel = '$9 x TM';
                }
            }
        }
        
        rowDel.querySelector('.input-delivery-rate').innerText = rateLabel;
        rowDel.querySelector('.input-delivery-monto').value = '$' + deliveryFinal.toLocaleString('de-DE', {minimumFractionDigits: 2, maximumFractionDigits: 2});

        totalGeneral += (subtotalProd + deliveryFinal);
    });

    document.getElementById('lbl-total-general').innerText = '$' + totalGeneral.toLocaleString('de-DE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
</script>
@endsection