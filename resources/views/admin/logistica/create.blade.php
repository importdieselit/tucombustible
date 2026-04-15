@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" x-data="constructorDeCarga()">
    {{-- ENCABEZADO --}}
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center bg-white p-3 shadow-sm rounded border-left-orange">
            <div>
                <h3 class="text-orange mb-0 fw-black"><i class="fas fa-route me-2"></i>Nueva Planificación de Despacho</h3>
                <p class="text-muted small mb-0">Gestión logística de rutas y asignación de unidades.</p>
            </div>
            <div class="text-right">
                <span class="badge bg-dark text-white p-2 text-uppercase" x-text="getNombreCombustible()"></span>
            </div>
        </div>
    </div>

    <form action="{{ route('logistica.store') }}" method="POST">
        @csrf
        <div class="row">
            {{-- COLUMNA IZQUIERDA: CONFIGURACIÓN --}}
            <div class="col-md-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-dark text-white py-3">
                        <h6 class="mb-0 fw-bold text-uppercase small">1. Configuración de Viaje</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="small fw-bold text-muted text-uppercase">Producto</label>
                            <select name="tipo_combustible_id" class="form-select fw-bold border-orange" 
                                    x-model="tipoCombustibleId" required>
                                <option value="">Seleccione producto...</option>
                                @foreach($tipos as $tipo)
                                    <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="small fw-bold text-muted text-uppercase">Fecha Programada</label>
                            <input type="date" name="fecha_programada" class="form-control fw-bold" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <div class="mb-3 border-top pt-3">
                            <label class="small fw-bold text-muted text-uppercase">Origen de Transporte</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="es_transporte_propio" id="propio1" value="1" x-model="esPropio" checked>
                                <label class="btn btn-outline-dark btn-sm fw-bold" for="propio1">FLOTA PROPIA</label>
                                
                                <input type="radio" class="btn-check" name="es_transporte_propio" id="propio0" value="0" x-model="esPropio">
                                <label class="btn btn-outline-dark btn-sm fw-bold" for="propio0">EXTERNO / CLIENTE</label>
                            </div>
                        </div>

                        {{-- SECCIÓN FLOTA PROPIA --}}
                        <div x-show="esPropio == '1'" x-transition>
                            <div class="mb-3">
                                <label class="small fw-bold text-muted text-uppercase">Unidad (Chuto/Camión)</label>
                                <select name="vehiculo_id" class="form-select form-select-sm fw-bold" x-model="vehiculoId" @change="cambioVehiculo($event)">
                                    <option value="">Seleccione...</option>
                                    @foreach($vehiculos as $v)
                                        <option value="{{ $v->id }}" data-capacidad="{{ $v->carga_max }}">{{ $v->placa }} ({{ $v->tipo }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3 p-2 bg-light rounded border-orange" x-show="necesitaCisterna" x-transition>
                                <label class="small fw-bold text-danger text-uppercase"><i class="fas fa-trailer me-1"></i>Requiere Cisterna</label>
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
                                <label class="small fw-bold text-muted text-uppercase">Chofer Asignado</label>
                                <select name="chofer_id" class="form-select form-select-sm fw-bold">
                                    <option value="">Seleccione chofer...</option>
                                    @foreach($personal as $p)
                                        <option value="{{ $p->id }}">{{ $p->persona->nombre }} {{ $p->persona->apellido }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- SECCIÓN EXTERNA --}}
                        <div x-show="esPropio == '0'" x-transition>
                            <div class="mb-3">
                                <label class="small fw-bold text-muted text-uppercase">Identificación Unidad Externa</label>
                                <input type="text" name="vehiculo_externo" class="form-control form-control-sm" placeholder="Ej: Placa / Empresa / Color">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- PANEL DE CAPACIDAD (STICKY) --}}
                <div class="card shadow-sm sticky-top" style="top: 20px;" :class="excesoCarga ? 'border-danger' : 'border-success'">
                    <div class="card-body text-center p-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small fw-bold text-muted uppercase">Progreso de Carga</span>
                            <span class="small fw-bold" :class="excesoCarga ? 'text-danger' : 'text-success'" x-text="porcentajeCarga + '%'"></span>
                        </div>
                        <div class="progress mb-3" style="height: 10px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 :class="excesoCarga ? 'bg-danger' : 'bg-success'"
                                 role="progressbar" :style="'width: ' + porcentajeCarga + '%'"></div>
                        </div>
                        <div class="row g-0 border-top pt-2">
                            <div class="col-6 border-end">
                                <small class="d-block text-muted text-[9px] uppercase">Planificado</small>
                                <span class="fw-black fs-5" x-text="formatoNumero(totalLitros)"></span>
                            </div>
                            <div class="col-6">
                                <small class="d-block text-muted text-[9px] uppercase">Capacidad</small>
                                <span class="fw-black fs-5 text-muted" x-text="formatoNumero(capacidadMaxima)"></span>
                            </div>
                        </div>
                        <template x-if="excesoCarga">
                            <div class="alert alert-danger p-1 mt-2 mb-0 small fw-bold">
                                <i class="fas fa-exclamation-circle me-1"></i> EXCESO DE CAPACIDAD
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- COLUMNA DERECHA: DESTINOS --}}
            <div class="col-md-8">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                        <h6 class="mb-0 fw-bold text-uppercase small text-dark">2. Plan de Despacho (Destinos)</h6>
                        <button type="button" class="btn btn-dark btn-sm fw-bold shadow-sm" @click="abrirModal()">
                            <i class="fas fa-plus-circle me-1"></i> AGREGAR DESTINO
                        </button>
                    </div>
                    
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr class="text-muted small uppercase">
                                        <th class="ps-3">Cliente / Destino</th>
                                        <th width="150">Cantidad (L)</th>
                                        <th>Referencia / Buque</th>
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
                                                    <input type="number" :name="'items['+index+'][litros]'" 
                                                           class="form-control fw-bold border-orange" 
                                                           x-model.number="item.litros" @input="calcularTotal()">
                                                    <span class="input-group-text bg-white border-orange fw-bold">L</span>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="text" :name="'items['+index+'][buque_nombre]'" 
                                                       class="form-control form-control-sm" 
                                                       x-model="item.buque_nombre" 
                                                       :placeholder="tipoCombustibleId == 2 ? 'Nombre del Buque' : 'Observaciones'">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-link text-danger p-0" @click="removerItem(index)">
                                                    <i class="fas fa-times-circle fa-lg"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="items.length === 0">
                                        <td colspan="4" class="text-center py-5">
                                            <img src="https://cdn-icons-png.flaticon.com/512/2362/2362252.png" width="80" class="opacity-25 mb-3">
                                            <p class="text-muted fw-bold">No hay destinos agregados a esta planificación.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer bg-light border-top p-3 text-end">
                        <button type="submit" class="btn btn-orange btn-lg px-5 text-white fw-black shadow" 
                                :disabled="items.length === 0 || excesoCarga">
                            <i class="fas fa-check-double me-2"></i>FINALIZAR PLANIFICACIÓN
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- MODAL DE AGREGAR DESTINO --}}
    <div class="modal fade" id="modalAdd" tabindex="-1" aria-hidden="true" x-ref="modal">
        <div class="modal-dialog modal-lg shadow-lg">
            <div class="modal-content border-0">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold small text-uppercase">Agregar Destino a la Carga</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <ul class="nav nav-tabs nav-fill bg-light" id="destinyTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold small py-3" data-bs-toggle="tab" data-bs-target="#tab-pedidos" type="button">PEDIDOS DIESEL</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold small py-3" data-bs-toggle="tab" data-bs-target="#tab-manual" type="button">CARGA MANUAL / MGO</button>
                        </li>
                    </ul>
                    <div class="tab-content p-4">
                        {{-- TAB PEDIDOS --}}
                        <div class="tab-pane fade show active" id="tab-pedidos">
                            <div class="table-responsive" style="max-height: 400px;">
                                <table class="table table-sm table-hover align-middle">
                                    <thead class="text-muted small uppercase bg-white sticky-top">
                                        <tr>
                                            <th>Cliente</th>
                                            <th>Cantidad</th>
                                            <th>Fecha</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($pedidosPendientes as $ped)
                                            <tr>
                                                <td>
                                                    <span class="fw-bold d-block">{{ $ped->cliente->nombre }}</span>
                                                    <small class="text-muted">RIF: {{ $ped->cliente->rif }}</small>
                                                </td>
                                                <td class="fw-black">{{ number_format($ped->cantidad_solicitada) }} L</td>
                                                <td class="text-primary small fw-bold">{{ $ped->fecha_entrega->format('d/m/Y') }}</td>
                                                <td class="text-end">
                                                    <button class="btn btn-dark btn-sm fw-bold rounded-pill px-3" 
                                                            @click="addPedido({{ $ped->id }}, {{ $ped->cliente_id }}, '{{ $ped->cliente->nombre }}', {{ $ped->cantidad_solicitada }})">
                                                        SUMAR
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center py-4 text-muted small italic">No hay pedidos de Diesel pendientes.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        {{-- TAB MANUAL --}}
                        <div class="tab-pane fade" id="tab-manual">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="small fw-bold text-muted uppercase">Cliente Destino</label>
                                    <select class="form-select border-orange fw-bold" x-model="manual.cliente_id" id="selectManual">
                                        <option value="">Seleccione cliente...</option>
                                        @foreach($clientes as $cli)
                                            <option value="{{ $cli->id }}" 
                                                    data-nombre="{{ $cli->nombre }}"
                                                    data-disponible="{{ $cli->disponible ?? 0 }}">
                                                {{ $cli->nombre }} (Disp: {{ number_format($cli->disponible ?? 0) }}L)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted uppercase">Cantidad a Cargar (L)</label>
                                    <input type="number" class="form-control border-orange fw-black" x-model.number="manual.litros">
                                </div>
                                <div class="col-md-6">
                                    <label class="small fw-bold text-muted uppercase">Nombre Buque / Referencia</label>
                                    <input type="text" class="form-control border-orange" x-model="manual.buque_nombre" placeholder="Ej: MV Sea Wolf / Despacho Especial">
                                </div>
                                <div class="col-12 mt-4 text-center">
                                    <button class="btn btn-orange text-white fw-black btn-lg w-100" @click="addManual()">
                                        <i class="fas fa-plus-circle me-1"></i> AGREGAR A LA LISTA
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- SCRIPTS --}}
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
    function constructorDeCarga() {
        return {
            tipoCombustibleId: '{{ $tipoSeleccionado ?? "" }}',
            esPropio: '1',
            vehiculoId: '',
            cisternaId: '',
            capacidadMaxima: 0,
            necesitaCisterna: false,
            totalLitros: 0,
            items: [],
            manual: { cliente_id: '', litros: 0, buque_nombre: '' },

            // Reactividad mejorada para el UI de capacidad
            get porcentajeCarga() {
                if (this.capacidadMaxima <= 0) return 0;
                let perc = Math.round((this.totalLitros / this.capacidadMaxima) * 100);
                return perc > 100 ? 100 : perc;
            },

            get excesoCarga() {
                return (this.esPropio == '1' && this.capacidadMaxima > 0 && this.totalLitros > this.capacidadMaxima);
            },

            getNombreCombustible() {
                if(!this.tipoCombustibleId) return 'Seleccione Producto';
                return this.tipoCombustibleId == 1 ? 'Diesel (Cupo)' : 'MGO (Libre)';
            },

            cambioVehiculo(e) {
                const opt = e.target.options[e.target.selectedIndex];
                const cap = parseFloat(opt.dataset.capacidad || 0);
                
                if (cap === 0) {
                    this.necesitaCisterna = true;
                    this.capacidadMaxima = 0;
                    this.cisternaId = '';
                } else {
                    this.necesitaCisterna = false;
                    this.capacidadMaxima = cap;
                    this.cisternaId = '';
                }
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
                this.items.push({
                    pedido_id: id,
                    cliente_id: clienteId,
                    cliente_nombre: nombre,
                    litros: litros,
                    referencia: 'Pedido #' + id,
                    buque_nombre: ''
                });
                this.calcularTotal();
                this.cerrarModal();
            },

            addManual() {
                if (!this.manual.cliente_id || this.manual.litros <= 0) {
                    alert('Debe seleccionar un cliente y una cantidad válida.');
                    return;
                }

                // Obtener datos del select usando el ID
                const select = document.getElementById('selectManual');
                const opt = select.options[select.selectedIndex];
                const nombre = opt.dataset.nombre;
                const disponible = parseFloat(opt.dataset.disponible || 0);

                // Validación de Cupo para DIESEL (ID 1)
                if (this.tipoCombustibleId == 1 && this.manual.litros > disponible) {
                    alert('ERROR: El cliente no tiene suficiente cupo disponible (' + disponible + 'L).');
                    return;
                }

                this.items.push({
                    pedido_id: null,
                    cliente_id: this.manual.cliente_id,
                    cliente_nombre: nombre,
                    litros: this.manual.litros,
                    referencia: this.manual.buque_nombre ? 'MGO: ' + this.manual.buque_nombre : 'Carga Manual',
                    buque_nombre: this.manual.buque_nombre
                });

                this.calcularTotal();
                this.manual = { cliente_id: '', litros: 0, buque_nombre: '' };
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
    .bg-orange { background-color: #ff6600; }
    .border-orange { border-color: #ff6600 !important; }
    .btn-orange { background-color: #ff6600; color: white; border: none; }
    .btn-orange:hover { background-color: #e65c00; color: white; }
    .fw-black { font-weight: 900; }
</style>
@endsection