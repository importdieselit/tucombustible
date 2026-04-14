@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" x-data="constructorDeCarga()">
    <form action="{{ route('logistica.store') }}" method="POST">
        @csrf
        
        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Configuración de Viaje</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Producto a Despachar</label>
                            <select name="tipo_combustible_id" class="form-control" 
                                    x-model="tipoCombustibleId" required>
                                <option value="">Seleccione producto...</option>
                                @foreach($tipos as $tipo)
                                    <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Fecha Programada</label>
                            <input type="date" name="fecha_programada" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tipo de Unidad</label>
                            <select name="es_transporte_propio" class="form-control" x-model="esPropio">
                                <option value="1">Flota ImporDiesel</option>
                                <option value="0">Transporte Externo / Cliente</option>
                            </select>
                        </div>

                        <template x-if="esPropio == '1'">
                            <div>
                                <div class="mb-3">
                                    <label class="form-label">Vehículo / Chuto</label>
                                    <select name="vehiculo_id" class="form-control" x-model="vehiculoId" @change="actualizarCapacidad()">
                                        <option value="">Seleccione unidad...</option>
                                        @foreach($vehiculos as $v)
                                            <option value="{{ $v->id }}" data-capacidad="{{ $v->carga_max }}">{{ $v->placa }} ({{ $v->tipo }})</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3" x-show="necesitaCisterna" x-transition>
                                    <label class="form-label text-danger font-weight-bold small">REQUIERE CISTERNA ACOPLADA</label>
                                    <select name="cisterna_id" class="form-control border-danger" x-model="cisternaId" @change="actualizarCapacidad()">
                                        <option value="">Seleccione cisterna...</option>
                                        @foreach($vehiculos as $v)
                                            @if($v->carga_max > 0)
                                                <option value="{{ $v->id }}" data-capacidad="{{ $v->carga_max }}">{{ $v->placa }} - Cap: {{ number_format($v->carga_max) }}L</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Chófer</label>
                                    <select name="chofer_id" class="form-control">
                                        @foreach($personal as $p)
                                            <option value="{{ $p->id }}">{{ $p->persona->nombre }} {{ $p->persona->apellido }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </template>

                        <template x-if="esPropio == '0'">
                            <div class="mb-3">
                                <label class="form-label">Datos de Unidad Externa</label>
                                <input type="text" name="vehiculo_externo" class="form-control" placeholder="Placa / Empresa / Color">
                            </div>
                        </template>
                    </div>
                </div>

                <div class="card shadow-sm border-0" :class="totalLitros > capacidadMaxima ? 'bg-danger text-white' : 'bg-light'">
                    <div class="card-body text-center">
                        <span class="small text-uppercase">Total Planificado</span>
                        <h2 class="mb-0 font-weight-bold">
                            <span x-text="formatoNumero(totalLitros)"></span> L
                        </h2>
                        <hr>
                        <span class="small text-uppercase">Capacidad Unidad</span>
                        <h4 class="mb-0">
                            <span x-text="formatoNumero(capacidadMaxima)"></span> L
                        </h4>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Plan de Despacho (Destinos)</h5>
                        <button type="button" class="btn btn-sm btn-success" @click="abrirModalAdd()">
                            <i class="fas fa-plus-circle"></i> Agregar Destino
                        </button>
                    </div>
                    
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Cliente / Destino</th>
                                    <th width="180">Litros</th>
                                    <th>Referencia / Buque</th>
                                    <th width="50"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, index) in items" :key="index">
                                    <tr>
                                        <td>
                                            <span class="font-weight-bold" x-text="item.cliente_nombre"></span><br>
                                            <small class="text-muted" x-text="item.referencia"></small>
                                            <input type="hidden" :name="'items['+index+'][cliente_id]'" :value="item.cliente_id">
                                            <input type="hidden" :name="'items['+index+'][pedido_id]'" :value="item.pedido_id">
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <input type="number" :name="'items['+index+'][litros]'" 
                                                       class="form-control" 
                                                       x-model.number="item.litros" @input="calcularTotal()">
                                                <div class="input-group-append"><span class="input-group-text">L</span></div>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" :name="'items['+index+'][buque_nombre]'" 
                                                   class="form-control form-control-sm" 
                                                   x-model="item.buque_nombre" 
                                                   :placeholder="tipoCombustibleId == 2 ? 'Nombre Buque' : 'Dirección/Obs'">
                                        </td>
                                        <td class="align-middle">
                                            <button type="button" class="btn btn-sm text-danger" @click="removerItem(index)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="items.length === 0">
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="fas fa-truck-loading fa-3x mb-3 d-block"></i>
                                        No hay despachos agregados a este viaje.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer bg-white text-right">
                        <button type="submit" class="btn btn-primary btn-lg" :disabled="items.length === 0 || (esPropio == '1' && totalLitros > capacidadMaxima)">
                            Finalizar Planificación
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="modal fade" id="modalAdd" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">Agregar Destino a la Carga</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-pills nav-justified mb-3" id="pills-tab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="pill" href="#tab-pedidos">Pedidos Pendientes (Diesel)</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="pill" href="#tab-manual">Carga Manual / MGO</a>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="tab-pedidos">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr class="text-muted">
                                            <th>Cliente</th>
                                            <th>Solicitado</th>
                                            <th>Fecha Entrega</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($pedidosPendientes as $ped)
                                            <tr>
                                                <td class="align-middle">
                                                    <strong>{{ $ped->cliente->nombre }}</strong><br>
                                                    <small>{{ $ped->cliente->rif }}</small>
                                                </td>
                                                <td class="align-middle">{{ number_format($ped->cantidad_solicitada) }} L</td>
                                                <td class="align-middle text-primary">{{ $ped->fecha_entrega->format('d/m/Y') }}</td>
                                                <td class="text-right">
                                                    <button class="btn btn-sm btn-primary" 
                                                            @click="addPedido({{ $ped->id }}, {{ $ped->cliente_id }}, '{{ $ped->cliente->nombre }}', {{ $ped->cantidad_solicitada }})">
                                                        Seleccionar
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center py-3">No hay pedidos pendientes de Diesel.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab-manual">
                            <div class="row">
                                <div class="col-md-7">
                                    <label class="small font-weight-bold text-uppercase">Cliente</label>
                                    <select class="form-control mb-3" x-model="manual.cliente_id">
                                        <option value="">Seleccione cliente...</option>
                                        @foreach($clientes as $cli)
                                            <option value="{{ $cli->id }}" 
                                                    data-gasco="{{ $cli->cupo_gasco }}"
                                                    data-aprobado="{{ $cli->cupo }}">
                                                {{ $cli->nombre }} 
                                                <template x-if="tipoCombustibleId != 2">
                                                    <span>(GASCO: {{ number_format($cli->cupo_gasco) }}L / Ref: {{ number_format($cli->cupo) }}L)</span>
                                                </template>
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="small font-weight-bold text-uppercase">Litros</label>
                                    <input type="number" class="form-control mb-3" x-model.number="manual.litros">
                                </div>
                                <div class="col-md-12" x-show="tipoCombustibleId == 2"> <label class="small font-weight-bold text-uppercase text-info">Nombre del Buque (MGO)</label>
                                    <input type="text" class="form-control mb-3" x-model="manual.buque_nombre" placeholder="Ej: MV Sea Wolf">
                                </div>
                            </div>
                            <button class="btn btn-primary btn-block" @click="addManual()">Agregar a la Lista</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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

            actualizarCapacidad() {
                if(this.esPropio == '0') {
                    this.capacidadMaxima = 999999; // Sin límite para externos
                    return;
                }

                let selVehiculo = document.querySelector(`select[name="vehiculo_id"] option:checked`);
                let capVehiculo = parseFloat(selVehiculo.dataset.capacidad || 0);

                if (capVehiculo === 0) {
                    this.necesitaCisterna = true;
                    let selCisterna = document.querySelector(`select[name="cisterna_id"] option:checked`);
                    this.capacidadMaxima = parseFloat(selCisterna?.dataset.capacidad || 0);
                } else {
                    this.necesitaCisterna = false;
                    this.cisternaId = '';
                    this.capacidadMaxima = capVehiculo;
                }
            },

            abrirModalAdd() {
                $('#modalAdd').modal('show');
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
                $('#modalAdd').modal('hide');
            },

            addManual() {
                if (!this.manual.cliente_id || this.manual.litros <= 0) return;

                // Obtenemos los datos del cliente seleccionado en el select
                const sel = document.querySelector('#tab-manual select');
                const option = sel.options[sel.selectedIndex];
                const nombre = option.text;
                const disponible = parseFloat(option.dataset.disponible || 0);

                // Si NO es MGO (ID 2), verificamos el cupo disponible
                if (this.tipoCombustibleId != 2 && this.manual.litros > disponible) {
                    alert('Error: El litraje excede el cupo disponible del cliente para Diesel.');
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
                $('#modalAdd').modal('hide');
            },

            removerItem(index) {
                this.items.splice(index, 1);
                this.calcularTotal();
            },

            calcularTotal() {
                this.totalLitros = this.items.reduce((sum, item) => sum + parseFloat(item.litros || 0), 0);
            },

            formatoNumero(n) {
                return new Intl.NumberFormat('es-VE').format(n);
            }
        }
    }
</script>
@endsection