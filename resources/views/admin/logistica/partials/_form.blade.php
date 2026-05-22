@php
    // Extraemos la compra de forma segura al inicio del archivo si es una colección
    $compra = isset($viaje) ? $viaje->compraCombustible : null;
    if ($compra && $compra instanceof \Illuminate\Database\Eloquent\Collection) {
        $compra = $compra->first();
    }
@endphp
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
                        <input type="text" name="producto_flete" class="form-control fw-bold border-orange" 
                               value="{{ old('producto_flete', $viaje->producto_flete ?? '') }}" placeholder="Ej: Tuberías, Lubricantes, etc." required>
                    </div>
                </template>

                <template x-if="modo === 'compra'">
                    <div class="mb-3">
                        <label class="small fw-bold text-muted text-uppercase">Tipo de Combustible</label>
                        <select name="tipo_combustible_id" class="form-select fw-bold border-orange" required>
                            <option value="">Seleccione...</option>
                            @foreach($tipos as $tc)
                                <option value="{{ $tc->id }}" {{ old('tipo_combustible_id', $compra->tipo_combustible_id ?? ($viaje->tipo ?? '')) == $tc->id ? 'selected' : '' }}>
                                    {{ $tc->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </template>

                <template x-if="modo !== 'flete' && modo !== 'compra'">
                    <input type="hidden" name="tipo_combustible_id" value="{{ $tipoPlanificacionId }}">
                </template>

                <div class="mb-3">
                    <label class="small fw-bold text-muted text-uppercase">Fecha Programada</label>
                    <input type="date" name="fecha_programada" class="form-control fw-bold" @if(!isset($viaje)) min="{{ date('Y-m-d') }}" @endif
                        value="{{ old('fecha_programada', isset($viaje) ? \Carbon\Carbon::parse($viaje->fecha_salida ?? now())->format('Y-m-d') : date('Y-m-d')) }}" required>
                </div>

                {{-- NUEVO INPUT: DESTINO PARA TABULADOR --}}
                <div class="mb-3">
                    <label class="small fw-bold text-muted text-uppercase text-orange">Destino para Tabulador</label>
                    <select name="destino_ciudad" class="form-select fw-bold border-orange" required>
                        <option value="">Seleccione destino...</option>
                        @foreach($tabuladores as $tabulador)
                            {{-- Usamos el texto del destino como value para guardarlo directo en viajes --}}
                            <option value="{{ $tabulador->destino }}" 
                                {{ old('destino_ciudad', $viaje->destino_ciudad ?? '') == $tabulador->destino ? 'selected' : '' }}>
                                {{ $tabulador->destino }} {{ $tabulador->tipo_viaje ? "({$tabulador->tipo_viaje})" : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3" x-show="modo !== 'flete' && modo !== 'compra'">
                    <label class="small fw-bold text-muted text-uppercase">Sede de Despacho</label>
                    <select name="sede_id" class="form-select fw-bold border-orange" :required="modo !== 'flete' && modo !== 'compra'">
                        <option value="">Seleccione sede...</option>
                        @foreach($sedes as $s)
                            <option value="{{ $s->id }}" {{ old('sede_id', $viaje->sede_id ?? '') == $s->id ? 'selected' : '' }}>{{ $s->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3 border-top pt-3">
                    <label class="small fw-bold text-muted text-uppercase">Tipo de Transporte</label>
                    <div class="btn-group w-100 mb-3">
                        <input type="radio" class="btn-check" name="es_transporte_propio" id="propio1" value="1" x-model="esPropio">
                        <label class="btn btn-outline-dark btn-sm fw-bold" for="propio1">IMPORDIESEL</label>
                        <input type="radio" class="btn-check" name="es_transporte_propio" id="propio0" value="0" x-model="esPropio">
                        <label class="btn btn-outline-dark btn-sm fw-bold" for="propio0">EXTERNO</label>
                    </div>
                </div>

                <div x-show="esPropio == '1'" x-transition>
                    <div class="mb-3">
                        <label class="small fw-bold text-muted text-uppercase">Vehículo / Chuto</label>
                        <select name="vehiculo_id" id="vehiculo_select" class="form-select form-select-sm fw-bold" x-model="vehiculoId" @change="cambioVehiculo($event)" :required="esPropio == '1'">
                            <option value="">Seleccione...</option>
                            @foreach($vehiculos as $v)
                                <option value="{{ $v->id }}" data-tipo="{{ $v->tipo }}" data-capacidad="{{ $v->carga_max }}">
                                    {{ $v->flota }} - {{ $v->placa }} ({{ number_format($v->carga_max, 0, ',', '.') }} Lts)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3 p-2 bg-light rounded border-orange" x-show="tipoVehiculoSeleccionado == '3'" x-transition>
                        <label class="small fw-bold text-danger text-uppercase">Cisterna / Remolque</label>
                        <select name="cisterna" id="cisterna_select" class="form-select form-select-sm fw-bold border-danger" x-model="cisternaId" @change="cambioCisterna($event)" :required="esPropio == '1' && tipoVehiculoSeleccionado == '3'">
                            <option value="">Seleccione acople...</option>
                            @foreach($cisternas ?? [] as $c)
                                <option value="{{ $c->id }}" data-capacidad="{{ $c->vol ?? $c->carga_max }}">
                                    {{ $c->flota }} {{ $c->placa }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted text-uppercase">Chofer</label>
                        <select name="chofer_id" class="form-select form-select-sm fw-bold" :required="esPropio == '1'">
                            <option value="">Seleccione...</option>
                            @foreach($personal as $p)
                                <option value="{{ $p->id }}" {{ old('chofer_id', $viaje->chofer_id ?? '') == $p->id ? 'selected' : '' }}>
                                    {{ $p->persona->nombre }} {{ $p->persona->apellido }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted text-uppercase">Ayudante</label>
                        <select name="ayudante_id" class="form-select form-select-sm fw-bold">
                            <option value="">Seleccione...</option>
                            @foreach($personal as $p)
                                <option value="{{ $p->id }}" {{ old('ayudante_id', $viaje->ayudante_id ?? '') == $p->id ? 'selected' : '' }}>
                                    {{ $p->persona->nombre }} {{ $p->persona->apellido }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div x-show="esPropio == '0'" x-transition class="p-2 border rounded bg-light">
                    <div class="row g-2">
                        <div class="col-6 mb-2">
                            <label class="small fw-bold text-muted text-uppercase">Placa Vehículo</label>
                            <input type="text" name="vehiculo_externo" x-model="externo_vehiculo" class="form-control form-control-sm border-orange uppercase" placeholder="ABC-123" :required="esPropio == '0'">
                        </div>
                        <div class="col-6 mb-2">
                            <label class="small fw-bold text-muted text-uppercase">Placa Cisterna</label>
                            <input type="text" name="cisterna_externo" x-model="externo_cisterna" class="form-control form-control-sm border-orange uppercase" placeholder="Opcional">
                        </div>
                        <div class="col-12">
                            <div class="input-group input-group-sm mb-1">
                                <span class="input-group-text">Chofer</span>
                                <input type="text" name="chofer_externo" x-model="externo_chofer" class="form-control uppercase" placeholder="Nombre completo" :required="esPropio == '0'">
                            </div>
                            <div class="input-group input-group-sm mb-1">
                                <span class="input-group-text">Ayudante</span>
                                <input type="text" name="ayudante_externo" x-model="externo_ayudante" class="form-control uppercase" placeholder="Nombre completo">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div x-show="modo !== 'flete'" class="card shadow-sm sticky-top border-0" style="top: 20px;">
            <div class="card-body text-center p-3" :class="excesoCarga ? 'bg-danger text-white rounded' : 'bg-light rounded'">
                <div class="d-flex justify-content-between mb-1">
                    <span class="small fw-bold text-uppercase">Ocupación</span>
                    <span class="small fw-bold" x-text="porcentajeCarga + '%'"></span>
                </div>
                <div class="progress mb-3" style="height: 8px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" :class="excesoCarga ? 'bg-white' : 'bg-orange'" :style="'width: ' + porcentajeCarga + '%'"></div>
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
                                    <option value="{{ $prov->id }}" {{ old('proveedor_id', $compra->proveedor_id ?? '') == $prov->id ? 'selected' : '' }}>{{ $prov->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted text-uppercase">Sede de Recepción</label>
                            <select name="sede_id" class="form-select border-orange fw-bold" required>
                                <option value="">-- Seleccione --</option>
                                @foreach($sedes as $s)
                                    <option value="{{ $s->id }}" {{ old('sede_id', $compra->planta_destino_id ?? ($viaje->sede_id ?? '')) == $s->id ? 'selected' : '' }}>{{ $s->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="small fw-bold text-muted text-uppercase">Código SAP</label>
                            <input type="text" name="codigo_sap" class="form-control border-orange fw-bold" value="{{ old('codigo_sap', $compra->sap ?? ($viaje->codigo_sap ?? '')) }}">
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
                            <textarea name="observaciones" class="form-control border-light" rows="3">{{ old('observaciones', $compra->observaciones ?? ($viaje->observaciones ?? '')) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        {{-- MODO DIESEL / MGO --}}
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
                                    <th x-show="modo === 'mgo'" width="250">Logística Buque</th>
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
                                            <input type="hidden" :name="'items['+index+'][pedido_id]'" :value="item.pedido_id">
                                        </td>
                                        <td><input type="number" :name="'items['+index+'][litros]'" class="form-control form-control-sm fw-bold border-orange" x-model.number="item.litros" @input="calcularTotal()"></td>
                                        <td x-show="modo === 'mgo'">
                                            <div class="d-flex flex-column gap-1">
                                                <select class="form-select form-select-sm mb-1" x-model="item.buque_id" @change="onBuqueChange(item)">
                                                    <option value="">-- Seleccione --</option>
                                                    <template x-for="b in buques.filter(x => x.cliente_id == item.cliente_id)" :key="b.id">
                                                        <option :value="b.id" x-text="b.nombre"></option>
                                                    </template>
                                                    <option value="manual" class="text-primary fw-bold">+ Manual</option>
                                                </select>
                                                <input type="hidden" :name="'items['+index+'][buque_id]'" x-model="item.buque_id">
                                                <input type="text" :name="'items['+index+'][buque_nombre]'" class="form-control form-control-sm border-primary" placeholder="Nombre" x-model="item.buque_nombre" x-show="item.buque_id === 'manual'">
                                                <div class="d-flex gap-1">
                                                    <input type="text" :name="'items['+index+'][buque_imo]'" class="form-control form-control-sm" placeholder="IMO" x-model="item.buque_imo" :readonly="item.buque_id !== 'manual' && item.buque_id !== ''">
                                                    <input type="text" :name="'items['+index+'][buque_bandera]'" class="form-control form-control-sm" placeholder="Bandera" x-model="item.buque_bandera" :readonly="item.buque_id !== 'manual' && item.buque_id !== ''">
                                                </div>
                                            </div>
                                        </td>
                                        <td><textarea :name="'items['+index+'][observaciones]'" class="form-control form-control-sm" rows="1" x-model="item.observaciones"></textarea></td>
                                        <td><button type="button" class="btn btn-link text-danger" @click="removerItem(index)"><i class="fas fa-times"></i></button></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </template>

        {{-- MODO FLETE --}}
        <template x-if="modo === 'flete'">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="mb-0 fw-black text-uppercase small text-dark">2. Detalles del Flete</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-12 mb-2">
                            <label class="small fw-bold text-muted uppercase">Cliente (Opcional)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-orange"><i class="fas fa-user-tie text-orange"></i></span>
                                <select name="cliente_id" class="form-select border-orange fw-black text-uppercase" style="font-size: 12px;">
                                    <option value="">-- MOVIMIENTO INTERNO (SIN CLIENTE) --</option>
                                    @foreach($clientes as $cliente)
                                        <option value="{{ $cliente->id }}" {{ old('cliente_id', $viaje->cliente_id ?? '') == $cliente->id ? 'selected' : '' }}>{{ $cliente->nombre }} - {{ $cliente->rif }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted uppercase">Punto Salida</label>
                            <input type="text" name="punto_salida" class="form-control border-orange uppercase fw-bold" placeholder="EJ: Planta El Palito" value="{{ old('punto_salida', $viaje->punto_salida ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted uppercase">Punto Llegada</label>
                            <input type="text" name="punto_llegada" class="form-control border-orange uppercase fw-bold" placeholder="EJ: Boleíta" value="{{ old('punto_llegada', $viaje->punto_llegada ?? '') }}">
                        </div>
                        <div class="col-12 mt-3">
                            <label class="small fw-bold text-muted uppercase">Observaciones del Flete</label>
                            <textarea name="observaciones" class="form-control border-light" rows="3">{{ old('observaciones', $viaje->observaciones ?? '') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div class="mt-4 text-end">
            <button type="submit" class="btn btn-orange btn-lg px-5 text-white fw-black shadow" :disabled="excesoCarga">
                {{ isset($viaje) ? 'ACTUALIZAR PLANIFICACIÓN' : 'GUARDAR PLANIFICACIÓN' }}
            </button>
        </div>
    </div>
</div>