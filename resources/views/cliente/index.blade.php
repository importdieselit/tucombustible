@extends('layouts.app')
@section('title', 'Mi Portal - ImporDiesel')

@section('content')
@if($viendoSucursal)
    <div class="mb-4">
        <a href="{{ route('portal.clientes.index') }}" class="bg-red-600 text-white px-4 py-2 rounded font-black text-[10px] uppercase shadow-md hover:bg-red-700">
            <i class="fas fa-arrow-left mr-2"></i> Salir del modo sucursal (Volver a mi perfil)
        </a>
    </div>
@endif
<div class="container mx-auto py-6 px-4">

    {{-- ENCABEZADO --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 bg-white p-6 rounded-xl shadow-sm border-t-4 border-orange-impordiesel">
        <div class="flex items-center mb-4 md:mb-0">
            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center text-orange-impordiesel mr-4 shadow-inner">
                <i class="fas fa-user-tie fa-lg"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-800 uppercase tracking-tight">{{ $cliente->nombre }}</h1>
                <p class="text-gray-500 text-xs font-bold uppercase tracking-widest">
                    RIF: {{ $cliente->rif }} — {{ $cliente->es_padre ? 'Sede Principal' : 'Sucursal' }}
                </p>
            </div>
        </div>
        <span class="bg-green-100 text-green-700 px-4 py-2 rounded-full text-xs font-black uppercase tracking-tighter border border-green-200 flex items-center">
            <i class="fas fa-check-circle mr-2"></i> Cliente Activo
        </span>
    </div>

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

                    {{-- CONTACTO PRINCIPAL --}}
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Contacto Principal</p>
                        <p class="font-black text-gray-700 uppercase mt-1">{{ $cliente->contacto ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Teléfono Principal</p>
                        <p class="font-black text-gray-700 mt-1">{{ $cliente->telefono ?? 'N/A' }}</p>
                    </div>

                    {{-- CONTACTO ALTERNATIVO --}}
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Contacto Alternativo</p>
                        <p class="font-black text-gray-700 uppercase mt-1">{{ $cliente->contacto_alt ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Teléfono Alternativo</p>
                        <p class="font-black text-gray-700 mt-1">{{ $cliente->telefono_alt ?? '—' }}</p>
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

    {{-- CUPOS DE COMBUSTIBLE --}}
    <div class="mb-8">
        <h2 class="text-sm font-black uppercase text-gray-700 tracking-widest mb-4">
            <span class="text-orange-impordiesel">|</span> Cupo Mensual Aprobado
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @forelse($cupos as $cupo)
                <div class="bg-white p-6 rounded-xl border-l-4 border-orange-impordiesel shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest mb-1 text-orange-impordiesel">
                        {{ $cupo->tipoCombustible->nombre }}
                    </p>
                    <h3 class="text-3xl font-black text-gray-800">
                        {{ number_format($cupo->litros_aprobados, 0, ',', '.') }}
                        <small class="text-xs text-gray-500 uppercase font-bold">Litros / mes</small>
                    </h3>
                    <p class="text-[10px] text-gray-400 font-bold uppercase mt-2">
                        Aprobado el {{ $cliente->fecha_aprobacion?->format('d/m/Y') ?? 'N/A' }}
                    </p>
                </div>
            @empty
                <div class="bg-gray-50 p-6 rounded-xl border border-gray-200 text-center col-span-2">
                    <p class="text-gray-400 font-black uppercase text-xs">Sin cupo asignado aún.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- TOKEN PARA SUCURSALES (SOLO PADRE) --}}
    @if($cliente->es_padre)
    <div class="mb-8">
        <h2 class="text-sm font-black uppercase text-gray-700 tracking-widest mb-4">
            <span class="text-orange-impordiesel">|</span> Código para Registro de Sucursales
        </h2>
        <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-orange-impordiesel flex items-center justify-between max-w-md">
            <div>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Token de Empresa</p>
                <span id="tokenInvitacion" class="text-xl font-black text-gray-800 tracking-widest">
                    {{ $cliente->token_registro ?? 'SIN TOKEN' }}
                </span>
            </div>
            <button onclick="copyToken()" class="text-orange-impordiesel hover:text-orange-800 p-3 transition" title="Copiar token">
                <i class="fas fa-copy fa-lg"></i>
            </button>
        </div>
    </div>
    @endif

    {{-- PLACAS Y CHOFERES --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">

        {{-- PLACAS --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gray-industrial px-6 py-3 flex justify-between items-center">
                <h5 class="text-[10px] font-black uppercase text-orange-impordiesel italic tracking-widest">
                    <i class="fas fa-truck-moving mr-2"></i> Placas Autorizadas
                </h5>
                <span class="bg-orange-impordiesel text-white text-[9px] px-2 py-0.5 rounded-full font-black">
                    {{ $placas->count() }}
                </span>
            </div>
            <div class="p-2 border-b border-gray-100">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-[10px]"></i>
                    <input type="text" id="filterPlacas" onkeyup="filtrarLista('filterPlacas', 'listaPlacas')"
                           class="w-full pl-8 pr-4 py-2 bg-gray-100 border-none rounded text-[10px] font-black uppercase outline-none focus:ring-1 focus:ring-orange-impordiesel"
                           placeholder="Buscar placa...">
                </div>
            </div>
            <div class="divide-y divide-gray-100 max-h-64 overflow-y-auto" id="listaPlacas">
                @forelse($placas as $placa)
                    <div class="placa-item flex justify-between items-center px-6 py-3 hover:bg-gray-50">
                        <span class="text-xs font-black text-gray-700 tracking-widest">{{ $placa->placa }}</span>
                        <i class="fas fa-check-circle text-green-500 text-[10px]"></i>
                    </div>
                @empty
                    <p class="text-[10px] text-gray-400 text-center py-8 font-bold uppercase italic">Sin placas registradas.</p>
                @endforelse
            </div>
        </div>

        {{-- CHOFERES --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gray-industrial px-6 py-3 flex justify-between items-center">
                <h5 class="text-[10px] font-black uppercase text-blue-400 italic tracking-widest">
                    <i class="fas fa-id-card mr-2"></i> Personal Autorizado
                </h5>
                <span class="bg-blue-600 text-white text-[9px] px-2 py-0.5 rounded-full font-black">
                    {{ $choferes->count() }}
                </span>
            </div>
            <div class="p-2 border-b border-gray-100">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-[10px]"></i>
                    <input type="text" id="filterChoferes" onkeyup="filtrarLista('filterChoferes', 'listaChoferes')"
                           class="w-full pl-8 pr-4 py-2 bg-gray-100 border-none rounded text-[10px] font-black uppercase outline-none focus:ring-1 focus:ring-blue-600"
                           placeholder="Buscar chofer...">
                </div>
            </div>
            <div class="divide-y divide-gray-100 max-h-64 overflow-y-auto" id="listaChoferes">
                @forelse($choferes as $chofer)
                    <div class="chofer-item px-6 py-3 hover:bg-gray-50">
                        <p class="text-[10px] font-black text-gray-800 uppercase">{{ $chofer->nombre_completo }}</p>
                        <p class="text-[9px] text-gray-500 font-bold">C.I: {{ $chofer->cedula }}</p>
                    </div>
                @empty
                    <p class="text-[10px] text-gray-400 text-center py-8 font-bold uppercase italic">Sin choferes registrados.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- SUCURSALES (SOLO PADRE) --}}
    @if($cliente->es_padre && $sucursales->count() > 0)
    <div class="mb-8">
        <h2 class="text-sm font-black uppercase text-gray-700 tracking-widest mb-4">
            <span class="text-orange-impordiesel">|</span> Sucursales Vinculadas
        </h2>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-gray-industrial text-white text-[10px] font-black uppercase tracking-widest">
                        <th class="px-6 py-3">Sucursal</th>
                        <th class="px-6 py-3 text-center">Estatus</th>
                        <th class="px-6 py-3 text-center">Progreso</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($sucursales as $suc)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 font-black text-gray-700 uppercase">
                            {{ $suc->nombre }}<br>
                            <span class="text-[9px] text-gray-400 font-bold">{{ $suc->rif }}</span>
                        </td>
                        <td class="px-6 py-3 text-center">
                            <span class="{{ $suc->color_status }} text-white px-2 py-1 rounded text-[9px] font-black uppercase">
                                {{ $suc->label_status }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-center">
                            <div class="w-24 bg-gray-200 h-1.5 rounded-full overflow-hidden inline-block">
                                <div class="bg-orange-impordiesel h-full" style="width: {{ $suc->porcentaje_registro }}%"></div>
                            </div>
                            <span class="text-[9px] font-black text-gray-500 block uppercase">Paso {{ $suc->registro_paso }}/5</span>
                        </td>
                        {{-- Dentro de tu @foreach($sucursales as $suc) --}}
                        <td class="px-6 py-3 text-center">
                            <a href="{{ route('portal.clientes.index', ['sucursal_id' => $suc->id]) }}" 
                            class="bg-orange-impordiesel text-white px-3 py-1 rounded text-[10px] font-black uppercase hover:bg-black transition">
                                Ver Detalle <i class="fas fa-chevron-right ml-1"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- HISTORIAL DE PEDIDOS --}}
    <div class="mb-8">
        <h2 class="text-sm font-black uppercase text-gray-700 tracking-widest mb-4">
            <span class="text-orange-impordiesel">|</span> Historial de Pedidos Recientes
        </h2>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-gray-industrial text-white text-[10px] font-black uppercase tracking-widest">
                        <th class="px-6 py-3">ID Pedido</th>
                        <th class="px-6 py-3">Tipo de Combustible</th>
                        <th class="px-6 py-3 text-center">Litros Solicitados</th>
                        <th class="px-6 py-3 text-center">Estatus</th>
                        <th class="px-6 py-3 text-center">Fecha</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($pedidos as $pedido)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-3 font-black text-gray-700">
                            #{{ str_pad($pedido->id, 5, '0', STR_PAD_LEFT) }}
                        </td>
                        <td class="px-6 py-3 font-bold text-gray-600 uppercase text-[10px]">
                            Combustible
                        </td>
                        <td class="px-6 py-3 text-center font-black text-gray-800">
                            {{-- CORRECCIÓN: cantidad_solicitada --}}
                            {{ number_format($pedido->cantidad_solicitada, 0, ',', '.') }} Lts
                        </td>
                        <td class="px-6 py-3 text-center">
                            {{-- CORRECCIÓN: Usar los accessors del modelo Pedido --}}
                            <span class="px-2 py-1 rounded text-[9px] font-black uppercase border" 
                                style="background-color: {{ $pedido->estado_color }}20; color: {{ $pedido->estado_color }}; border-color: {{ $pedido->estado_color }}40;">
                                {{ $pedido->estado_text }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-center text-gray-500 font-bold">
                            {{-- CORRECCIÓN: fecha_solicitud --}}
                            {{ $pedido->fecha_solicitud ? $pedido->fecha_solicitud->format('d/m/Y') : 'N/A' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <i class="fas fa-box-open text-gray-200 fa-3x mb-3"></i>
                            <p class="text-gray-400 font-black uppercase text-[10px] tracking-widest">
                                No se encontraron pedidos registrados.
                            </p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-center mt-8">
        <small class="text-gray-400 uppercase tracking-widest text-xs font-black">
            Portal de Clientes - ImporDiesel &copy; {{ date('Y') }}
        </small>
    </div>
</div>

<script>
    function copyToken() {
        const t = document.getElementById('tokenInvitacion').innerText.trim();
        navigator.clipboard.writeText(t).then(() => alert('¡Token copiado al portapapeles!'));
    }
    function filtrarLista(inputId, listaId) {
        const filtro = document.getElementById(inputId).value.toUpperCase();
        const items  = document.getElementById(listaId).children;
        for (let i = 0; i < items.length; i++) {
            items[i].style.display = (items[i].textContent || items[i].innerText).toUpperCase().includes(filtro) ? '' : 'none';
        }
    }
</script>
@endsection