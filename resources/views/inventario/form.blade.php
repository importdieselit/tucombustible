@extends('layouts.app')
@push('styles')
<style>
:root {
    --corp-blue: #003366;      /* Azul principal (Headers, Botones Primarios) */
    --corp-orange: #F39200;    /* Naranja de acento (Alertas, Pasos activos, Hover) */
    --corp-light-bg: #F4F7F9;  /* Fondo de página grisáceo suave */
    --corp-text: #333333;
}

/* Overrides de estilos */
.btn-primary {
    background-color: var(--corp-blue) !important;
    border-color: var(--corp-blue) !important;
}
.text-corp-blue { color: #003366 !important; }
    .btn-orange { background-color: #F39200; color: white; border: none; }
    .btn-orange:hover { background-color: #d68100; color: white; }
    .association-row { background: #f8f9fa; border-radius: 8px; padding: 10px; margin-bottom: 10px; border-left: 4px solid #003366; }
    .btn-remove { color: #dc3545; cursor: pointer; }
.btn-primary:hover {
    background-color: #002244 !important;
}

.text-primary {
    color: var(--corp-blue) !important;
}

/* Wizard Estilizado */
.wizard-steps .step {
    border-bottom: 4px solid #dee2e6;
    color: #adb5bd;
    transition: all 0.3s ease;
}

.wizard-steps .step.active {
    border-color: var(--corp-orange);
    color: var(--corp-blue);
    font-weight: 800;
}

/* Inputs con estilo corporativo */
.form-control:focus, .form-select:focus {
    border-color: var(--corp-orange);
    box-shadow: 0 0 0 0.25rem rgba(243, 146, 0, 0.2);
}

.card {
    border-top: 5px solid var(--corp-blue);
}
</style>
@endpush
@section('content')
<div class="container-fluid">
    <div class="row page-titles mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h3 class="text-primary fw-bold mb-0"><i class="bi bi-cart-plus me-2"></i>Registrar Ingreso de Mercancía</h3>
            <a href="{{ route('inventario.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div class="card shadow-lg border-0">
        <div class="card-body p-4">
            <!-- Barra de Pasos (Wizard Visual) -->
            <div class="wizard-steps d-flex justify-content-around mb-5">
                <div class="step active" id="step-1-indicator">1. Identificación</div>
                <div class="step" id="step-2-indicator">2. Datos de Compra</div>
                <div class="step" id="step-3-indicator">3. Ficha Técnica</div>
            </div>

            <form action="{{ route('inventario.store') }}" method="POST" id="purchase-form">
                @csrf
                
                <!-- PASO 1: Identificación del Producto -->
                <div class="step-content" id="step-1">
                    <div class="row justify-content-center">
                        <div class="col-md-6 text-center">
                            <label class="form-label fw-bold">Buscar Código de Parte o Descripción</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-primary text-white"><i class="bi bi-search"></i></span>
                                <input type="text" id="smart-search" class="form-control" placeholder="Escriba el código o nombre del repuesto...">
                            </div>
                            <div id="search-results" class="list-group shadow-sm mt-2 text-start" style="position: absolute; z-index: 1000; width: 48%;"></div>
                            <p class="text-muted mt-3 small">Si el código no existe, el sistema le guiará para crearlo.</p>
                        </div>
                    </div>
                </div>

                <!-- PASO 2: Datos de la Transacción (Común para ambos casos) -->
                <div class="step-content d-none" id="step-2">
                    <h5 class="border-bottom pb-2 mb-4 text-primary">Información de Factura y Costos</h5>
                    <div class="row">
                        <input type="hidden" name="item_id" id="item_id"> <!-- Si existe, se llena aquí -->
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Nro. Factura / Control</label>
                            <input type="text" name="numero_factura" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Proveedor</label>
                            <select name="proveedor_id" class="form-select" required>
                                @foreach($proveedores as $p)
                                    <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Cantidad</label>
                            <input type="number" name="cantidad" id="cantidad_ingreso" class="form-control" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Costo Unitario ($)</label>
                            <input type="number" step="0.01" name="costo_unitario" class="form-control" required>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Almacén Destino</label>
                            <select name="id_almacen" class="form-select" required>
                                @foreach($almacenes as $a)
                                    <option value="{{ $a->id }}">{{ $a->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- PASO 3: Ficha Técnica (Solo si el ítem es NUEVO) -->
                <div class="step-content d-none" id="step-3">
                    <h5 class="border-bottom pb-2 mb-4 text-warning">Nuevo Ítem: Complete la Ficha Técnica</h5>
                    <div class="row">
                        <!-- Aquí reutilizamos los campos de tu formulario original (Columna 1, 2 y 3) -->
                        <div class="col-md-4">
                            <label class="form-label">Código de Parte</label>
                            <input type="text" name="codigo" id="new_codigo" class="form-control bg-light">
                            <!-- ... Resto de tus campos de descripción, referencia, etc. ... -->
                        </div>
                        <div class="row">
        <div class="col-md-6 mb-4">
            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                <h6 class="text-corp-blue fw-bold mb-0"><i class="bi bi-truck me-2"></i>Compatibilidad por Modelo</h6>
                <button type="button" class="btn btn-sm btn-orange" onclick="addRow('vehicle')">
                    <i class="bi bi-plus-lg"></i> Agregar
                </button>
            </div>
                                <div id="container-vehicles" class="association-container">
                                    </div>
                            </div>

                            <div class="col-md-6 mb-4">
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                    <h6 class="text-corp-blue fw-bold mb-0"><i class="bi bi-gear-wide-connected me-2"></i>Uso en Rutinas (M1, M2...)</h6>
                                    <button type="button" class="btn btn-sm btn-orange" onclick="addRow('service')">
                                        <i class="bi bi-plus-lg"></i> Agregar
                                    </button>
                                </div>
                                <div id="container-services" class="association-container">
                                    </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <h6 class="text-corp-blue fw-bold border-bottom pb-2 mb-3">Repuestos Equivalentes / Reemplazos</h6>
                                <div class="input-group mb-3">
                                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                                    <input type="text" id="search-equivalents" class="form-control" placeholder="Buscar equivalente por código o descripción...">
                                </div>
                                <div id="selected-equivalents" class="d-flex flex-wrap gap-2">
                                    </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de Navegación -->
                <div class="d-flex justify-content-between mt-5 border-top pt-4">
                    <button type="button" class="btn btn-secondary d-none" id="btn-prev">Anterior</button>
                    <button type="button" class="btn btn-primary" id="btn-next">Siguiente</button>
                    <button type="submit" class="btn btn-success d-none" id="btn-save">Procesar Ingreso</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    :root { --primary-corp: #0d6efd; --secondary-corp: #6c757d; }
    .wizard-steps .step { padding: 10px 20px; border-bottom: 3px solid #eee; color: #ccc; font-weight: bold; flex-grow: 1; text-align: center; }
    .wizard-steps .step.active { border-color: var(--primary-corp); color: var(--primary-corp); }
    .form-control:focus { border-color: var(--primary-corp); box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15); }
</style>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let currentStep = 1;
        let isNewItem = false;

        const smartSearch = document.getElementById('smart-search');
        const resultsDiv = document.getElementById('search-results');

        // Buscador en tiempo real
        smartSearch.addEventListener('input', function() {
            if (this.value.length < 3) { resultsDiv.innerHTML = ''; return; }
            
            fetch(`/api/inventario/buscar?q=${this.value}`)
                .then(res => res.json())
                .then(data => {
                    resultsDiv.innerHTML = '';
                    data.forEach(item => {
                        let btn = document.createElement('button');
                        btn.className = 'list-group-item list-group-item-action';
                        btn.innerHTML = `<strong>${item.codigo}</strong> - ${item.descripcion}`;
                        btn.onclick = (e) => {
                            e.preventDefault();
                            selectItem(item);
                        };
                        resultsDiv.appendChild(btn);
                    });
                    
                    // Opción para crear nuevo si no aparece
                    let addNew = document.createElement('button');
                    addNew.className = 'list-group-item list-group-item-action list-group-item-warning';
                    addNew.innerHTML = `<i class="bi bi-plus-circle"></i> No existe: Crear "${this.value}"`;
                    addNew.onclick = (e) => { e.preventDefault(); setupNewItem(this.value); };
                    resultsDiv.appendChild(addNew);
                });
        });

        function selectItem(item) {
            document.getElementById('item_id').value = item.id;
            isNewItem = false;
            goToStep(2);
        }

        function setupNewItem(codigo) {
            document.getElementById('item_id').value = '';
            document.getElementById('new_codigo').value = codigo;
            isNewItem = true;
            goToStep(2); // Pasamos por datos de compra primero
        }

        function goToStep(step) {
            document.querySelectorAll('.step-content').forEach(s => s.classList.add('d-none'));
            document.getElementById(`step-${step}`).classList.remove('d-none');
            
            // Indicadores visuales
            document.querySelectorAll('.step').forEach((s, idx) => {
                s.classList.toggle('active', idx + 1 === step);
            });

            // Lógica de botones
            document.getElementById('btn-prev').classList.toggle('d-none', step === 1);
            
            if (step === 2 && !isNewItem) {
                document.getElementById('btn-next').classList.add('d-none');
                document.getElementById('btn-save').classList.remove('d-none');
            } else if (step === 2 && isNewItem) {
                document.getElementById('btn-next').classList.remove('d-none');
                document.getElementById('btn-save').classList.add('d-none');
            } else if (step === 3) {
                document.getElementById('btn-next').classList.add('d-none');
                document.getElementById('btn-save').classList.remove('d-none');
            }
            currentStep = step;
        }

        document.getElementById('btn-next').onclick = () => goToStep(currentStep + 1);
        document.getElementById('btn-prev').onclick = () => goToStep(currentStep - 1);
    });

    let vehicleIndex = 0;
let serviceIndex = 0;

function addRow(type) {
    const container = type === 'vehicle' ? document.getElementById('container-vehicles') : document.getElementById('container-services');
    const div = document.createElement('div');
    div.className = 'association-row d-flex align-items-center gap-2 animate__animated animate__fadeIn';

    if (type === 'vehicle') {
        div.innerHTML = `
            <select name="asociaciones_vehiculos[${vehicleIndex}][marca]" class="form-select form-select-sm" required>
                <option value="">Marca</option>
                @foreach($marcas as $m) <option value="{{ $m->id }}">{{ $m->nombre }}</option> @endforeach
            </select>
            <select name="asociaciones_vehiculos[${vehicleIndex}][modelo]" class="form-select form-select-sm" required>
                <option value="">Modelo</option>
                </select>
            <i class="bi bi-trash btn-remove" onclick="this.parentElement.remove()"></i>
        `;
        vehicleIndex++;
    } else {
        div.innerHTML = `
            <select name="planes_mantenimiento[${serviceIndex}][id_plan]" class="form-select form-select-sm" required>
                <option value="">Plan (M1, M2...)</option>
                @foreach($planes as $p) <option value="{{ $p->id_plan }}">{{ $p->nombre }}</option> @endforeach
            </select>
            <select name="planes_mantenimiento[${serviceIndex}][id_servicio]" class="form-select form-select-sm" required>
                <option value="">Servicio</option>
                @foreach($servicios as $s) <option value="{{ $s->id }}">{{ $s->nombre }}</option> @endforeach
            </select>
            <input type="number" name="planes_mantenimiento[${serviceIndex}][cantidad]" class="form-control form-control-sm" style="width: 80px" placeholder="Cant." value="1">
            <i class="bi bi-trash btn-remove" onclick="this.parentElement.remove()"></i>
        `;
        serviceIndex++;
    }
    container.appendChild(div);
}

// Lógica para equivalentes (Chips dinámicos)
const searchEquiv = document.getElementById('search-equivalents');
const selectedContainer = document.getElementById('selected-equivalents');

searchEquiv.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        // Aquí llamarías a tu API de búsqueda. Ejemplo manual:
        const mockItem = { id: 50, codigo: 'EQ-202', desc: 'Filtro Reemplazo' };
        addEquivalentChip(mockItem);
        this.value = '';
    }
});

function addEquivalentChip(item) {
    if (document.getElementById(`equiv-${item.id}`)) return; // Evitar duplicados

    const chip = document.createElement('div');
    chip.id = `equiv-${item.id}`;
    chip.className = 'badge bg-light text-dark border p-2 d-flex align-items-center gap-2';
    chip.innerHTML = `
        <input type="hidden" name="equivalentes[]" value="${item.id}">
        <span class="text-corp-blue fw-bold">${item.codigo}</span> - ${item.desc}
        <i class="bi bi-x-circle-fill text-danger cursor-pointer" onclick="this.parentElement.remove()"></i>
    `;
    selectedContainer.appendChild(chip);
}
    </script>
@endpush
@endsection
