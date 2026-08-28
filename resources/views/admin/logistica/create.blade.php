@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" x-data="constructorDeCarga('{{ $tipo }}', {{ $buques->toJson() }})">
    {{-- ENCABEZADO --}}
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center bg-white p-3 shadow-sm rounded border-left-orange">
            <div>
                <h3 class="text-orange mb-0 fw-black text-uppercase">
                    <i class="fas fa-plus-circle me-2"></i>Nueva Planificación: <span x-text="getTitulo()" class="text-uppercase"></span>
                </h3>
            </div>
            <div class="text-right">
                <a href="{{ route('logistica.index') }}" class="btn btn-sm btn-outline-secondary fw-bold">VOLVER</a>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h5 class="fw-bold text-uppercase small"><i class="fas fa-exclamation-triangle me-2"></i> Campos con errores:</h5>
            <ul class="mb-0 small">
                @foreach ($errors->getMessages() as $campo => $mensajes)
                    <li>
                        <strong>Error en el campo [ <span class="text-primary">{{ $campo }}</span> ]:</strong> 
                        {{ $mensajes[0] }}
                    </li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('logistica.store') }}" id="formPlanificacion" method="POST">
        @csrf
        <input type="hidden" name="tipo_planificacion" value="{{ $tipoPlanificacionId }}">
        
        {{-- INYECTAMOS EL FORMULARIO VACÍO --}}
        @include('admin.logistica.partials._form', ['viaje' => null])
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
            
            // --- NUEVO ESTADO PARA REGISTRO RÁPIDO ---
            coincidenciasCliente: [],
            formRapido: { 
                nombre: '', 
                rif_tipo: 'J', 
                rif_numero: '', 
                direccion: '', 
                contacto: '', 
                telefono: '' 
            },
            cargandoRapido: false,

            // Búsqueda en tiempo real mientras escribe el nombre
            buscarSimilares() {
                if (this.formRapido.nombre.trim().length < 2) {
                    this.coincidenciasCliente = [];
                    return;
                }
                // Usa la ruta nombrada clientes.similares
                fetch(`{{ route('logistica.clientes.similares') }}?q=${encodeURIComponent(this.formRapido.nombre)}`)
                    .then(res => res.json())
                    .then(data => { this.coincidenciasCliente = data; })
                    .catch(() => { this.coincidenciasCliente = []; });
            },

            // Guarda el cliente (status=2) y lo inyecta a la tabla de destinos
            guardarClienteRapido() {
                if (!this.formRapido.nombre) return alert('El nombre o Razón Social es obligatorio.');

                this.cargandoRapido = true;

                // Forzamos tipo a 'J' si viene undefined o vacío
                const tipo = this.formRapido.rif_tipo || 'J';
                const numero = (this.formRapido.rif_numero || '').trim();

                const payload = {
                    ...this.formRapido,
                    rif: numero ? `${tipo}-${numero}` : null
                };

                fetch('{{ route("logistica.clientes.store_rapido") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(data => {
                    this.cargandoRapido = false;

                    if (data.success) {
                        const cliente = data.cliente;

                        // 1. Si la planificación actual es MODO FLETE, lo inyectamos en el <select> de fletes y lo seleccionamos
                        if (this.modo === 'flete') {
                            const selectFlete = document.getElementById('selectClienteFlete');
                            if (selectFlete) {
                                const opt = document.createElement('option');
                                opt.value = cliente.id;
                                opt.textContent = `${cliente.nombre} - ${cliente.rif || 'SIN RIF'}`;
                                opt.selected = true;
                                selectFlete.appendChild(opt);
                            }
                        } else {
                            // 2. Si estamos en DIESEL o MGO, lo agregamos a la tabla de ítems/destinos
                            this.items.push({
                                pedido_id: null,
                                cliente_id: cliente.id,
                                cliente_nombre: cliente.nombre,
                                cliente_rif: cliente.rif || 'N/A',
                                cliente_cupo: 0,
                                litros: 0,
                                muelle_id: '',
                                buque_nombre: '', 
                                buque_imo: '', 
                                buque_bandera: '', 
                                observaciones: ''
                            });
                        }

                        // 3. Lo registramos en el <select> de selección manual por si se necesita reutilizar
                        const selectManual = document.getElementById('selectManual');
                        if (selectManual) {
                            const optManual = document.createElement('option');
                            optManual.value = cliente.id;
                            optManual.textContent = cliente.nombre;
                            optManual.dataset.nombre = cliente.nombre;
                            optManual.dataset.rif = cliente.rif || '';
                            optManual.dataset.cupo = 0;
                            selectManual.appendChild(optManual);
                        }

                        // Limpieza del formulario y reseteo del estado
                        this.formRapido = { nombre: '', rif_tipo: 'J', rif_numero: '', direccion: '', contacto: '', telefono: '' };
                        this.coincidenciasCliente = [];
                        
                        const modalEl = document.getElementById('modalAdd');
                        const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                        modalInstance.hide();
                    }
                })
                .catch(err => {
                    this.cargandoRapido = false;
                    alert('Error al registrar el cliente.');
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