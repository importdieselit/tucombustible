@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" x-data="loadBuilder()">
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
                            <label class="form-label font-weight-bold">Producto</label>
                            <select name="tipo_combustible_id" class="form-control" 
                                    x-model="tipoCombustible" @change="filtrarPedidos()">
                                <option value="">Seleccione producto...</option>
                                @foreach($tipos as $tipo)
                                    <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Fecha Programada</label>
                            <input type="date" name="fecha_programada" class="form-control" value="{{ date('Y-m-d') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Chófer</label>
                            <select name="chofer_id" class="form-control select2">
                                @foreach($personal as $p)
                                    <option value="{{ $p->id }}">{{ $p->persona->nombre }} {{ $p->persona->apellido }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ayudante (Opcional)</label>
                            <select name="ayudante_id" class="form-control">
                                <option value="">Sin ayudante</option>
                                @foreach($personal as $p)
                                    <option value="{{ $p->id }}">{{ $p->persona->nombre }} {{ $p->persona->apellido }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Vehículo / Chuto</label>
                            <select name="vehiculo_id" class="form-control" x-model="vehiculoId" @change="actualizarCapacidad()">
                                <option value="">Seleccione unidad...</option>
                                @foreach($vehiculos as $v)
                                    <option value="{{ $v->id }}" data-capacidad="{{ $v->carga_max }}">{{ $v->placa }} ({{ $v->tipo }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3" x-show="necesitaCisterna" x-transition>
                            <label class="form-label text-danger font-weight-bold">Requiere Cisterna</label>
                            <select name="cisterna_id" class="form-control border-danger" x-model="cisternaId" @change="actualizarCapacidad()">
                                <option value="">Seleccione cisterna...</option>
                                @foreach($vehiculos as $v)
                                    @if($v->carga_max > 0)
                                        <option value="{{ $v->id }}" data-capacidad="{{ $v->carga_max }}">{{ $v->placa }} - Cap: {{ number_format($v->carga_max) }}L</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div class="p-3 bg-light rounded text-center">
                            <span class="text-muted d-block small uppercase">Capacidad Disponible</span>
                            <h3 class="mb-0" :class="totalLitros > capacidadMaxima ? 'text-danger' : 'text-primary'">
                                <span x-text="formatoNumero(totalLitros)"></span> / 
                                <span x-text="formatoNumero(capacidadMaxima)"></span> L
                            </h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Detalle de Carga</h5>
                        <button type="button" class="btn btn-sm btn-success" @click="abrirModalManual()">+ Agregar Cliente Manual</button>
                    </div>
                    
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Cliente</th>
                                    <th width="150">Litros</th>
                                    <th>Obs.</th>
                                    <th width="50"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, index) in listaCarga" :key="index">
                                    <tr>
                                        <td>
                                            <span class="font-weight-bold" x-text="item.nombre"></span><br>
                                            <small class="text-muted" x-text="item.rif"></small>
                                            <input type="hidden" :name="'clientes['+index+'][cliente_id]'" :value="item.id">
                                            <input type="hidden" :name="'clientes['+index+'][pedido_id]'" :value="item.pedido_id">
                                        </td>
                                        <td>
                                            <input type="number" :name="'clientes['+index+'][litros]'" 
                                                   class="form-control form-control-sm" 
                                                   x-model.number="item.litros" @input="calcularTotal()">
                                        </td>
                                        <td>
                                            <input type="text" :name="'clientes['+index+'][observacion]'" 
                                                   class="form-control form-control-sm" x-model="item.observacion">
                                        </td>
                                        <td class="align-middle">
                                            <button type="button" class="btn btn-sm btn-outline-danger" @click="removerItem(index)">&times;</button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="listaCarga.length === 0">
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        No hay clientes agregados a la carga.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer bg-white text-right">
                        <button type="submit" class="btn btn-primary btn-lg" :disabled="totalLitros > capacidadMaxima || totalLitros === 0">
                            Guardar Planificación
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
    function loadBuilder() {
        return {
            tipoCombustible: '',
            vehiculoId: '',
            cisternaId: '',
            capacidadMaxima: 0,
            necesitaCisterna: false,
            totalLitros: 0,
            listaCarga: [],

            actualizarCapacidad() {
                // Lógica de validación de camión vs cisterna
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
                this.calcularTotal();
            },

            agregarCliente(data) {
                this.listaCarga.push({
                    id: data.id,
                    nombre: data.nombre,
                    rif: data.rif,
                    litros: data.litros || 0,
                    pedido_id: data.pedido_id || null,
                    observacion: ''
                });
                this.calcularTotal();
            },

            removerItem(index) {
                this.listaCarga.splice(index, 1);
                this.calcularTotal();
            },

            calcularTotal() {
                this.totalLitros = this.listaCarga.reduce((sum, item) => sum + parseFloat(item.litros || 0), 0);
            },

            formatoNumero(n) {
                return new Intl.NumberFormat('es-VE').format(n);
            },

            filtrarPedidos() {
                // Aquí podrías disparar un fetch para traer pedidos pendientes del tipo de combustible seleccionado
                console.log("Cargando pedidos para tipo:", this.tipoCombustible);
            }
        }
    }
</script>
@endsection