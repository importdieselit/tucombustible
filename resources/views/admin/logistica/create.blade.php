@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" x-data="constructorDeCarga('{{ $tipo }}')">
    {{-- ENCABEZADO DINÁMICO --}}
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center bg-white p-3 shadow-sm rounded border-left-orange">
            <div>
                <h3 class="text-orange mb-0 fw-black text-uppercase">
                    <i class="fas fa-file-signature me-2"></i>Nueva Planificación: <span x-text="getTitulo()"></span>
                </h3>
                <p class="text-muted small mb-0">Complete los datos logísticos para la operación de <span x-text="getTitulo().toLowerCase()"></span>.</p>
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
        {{-- Campo oculto para saber qué estamos guardando --}}
        <input type="hidden" name="tipo_planificacion" value="{{ $tipo }}">

        <div class="row">
            {{-- COLUMNA IZQUIERDA: CONFIGURACIÓN --}}
            <div class="col-md-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-dark text-white py-3">
                        <h6 class="mb-0 fw-bold text-uppercase small">1. Datos del Viaje</h6>
                    </div>
                    <div class="card-body">
                        
                        {{-- Producto (Solo se muestra/selecciona si no es Flete) --}}
                        <template x-if="modo !== 'flete'">
                            <div class="mb-3">
                                <label class="small fw-bold text-muted text-uppercase">Producto</label>
                                <select name="tipo_combustible_id" class="form-select fw-bold border-orange" required>
                                    @foreach($tipos as $t)
                                        <option value="{{ $t->id }}" 
                                            {{ (isset($tipo) && (($tipo == 'diesel' && $t->id == 1) || ($tipo == 'mgo' && $t->id == 2))) ? 'selected' : '' }}>
                                            {{ $t->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </template>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted text-uppercase">Fecha Programada</label>
                            <input type="date" name="fecha_programada" class="form-control fw-bold" value="{{ date('Y-m-d') }}" required>
                        </div>

                        {{-- Origen si es Compra --}}
                        <template x-if="modo === 'compra'">
                            <div class="mb-3">
                                <label class="small fw-bold text-muted text-uppercase">Código SAP / Factura</label>
                                <input type="text" name="codigo_sap" class="form-control border-orange fw-bold" placeholder="Ej: 80001234">
                            </div>
                        </template>

                        <div class="mb-3 border-top pt-3">
                            <label class="small fw-bold text-muted text-uppercase">Unidad de Transporte</label>
                            <div class="btn-group w-100 mb-3" role="group">
                                <input type="radio" class="btn-check" name="es_transporte_propio" id="propio1" value="1" x-model="esPropio">
                                <label class="btn btn-outline-dark btn-sm fw-bold" for="propio1">PROPIA</label>
                                
                                <input type="radio" class="btn-check" name="es_transporte_propio" id="propio0" value="0" x-model="esPropio">
                                <label class="btn btn-outline-dark btn-sm fw-bold" for="propio0">EXTERNA</label>
                            </div>
                        </div>

                        {{-- SECCIÓN FLOTA PROPIA --}}
                        <div x-show="esPropio == '1'" x-transition>
                            <div class="mb-3">
                                <label class="small fw-bold text-muted text-uppercase">Vehículo (Chuto/Camión)</label>
                                <select name="vehiculo_id" class="form-select form-select-sm fw-bold" x-model="vehiculoId" @change="cambioVehiculo($event)">
                                    <option value="">Seleccione...</option>
                                    @foreach($vehiculos as $v)
                                        <option value="{{ $v->id }}" data-capacidad="{{ $v->carga_max }}">{{ $v->placa }} ({{ $v->tipo }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3 p-2 bg-light rounded border-orange" x-show="necesitaCisterna">
                                <label class="small fw-bold text-danger text-uppercase">Requiere Cisterna/Remolque</label>
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
                        </div>

                        {{-- SECCIÓN EXTERNA --}}
                        <div x-show="esPropio == '0'" x-transition>
                            <div class="mb-3">
                                <label class="small fw-bold text-muted text-uppercase">Datos Transporte Externo</label>
                                <input type="text" name="vehiculo_externo" class="form-control form-control-sm border-orange" placeholder="Placa / Empresa / Chofer">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- PANEL DE CAPACIDAD (STICKY) --}}
                <div x-show="modo !== 'flete'" class="card shadow-sm sticky-top border-0" style="top: 20px;">
                    <div class="card-body text-center p-3" :class="excesoCarga ? 'bg-danger text-white rounded' : 'bg-light rounded'">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small fw-bold text-uppercase">Carga Total</span>
                            <span class="small fw-bold" x-text="porcentajeCarga + '%'"></span>
                        </div>
                        <div class="progress mb-3" style="height: 8px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 :class="excesoCarga ? 'bg-white' : 'bg-orange'"
                                 role="progressbar" :style="'width: ' + porcentajeCarga + '%'"></div>
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

            {{-- COLUMNA DERECHA: LOGÍSTICA ESPECÍFICA --}}
            <div class="col-md-8">
                
                {{-- CASO A: DESPACHOS (DIESEL / MGO) --}}
                <template x-if="modo === 'diesel' || modo === 'mgo'">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                            <h6 class="mb-0 fw-black text-uppercase small">2. Plan de Despacho (Destinos)</h6>
                            <button type="button" class="btn btn-dark btn-sm fw-bold shadow-sm" @click="abrirModal()">
                                <i class="fas fa-plus-circle me-1"></i> AGREGAR DESTINO
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light text-muted small uppercase">
                                        <tr>
                                            <th class="ps-3">Cliente</th>
                                            <th width="180">Litros</th>
                                            <th>Referencia / Observación</th>
                                            <th width="50"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(item, index) in items" :key="index">
                                            <tr>
                                                <td class="ps-3">
                                                    <span class="fw-bold text-dark d-block" x-text="item.cliente_nombre"></span>
                                                    <span class="badge bg-light text-muted border" style="font-size: 9px;" x-text="item.referencia"></span>
                                                    <input type="hidden" :name="'items['+index+'][cliente_id]'" :value="item.cliente_id">
                                                    <input type="hidden" :name="'items['+index+'][pedido_id]'" :value="item.pedido_id">
                                                </td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" :name="'items['+index+'][litros]'" class="form-control fw-bold border-orange" x-model.number="item.litros" @input="calcularTotal()">
                                                        <span class="input-group-text bg-white border-orange">L</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <input type="text" :name="'items['+index+'][buque_nombre]'" class="form-control form-control-sm" x-model="item.buque_nombre" :placeholder="modo === 'mgo' ? 'Nombre del Buque' : 'Nota'">
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-link text-danger p-0" @click="removerItem(index)"><i class="fas fa-times-circle fa-lg"></i></button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- CASO B: FLETES --}}
                <template x-if="modo === 'flete'">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="mb-0 fw-black text-uppercase small text-dark">2. Detalles del Servicio de Flete</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted uppercase">Cliente que solicita</label>
                                    <select name="cliente_id" class="form-select border-orange fw-bold">
                                        @foreach($clientes as $cli)
                                            <option value="{{ $cli->id }}">{{ $cli->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted uppercase">Punto de Salida / Origen</label>
                                    <input type="text" name="punto_salida" class="form-control" placeholder="Ej: Planta Carenero">
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted uppercase">Punto de Llegada / Destino</label>
                                    <input type="text" name="punto_llegada" class="form-control" placeholder="Ej: Sede Cliente Maracay">
                                </div>
                                <div class="col-md-3">
                                    <label class="small fw-bold text-muted uppercase">Volumen (L)</label>
                                    <input type="number" name="litros" class="form-control fw-black text-orange" placeholder="0">
                                </div>
                                <div class="col-md-3">
                                    <label class="small fw-bold text-muted uppercase">Monto Flete ($)</label>
                                    <input type="number" step="0.01" name="monto_flete" class="form-control fw-bold" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- BOTÓN FINAL --}}
                <div class="mt-4 text-end">
                    <button type="submit" class="btn btn-orange btn-lg px-5 text-white fw-black shadow" 
                            :disabled="(modo !== 'flete' && items.length === 0) || excesoCarga">
                        <i class="fas fa-check-double me-2"></i>CONFIRMAR OPERACIÓN
                    </button>
                </div>
            </div>
        </div>
    </form>

    {{-- EL MODAL SE MANTIENE PERO CON FILTRO PARA PEDIDOS --}}
    @include('admin.logistica.partials.modal_add_destino')

</div>

{{-- Alpine.js Logic --}}
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
                const titulos = {
                    'diesel': 'Despacho de Diesel',
                    'mgo': 'Despacho de MGO',
                    'flete': 'Servicio de Flete',
                    'compra': 'Compra / Recepción'
                };
                return titulos[this.modo] || 'Planificación';
            },

            get porcentajeCarga() {
                if (this.capacidadMaxima <= 0) return 0;
                let perc = Math.round((this.totalLitros / this.capacidadMaxima) * 100);
                return perc > 100 ? 100 : perc;
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
                const myModal = new bootstrap.Modal(document.getElementById('modalAdd'));
                myModal.show();
            },

            addPedido(id, clienteId, nombre, litros) {
                this.items.push({ pedido_id: id, cliente_id: clienteId, cliente_nombre: nombre, litros: litros, referencia: 'Pedido #' + id, buque_nombre: '' });
                this.calcularTotal();
                this.cerrarModal();
            },

            removerItem(index) {
                this.items.splice(index, 1);
                this.calcularTotal();
            },

            calcularTotal() {
                this.totalLitros = this.items.reduce((sum, item) => sum + parseFloat(item.litros || 0), 0);
            },

            cerrarModal() {
                const modalEl = document.getElementById('modalAdd');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            },

            formatoNumero(n) {
                return new Intl.NumberFormat('es-VE').format(n);
            }
        }
    }
</script>

<style>
    .border-left-orange { border-left: 5px solid #ff6600 !important; }
    .text-orange { color: #ff6600; }
    .btn-orange { background-color: #ff6600; color: white; border: none; }
    .btn-orange:hover { background-color: #e65c00; color: white; }
    .fw-black { font-weight: 900; }
</style>
@endsection