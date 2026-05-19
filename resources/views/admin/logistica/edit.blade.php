@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" x-data="constructorDeCarga('{{ $tipo }}', {{ $buques->toJson() }})">
    {{-- ENCABEZADO --}}
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center bg-white p-3 shadow-sm rounded border-left-orange">
            <div>
                <h3 class="text-orange mb-0 fw-black text-uppercase">
                    <i class="fas fa-file-signature me-2"></i>Editar Planificación: <span x-text="getTitulo()" class="text-uppercase"></span>
                </h3>
            </div>
            <div class="text-right">
                <a href="{{ route('logistica.index') }}" class="btn btn-sm btn-outline-secondary fw-bold">VOLVER</a>
            </div>
        </div>
    </div>

    <form action="{{ route('logistica.update', $viaje->id) }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="tipo_planificacion" value="{{ $tipoPlanificacionId }}">
        
        {{-- INYECTAMOS EL FORMULARIO CON LOS DATOS DEL VIAJE --}}
        @include('admin.logistica.partials._form', ['viaje' => $viaje])
    </form>

    @include('admin.logistica.partials.modal_add_destino')
</div>

<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
    function constructorDeCarga(tipoModo, buquesIniciales) {
        return {
            modo: tipoModo,
            buques: buquesIniciales || [],
            tipoVehiculoSeleccionado: '',
            esPropio: '1', vehiculo_externo: '', cisterna_externo: '', chofer_externo: '', ayudante_externo: '', vehiculoId: '', cisternaId: '', capacidadMaxima: 0, totalLitros: 0, items: [],
            
            init() {
                const viaje = @json($viaje);
                const detalles = @json($viaje->detalles ?? []);
                const compra = @json($viaje->compraCombustible ?? null);

                this.esPropio = viaje.es_transporte_externo ? '0' : '1';
                if (this.esPropio === '0') {
                    this.vehiculo_externo = viaje.vehiculo_externo || ''; this.cisterna_externo = viaje.cisterna_externa || ''; this.chofer_externo = viaje.chofer_externo || ''; this.ayudante_externo = viaje.ayudante_externo || '';
                }

                if (this.modo === 'compra' && compra) this.totalLitros = parseFloat(compra.cantidad_litros || 0);

                if (this.modo === 'diesel' || this.modo === 'mgo') {
                    this.items = detalles.map(d => ({
                        pedido_id: d.pedido_id || '', cliente_id: d.cliente_id || '', cliente_nombre: d.cliente ? d.cliente.nombre : 'Cliente', cliente_rif: d.cliente ? d.cliente.rif : '', litros: parseFloat(d.litros || 0), buque_id: d.buque_id || '', buque_nombre: d.buque_nombre || '', buque_imo: d.buque_imo || '', buque_bandera: d.buque_bandera || '', observaciones: d.observaciones || ''
                    }));
                    this.calcularTotal();
                }

                this.$nextTick(() => {
                    if (this.esPropio === '1') {
                        this.vehiculoId = viaje.vehiculo_id || '';
                        let elVehiculo = document.getElementById('vehiculo_select');
                        if(elVehiculo && this.vehiculoId) { elVehiculo.value = this.vehiculoId; elVehiculo.dispatchEvent(new Event('change')); }
                        setTimeout(() => {
                            if (this.tipoVehiculoSeleccionado == '3') {
                                this.cisternaId = viaje.cisterna_acoplada_id || viaje.cisterna_id || ''; 
                                let elCisterna = document.getElementById('cisterna_select');
                                if(elCisterna && this.cisternaId) { elCisterna.value = this.cisternaId; elCisterna.dispatchEvent(new Event('change')); }
                            }
                        }, 150);
                    }
                });
            },

            getTitulo() { const titulos = { 'diesel': 'Diesel', 'mgo': 'MGO', 'flete': 'Flete', 'compra': 'Compra' }; return titulos[this.modo] || 'Planificación'; },
            get porcentajeCarga() { if (this.capacidadMaxima <= 0) return 0; let p = Math.round((this.totalLitros / this.capacidadMaxima) * 100); return p > 100 ? 100 : p; },
            get excesoCarga() { return (this.esPropio == '1' && this.capacidadMaxima > 0 && this.totalLitros > this.capacidadMaxima); },
            cambioVehiculo(e) { const opt = e.target.options[e.target.selectedIndex]; if (!opt.value) { this.tipoVehiculoSeleccionado = ''; this.capacidadMaxima = 0; return; } this.tipoVehiculoSeleccionado = opt.dataset.tipo; this.capacidadMaxima = parseFloat(opt.dataset.capacidad || 0); if (this.tipoVehiculoSeleccionado == '3') { this.capacidadMaxima = 0; } this.cisternaId = ''; },
            cambioCisterna(e) { const opt = e.target.options[e.target.selectedIndex]; this.capacidadMaxima = opt.value ? parseFloat(opt.dataset.capacidad || 0) : 0; },
            abrirModal() { new bootstrap.Modal(document.getElementById('modalAdd')).show(); },
            addPedido(id, clienteId, nombre, litros, rif, cupo) { this.items.push({ pedido_id: id, cliente_id: clienteId, cliente_nombre: nombre, cliente_rif: rif, litros: parseFloat(litros), buque_id: '', buque_nombre: '', buque_imo: '', buque_bandera: '', observaciones: '' }); this.calcularTotal(); bootstrap.Modal.getInstance(document.getElementById('modalAdd')).hide(); },
            onBuqueChange(item) { if (item.buque_id && item.buque_id !== 'manual') { const b = this.buques.find(x => x.id == item.buque_id); if (b) { item.buque_nombre = b.nombre; item.buque_imo = b.imo || ''; item.buque_bandera = b.bandera || ''; } } else { item.buque_nombre = ''; item.buque_imo = ''; item.buque_bandera = ''; } },
            removerItem(index) { this.items.splice(index, 1); this.calcularTotal(); },
            calcularTotal() { if(this.modo === 'compra') return; this.totalLitros = this.items.reduce((sum, item) => sum + parseFloat(item.litros || 0), 0); },
            formatoNumero(n) { return new Intl.NumberFormat('es-VE').format(n); }
        }
    }
</script>

<style>
    .uppercase { text-transform: uppercase; }
    .btn-orange { background-color: #ff6600 !important; color: white !important; }
    .text-orange { color: #ff6600 !important; }
    .fw-black { font-weight: 900 !important; }
    .border-left-orange { border-left: 5px solid #ff6600 !important; }
    .border-orange { border-color: #ff6600 !important; }
</style>
@endsection