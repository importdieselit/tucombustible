@extends('layouts.app')
@section('title', 'Expediente - {{ $cliente->nombre }}')

@section('content')
<div class="container mx-auto py-6 px-4">

    {{-- BARRA SUPERIOR --}}
    <div class="flex items-center justify-between mb-6 bg-white p-3 rounded-lg border border-gray-300 shadow-sm no-print">
        <a href="{{ route('clientes.index') }}"
           class="bg-gray-industrial text-white px-5 py-2.5 rounded text-xs font-black uppercase hover:bg-black transition flex items-center shadow-md">
            <i class="fas fa-chevron-left mr-2 text-orange-impordiesel"></i> Volver al Listado
        </a>
        <button onclick="window.print()"
                class="bg-white border-2 border-gray-800 text-gray-800 px-5 py-2 rounded text-xs font-black uppercase hover:bg-gray-800 hover:text-white transition shadow-sm">
            <i class="fas fa-print mr-2"></i> Imprimir
        </button>
    </div>

    {{-- LÍNEA DE TIEMPO --}}
    <div class="bg-white p-6 rounded-lg shadow-md border-2 border-gray-300 mb-8 no-print">
        <p class="text-[10px] font-black uppercase text-gray-400 mb-6 tracking-widest border-b pb-2">
            Línea de Tiempo del Registro (5 Etapas)
        </p>
        <div class="flex items-start justify-between relative">
            <div class="absolute top-5 left-0 right-0 h-1 bg-gray-200 z-0"></div>
            <div class="absolute top-5 left-0 h-1 bg-orange-impordiesel z-0"
                 style="width: {{ $cliente->porcentaje_registro }}%"></div>

            @foreach($tiposCombustible->first() ? \App\Models\RegistroPaso::activos()->get() : collect() as $paso)
                @php
                    $completado = $paso->id < $cliente->registro_paso;
                    $actual     = $paso->id == $cliente->registro_paso;
                @endphp
                <div class="flex flex-col items-center z-10 flex-1">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-black text-sm border-2
                        {{ $completado ? 'bg-orange-impordiesel border-orange-impordiesel text-white'
                                       : ($actual ? 'bg-white border-orange-impordiesel text-orange-impordiesel shadow-lg'
                                                  : 'bg-white border-gray-300 text-gray-400') }}">
                        @if($completado)
                            <i class="fas fa-check text-xs"></i>
                        @else
                            {{ $paso->orden }}
                        @endif
                    </div>
                    <p class="text-[9px] font-black uppercase mt-2 text-center leading-tight max-w-[80px]
                        {{ $actual ? 'text-orange-impordiesel' : ($completado ? 'text-gray-600' : 'text-gray-400') }}">
                        {{ $paso->nombre }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- COLUMNA IZQUIERDA --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- DATOS PRINCIPALES --}}
            <div class="bg-white rounded border-2 border-gray-300 shadow-md overflow-hidden">
                <div class="bg-gray-800 p-5">
                    <h2 class="text-2xl font-black text-white uppercase tracking-tighter">{{ $cliente->nombre }}</h2>
                    <p class="text-orange-impordiesel text-sm font-black uppercase tracking-widest">
                        RIF: {{ $cliente->rif }} —
                        <span class="{{ $cliente->color_status }} text-white px-2 py-0.5 rounded text-[10px] font-black uppercase ml-1">
                            {{ $cliente->label_status }}
                        </span>
                    </p>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Contacto</p>
                        <p class="font-black text-gray-700 uppercase mt-1">{{ $cliente->contacto ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Teléfono</p>
                        <p class="font-black text-gray-700 mt-1">{{ $cliente->telefono ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Correo</p>
                        <p class="font-black text-gray-700 mt-1">{{ $cliente->email ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Estado / Ciudad</p>
                        <p class="font-black text-gray-700 uppercase mt-1">
                            {{ $cliente->estado->nombre ?? 'N/A' }} / {{ $cliente->ciudad->nombre ?? 'N/A' }}
                        </p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Dirección Fiscal</p>
                        <p class="font-black text-gray-700 uppercase mt-1">{{ $cliente->direccion ?? 'N/A' }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-[10px] font-black text-orange-impordiesel uppercase tracking-widest">Dirección Operativa</p>
                        <p class="font-black text-gray-700 uppercase mt-1">{{ $cliente->direccion_operativa ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Fecha de Creación</p>
                        <p class="font-black text-gray-700 mt-1">{{ $cliente->created_at?->format('d/m/Y') ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Fecha de Aprobación</p>
                        <p class="font-black text-gray-700 mt-1">{{ $cliente->fecha_aprobacion?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                </div>
            </div>

            {{-- PLACAS Y CHOFERES --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 no-print">

                {{-- PLACAS --}}
                <div class="bg-white rounded border-2 border-gray-300 shadow-md flex flex-col h-[380px]">
                    <div class="bg-gray-800 p-3 flex justify-between items-center">
                        <h5 class="text-[10px] font-black uppercase text-orange-impordiesel italic">
                            <i class="fas fa-truck-moving mr-2"></i> Placas Autorizadas
                        </h5>
                        <span class="bg-orange-impordiesel text-white text-[9px] px-2 py-0.5 rounded-full font-black">
                            {{ $cliente->placas->count() }}
                        </span>
                    </div>
                    <div class="p-2 border-b">
                        <input type="text" id="filterPlacas" onkeyup="filtrarLista('filterPlacas', 'containerPlacas')"
                               class="w-full px-3 py-2 bg-gray-100 border-none rounded text-[10px] font-black uppercase outline-none focus:ring-1 focus:ring-orange-impordiesel"
                               placeholder="Buscar placa...">
                    </div>
                    <div class="flex-1 overflow-y-auto p-2" id="containerPlacas">
                        @forelse($cliente->placas as $placa)
                            <div class="activo-item flex justify-between items-center p-2 mb-1 bg-gray-50 border border-gray-200 rounded hover:border-orange-impordiesel transition">
                                <span class="text-xs font-black text-gray-700 tracking-widest">{{ $placa->placa }}</span>
                                <form action="{{ route('clientes.placas.inactivar', $placa->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-red-400 hover:text-red-600 text-[10px] font-black uppercase transition"
                                            onclick="return confirm('¿Inactivar esta placa?')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </div>
                        @empty
                            <p class="text-[10px] text-gray-400 text-center mt-10 font-bold uppercase italic">Sin placas registradas.</p>
                        @endforelse
                    </div>
                </div>

                {{-- CHOFERES --}}
                <div class="bg-white rounded border-2 border-gray-300 shadow-md flex flex-col h-[380px]">
                    <div class="bg-gray-800 p-3 flex justify-between items-center">
                        <h5 class="text-[10px] font-black uppercase text-blue-400 italic">
                            <i class="fas fa-id-card mr-2"></i> Personal Autorizado
                        </h5>
                        <span class="bg-blue-600 text-white text-[9px] px-2 py-0.5 rounded-full font-black">
                            {{ $cliente->choferes->count() }}
                        </span>
                    </div>
                    <div class="p-2 border-b">
                        <input type="text" id="filterChoferes" onkeyup="filtrarLista('filterChoferes', 'containerChoferes')"
                               class="w-full px-3 py-2 bg-gray-100 border-none rounded text-[10px] font-black uppercase outline-none focus:ring-1 focus:ring-blue-600"
                               placeholder="Buscar por nombre o cédula...">
                    </div>
                    <div class="flex-1 overflow-y-auto p-2" id="containerChoferes">
                        @forelse($cliente->choferes as $chofer)
                            <div class="activo-item p-2 mb-1 bg-gray-50 border border-gray-200 rounded hover:border-blue-600 transition flex justify-between items-center">
                                <div>
                                    <div class="text-[10px] font-black text-gray-800 uppercase">{{ $chofer->nombre_completo }}</div>
                                    <div class="text-[9px] text-gray-500 font-bold">C.I: {{ $chofer->cedula }}</div>
                                </div>
                                <form action="{{ route('clientes.choferes.inactivar', $chofer->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-red-400 hover:text-red-600 text-[10px] font-black uppercase transition"
                                            onclick="return confirm('¿Inactivar este chofer?')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </div>
                        @empty
                            <p class="text-[10px] text-gray-400 text-center mt-10 font-bold uppercase italic">Sin choferes registrados.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- COLUMNA DERECHA — PANEL DE CONTROL --}}
        <div class="space-y-6">

            {{-- AVANZAR PASO --}}
            @if($cliente->status == \App\Models\Cliente::STATUS_EN_REGISTRO)
            <div class="bg-white rounded border-2 border-gray-300 p-5 shadow-md">
                <h5 class="text-xs font-black uppercase text-gray-800 mb-4 border-b-2 border-orange-impordiesel pb-2">
                    <i class="fas fa-step-forward mr-2 text-orange-impordiesel"></i> Avanzar Etapa
                </h5>
                <form action="{{ route('clientes.avanzarPaso', $cliente->id) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="text-[10px] font-black text-gray-500 uppercase block mb-1 tracking-widest">Mover a etapa:</label>
                        <select name="paso" class="w-full text-xs font-black border-2 border-gray-400 rounded p-2 outline-none focus:border-orange-impordiesel bg-gray-50 uppercase">
                            @foreach(\App\Models\RegistroPaso::activos()->get() as $paso)
                                <option value="{{ $paso->id }}" {{ $cliente->registro_paso == $paso->id ? 'selected' : '' }}>
                                    Paso {{ $paso->orden }}: {{ $paso->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit"
                            class="w-full bg-gray-industrial text-white font-black py-3 rounded text-xs uppercase hover:bg-black transition shadow-lg border-b-4 border-black tracking-widest">
                        Actualizar Etapa
                    </button>
                </form>
            </div>
            @endif

            {{-- APROBAR --}}
            @if($cliente->status == \App\Models\Cliente::STATUS_EN_REGISTRO)
            <div class="bg-white rounded border-2 border-green-200 p-5 shadow-md">
                <h5 class="text-xs font-black uppercase text-green-700 mb-4 border-b-2 border-green-500 pb-2">
                    <i class="fas fa-check-circle mr-2"></i> Aprobar Cliente
                </h5>
                <form action="{{ route('clientes.aprobar', $cliente->id) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="text-[10px] font-black text-gray-500 uppercase block mb-1 tracking-widest">Tipo de Combustible:</label>
                        <select name="tipo_combustible_id" required
                                class="w-full text-xs font-black border-2 border-gray-400 rounded p-2 outline-none focus:border-green-500 bg-gray-50 uppercase">
                            <option value="" disabled selected>Seleccione...</option>
                            @foreach($tiposCombustible as $tipo)
                                <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-gray-500 uppercase block mb-1 tracking-widest">Litros Aprobados / Mes:</label>
                        <input type="number" name="litros_aprobados" min="1" required
                               class="w-full text-sm font-black border-2 border-gray-400 rounded p-2 outline-none focus:border-green-500"
                               placeholder="Ej: 10000">
                    </div>
                    <button type="submit"
                            class="w-full bg-green-600 text-white font-black py-3 rounded text-xs uppercase hover:bg-green-700 transition shadow-lg border-b-4 border-green-900 tracking-widest"
                            onclick="return confirm('¿Confirmar aprobación del cliente?')">
                        <i class="fas fa-check mr-1"></i> Aprobar y Asignar Cupo
                    </button>
                </form>
            </div>
            @endif

            {{-- RECHAZAR --}}
            @if($cliente->status == \App\Models\Cliente::STATUS_EN_REGISTRO)
            <div class="bg-white rounded border-2 border-red-200 p-5 shadow-md">
                <h5 class="text-xs font-black uppercase text-red-700 mb-4 border-b-2 border-red-500 pb-2">
                    <i class="fas fa-times-circle mr-2"></i> Rechazar Cliente
                </h5>
                <form action="{{ route('clientes.rechazar', $cliente->id) }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="w-full bg-red-600 text-white font-black py-3 rounded text-xs uppercase hover:bg-red-700 transition shadow-lg border-b-4 border-red-900 tracking-widest"
                            onclick="return confirm('¿Confirmar rechazo del cliente? Esta acción no se puede deshacer fácilmente.')">
                        <i class="fas fa-times mr-1"></i> Marcar como Rechazado
                    </button>
                </form>
            </div>
            @endif

            {{-- AJUSTAR CUPO (solo aprobados) --}}
            @if($cliente->status == \App\Models\Cliente::STATUS_APROBADO)
            <div class="bg-white rounded border-2 border-gray-300 p-5 shadow-md">
                <h5 class="text-xs font-black uppercase text-gray-800 mb-4 border-b-2 border-orange-impordiesel pb-2">
                    <i class="fas fa-sliders-h mr-2 text-orange-impordiesel"></i> Ajustar Cupo
                </h5>
                @foreach($cliente->cupos as $cupo)
                <form action="{{ route('clientes.ajustarCupo', $cliente->id) }}" method="POST" class="space-y-3 mb-4">
                    @csrf
                    <input type="hidden" name="tipo_combustible_id" value="{{ $cupo->tipo_combustible_id }}">
                    <p class="text-[10px] font-black text-orange-impordiesel uppercase tracking-widest">
                        {{ $cupo->tipoCombustible->nombre }} — Actual: {{ number_format($cupo->litros_aprobados, 0) }} L
                    </p>
                    <input type="number" name="litros_aprobados" min="1" required
                           value="{{ $cupo->litros_aprobados }}"
                           class="w-full text-sm font-black border-2 border-gray-400 rounded p-2 outline-none focus:border-orange-impordiesel">
                    <button type="submit"
                            class="w-full bg-orange-impordiesel text-white font-black py-2 rounded text-xs uppercase hover:bg-orange-700 transition border-b-2 border-orange-900 tracking-widest">
                        Actualizar Cupo {{ $cupo->tipoCombustible->nombre }}
                    </button>
                </form>
                @endforeach
            </div>
            @endif

            {{-- INACTIVAR / REACTIVAR --}}
            @if($cliente->status == \App\Models\Cliente::STATUS_APROBADO)
            <div class="bg-white rounded border-2 border-gray-300 p-5 shadow-md">
                <h5 class="text-xs font-black uppercase text-gray-800 mb-4 border-b-2 border-gray-400 pb-2">
                    <i class="fas fa-power-off mr-2 text-gray-500"></i> Inactivar Cliente
                </h5>
                <form action="{{ route('clientes.inactivar', $cliente->id) }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="w-full bg-gray-500 text-white font-black py-3 rounded text-xs uppercase hover:bg-gray-700 transition shadow-lg border-b-4 border-gray-900 tracking-widest"
                            onclick="return confirm('¿Inactivar este cliente? Si es Cliente Padre, todas sus sucursales serán inactivadas también.')">
                        <i class="fas fa-ban mr-1"></i> Inactivar
                    </button>
                </form>
            </div>
            @endif

            @if($cliente->status == \App\Models\Cliente::STATUS_INACTIVO)
            <div class="bg-white rounded border-2 border-gray-300 p-5 shadow-md">
                <h5 class="text-xs font-black uppercase text-gray-800 mb-4 border-b-2 border-green-400 pb-2">
                    <i class="fas fa-redo mr-2 text-green-500"></i> Reactivar Cliente
                </h5>
                <form action="{{ route('clientes.reactivar', $cliente->id) }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="w-full bg-green-600 text-white font-black py-3 rounded text-xs uppercase hover:bg-green-700 transition shadow-lg border-b-4 border-green-900 tracking-widest"
                            onclick="return confirm('¿Reactivar este cliente?')">
                        <i class="fas fa-redo mr-1"></i> Reactivar
                    </button>
                </form>
            </div>
            @endif

            {{-- REGISTRAR PLACA --}}
            @if($cliente->status == \App\Models\Cliente::STATUS_APROBADO)
            <div class="bg-white rounded border-2 border-gray-300 p-5 shadow-md no-print">
                <h5 class="text-xs font-black uppercase text-gray-800 mb-4 border-b-2 border-orange-impordiesel pb-2">
                    <i class="fas fa-truck-moving mr-2 text-orange-impordiesel"></i> Registrar Placa
                </h5>
                <form action="{{ route('clientes.placas.store', $cliente->id) }}" method="POST" class="flex gap-2">
                    @csrf
                    <input type="text" name="placa" maxlength="8" required
                           class="flex-1 text-xs font-black border-2 border-gray-400 rounded p-2 outline-none focus:border-orange-impordiesel uppercase"
                           placeholder="Ej: ABC123D">
                    <button type="submit"
                            class="bg-orange-impordiesel text-white px-4 py-2 rounded text-xs font-black uppercase hover:bg-orange-700 transition border-b-2 border-orange-900">
                        <i class="fas fa-plus"></i>
                    </button>
                </form>
            </div>
            @endif

            {{-- REGISTRAR CHOFER --}}
            @if($cliente->status == \App\Models\Cliente::STATUS_APROBADO)
            <div class="bg-white rounded border-2 border-gray-300 p-5 shadow-md no-print">
                <h5 class="text-xs font-black uppercase text-gray-800 mb-4 border-b-2 border-blue-500 pb-2">
                    <i class="fas fa-id-card mr-2 text-blue-500"></i> Registrar Chofer
                </h5>
                <form action="{{ route('clientes.choferes.store', $cliente->id) }}" method="POST" class="space-y-2">
                    @csrf
                    <input type="text" name="nombre_completo" required
                           class="w-full text-xs font-black border-2 border-gray-400 rounded p-2 outline-none focus:border-blue-500 uppercase"
                           placeholder="Nombre completo">
                    <div class="flex gap-2">
                        <input type="text" name="cedula" maxlength="15" required
                               class="flex-1 text-xs font-black border-2 border-gray-400 rounded p-2 outline-none focus:border-blue-500"
                               placeholder="Cédula">
                        <button type="submit"
                                class="bg-blue-600 text-white px-4 py-2 rounded text-xs font-black uppercase hover:bg-blue-700 transition border-b-2 border-blue-900">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </form>
            </div>
            @endif

        </div>
    </div>
</div>

<style>
    @media print {
        .no-print { display: none !important; }
    }
</style>

<script>
    function filtrarLista(inputId, containerId) {
        const filtro  = document.getElementById(inputId).value.toUpperCase();
        const items   = document.getElementById(containerId).getElementsByClassName('activo-item');
        for (let i = 0; i < items.length; i++) {
            items[i].style.display = (items[i].textContent || items[i].innerText).toUpperCase().includes(filtro) ? '' : 'none';
        }
    }
</script>
@endsection