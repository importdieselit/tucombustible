@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    @php
        $isEdit = isset($cotizacion) && $cotizacion->id;
        $formAction = $isEdit ? route('cotizaciones-bunkering.update', $cotizacion->id) : route('cotizacionesBunkering.store');
    @endphp

    <h1 class="mt-4">{{ $isEdit ? 'Editar Cotización Bunkering / STS' : 'Nueva Cotización Bunkering / STS' }}</h1>

    <form action="{{ $formAction }}" method="POST">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <!-- Datos Generales y Selector de Costos -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">Datos Generales y Selector de Costos</div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">Compañía / Buyer</label>
                        <input type="text" name="pagador" class="form-control" value="{{ $cotizacion->pagador ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">Nombre del Buque</label>
                        <input type="text" name="nombre_buque" class="form-control" required value="{{ $cotizacion->nombre_buque ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">Bandera del Buque</label>
                        <input type="text" name="bandera_buque" class="form-control" required value="{{ $cotizacion->bandera_buque ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">IMO</label>
                        <input type="text" name="imo_buque" class="form-control" required value="{{ $cotizacion->imo_buque ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label font-weight-bold">Agencia Naviera</label>
                        <input type="text" name="agencia_naviera" class="form-control" value="{{ $cotizacion->agencia_naviera ?? '' }}">
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
                                <option value="{{ $centro->nombre }}">
                                    {{ $centro->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Lugar de Entrega Específico</label>
                        <input type="text" name="lugar_entrega" class="form-control" placeholder="Ej: Fondeadero / Muelle" value="{{ $cotizacion->lugar_entrega ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Método de Entrega</label>
                        <select name="metodo_despacho" id="metodo_despacho" class="form-control" disabled required>
                            <option value="">Primero seleccione un centro...</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Fecha Entrega</label>
                        <input type="date" name="fecha_entrega" class="form-control" value="{{ isset($cotizacion->fecha_entrega) ? \Carbon\Carbon::parse($cotizacion->fecha_entrega)->format('Y-m-d') : '' }}">
                    </div>
                </div>
                <div class="row g-3 mb-3 mt-1">
                    <div class="col-md-12">
                        <label class="form-label">Observaciones</label>
                        <textarea name="observaciones" class="form-control" rows="3">{{ $cotizacion->observaciones ?? '' }}</textarea>
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
                                <th style="width: 25%;">PRODUCTO / PRODUCT</th>
                                <th style="width: 20%;">CANTIDAD / UNIDAD</th>
                                <th style="width: 15%;">PRECIO UNIT. (Ajustado)</th>
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
                                            <!-- INYECCIÓN DEL DATA-LITROSTM CLAVE PARA LA CONVERSIÓN -->
                                            <option value="{{ $producto->nombre }}" data-precio="{{ $producto->precio }}" data-litrostm="{{ $producto->litros_tm ?? 1000 }}">{{ $producto->nombre }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="productos[0][obs]" class="form-control mt-1" placeholder="Observación opcional del producto">
                                </td>
                                <td>
                                    <div class="input-group">
                                        <input type="number" step="0.001" name="productos[0][tm]" class="form-control input-tm" placeholder="0.00" required>
                                        <!-- SELECT DE UNIDAD -->
                                        <select name="productos[0][unidad]" class="form-select select-unidad" style="max-width: 90px;" data-old="MT">
                                            <option value="MT">MT</option>
                                            <option value="LT">Litros</option>
                                            <option value="GL">Galones</option>
                                            <option value="DR">Tambores</option>
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <input type="number" step="0.01" name="productos[0][precio]" class="form-control input-precio" placeholder="0.00" required>
                                </td>
                                <td class="text-center align-middle">-</td>
                                <td>
                                    <input type="text" class="form-control bg-light text-end fw-bold input-subtotal-prod" readonly value="$0.00">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm" disabled><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <!-- Fila Delivery Fee -->
                            <tr class="row-delivery bg-light" data-index="0">
                                <td class="ps-4 text-muted fst-italic">
                                    DELIVERY FEE <span class="fst-normal label-del-fee" name="productos[0][del_fee]"></span>
                                    <div class="campos-abiertos d-none mt-1">
                                        <input type="number" step="0.01" name="productos[0][del_tm]" class="form-control form-control-sm input-del-tm mb-1" placeholder="Tarifa x MT">
                                        <input type="number" step="0.01" name="productos[0][del_comp]" class="form-control form-control-sm input-del-comp" placeholder="Tarifa Completa">
                                    </div>
                                </td>
                                <td class="text-center text-muted">-</td>
                                <td class="text-center text-muted">-</td>
                                <td class="text-center fw-bold text-warning input-delivery-rate">
                                    $0.00
                                </td>
                                <td>
                                    <input type="text" class="form-control bg-light text-end text-warning fw-bold input-delivery-monto" readonly value="$0.00">
                                </td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="card bg-light p-3 mt-3">
                    <div class="row text-end">
                        <div class="col-12">
                            <h4 class="mb-0 text-success">TOTAL GENERAL: <span id="lbl-total-general">$0.00</span></h4>
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
    let rowIndex = document.querySelectorAll('.row-producto').length || 1;
    
    // Asumimos que $configDelivery es el arreglo del parámetro 'DELIVERY_BUNKERING_TARIFAS'
    const CONFIG_DELIVERY = @json($configDelivery ?? []);
    // Asumimos que $matrizCentros es el contenido del parámetro 'ENLACE_CENTROS_METODOS'
    const MATRIZ_CENTROS = @json($matrizCentros ?? []);

    let productosOptions = `<option value="">Seleccione...</option>@foreach($productos as $producto)<option value="{{ $producto->nombre }}" data-precio="{{ $producto->precio }}" data-litrostm="{{ $producto->litros_tm ?? 1000 }}">{{ $producto->nombre }}</option>@endforeach`;

    // --- FUNCIONES DE APOYO ---

    function getPorcentajeRecargo() {
        let selectorVal = document.getElementById('selector_costos').value;
        if (selectorVal === '5') {
            let customVal = parseFloat(document.getElementById('selector_costos_personalizado').value);
            return isNaN(customVal) ? 0 : customVal;
        }
        let mapeo = { '1': 30.0, '2': 20.0, '3': 10.0, '4': 0.0 };
        return mapeo[selectorVal] || 0;
    }

    function getFactoresEnLitros(litrosPorTm) {
        return {
            'MT': parseFloat(litrosPorTm), 
            'LT': 1.0,                     
            'GL': 3.78541,                 
            'DR': 200.0                    
        };
    }

    function actualizarPreciosVisibles() {
        let recargo = getPorcentajeRecargo();
        document.querySelectorAll('.row-producto').forEach(fila => {
            let select = fila.querySelector('.select-producto');
            let selectedOption = select.options[select.selectedIndex];
            
            if (!selectedOption || !selectedOption.value) return;

            let precioBaseMT = parseFloat(selectedOption.getAttribute('data-precio'));
            let litrosTm = parseFloat(selectedOption.getAttribute('data-litrostm')) || 1000;
            let unidadActual = fila.querySelector('.select-unidad').value;

            if (!isNaN(precioBaseMT)) {
                let factores = getFactoresEnLitros(litrosTm);
                let precioMTConRecargo = precioBaseMT * (1 + (recargo / 100));
                let precioPorUnidad = (precioMTConRecargo / litrosTm) * factores[unidadActual];
                
                fila.querySelector('.input-precio').value = precioPorUnidad.toFixed(2); 
            }
        });
    }

    // --- EVENTOS DEL FORMULARIO GENERAL ---

    document.getElementById('centro_suministro').addEventListener('change', function() {
        let metodoDespachoSelect = document.getElementById('metodo_despacho');
        metodoDespachoSelect.innerHTML = '<option value="">Seleccione método...</option>';
        
        if (!this.value) {
            metodoDespachoSelect.setAttribute('disabled', 'disabled'); 
            return;
        }
        metodoDespachoSelect.removeAttribute('disabled');
        
        let metodosDinamicos = {};
        if (CONFIG_DELIVERY.metodos_despacho) {
            CONFIG_DELIVERY.metodos_despacho.forEach(m => {
                metodosDinamicos[m.codigo] = m.etiqueta;
            });
        }

        let centroNombre = this.value; 
        let metodosPermitidos = [];

        if (MATRIZ_CENTROS && MATRIZ_CENTROS[centroNombre]) {
            for (let codigoMetodo in MATRIZ_CENTROS[centroNombre]) {
                if (MATRIZ_CENTROS[centroNombre][codigoMetodo] == "1") {
                    metodosPermitidos.push(codigoMetodo);
                }
            }
        }

        if (metodosPermitidos.length === 0) {
            metodosPermitidos = Object.keys(metodosDinamicos);
        }

        metodosPermitidos.forEach(codigo => {
            if (metodosDinamicos[codigo]) {
                metodoDespachoSelect.innerHTML += `<option value="${codigo}">${metodosDinamicos[codigo]}</option>`;
            }
        });
    });

    document.getElementById('selector_costos').addEventListener('change', function() {
        let customInput = document.getElementById('selector_costos_personalizado');
        if (this.value === '5') {
            customInput.classList.remove('d-none');
        } else {
            customInput.classList.add('d-none');
        }
        actualizarPreciosVisibles();
        calcularTotales();
    });

    document.getElementById('selector_costos_personalizado').addEventListener('input', function() {
        actualizarPreciosVisibles();
        calcularTotales();
    });

    document.getElementById('btn-add-row').addEventListener('click', function() {
        let tbody = document.querySelector('#tabla-productos tbody');
        let html = `
            <tr class="row-producto" data-index="${rowIndex}">
                <td>
                    <select name="productos[${rowIndex}][tipo]" class="form-select select-producto" required>
                        ${productosOptions}
                    </select>
                    <input type="text" name="productos[${rowIndex}][obs]" class="form-control mt-1" placeholder="Observación opcional">
                </td>
                <td>
                    <div class="input-group">
                        <input type="number" step="0.001" name="productos[${rowIndex}][tm]" class="form-control input-tm" placeholder="0.00" required>
                        <select name="productos[${rowIndex}][unidad]" class="form-select select-unidad" style="max-width: 90px;" data-old="MT">
                            <option value="MT">MT</option>
                            <option value="LT">Litros</option>
                            <option value="GL">Galones</option>
                            <option value="DR">Tambores</option>
                        </select>
                    </div>
                </td>
                <td>
                    <input type="number" step="0.01" name="productos[${rowIndex}][precio]" class="form-control input-precio" placeholder="0.00" required>
                </td>
                <td class="text-center align-middle">-</td>
                <td>
                    <input type="text" class="form-control bg-light text-end fw-bold input-subtotal-prod" readonly value="$0.00">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm btn-delete"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
            <tr class="row-delivery bg-light" data-index="${rowIndex}">
                <td class="ps-4 text-muted fst-italic">
                    DELIVERY FEE <span class="fst-normal label-del-fee" name="productos[${rowIndex}][del_fee]"></span>
                    <div class="campos-abiertos d-none mt-1">
                        <input type="number" step="0.01" name="productos[${rowIndex}][del_tm]" class="form-control form-control-sm input-del-tm mb-1" placeholder="Tarifa x MT">
                        <input type="number" step="0.01" name="productos[${rowIndex}][del_comp]" class="form-control form-control-sm input-del-comp" placeholder="Tarifa Completa">
                    </div>
                </td>
                <td class="text-center text-muted">-</td>
                <td class="text-center text-muted">-</td>
                <td class="text-center fw-bold text-warning input-delivery-rate">$0.00</td>
                <td>
                    <input type="text" class="form-control bg-light text-end text-warning fw-bold input-delivery-monto" readonly value="$0.00">
                </td>
                <td></td>
            </tr>
        `;
        tbody.insertAdjacentHTML('beforeend', html);
        rowIndex++;
    });

    // --- EVENTOS DE LA TABLA (DELEGACIÓN CENTRAL) ---

    document.querySelector('#tabla-productos').addEventListener('change', function(e) {
        
        // 1. Cambio de Producto
        if (e.target.classList.contains('select-producto')) {
            let rowProd = e.target.closest('tr');
            let selectUnidad = rowProd.querySelector('.select-unidad');
            let selectedOption = e.target.options[e.target.selectedIndex];
            
            // Forzar MT al cambiar de producto para no descuadrar la lógica base
            selectUnidad.value = 'MT'; 
            selectUnidad.setAttribute('data-old', 'MT');

            actualizarPreciosVisibles();
            
            let tipo = selectedOption.value;
            let rowDel = document.querySelector(`.row-delivery[data-index="${rowProd.dataset.index}"]`);
            let divAbiertos = rowDel.querySelector('.campos-abiertos');

            if (['VLSFO', 'HSFO', 'LSMGO'].some(term => tipo.includes(term))) {
                divAbiertos.classList.remove('d-none');
            } else {
                divAbiertos.classList.add('d-none');
                rowDel.querySelector('.input-del-tm').value = '';
                rowDel.querySelector('.input-del-comp').value = '';
            }
            calcularTotales();
        }

        // 2. Cambio de Unidad Volumétrica
        if (e.target.classList.contains('select-unidad')) {
            let rowProd = e.target.closest('tr');
            let selectProd = rowProd.querySelector('.select-producto');
            let selectedOption = selectProd.options[selectProd.selectedIndex];

            if (!selectedOption || !selectedOption.value) {
                e.target.setAttribute('data-old', e.target.value);
                alert("Selecciona primero un producto.");
                return;
            }

            let litrosTm = parseFloat(selectedOption.getAttribute('data-litrostm')) || 1000;
            let factores = getFactoresEnLitros(litrosTm);
            
            let oldUnit = e.target.getAttribute('data-old');
            let newUnit = e.target.value;
            
            let inputQty = rowProd.querySelector('.input-tm');
            let currentQty = parseFloat(inputQty.value) || 0;

            if (currentQty > 0) {
                let litrosTotales = currentQty * factores[oldUnit];
                let nuevaCantidad = litrosTotales / factores[newUnit];
                inputQty.value = nuevaCantidad.toFixed(2);
            }

            e.target.setAttribute('data-old', newUnit);
            actualizarPreciosVisibles();
            calcularTotales();
        }
    });

    document.querySelector('#tabla-productos').addEventListener('input', function(e) {
        if (e.target.matches('.input-tm, .input-precio, .input-del-tm, .input-del-comp')) {
            calcularTotales();
        }
    });

    document.querySelector('#tabla-productos').addEventListener('click', function(e) {
        let btnDelete = e.target.closest('.btn-delete');
        if (btnDelete) {
            let rowProd = btnDelete.closest('tr');
            let rowDel = document.querySelector(`.row-delivery[data-index="${rowProd.dataset.index}"]`);
            rowProd.remove();
            if(rowDel) rowDel.remove();
            calcularTotales();
        }
    });

    // --- MOTOR DE CÁLCULO GENERAL ---

    function calcularTotales() {
        let totalGeneral = 0;

        document.querySelectorAll('.row-producto').forEach(rowProd => {
            let idx = rowProd.dataset.index;
            let rowDel = document.querySelector(`.row-delivery[data-index="${idx}"]`);
            
            let selectProd = rowProd.querySelector('.select-producto');
            let selectedOption = selectProd.options[selectProd.selectedIndex];
            
            if(!selectedOption || !selectedOption.value) return;

            let litrosTm = parseFloat(selectedOption.getAttribute('data-litrostm')) || 1000;
            let unidad = rowProd.querySelector('.select-unidad').value;
            let cantidadVisual = parseFloat(rowProd.querySelector('.input-tm').value) || 0;
            let precioVisual = parseFloat(rowProd.querySelector('.input-precio').value) || 0;

            let subtotalProd = cantidadVisual * precioVisual;
            rowProd.querySelector('.input-subtotal-prod').value = '$' + subtotalProd.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

            // Re-calculamos las MT reales para el Delivery Fee (el barco cobra por peso, no por tambores)
            let factores = getFactoresEnLitros(litrosTm);
            let mtReales = (cantidadVisual * factores[unidad]) / litrosTm;

            let deliveryFinal = 0;
            let rateLabel = '-';
            let tipo = selectedOption.value;

            if (['VLSFO', 'HSFO', 'LSMGO'].some(term => tipo.includes(term))) {
                let delComp = parseFloat(rowDel.querySelector('.input-del-comp').value) || 0;
                let delTm = parseFloat(rowDel.querySelector('.input-del-tm').value) || 0;

                if (delComp > 0) {
                    deliveryFinal = delComp;
                    rateLabel = '$' + delComp.toLocaleString('en-US', {minimumFractionDigits: 2});
                } else if (delTm > 0) {
                    deliveryFinal = mtReales * delTm;
                    rateLabel = '$' + delTm.toLocaleString('en-US', {minimumFractionDigits: 2});
                }
            } else {
                if (mtReales > 0) {
                    // Magia Paramétrica de la Base de Datos
                    let umbral = parseFloat(CONFIG_DELIVERY.umbral_minimo_mt) || 890;
                    let tarifaPlana = parseFloat(CONFIG_DELIVERY.tarifa_plana_minima) || 8000;
                    let tarifaPorMt = parseFloat(CONFIG_DELIVERY.tarifa_por_mt) || 9;

                    if (mtReales < umbral) {
                        deliveryFinal = tarifaPlana;
                        rateLabel = '$' + tarifaPlana.toLocaleString('en-US', {minimumFractionDigits: 2});
                        rowDel.querySelector('.label-del-fee').innerText = '';
                    } else {
                        deliveryFinal = mtReales * tarifaPorMt;
                        rowDel.querySelector('.label-del-fee').innerText = '$' + tarifaPorMt + 'xMT';
                        rateLabel = '$' + tarifaPorMt.toLocaleString('en-US', {minimumFractionDigits: 2});
                    }
                }
            }
            
            rowDel.querySelector('.input-delivery-rate').innerText = rateLabel;
            rowDel.querySelector('.input-delivery-monto').value = '$' + deliveryFinal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

            totalGeneral += (subtotalProd + deliveryFinal);
        });

        document.getElementById('lbl-total-general').innerText = '$' + totalGeneral.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
</script>
@endsection