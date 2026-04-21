@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" x-data="constructorDeCarga('{{ $tipo }}')">
    {{-- ENCABEZADO --}}
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center bg-white p-3 shadow-sm rounded border-left-orange">
            <div>
                <h3 class="text-orange mb-0 fw-black text-uppercase">
                    <i class="fas fa-file-signature me-2"></i>Nueva Planificación: <span x-text="getTitulo()" class="text-uppercase"></span>
                </h3>
                <p class="text-muted small mb-0">Gestión logística de despacho y transporte de combustible.</p>
            </div>
            <div class="text-right">
                <a href="{{ route('logistica.index') }}" class="btn btn-sm btn-outline-secondary fw-bold">
                    <i class="fas fa-arrow-left me-1"></i> VOLVER
                </a>
            </div>
        </div>
    </div>

    <form action="{{ route('logistica.store') }}" method="POST">
        @csrf
        <input type="hidden" name="tipo_planificacion" value="{{ $tipoPlanificacionId }}">

        <div class="row">
            {{-- COLUMNA IZQUIERDA: VEHÍCULOS Y PERSONAL --}}
            <div class="col-md-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-dark text-white py-3">
                        <h6 class="mb-0 fw-bold text-uppercase small">1. Datos del Vehículo y Personal</h6>
                    </div>
                    <div class="card-body">
                        
                        <template x-if="modo === 'flete'">
                            <div class="mb-3">
                                <label class="small fw-bold text-muted text-uppercase">Producto a Transportar</label>
                                <input type="text" name="producto_flete" class="form-control fw-bold border-orange" placeholder="Ej: Tuberías, Lubricantes, etc." required>
                            </div>
                        </template>

                        {{-- PARA COMPRA: ELIGE COMBUSTIBLE --}}
                        <template x-if="modo === 'compra'">
                            <div class="mb-3">
                                <label class="small fw-bold text-muted text-uppercase">Tipo de Combustible</label>
                                <select name="tipo_combustible_id" class="form-select fw-bold border-orange" required>
                                    <option value="">Seleccione...</option>
                                    @foreach($tipos as $tc)
                                        <option value="{{ $tc->id }}">{{ $tc->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </template>

                        <template x-if="modo !== 'flete' && modo !== 'compra'">
                            <input type="hidden" name="tipo_combustible_id" value="{{ $tipoPlanificacionId }}">
                        </template>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted text-uppercase">Fecha Programada</label>
                            <input type="date" name="fecha_programada" class="form-control fw-bold" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <div class="mb-3" x-show="modo !== 'flete' && modo !== 'compra'">
                            <label class="small fw-bold text-muted text-uppercase">Sede de Despacho</label>
                            <select name="sede_id" class="form-select fw-bold border-orange" :required="modo !== 'flete' && modo !== 'compra'">
                                <option value="">Seleccione sede...</option>
                                @foreach($sedes as $s)
                                    <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3 border-top pt-3">
                            <label class="small fw-bold text-muted text-uppercase">Tipo de Transporte (Vehículo)</label>
                            <div class="btn-group w-100 mb-3">
                                <input type="radio" class="btn-check" name="es_transporte_propio" id="propio1" value="1" x-model="esPropio">
                                <label class="btn btn-outline-dark btn-sm fw-bold" for="propio1">IMPORDIESEL</label>
                                <input type="radio" class="btn-check" name="es_transporte_propio" id="propio0" value="0" x-model="esPropio">
                                <label class="btn btn-outline-dark btn-sm fw-bold" for="propio0">EXTERNO</label>
                            </div>
                        </div>

                        {{-- Lógica de Vehículo Propio --}}
                        <div x-show="esPropio == '1'" x-transition>
                            <div class="mb-3">
                                <label class="small fw-bold text-muted text-uppercase">Vehículo / Chuto</label>
                                <select name="vehiculo_id" class="form-select form-select-sm fw-bold" x-model="vehiculoId" @change="cambioVehiculo($event)">
                                    <option value="">Seleccione...</option>
                                    @foreach($vehiculos as $v)
                                        <option value="{{ $v->id }}" data-capacidad="{{ $v->carga_max }}">{{ $v->placa }} ({{ $v->tipo }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3 p-2 bg-light rounded border-orange" x-show="necesitaCisterna">
                                <label class="small fw-bold text-danger text-uppercase">Cisterna / Remolque</label>
                                <select name="cisterna_id" class="form-select form-select-sm fw-bold border-danger" x-model="cisternaId" @change="cambioCisterna($event)">
                                    <option value="">Seleccione acople...</option>
                                    @foreach($vehiculos as $v)
                                        @if($v->carga_max > 0)
                                            <option value="{{ $v->id }}" data-capacidad="{{ $v->carga_max }}">{{ $v->placa }} - Cap: {{ number_format($v->carga_max) }}L</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="small fw-bold text-muted text-uppercase">Chofer</label>
                                <select name="chofer_id" class="form-select form-select-sm fw-bold">
                                    <option value="">Seleccione...</option>
                                    @foreach($personal as $p)
                                        <option value="{{ $p->id }}">{{ $p->persona->nombre }} {{ $p->persona->apellido }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="small fw-bold text-muted text-uppercase">Ayudante</label>
                                <select name="ayudante_id" class="form-select form-select-sm fw-bold">
                                    <option value="">Seleccione...</option>
                                    @foreach($personal as $p)
                                        <option value="{{ $p->id }}">{{ $p->persona->nombre }} {{ $p->persona->apellido }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Transporte Externo --}}
                        <div x-show="esPropio == '0'" x-transition class="p-2 border rounded bg-light">
                            <div class="row g-2">
                                <div class="col-6 mb-2">
                                    <label class="small fw-bold text-muted text-uppercase">Placa Vehículo</label>
                                    <input type="text" name="externo_vehiculo_placa" class="form-control form-control-sm border-orange" placeholder="ABC-123" style="text-transform: uppercase;">
                                </div>
                                <div class="col-6 mb-2">
                                    <label class="small fw-bold text-muted text-uppercase">Placa Cisterna</label>
                                    <input type="text" name="externo_cisterna_placa" class="form-control form-control-sm border-orange" placeholder="Opcional" style="text-transform: uppercase;">
                                </div>
                                <div class="col-12">
                                    <div class="input-group input-group-sm mb-1">
                                        <span class="input-group-text">Chofer</span>
                                        <input type="text" name="externo_chofer_nombre" class="form-control" placeholder="Nombre completo" style="text-transform: uppercase;">
                                    </div>
                                    <div class="input-group input-group-sm mb-1">
                                        <span class="input-group-text">Ayudante</span>
                                        <input type="text" name="externo_ayudante_nombre" class="form-control" placeholder="Nombre completo" style="text-transform: uppercase;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- INDICADOR DE CARGA --}}
                <div x-show="modo !== 'flete'" class="card shadow-sm sticky-top border-0" style="top: 20px;">
                    <div class="card-body text-center p-3" :class="excesoCarga ? 'bg-danger text-white rounded' : 'bg-light rounded'">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small fw-bold text-uppercase">Ocupación</span>
                            <span class="small fw-bold" x-text="porcentajeCarga + '%'"></span>
                        </div>
                        <div class="progress mb-3" style="height: 8px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" :class="excesoCarga ? 'bg-white' : 'bg-orange'" role="progressbar" :style="'width: ' + porcentajeCarga + '%'"></div>
                        </div>
                        <div class="row g-0 border-top pt-2">
                            <div class="col-6 border-end">
                                <small class="d-block text-[9px] uppercase">Planificado</small>
                                <span class="fw-black fs-5" x-text="formatoNumero(totalLitros)"></span>
                            </div>
                            <div class="col-6">
                                <small class="d-block text-[9px] uppercase">Capacidad</small>
                                <span class="fw-black fs-5" x-text="formatoNumero(capacidadMaxima)"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- COLUMNA DERECHA: DINÁMICA POR MODO --}}
            <div class="col-md-8">

                {{-- MODO COMPRA --}}
                <template x-if="modo === 'compra'">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="mb-0 fw-black text-uppercase small text-dark">2. Detalle de la Compra</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted text-uppercase">Proveedor</label>
                                    <select name="proveedor_id" class="form-select border-orange fw-bold" required>
                                        <option value="">-- Seleccione --</option>
                                        @foreach($proveedores as $prov)
                                            <option value="{{ $prov->id }}">{{ $prov->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted text-uppercase">Sede de Recepción</label>
                                    <select name="sede_id" class="form-select border-orange fw-bold" required>
                                        <option value="">-- Seleccione --</option>
                                        @foreach($sedes as $s)
                                            <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="small fw-bold text-muted text-uppercase">Código SAP</label>
                                    <input type="text" name="codigo_sap" class="form-control border-orange fw-bold">
                                </div>
                                <div class="col-md-4">
                                    <label class="small fw-bold text-muted text-uppercase">Litros a Comprar</label>
                                    <div class="input-group">
                                        <input type="number" name="cantidad_litros" class="form-control border-orange fw-black text-orange" x-model.number="totalLitros" required>
                                        <span class="input-group-text bg-white border-orange">L</span>
                                    </div>
                                </div>
                                <div class="col-12 mt-3">
                                    <label class="small fw-bold text-muted text-uppercase">Observaciones</label>
                                    <textarea name="observacion" class="form-control border-light" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- MODO DIESEL / MGO (RESTAURADO) --}}
                <template x-if="modo === 'diesel' || modo === 'mgo'">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                            <h6 class="mb-0 fw-black text-uppercase small">2. Plan de Despacho (Destinos)</h6>
                            <button type="button" class="btn btn-dark btn-sm fw-bold" @click="abrirModal()">
                                <i class="fas fa-plus-circle me-1"></i> AGREGAR DESTINO
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light text-muted small uppercase">
                                        <tr>
                                            <th class="ps-3">Cliente</th>
                                            <th width="150">Litros</th>
                                            <th x-show="modo === 'mgo'">Logística Buque</th>
                                            <th>Notas</th>
                                            <th width="50"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(item, index) in items" :key="index">
                                            <tr>
                                                <td class="ps-3 small">
                                                    <b x-text="item.cliente_nombre"></b><br>
                                                    <span class="text-muted" x-text="'RIF: ' + item.cliente_rif"></span>
                                                    <input type="hidden" :name="'items['+index+'][cliente_id]'" :value="item.cliente_id">
                                                </td>
                                                <td>
                                                    <input type="number" :name="'items['+index+'][litros]'" class="form-control form-control-sm fw-bold border-orange" x-model.number="item.litros" @input="calcularTotal()">
                                                </td>
                                                <td x-show="modo === 'mgo'">
                                                    <input type="text" :name="'items['+index+'][buque_nombre]'" class="form-control form-control-sm mb-1" placeholder="Buque" x-model="item.buque_nombre">
                                                </td>
                                                <td>
                                                    <textarea :name="'items['+index+'][observaciones]'" class="form-control form-control-sm" rows="1" x-model="item.observaciones"></textarea>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-link text-danger" @click="removerItem(index)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- MODO FLETE (RESTAURADO) --}}
                <template x-if="modo === 'flete'">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="mb-0 fw-black text-uppercase small text-dark">2. Detalles del Flete</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted uppercase">Punto Salida</label>
                                    <input type="text" name="punto_salida" class="form-control border-orange">
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted uppercase">Punto Llegada</label>
                                    <input type="text" name="punto_llegada" class="form-control border-orange">
                                </div>
                                <div class="col-12 mt-3">
                                    <label class="small fw-bold text-muted uppercase">Observaciones</label>
                                    <textarea name="observacion" class="form-control border-light" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <div class="mt-4 text-end">
                    <button type="submit" class="btn btn-orange btn-lg px-5 text-white fw-black shadow" :disabled="excesoCarga">
                        GUARDAR PLANIFICACIÓN
                    </button>
                </div>
            </div>
        </div>
    </form>

    @include('admin.logistica.partials.modal_add_destino')
</div>

<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
    function constructorDeCarga(tipoModo) {
        return {
            modo: tipoModo,
            esPropio: '1',
            vehiculoId: '',
            cisternaId: '',
            capacidadMaxima: 0,
            necesitaCisterna: false,
            totalLitros: 0,
            items: [],
            
            getTitulo() {
                const titulos = { 'diesel': 'Diesel', 'mgo': 'MGO', 'flete': 'Flete', 'compra': 'Compra' };
                return titulos[this.modo] || 'Planificación';
            },

            get porcentajeCarga() {
                if (this.capacidadMaxima <= 0) return 0;
                let p = Math.round((this.totalLitros / this.capacidadMaxima) * 100);
                return p > 100 ? 100 : p;
            },

            get excesoCarga() {
                return (this.esPropio == '1' && this.capacidadMaxima > 0 && this.totalLitros > this.capacidadMaxima);
            },

            cambioVehiculo(e) {
                const opt = e.target.options[e.target.selectedIndex];
                const cap = parseFloat(opt.dataset.capacidad || 0);
                this.necesitaCisterna = (cap === 0);
                this.capacidadMaxima = cap;
                this.cisternaId = '';
            },

            cambioCisterna(e) {
                const opt = e.target.options[e.target.selectedIndex];
                this.capacidadMaxima = parseFloat(opt.dataset.capacidad || 0);
            },

            abrirModal() {
                const modal = new bootstrap.Modal(document.getElementById('modalAdd'));
                modal.show();
            },

            addPedido(id, clienteId, nombre, litros, rif, cupo) {
                this.items.push({ 
                    pedido_id: id, 
                    cliente_id: clienteId, 
                    cliente_nombre: nombre,
                    cliente_rif: rif,
                    litros: parseFloat(litros),
                    observaciones: ''
                });
                this.calcularTotal();
                const modal = bootstrap.Modal.getInstance(document.getElementById('modalAdd'));
                modal.hide();
            },

            removerItem(index) {
                this.items.splice(index, 1);
                this.calcularTotal();
            },

            calcularTotal() {
                if(this.modo === 'compra') return;
                this.totalLitros = this.items.reduce((sum, item) => sum + parseFloat(item.litros || 0), 0);
            },

            formatoNumero(n) {
                return new Intl.NumberFormat('es-VE').format(n);
            }
        }
    }
</script>

<style>
    .btn-orange { background-color: #ff6600 !important; color: white !important; }
    .text-orange { color: #ff6600 !important; }
    .fw-black { font-weight: 900 !important; }
    .border-left-orange { border-left: 5px solid #ff6600 !important; }
    .border-orange { border-color: #ff6600 !important; }
</style>
@endsection