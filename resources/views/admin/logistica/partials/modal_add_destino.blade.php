<div class="modal fade" id="modalAdd" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h6 class="modal-title fw-bold text-uppercase small">Añadir Destino</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body p-0">
                <ul class="nav nav-tabs nav-fill bg-light" id="destinyTabs">
                    <template x-if="modo === 'diesel'">
                        <li class="nav-item">
                            <button class="nav-link active fw-bold small py-3" data-bs-toggle="tab" data-bs-target="#tab-pedidos">
                                PEDIDOS PENDIENTES
                            </button>
                        </li>
                    </template>
                    <li class="nav-item">
                        <button class="nav-link fw-bold small py-3" :class="modo !== 'diesel' ? 'active' : ''" data-bs-toggle="tab" data-bs-target="#tab-manual">
                            SELECCIÓN MANUAL
                        </button>
                    </li>
                </ul>

                <div class="tab-content p-4">
                    <template x-if="modo === 'diesel'">
                        <div class="tab-pane fade show active" id="tab-pedidos">
                            <div class="table-responsive" style="max-height: 350px;">
                                <table class="table table-sm table-hover">
                                    <thead class="text-muted small uppercase">
                                        <tr>
                                            <th>Cliente</th>
                                            <th>Cantidad</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($pedidosPendientes as $ped)
                                            <tr>
                                                <td>
                                                    <span class="fw-bold d-block text-dark">{{ $ped->cliente->nombre }}</span>
                                                    <small class="text-muted">RIF: {{ $ped->cliente->rif }}</small>
                                                </td>
                                                <td class="fw-black text-orange">{{ number_format($ped->cantidad_solicitada) }} L</td>
                                                <td class="text-end">
                                                    <button type="button" class="btn btn-dark btn-sm rounded-pill px-3" 
                                                            @click="addPedido({{ $ped->id }}, {{ $ped->cliente_id }}, '{{ $ped->cliente->nombre }}', {{ $ped->cantidad_solicitada }}, '{{ $ped->cliente->rif }}', {{ $ped->cliente->cupo }})">
                                                        AÑADIR
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="text-center py-4 text-muted">No hay pedidos pendientes.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>
                    
                    <div class="tab-pane fade" :class="modo !== 'diesel' ? 'show active' : ''" id="tab-manual">
                        <div class="row g-3" x-data="{ manual: { cliente_id: '', litros: 0, buque: '' } }">
                            <div class="col-md-12">
                                <label class="small fw-bold text-muted uppercase">Cliente</label>
                                <select class="form-select border-orange fw-bold" x-model="manual.cliente_id" id="selectManual">
                                    <option value="">-- Buscar Empresa --</option>
                                    @foreach($clientes as $cli)
                                        <option value="{{ $cli->id }}" data-nombre="{{ $cli->nombre }}" data-rif="{{ $cli->rif }}" data-cupo="{{ $cli->cupo ?? 0 }}">
                                            {{ $cli->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label class="small fw-bold text-muted uppercase">Cantidad (Litros)</label>
                                <input type="number" class="form-control border-orange fw-black" x-model.number="manual.litros">
                            </div>
                            
                            {{-- CONDICIONAL: Solo aparece si es MGO --}}
                            <div class="col-md-8" x-show="modo === 'mgo'">
                                <label class="small fw-bold text-muted uppercase">Nombre del Buque</label>
                                <input type="text" class="form-control" x-model="manual.buque" placeholder="Ej: MV Clipper">
                            </div>

                            <div class="col-12 mt-4">
                                <button type="button" class="btn btn-orange text-white fw-black btn-lg w-100 shadow-sm" 
                                        @click="
                                            const sel = document.getElementById('selectManual');
                                            const opt = sel.options[sel.selectedIndex];
                                            if(!manual.cliente_id || manual.litros <= 0) return alert('Datos incompletos');
                                            
                                            items.push({
                                                pedido_id: null,
                                                cliente_id: manual.cliente_id,
                                                cliente_nombre: opt.dataset.nombre,
                                                cliente_rif: opt.dataset.rif,
                                                cliente_cupo: parseFloat(opt.dataset.cupo),
                                                litros: manual.litros,
                                                muelle_id: '',
                                                buque_nombre: manual.buque, 
                                                buque_imo: '', 
                                                buque_bandera: '', 
                                                observaciones: ''
                                            });
                                            calcularTotal();
                                            cerrarModal();
                                            manual.litros = 0; manual.buque = '';
                                        ">
                                    AGREGAR DESTINO
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>