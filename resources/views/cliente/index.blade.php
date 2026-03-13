@extends('layouts.app')
@section('title', 'TuCombustible - '.Auth::user()->name)

@section('content')
<div class="container mx-auto py-6 px-4" id="dashboard-main-view">
    {{-- Header del Dashboard --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 bg-white p-6 rounded-xl shadow-sm border-t-4 border-orange-impordiesel">
        <div class="flex items-center mb-4 md:mb-0">
            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center text-orange-impordiesel mr-4 shadow-inner">
                <i class="fas fa-user-tie fa-lg"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-800 uppercase tracking-tight">{{ Auth::user()->name }}</h1>
                <p class="text-gray-500 text-xs font-bold uppercase tracking-widest">Portal de Autogestión — ImporDiesel</p>
            </div>
        </div>
        <div class="flex space-x-2">
            <button onclick="openOrderModal()" class="bg-orange-impordiesel text-white px-6 py-2 rounded-lg font-black text-xs uppercase hover:bg-orange-700 transition shadow-md border-b-4 border-orange-900">
                <i class="fas fa-gas-pump mr-2"></i> NUEVA SOLICITUD DE COMBUSTIBLE
            </button>
            <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-tighter border border-green-200 flex items-center">
                <i class="fas fa-check-circle mr-1"></i> Cliente Operativo
            </span>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <p class="text-gray-500 text-[10px] font-black uppercase tracking-widest mb-1">Cupo Total Aprobado</p>
            <h3 class="text-2xl font-black text-gray-800">{{ number_format($stats['cupo'] ?? 0, 0) }} <span class="text-xs">Ltrs</span></h3>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <p class="text-gray-500 text-[10px] font-black uppercase tracking-widest mb-1">Disponible Actual</p>
            <h3 class="text-2xl font-black text-orange-impordiesel">{{ number_format($stats['disponible'] ?? 0, 0) }} <span class="text-xs">Ltrs</span></h3>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <p class="text-gray-500 text-[10px] font-black uppercase tracking-widest mb-1">Consumo del Período</p>
            <h3 class="text-2xl font-black text-gray-800">{{ number_format($stats['consumido'] ?? 0, 0) }} <span class="text-xs">Ltrs</span></h3>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <p class="text-gray-500 text-[10px] font-black uppercase tracking-widest mb-1">Pedidos Activos</p>
            <h3 class="text-2xl font-black text-blue-600">{{ $stats['pedidos_activos'] ?? 0 }}</h3>
        </div>
        @if($es_padre)
        <a href="#seccion-sucursales" class="bg-gray-800 p-6 rounded-xl shadow-lg border-b-4 border-orange-impordiesel hover:bg-black transition group text-left">
            <p class="text-gray-400 text-[10px] font-black uppercase tracking-widest mb-1 group-hover:text-orange-impordiesel">Sucursales Vinculadas</p>
            <h3 class="text-2xl font-black text-white">{{ $stats['sucursales_vinculadas'] ?? 0 }} <i class="fas fa-external-link-alt text-xs ml-2 opacity-50"></i></h3>
        </a>
        @endif
    </div>

    {{-- GESTIÓN DE ACTIVOS (REGISTRO RÁPIDO) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
        <div class="bg-gray-800 p-5 rounded-xl shadow-lg border-b-4 border-black">
            <h4 class="text-white font-black text-xs uppercase tracking-widest mb-4 flex items-center italic">
                <i class="fas fa-truck-moving mr-2 text-orange-impordiesel"></i> Registro de Placas
            </h4>
            <div class="flex space-x-2">
                <input type="text" id="auto_placa" class="flex-1 bg-gray-700 border-0 rounded p-2 text-white text-xs font-bold uppercase outline-none focus:ring-1 focus:ring-orange-impordiesel" placeholder="EJ: ABC123D">
                <button onclick="registrarActivoRapido('placa')" class="bg-orange-impordiesel text-white px-4 py-2 rounded text-[10px] font-black uppercase hover:bg-orange-600 transition">Registrar</button>
            </div>
        </div>
        <div class="bg-gray-800 p-5 rounded-xl shadow-lg border-b-4 border-black">
            <h4 class="text-white font-black text-xs uppercase tracking-widest mb-4 flex items-center italic">
                <i class="fas fa-id-card mr-2 text-orange-impordiesel"></i> Registro de Choferes
            </h4>
            <div class="flex flex-col space-y-2">
                <input type="text" id="auto_chofer_nom" class="bg-gray-700 border-0 rounded p-2 text-white text-xs font-bold uppercase outline-none focus:ring-1 focus:ring-orange-impordiesel" placeholder="Nombre Completo">
                <div class="flex space-x-2">
                    <input type="number" id="auto_chofer_ced" class="flex-1 bg-gray-700 border-0 rounded p-2 text-white text-xs font-bold uppercase outline-none focus:ring-1 focus:ring-orange-impordiesel" placeholder="Cédula">
                    <button onclick="registrarActivoRapido('chofer')" class="bg-orange-impordiesel text-white px-4 py-2 rounded text-[10px] font-black uppercase hover:bg-orange-600 transition">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- TOKEN Y SUCURSALES (SÓLO PADRE) --}}
    @if($es_padre)
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8" id="seccion-sucursales">
        <div class="lg:col-span-1 bg-white p-6 rounded-xl shadow-sm border-l-4 border-orange-impordiesel">
            <h4 class="text-xs font-black text-gray-700 uppercase tracking-widest mb-4">
                <i class="fas fa-key mr-2 text-orange-impordiesel"></i> Código para Sucursales
            </h4>
            <div class="flex items-center bg-gray-100 px-4 py-3 rounded-lg border border-gray-200 justify-between">
                <span id="tokenInvitacion" class="text-lg font-black text-gray-800 tracking-widest">{{ $cliente->token_registro ?? 'SIN TOKEN' }}</span>
                <button onclick="copyToken()" class="text-orange-impordiesel hover:text-orange-800 p-2"><i class="fas fa-copy fa-lg"></i></button>
            </div>
        </div>
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="bg-gray-50 px-6 py-3 border-b border-gray-100 font-black text-[10px] uppercase text-gray-800">Sucursales Vinculadas</div>
            <div class="overflow-y-auto max-h-48">
                <table class="w-full text-left text-xs">
                    <tbody class="divide-y divide-gray-100">
                        @forelse($sucursales as $suc)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-black text-gray-700 uppercase">{{ $suc->nombre }}<br><span class="text-[9px] text-gray-400 font-bold">{{ $suc->rif }}</span></td>
                            <td class="px-6 py-3 text-center">
                                <div class="w-24 bg-gray-200 h-1.5 rounded-full overflow-hidden inline-block"><div class="bg-orange-impordiesel h-full" style="width: {{ ($suc->registro_paso / 10) * 100 }}%"></div></div>
                                <span class="text-[9px] font-black text-gray-500 block uppercase">Paso {{ $suc->registro_paso }}/10</span>
                            </td>
                            <td class="px-6 py-3 text-right">
                                {{-- RUTA CORREGIDA: portal.clientes.sucursales.show --}}
                                <a href="{{ route('portal.clientes.sucursales.show', $suc->id) }}" class="bg-gray-800 text-white text-[9px] font-black px-3 py-1.5 rounded hover:bg-orange-impordiesel transition uppercase">Expediente</a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="px-6 py-8 text-center text-gray-400 font-black uppercase text-[10px]">No hay sucursales aún.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- HISTORIAL DE PEDIDOS --}}
    <div class="bg-white rounded-xl shadow-sm p-6 border-t-4 border-orange-impordiesel mb-8">
        <h2 class="text-xl font-bold uppercase text-gray-800 italic mb-6"><span class="text-orange-impordiesel">|</span> Historial de Solicitudes</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-industrial text-white text-[10px] font-black uppercase tracking-widest">
                        <th class="px-4 py-3">Referencia</th>
                        <th class="px-4 py-3">Producto</th>
                        <th class="px-4 py-3 text-center">Litros</th>
                        <th class="px-4 py-3 text-right">Estatus</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($pedidos as $pedido)
                    <tr class="hover:bg-gray-50 font-bold">
                        <td class="px-4 py-4">
                            <span class="font-black text-gray-800">REQ-{{ str_pad($pedido->id, 5, '0', STR_PAD_LEFT) }}</span><br>
                            <span class="text-[10px] text-gray-500 uppercase">{{ $pedido->cliente->nombre }}</span>
                        </td>
                        <td class="px-4 py-4 uppercase text-gray-700">{{ $pedido->deposito_id == 1 ? 'DIESEL' : 'MGO' }}</td>
                        <td class="px-4 py-4 text-center font-black">{{ number_format($pedido->cantidad_solicitada, 0) }} L</td>
                        <td class="px-4 py-4 text-right">
                            {{-- LOGICA DE COLORES RESTAURADA --}}
                            @php
                                $statusColor = match($pedido->estado) {
                                    'completado'  => 'bg-green-600',
                                    'pendiente'   => 'bg-gray-500',
                                    'aprobado'    => 'bg-orange-impordiesel',
                                    'en_proceso'  => 'bg-blue-600',
                                    'rechazado'   => 'bg-red-600',
                                    'cancelado'   => 'bg-black',
                                    default       => 'bg-gray-400'
                                };
                            @endphp
                            <span class="{{ $statusColor }} text-white px-3 py-1 rounded text-[10px] font-black uppercase shadow-sm">
                                {{ $pedido->estado }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-4 py-12 text-center text-gray-400 font-black uppercase italic">Sin solicitudes registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $pedidos->links() }}</div>
    </div>
</div>

{{-- MODAL SOLICITUD --}}
<div id="orderModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-gray-industrial p-4 border-b-4 border-orange-impordiesel flex justify-between items-center text-white font-black uppercase text-sm italic">
            Nueva Solicitud <button onclick="closeOrderModal()" class="text-gray-400 hover:text-white text-2xl">&times;</button>
        </div>
        <form action="{{ route('portal.clientes.pedidos.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="cliente_id" value="{{ Auth::user()->cliente_id }}">
            <div>
                <label class="text-[10px] font-black text-gray-400 uppercase block mb-1">Producto</label>
                <select name="tipo_combustible_id" class="w-full border-2 border-gray-200 rounded p-3 text-sm font-black uppercase outline-none focus:border-orange-impordiesel" required>
                    <option value="" disabled selected>SELECCIONE...</option>
                    @foreach(\App\Models\ClienteCupo::where('cliente_id', Auth::user()->cliente_id)->get() as $cupo)
                        <option value="{{ $cupo->tipo_combustible_id }}">{{ $cupo->tipo_combustible_id == 1 ? 'DIESEL' : 'MGO' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-[10px] font-black text-gray-400 uppercase block mb-1">Litros (Máx: {{ number_format($stats['disponible'], 0) }} L)</label>
                <input type="number" name="cantidad" step="0.01" min="100" max="{{ $stats['disponible'] }}" class="w-full border-2 border-gray-200 rounded p-3 text-sm font-black" placeholder="MÍNIMO 100 L" required>
            </div>
            <button type="submit" class="w-full bg-orange-impordiesel text-white font-black py-4 rounded uppercase text-xs tracking-widest shadow-lg border-b-4 border-orange-900">PROCESAR</button>
        </form>
    </div>
</div>

<script>
    function openOrderModal() { document.getElementById('orderModal').classList.remove('hidden'); }
    function closeOrderModal() { document.getElementById('orderModal').classList.add('hidden'); }
    function copyToken() { const t = document.getElementById('tokenInvitacion').innerText.trim(); navigator.clipboard.writeText(t).then(() => alert("¡Token copiado al portapapeles!")); }

    {{-- LOGICA DE REGISTRO RÁPIDO CORREGIDA --}}
    function registrarActivoRapido(tipo) {
        let data = { placas: [], choferes: [] };
        if(tipo === 'placa') {
            const val = document.getElementById('auto_placa').value.trim();
            if(!val) return alert('Por favor, ingrese una placa válida.');
            data.placas = [val];
        } else {
            const nom = document.getElementById('auto_chofer_nom').value.trim();
            const ced = document.getElementById('auto_chofer_ced').value.trim();
            if(!nom || !ced) return alert('Debe completar nombre y cédula del chofer.');
            data.choferes = [{ nombre: nom, cedula: ced }];
        }

        fetch('{{ route("cliente.activos.asignar") }}', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json', 
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest' 
            },
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(res => {
            if(res.status === 'success') { 
                alert('¡Registro guardado con éxito!'); 
                location.reload(); 
            } else { 
                alert('Error al registrar: ' + res.message); 
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error crítico de comunicación con el servidor.');
        });
    }
</script>
@endsection