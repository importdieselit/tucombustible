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

    {{-- CONTENEDOR PRINCIPAL --}}
    <div class="space-y-6">

        {{-- DATOS PRINCIPALES (VISTA Y EDICIÓN CON JS PURO) --}}
        <div class="bg-white rounded border-2 border-gray-300 shadow-md overflow-hidden mb-8">
            <div class="bg-gray-800 p-5 flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-black text-white uppercase tracking-tighter">{{ $cliente->nombre }}</h2>
                    <p class="text-orange-impordiesel text-sm font-black uppercase tracking-widest">
                        RIF: {{ $cliente->rif }} —
                        <span class="{{ $cliente->color_status }} text-white px-2 py-0.5 rounded text-[10px] font-black uppercase ml-1">
                            {{ $cliente->label_status }}
                        </span>
                    </p>
                </div>

                {{-- Botón para activar el modo edición --}}
                <button type="button" id="btnEditPerfil" onclick="toggleEditPerfil()" class="bg-orange-impordiesel text-white font-black uppercase text-[15px] px-3 py-1.5 rounded shadow-sm hover:bg-opacity-90 transition">
                    <i class="fas fa-edit mr-1"></i> Editar Información
                </button>

                 <button type="button" class="bg-orange-impordiesel text-white font-black uppercase text-[15px] px-3 py-1.5 rounded shadow-sm hover:bg-opacity-90 transition" data-bs-toggle="modal" data-bs-target="#modalArchivero">
                    <i class="fas fa-folder-open text-orange me-2"></i> Mi Archivero Digital
                </button>
            </div>

            {{-- BLOQUE DE VISTA (SOLO LECTURA) --}}
            <div id="perfilViewMode" class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Contacto Principal</p>
                    <p class="font-black text-gray-700 uppercase mt-1">{{ $cliente->contacto ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Teléfono Principal</p>
                    <p class="font-black text-gray-700 mt-1">{{ $cliente->telefono ?? 'N/A' }}</p>
                </div>
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

            {{-- BLOQUE DE EDICIÓN (FORMULARIO OCULTO POR DEFECTO) --}}
            <form id="perfilEditMode" action="{{ route('portal.clientes.perfil.update') }}" method="POST" class="hidden p-6 text-xs border-t border-gray-100 bg-gray-50">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Razón Social</label>
                        <input type="text" name="nombre" value="{{ $cliente->nombre }}" class="w-full border border-gray-300 rounded p-2 font-black text-gray-800 uppercase outline-none focus:border-orange-impordiesel" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">RIF</label>
                        <input type="text" name="rif" value="{{ $cliente->rif }}" class="w-full border border-gray-300 rounded p-2 font-black text-gray-800 uppercase outline-none focus:border-orange-impordiesel" required>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Contacto Principal</label>
                        <input type="text" name="contacto" value="{{ $cliente->contacto }}" class="w-full border border-gray-300 rounded p-2 font-black text-gray-800 uppercase outline-none focus:border-orange-impordiesel" required>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Teléfono Principal</label>
                        <input type="text" name="telefono" value="{{ $cliente->telefono }}" class="w-full border border-gray-300 rounded p-2 font-black text-gray-800 outline-none focus:border-orange-impordiesel" maxlength="11" required>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Contacto Alternativo</label>
                        <input type="text" name="contacto_alt" value="{{ $cliente->contacto_alt }}" class="w-full border border-gray-300 rounded p-2 font-black text-gray-800 uppercase outline-none focus:border-orange-impordiesel">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Teléfono Alternativo</label>
                        <input type="text" name="telefono_alt" value="{{ $cliente->telefono_alt }}" class="w-full border border-gray-300 rounded p-2 font-black text-gray-800 outline-none focus:border-orange-impordiesel" maxlength="11">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Correo Electrónico</label>
                        <input type="email" name="email" value="{{ $cliente->email }}" class="w-full border border-gray-300 rounded p-2 font-black text-gray-800 outline-none focus:border-orange-impordiesel" required>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Estado</label>
                        <select name="estado_id" class="w-full border border-gray-300 rounded p-2 font-black text-gray-800 uppercase outline-none focus:border-orange-impordiesel" required>
                            @foreach(\App\Models\Estado::orderBy('nombre')->get() as $est)
                                <option value="{{ $est->id }}" {{ $cliente->estado_id == $est->id ? 'selected' : '' }}>{{ $est->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Ciudad</label>
                        <select name="ciudad_id" class="w-full border border-gray-300 rounded p-2 font-black text-gray-800 uppercase outline-none focus:border-orange-impordiesel" required>
                            @if($cliente->estado_id)
                                @foreach(\App\Models\Ciudad::where('estado_id', $cliente->estado_id)->orderBy('nombre')->get() as $ciu)
                                    <option value="{{ $ciu->id }}" {{ $cliente->ciudad_id == $ciu->id ? 'selected' : '' }}>{{ $ciu->nombre }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Dirección Fiscal</label>
                        <textarea name="direccion" rows="2" class="w-full border border-gray-300 rounded p-2 font-black text-gray-800 uppercase outline-none focus:border-orange-impordiesel" required>{{ $cliente->direccion }}</textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-orange-impordiesel uppercase tracking-widest mb-1">Dirección Operativa</label>
                        <textarea name="direccion_operativa" rows="2" class="w-full border border-gray-300 rounded p-2 font-black text-gray-800 uppercase outline-none focus:border-orange-impordiesel" required>{{ $cliente->direccion_operativa }}</textarea>
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-2 border-t border-gray-200 pt-4">
                    <button type="button" onclick="toggleEditPerfil()" class="bg-gray-600 hover:bg-gray-700 text-white font-black uppercase text-[10px] px-4 py-2 rounded transition">Cancelar</button>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-black uppercase text-[10px] px-4 py-2 rounded transition">Guardar Cambios</button>
                </div>
            </form>
        </div>

        {{-- RESUMEN DE CUPOS Y DISPONIBLE --}}
        <div class="mb-8">
            <h2 class="text-sm font-black uppercase text-gray-700 tracking-widest mb-4">
                <span class="text-orange-impordiesel">|</span> Resumen de Cupos y Disponible
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                {{-- CUPO GASCO (AZUL) --}}
                <div class="bg-white p-6 rounded-xl border-l-4 border-blue-500 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest mb-1 text-blue-500">Cupo GASCO Mensual</p>
                    <h3 class="text-3xl font-black text-gray-800">
                        {{ number_format($cupoGasco ?? 0, 0, ',', '.') }}
                        <small class="text-xs text-gray-500 uppercase font-bold">Litros</small>
                    </h3>
                </div>

                {{-- DISPONIBLE (VERDE) --}}
                <div class="bg-white p-6 rounded-xl border-l-4 border-green-500 shadow-sm">
                    <p class="text-[10px] font-black uppercase tracking-widest mb-1 text-green-500">Saldo Disponible</p>
                    <h3 class="text-3xl font-black text-gray-800">
                        {{ number_format($cliente->disponible ?? 0, 0, ',', '.') }}
                        <small class="text-xs text-gray-500 uppercase font-bold">Litros</small>
                    </h3>
                </div>

                {{-- CUPO SIAVCOM (NARANJA) --}}
                @if($cliente->cupo > 0)
                    <div class="bg-white p-6 rounded-xl border-l-4 border-orange-impordiesel shadow-sm">
                        <p class="text-[10px] font-black uppercase tracking-widest mb-1 text-orange-impordiesel">Cupo SIAVCOM</p>
                        <h3 class="text-3xl font-black text-gray-800">
                            {{ number_format($cliente->cupo, 0, ',', '.') }}
                            <small class="text-xs text-gray-500 uppercase font-bold">Litros</small>
                        </h3>
                    </div>
                @endif
            </div>
        </div>

        {{-- TOKEN PARA SUCURSALES (SOLO PADRE) --}}
        @if($cliente->es_padre && !$viendoSucursal)
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

        {{-- PLACAS Y CHOFERES CON FILTROS --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">

            {{-- PLACAS --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gray-800 px-6 py-3 flex justify-between items-center text-white">
                    <h5 class="text-[10px] font-black uppercase italic tracking-widest">
                        <i class="fas fa-truck-moving mr-2"></i> Placas Autorizadas
                    </h5>
                    <div class="flex items-center gap-2">
                        <span class="bg-orange-impordiesel text-white text-[10px] px-2 py-0.5 rounded-full font-black">
                            {{ $placas->count() }}
                        </span>
                        <button type="button" onclick="openModalPlaca()" class="bg-green-600 hover:bg-green-700 text-white text-[10px] font-black uppercase px-2 py-0.5 rounded transition shadow-sm">
                            <i class="fas fa-plus mr-1"></i> Agregar
                        </button>
                    </div>
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
                        <div class="flex justify-between items-center px-6 py-3 hover:bg-gray-50">
                            <span class="text-xs font-black text-gray-700 tracking-widest">{{ $placa->placa }}</span>
                            <div class="flex items-center gap-3">
                                <i class="fas fa-check-circle text-green-500 text-[10px]"></i>
                                <form action="{{ route('portal.clientes.placas.destroy', $placa->id) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas inactivar esta placa?')">
                                    @csrf
                                    <input type="hidden" name="cliente_id" value="{{ $cliente->id }}">
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 transition p-1 outline-none">
                                        <i class="fas fa-trash-alt text-[10px]"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-[10px] text-gray-400 text-center py-8 font-bold uppercase italic">Sin placas registradas.</p>
                    @endforelse
                </div>
            </div>

            {{-- CHOFERES --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gray-800 px-6 py-3 flex justify-between items-center text-white">
                    <h5 class="text-[10px] font-black uppercase italic tracking-widest">
                        <i class="fas fa-id-card mr-2"></i> Personal Autorizado
                    </h5>
                    <div class="flex items-center gap-2">
                        <span class="bg-blue-600 text-white text-[10px] px-2 py-0.5 rounded-full font-black">
                            {{ $choferes->count() }}
                        </span>
                        <button type="button" onclick="openModalChofer()" class="bg-green-600 hover:bg-green-700 text-white text-[10px] font-black uppercase px-2 py-0.5 rounded transition shadow-sm">
                            <i class="fas fa-plus mr-1"></i> Agregar
                        </button>
                    </div>
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
                        <div class="flex justify-between items-center px-6 py-3 hover:bg-gray-50">
                            <div>
                                <p class="text-[10px] font-black text-gray-800 uppercase">{{ $chofer->nombre_completo }}</p>
                                <p class="text-[10px] text-gray-500 font-bold">C.I: {{ $chofer->cedula }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <form action="{{ route('portal.clientes.choferes.destroy', $chofer->id) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas inactivar a este chofer?')">
                                    @csrf
                                    <input type="hidden" name="cliente_id" value="{{ $cliente->id }}">
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 transition p-1 outline-none">
                                        <i class="fas fa-trash-alt text-[10px]"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-[10px] text-gray-400 text-center py-8 font-bold uppercase italic">Sin choferes registrados.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- SUCURSALES (SOLO PADRE) --}}
        @if($cliente->es_padre && $sucursales->count() > 0 && !$viendoSucursal)
        <div class="mb-8">
            <h2 class="text-sm font-black uppercase text-gray-700 tracking-widest mb-4">
                <span class="text-orange-impordiesel">|</span> Sucursales Vinculadas
            </h2>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="bg-gray-800 text-white text-[10px] font-black uppercase tracking-widest">
                            <th class="px-6 py-3">Sucursal</th>
                            <th class="px-6 py-3 text-center">Estatus</th>
                            <th class="px-6 py-3 text-center">Progreso</th>
                            <th class="px-6 py-3 text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($sucursales as $suc)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-black text-gray-700 uppercase">
                                {{ $suc->nombre }}<br>
                                <span class="text-[10px] text-gray-400 font-bold">{{ $suc->rif }}</span>
                            </td>
                            <td class="px-6 py-3 text-center">
                                <span class="{{ $suc->color_status }} text-white px-2 py-1 rounded text-[10px] font-black uppercase">
                                    {{ $suc->label_status }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-center">
                                <div class="w-24 bg-gray-200 h-1.5 rounded-full overflow-hidden inline-block">
                                    <div class="bg-orange-impordiesel h-full" style="width: {{ $suc->porcentaje_registro }}%"></div>
                                </div>
                                <span class="text-[10px] font-black text-gray-500 block uppercase">Paso {{ $suc->registro_paso }}/5</span>
                            </td>
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

        <button onclick="openModalPedido()" 
                class="bg-orange-impordiesel text-white px-6 py-3 rounded shadow-lg font-black text-xs uppercase hover:bg-black transition-all flex items-center">
            <i class="fas fa-gas-pump mr-2"></i> Solicitar Combustible
        </button>

        {{-- CONTENEDOR DE TABLAS CON SCROLL Y FILTROS DEFINITIVO --}}
        <div class="grid grid-cols-1 gap-10">

            {{-- 1. HISTORIAL DE PEDIDOS RECIENTES --}}
            <div>
                <div class="flex flex-col xl:flex-row justify-between items-center mb-4 gap-4">
                    <h2 class="text-base font-black uppercase text-gray-700 tracking-widest">
                        <span class="text-orange-impordiesel">|</span> Historial de Pedidos Recientes
                    </h2>
                    
                    <form action="{{ route('portal.clientes.index') }}" method="GET" class="flex flex-wrap items-center gap-2 bg-gray-50 p-2 rounded-lg border border-gray-200">
                        {{-- Mantener sucursal si existe --}}
                        @if(request('sucursal_id')) <input type="hidden" name="sucursal_id" value="{{ request('sucursal_id') }}"> @endif

                        <input type="text" name="p_search" value="{{ request('p_search') }}" placeholder="ID PEDIDO..." 
                            class="text-[10px] border-gray-300 rounded-md p-2 w-28 uppercase font-black shadow-sm">
                        
                        <select name="p_status" class="text-[10px] border-gray-300 rounded-md p-2 font-black uppercase shadow-sm">
                            <option value="">ESTATUS (TODOS)</option>
                            <option value="1" {{ request('p_status') == '1' ? 'selected' : '' }}>PENDIENTE</option>
                            <option value="2" {{ request('p_status') == '2' ? 'selected' : '' }}>APROBADO</option>
                            <option value="3" {{ request('p_status') == '3' ? 'selected' : '' }}>DESPACHADO</option>
                            <option value="4" {{ request('p_status') == '4' ? 'selected' : '' }}>CANCELADO</option>
                        </select>

                        <div class="flex items-center gap-1 bg-white border border-gray-300 rounded px-2 py-1 shadow-sm">
                            <span class="text-[10px] font-black text-gray-400">DESDE:</span>
                            <input type="date" name="p_desde" value="{{ request('p_desde') }}" class="text-[10px] border-none p-1 focus:ring-0 font-black">
                            <span class="text-[10px] font-black text-gray-400">HASTA:</span>
                            <input type="date" name="p_hasta" value="{{ request('p_hasta') }}" class="text-[10px] border-none p-1 focus:ring-0 font-black">
                        </div>
                        
                        <button type="submit" class="bg-gray-800 text-white px-3 py-2 rounded-md hover:bg-black transition shadow-md">
                            <i class="fas fa-search text-[10px]"></i>
                        </button>
                        <a href="{{ route('portal.clientes.index') }}" class="bg-gray-200 text-gray-600 px-3 py-2 rounded-md hover:bg-gray-300 transition">
                            <i class="fas fa-undo text-[10px]"></i>
                        </a>
                    </form>
                </div>

                <div class="bg-white rounded-xl shadow-md border border-gray-300 overflow-hidden">
                    <div class="max-h-[450px] overflow-y-auto scrollbar-thin">
                        <table class="w-full text-left text-sm border-collapse">
                            <thead class="sticky top-0 z-10">
                                <tr class="bg-gray-800 text-white text-xs font-black uppercase tracking-widest">
                                    <th class="px-6 py-4 border-r border-gray-700">ID Pedido</th>
                                    <th class="px-6 py-4 border-r border-gray-700">Combustible</th>
                                    <th class="px-6 py-4 text-center border-r border-gray-700">Litros</th>
                                    <th class="px-6 py-4 text-center border-r border-gray-700">Estatus</th>
                                    <th class="px-6 py-4 text-center border-r border-gray-700">Fecha</th>
                                    <th class="px-6 py-4 text-center">Acción</th> {{-- NUEVA COLUMNA --}}
                                </tr>
                            </thead>
                            <tbody class="divide-y-2 divide-gray-200 bg-white">
                                @forelse($pedidos as $pedido)
                                <tr class="hover:bg-gray-50 transition border-b border-gray-200">
                                    <td class="px-6 py-5 font-black text-gray-800 border-r border-gray-100">
                                        #{{ str_pad($pedido->id, 5, '0', STR_PAD_LEFT) }}
                                    </td>
                                    <td class="px-6 py-5 font-bold text-gray-600 uppercase border-r border-gray-100">
                                        {{ $pedido->tipoCombustible->nombre ?? 'DIESEL' }}
                                    </td>
                                    <td class="px-6 py-5 text-center font-black text-gray-900 border-r border-gray-100">
                                        {{ number_format($pedido->cantidad_solicitada, 0, ',', '.') }} Lts
                                    </td>
                                    <td class="px-6 py-5 text-center border-r border-gray-100">
                                        <span class="px-3 py-1.5 rounded text-[11px] font-black uppercase border shadow-sm" 
                                            style="background-color: {{ $pedido->estado_color }}15; color: {{ $pedido->estado_color }}; border-color: {{ $pedido->estado_color }}50;">
                                            {{ $pedido->estado_text }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-5 text-center text-gray-600 font-bold border-r border-gray-100">
                                        {{ $pedido->fecha_solicitud ? $pedido->fecha_solicitud->format('d/m/Y') : 'N/A' }}
                                    </td>
                                    <td class="px-6 py-5 text-center">
                                        @if($pedido->estado === 'pendiente')
                                            {{-- Cambiamos el method a POST y añadimos @method('PUT') --}}
                                            <form action="{{ route('portal.clientes.pedidos.cancelar', $pedido->id) }}" method="POST" 
                                                onsubmit="return confirm('¿Seguro que deseas CANCELAR este pedido? Se devolverán los litros a tu cupo disponible.')">
                                                @csrf
                                                @method('PUT') 
                                                
                                                <button type="submit" class="text-red-600 hover:text-red-800 font-black text-[10px] uppercase border border-red-200 px-3 py-1 rounded-md hover:bg-red-50 transition shadow-sm">
                                                    <i class="fas fa-times me-1"></i> Cancelar
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-gray-300 font-black text-[10px] uppercase italic">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-16 text-center text-gray-400 font-black uppercase text-xs">
                                        No hay pedidos registrados.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($pedidos->hasPages())
                    <div class="p-4 bg-gray-50 border-t border-gray-200">
                        {{ $pedidos->links() }}
                    </div>
                    @endif
                </div>
            </div>

            {{-- 2. PLANIFICACIONES LOGÍSTICAS --}}
            <div class="mb-12">
                <div class="flex flex-col xl:flex-row justify-between items-center mb-4 gap-4">
                    <h2 class="text-base font-black uppercase text-gray-700 tracking-widest">
                        <span class="text-orange-impordiesel">|</span> Mis Despachos Programados
                    </h2>

                    <form action="{{ route('portal.clientes.index') }}" method="GET" class="flex flex-wrap items-center gap-2 bg-gray-50 p-2 rounded-lg border border-gray-200">
                        @if(request('sucursal_id')) <input type="hidden" name="sucursal_id" value="{{ request('sucursal_id') }}"> @endif

                        <input type="text" name="v_search" value="{{ request('v_search') }}" placeholder="ID VIAJE / PLACA..." 
                            class="text-[10px] border-gray-300 rounded-md p-2 w-40 uppercase font-black shadow-sm">
                        
                        <select name="v_status" class="text-[10px] border-gray-300 rounded-md p-2 font-black uppercase shadow-sm">
                            <option value="">ESTATUS (TODOS)</option>
                            <option value="PROGRAMADO" {{ request('v_status') == 'PROGRAMADO' ? 'selected' : '' }}>PROGRAMADO</option>
                            <option value="EN RUTA" {{ request('v_status') == 'EN RUTA' ? 'selected' : '' }}>EN RUTA</option>
                            <option value="COMPLETADO" {{ request('v_status') == 'COMPLETADO' ? 'selected' : '' }}>COMPLETADO</option>
                        </select>

                        <div class="flex items-center gap-1 bg-white border border-gray-300 rounded px-2 py-1 shadow-sm">
                            <span class="text-[10px] font-black text-gray-400">DESDE:</span>
                            <input type="date" name="v_desde" value="{{ request('v_desde') }}" class="text-[10px] border-none p-1 focus:ring-0 font-black">
                            <span class="text-[10px] font-black text-gray-400">HASTA:</span>
                            <input type="date" name="v_hasta" value="{{ request('v_hasta') }}" class="text-[10px] border-none p-1 focus:ring-0 font-black">
                        </div>
                        
                        <button type="submit" class="bg-gray-800 text-white px-3 py-2 rounded-md hover:bg-black transition shadow-md">
                            <i class="fas fa-search text-[10px]"></i>
                        </button>
                        <a href="{{ route('portal.clientes.index') }}" class="bg-gray-200 text-gray-600 px-3 py-2 rounded-md hover:bg-gray-300 transition">
                            <i class="fas fa-undo text-[10px]"></i>
                        </a>
                    </form>
                </div>

                {{-- CONTENEDOR DE ERROR CONTROLADO --}}
                @if(session('error_gps_modulo'))
                    <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-600 rounded-r-lg shadow-sm flex items-center gap-3">
                        <i class="fas fa-exclamation-triangle text-red-600 text-sm"></i>
                        <span class="text-xs font-black uppercase text-red-800 tracking-wide">
                            {{ session('error_gps_modulo') }}
                        </span>
                    </div>
                @endif
                <div class="bg-white rounded-xl shadow-md border border-gray-300 overflow-hidden">
                    <div class="max-h-[600px] overflow-y-auto scrollbar-thin">
                        <table class="w-full text-left text-sm border-collapse">
                            <thead class="sticky top-0 z-10">
                                <tr class="bg-gray-800 text-white text-xs font-black uppercase tracking-widest">
                                    <th class="px-6 py-4 border-r border-gray-700">Info. Viaje / Sede</th>
                                    <th class="px-6 py-4 border-r border-gray-700">Transporte</th>
                                    <th class="px-6 py-4 border-r border-gray-700">Hoja de Ruta (Destinos)</th>
                                    <th class="px-6 py-4 text-center">Estatus</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y-2 divide-gray-200 bg-white">
                                @forelse($planificaciones as $viaje)
                                <tr class="hover:bg-orange-50/30 transition border-b border-gray-200">
                                    {{-- Info Viaje --}}
                                    <td class="px-6 py-6 border-r border-gray-100 align-top">
                                        <span class="block font-black text-gray-900 text-base mb-1">V-{{ str_pad($viaje->id, 5, '0', STR_PAD_LEFT) }}</span>
                                        @if($viaje->tipo_planificacion == 2)
                                            <span class="inline-block px-3 py-1 mb-2 bg-blue-600 text-white rounded text-[11px] font-black uppercase shadow-sm">MGO (MARINO)</span>
                                        @else
                                            <span class="inline-block px-3 py-1 mb-2 bg-gray-800 text-white rounded text-[11px] font-black uppercase shadow-sm">DIESEL</span>
                                        @endif
                                        <span class="block text-xs font-bold text-gray-500 mb-2 italic">Fecha: {{ $viaje->fecha_salida->format('d/m/Y') }}</span>
                                    </td>

                                    {{-- Transporte --}}
                                    <td class="px-6 py-6 border-r border-gray-100 align-top text-xs">
                                        <div class="space-y-2">
                                            <p><span class="text-[10px] font-bold text-gray-400 block">UNIDAD:</span> <span class="font-black">{{ $viaje->vehiculo->placa ?? $viaje->vehiculo_externo }}</span></p>
                                            <p><span class="text-[10px] font-bold text-gray-400 block">CHÓFER:</span> <span class="font-black uppercase">{{ $viaje->chofer->persona->nombre ?? $viaje->chofer_externo }}</span></p>
                                        </div>
                                    </td>

                                    {{-- Hoja de Ruta --}}
                                    <td class="px-6 py-6 border-r border-gray-100">
                                        <div class="flex flex-col gap-3">
                                            @foreach($viaje->detalles as $det)
                                            <div class="p-3 rounded-lg border-2 {{ $det->cliente_id == $cliente->id ? 'bg-orange-50 border-orange-300 shadow-sm' : 'bg-gray-50 border-gray-200 opacity-75' }}">
                                                <div class="flex justify-between items-start">
                                                    <div>
                                                        <p class="font-black text-gray-900 uppercase text-xs">{{ $det->cliente->nombre }}</p>
                                                        <p class="text-[11px] font-black text-blue-600">RIF: {{ $det->cliente->rif }}</p>
                                                    </div>
                                                    <span class="font-black text-orange-impordiesel text-sm">{{ number_format($det->litros, 0, ',', '.') }} Lts</span>
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </td>

                                    {{-- Estatus --}}
                                    <td class="px-6 py-6 text-center align-top whitespace-nowrap">
                                        <div class="flex flex-col items-center justify-center gap-3">
                                            <span class="px-4 py-2 rounded-full font-black text-xs uppercase shadow-md inline-block
                                                @if($viaje->status == 'PROGRAMADO') bg-blue-100 text-blue-700
                                                @elseif($viaje->status == 'EN RUTA') bg-orange-100 text-orange-700
                                                @elseif($viaje->status == 'COMPLETADO') bg-green-100 text-green-700
                                                @else bg-gray-100 text-gray-600 @endif">
                                                {{ $viaje->status }}
                                            </span>

                                            {{-- BOTÓN CONDICIONADO A 'EN RUTA' --}}
                                            @if($viaje->status === 'EN RUTA')
                                                <a href="{{ route('portal.clientes.viajes.rastreo', $viaje->id) }}" 
                                                class="inline-flex items-center justify-center bg-gray-800 text-white px-3 py-2 rounded text-[10px] font-black uppercase tracking-wider hover:bg-orange-impordiesel transition shadow-md animate-pulse">
                                                    <i class="fas fa-location-arrow mr-1.5 text-orange-impordiesel"></i> Rastrear Unidad →
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-20 text-center text-gray-400 font-black uppercase text-xs">
                                        No hay despachos programados en su ruta.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($planificaciones->hasPages())
                    <div class="p-4 bg-gray-50 border-t border-gray-200">
                        {{ $planificaciones->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <style>
            .scrollbar-thin::-webkit-scrollbar { width: 6px; }
            .scrollbar-thin::-webkit-scrollbar-track { background: #f1f1f1; }
            .scrollbar-thin::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
        </style>

    </div> {{-- FIN CONTENEDOR PRINCIPAL --}}

    <div class="text-center mt-8">
        <small class="text-gray-400 uppercase tracking-widest text-xs font-black">
            Portal de Clientes - ImporDiesel &copy; {{ date('Y') }}
        </small>
    </div>
</div>

{{-- MODAL SOLICITAR COMBUSTIBLE --}}
<div id="modalPedido" class="fixed inset-0 z-50 hidden overflow-y-auto" style="background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);">
    <div class="flex items-center justify-center min-h-screen px-4 py-8">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden border-t-8 border-orange-impordiesel">
            
            <div class="bg-gray-800 p-5 flex justify-between items-center">
                <div>
                    <h3 class="text-white font-black uppercase text-sm tracking-widest">Nueva Solicitud de Despacho</h3>
                    <p class="text-orange-impordiesel text-[10px] font-black uppercase tracking-tighter">Formulario de Pedido de Combustible</p>
                </div>
                <button onclick="closeModalPedido()" class="text-gray-400 hover:text-white transition-colors">
                    <i class="fas fa-times fa-lg"></i>
                </button>
            </div>
            
            <form action="{{ route('portal.clientes.pedidos.store') }}" method="POST" class="p-8">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 tracking-widest">1. Seleccionar Sucursal de Destino</label>
                        <select id="sucursal_select" name="cliente_id" onchange="updateModalData(this.value)"
                                class="w-full border-2 border-gray-200 rounded-lg p-3 font-black text-xs uppercase focus:border-orange-impordiesel outline-none bg-gray-50">
                            <option value="{{ $cliente->id }}" 
                                    data-rif="{{ $cliente->rif }}"
                                    data-razon="{{ $cliente->nombre }}"
                                    data-combustible="DIESEL"
                                    data-cupo="{{ number_format($cupoGasco ?? 0, 0, ',', '.') }}"
                                    data-disponible="{{ number_format($cliente->disponible ?? 0, 0, ',', '.') }}"
                                    data-direccion="{{ $cliente->direccion_operativa }}">
                                [Principal] {{ $cliente->nombre }}
                            </option>
                            @if($cliente->es_padre)
                                @foreach($sucursales as $suc)
                                    <option value="{{ $suc->id }}"
                                            data-rif="{{ $suc->rif }}"
                                            data-razon="{{ $suc->nombre }}"
                                            data-combustible="DIESEL"
                                            data-cupo="{{ number_format($suc->cuposGasco->first()->litros_autorizados ?? 0, 0, ',', '.') }}"
                                            data-disponible="{{ number_format($suc->disponible ?? 0, 0, ',', '.') }}"
                                            data-direccion="{{ $suc->direccion_operativa }}">
                                        [Sucursal] {{ $suc->nombre }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Razón Social / RIF</label>
                        <p id="display_razon" class="text-[11px] font-black text-gray-800 uppercase">{{ $cliente->nombre }}</p>
                        <p id="display_rif" class="text-[10px] font-bold text-gray-500 uppercase">{{ $cliente->rif }}</p>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Tipo de Combustible</label>
                        <p id="display_combustible" class="text-[11px] font-black text-orange-impordiesel uppercase">DIESEL</p>
                    </div>

                    <div class="bg-orange-50 p-4 rounded-lg border border-orange-100">
                        <label class="block text-[10px] font-black text-orange-400 uppercase mb-1">Cupo Mensual GASCO</label>
                        <p id="display_cupo" class="text-lg font-black text-gray-800 tracking-tighter">
                            {{ number_format($cupoGasco ?? 0, 0, ',', '.') }} <span class="text-[10px]">Lts</span>
                        </p>
                    </div>

                    <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                        <label class="block text-[10px] font-black text-green-600 uppercase mb-1">Saldo Disponible</label>
                        <p id="display_disponible" class="text-lg font-black text-gray-800 tracking-tighter">
                            {{ number_format($cliente->disponible ?? 0, 0, ',', '.') }} <span class="text-[10px]">Lts</span>
                        </p>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase mb-2">Fecha de Entrega Sugerida</label>
                        <input type="date" name="fecha_entrega" required min="{{ date('Y-m-d') }}"
                               class="w-full border-2 border-gray-200 rounded-lg p-3 font-black text-xs outline-none focus:border-orange-impordiesel">
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-500 uppercase mb-2">Litros a Solicitar</label>
                        <input type="number" name="cantidad_solicitada" step="0.01" required 
                               class="w-full border-2 border-gray-200 rounded-lg p-3 font-black text-sm outline-none focus:border-orange-impordiesel"
                               placeholder="0.00">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-gray-500 uppercase mb-2">Dirección de Entrega (Confirmación)</label>
                        <textarea id="display_direccion" name="direccion_despacho" rows="2" required
                                  class="w-full border-2 border-gray-200 rounded-lg p-3 text-xs font-bold uppercase outline-none focus:border-orange-impordiesel bg-white">{{ $cliente->direccion_operativa }}</textarea>
                    </div>

                </div>

                <div class="mt-8">
                    <button type="submit" class="w-full bg-orange-impordiesel text-white py-4 rounded-xl font-black uppercase tracking-widest hover:bg-black transition-all shadow-xl flex items-center justify-center">
                        <i class="fas fa-paper-plane mr-3"></i> Enviar Solicitud de Pedido
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL REGISTRAR PLACA --}}
<div id="modalPlaca" class="fixed inset-0 z-50 hidden overflow-y-auto" style="background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);">
    <div class="flex items-center justify-center min-h-screen px-4 py-8">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden border-t-4 border-orange-impordiesel">
            <div class="bg-gray-800 p-4 flex justify-between items-center">
                <h3 class="text-white font-black uppercase text-xs tracking-widest"><i class="fas fa-truck-moving mr-2 text-orange-impordiesel"></i> Registrar Nueva Placa</h3>
                <button onclick="closeModalPlaca()" class="text-gray-400 hover:text-white transition-colors"><i class="fas fa-times fa-lg"></i></button>
            </div>
            <form action="{{ route('portal.clientes.placas.store') }}" method="POST" class="p-6">
                @csrf
                <input type="hidden" name="cliente_id" value="{{ $cliente->id }}">
                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-2">Número de Placa</label>
                <input type="text" name="placa" class="w-full border-2 border-gray-200 rounded-lg p-3 font-black text-xs uppercase outline-none focus:border-orange-impordiesel mb-6" placeholder="EJ: AB123CD" maxlength="8" required oninput="this.value = this.value.toUpperCase()">
                <button type="submit" class="w-full bg-orange-impordiesel text-white py-3 rounded-xl font-black uppercase text-xs hover:bg-black transition-all shadow-md">Registrar</button>
            </form>
        </div>
    </div>
</div>

{{-- MODAL REGISTRAR CHOFER --}}
<div id="modalChofer" class="fixed inset-0 z-50 hidden overflow-y-auto" style="background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);">
    <div class="flex items-center justify-center min-h-screen px-4 py-8">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden border-t-4 border-orange-impordiesel">
            <div class="bg-gray-800 p-4 flex justify-between items-center">
                <h3 class="text-white font-black uppercase text-xs tracking-widest"><i class="fas fa-user-plus mr-2 text-orange-impordiesel"></i> Registrar Personal</h3>
                <button onclick="closeModalChofer()" class="text-gray-400 hover:text-white transition-colors"><i class="fas fa-times fa-lg"></i></button>
            </div>
            <form action="{{ route('portal.clientes.choferes.store') }}" method="POST" class="p-6">
                @csrf
                <input type="hidden" name="cliente_id" value="{{ $cliente->id }}">
                <div class="mb-4">
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-2">Nombre Completo</label>
                    <input type="text" name="nombre_completo" class="w-full border-2 border-gray-200 rounded-lg p-3 font-black text-xs uppercase outline-none focus:border-orange-impordiesel" required oninput="this.value = this.value.toUpperCase()">
                </div>
                <div class="mb-6">
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-wider mb-2">Cédula de Identidad</label>
                    <input type="text" name="cedula" class="w-full border-2 border-gray-200 rounded-lg p-3 font-black text-xs outline-none focus:border-orange-impordiesel" placeholder="EJ: V-12345678" required>
                </div>
                <button type="submit" class="w-full bg-orange-impordiesel text-white py-3 rounded-xl font-black uppercase text-xs hover:bg-black transition-all shadow-md">Registrar</button>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalArchivero" tabindex="-1" aria-labelledby="modalArchiveroLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title h6 text-uppercase fw-black mb-0" id="modalArchiveroLabel">
                    <i class="fas fa-archive text-orange me-2"></i> Expediente de Documentos PDF
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                
                <div class="mb-4 bg-light p-3 rounded border">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-black text-uppercase text-muted">Almacenamiento del Archivero</span>
                        <span class="small fw-black text-dark">{{ $espacioUsadoMb }} MB / 120 MB</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-orange" role="progressbar" 
                             style="width: {{ ($espacioUsadoMb / 120) * 100 }}%; background-color: #FFA500;" 
                             aria-valuenow="{{ $espacioUsadoMb }}" aria-valuemin="0" aria-valuemax="120"></div>
                    </div>
                </div>

                <form action="{{ route('portal.clientes.documentos.store') }}" method="POST" enctype="multipart/form-data" class="mb-4 p-3 border rounded border-dashed">
                    @csrf
                    <p class="small fw-black text-uppercase text-dark mb-2"><i class="fas fa-upload me-1 text-orange"></i> Cargar nuevo documento PDF</p>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <input type="text" name="nombre_archivo" class="form-control form-control-sm" placeholder="Nombre descriptivo del archivo (Ej: Registro Mercantil)" required>
                        </div>
                        <div class="col-md-4">
                            <input type="file" name="archivo" class="form-control form-control-sm" accept="application/pdf" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-orange btn-sm w-100 fw-black text-uppercase text-white text-center" style="background-color: #FFA500;">
                                Subir
                            </button>
                        </div>
                    </div>
                    <div class="form-text text-muted" style="font-size: 10px;">Solo se permiten archivos formato PDF de hasta 50MB cada uno.</div>
                </form>

                <p class="small fw-black text-uppercase text-muted mb-2"><i class="fas fa-file-pdf me-1 text-danger"></i> Archivos resguardados en tu cuenta</p>
                <div class="table-responsive border rounded">
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size: 12px;">
                        <thead class="table-light">
                            <tr class="text-uppercase text-muted" style="font-size: 10px;">
                                <th class="px-3 py-2">Documento</th>
                                <th class="py-2">Fecha de Carga</th>
                                <th class="py-2 text-end px-3">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($documentos as $doc)
                                <tr>
                                    <td class="px-3 fw-bold text-dark text-uppercase">
                                        <i class="far fa-file-pdf text-danger me-2"></i> {{ $doc->nombre_archivo }}
                                    </td>
                                    <td class="text-muted">{{ $doc->created_at->format('d/m/Y h:i A') }}</td>
                                    <td class="text-end px-3">
                                        <a href="{{ route('portal.clientes.documentos.download', $doc->id) }}" class="btn btn-light btn-sm fw-black text-uppercase text-primary" style="font-size: 10px;">
                                            <i class="fas fa-download me-1"></i> Descargar
                                        </a>
                                        </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center p-4 text-muted fw-bold text-uppercase" style="font-size: 11px;">
                                        No has cargado ningún documento en tu archivero todavía.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    // Togglar Modo Edición del Perfil
    function toggleEditPerfil() {
        const viewMode = document.getElementById('perfilViewMode');
        const editMode = document.getElementById('perfilEditMode');
        const btnEdit  = document.getElementById('btnEditPerfil');
        
        if (viewMode.classList.contains('hidden')) {
            viewMode.classList.remove('hidden');
            editMode.classList.add('hidden');
            btnEdit.classList.remove('hidden');
        } else {
            viewMode.classList.add('hidden');
            editMode.classList.remove('hidden');
            btnEdit.classList.add('hidden');
        }
    }

    // Modal de Pedido (Original tuyo)
    function openModalPedido() { 
        document.getElementById('modalPedido').classList.remove('hidden');
        document.body.style.overflow = 'hidden'; 
        updateModalData(document.getElementById('sucursal_select').value);
    }
    
    function closeModalPedido() { 
        document.getElementById('modalPedido').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // Modal Placa
    function openModalPlaca() {
        document.getElementById('modalPlaca').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    
    function closeModalPlaca() {
        document.getElementById('modalPlaca').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // Modal Chofer
    function openModalChofer() {
        document.getElementById('modalChofer').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    
    function closeModalChofer() {
        document.getElementById('modalChofer').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // Lógica Original
    function updateModalData(clienteId) {
        const select = document.getElementById('sucursal_select');
        const selectedOption = select.options[select.selectedIndex];

        document.getElementById('display_razon').innerText = selectedOption.getAttribute('data-razon');
        document.getElementById('display_rif').innerText = selectedOption.getAttribute('data-rif');
        document.getElementById('display_cupo').innerHTML = `${selectedOption.getAttribute('data-cupo')} <span class="text-[10px]">Lts</span>`;
        document.getElementById('display_disponible').innerHTML = `${selectedOption.getAttribute('data-disponible')} <span class="text-[10px]">Lts</span>`;
        document.getElementById('display_direccion').value = selectedOption.getAttribute('data-direccion');
        
        const inputLitros = document.querySelector('input[name="cantidad_solicitada"]');
        const disponibleRaw = selectedOption.getAttribute('data-disponible').replace(/\./g, '').replace(',', '.');
        
        inputLitros.max = disponibleRaw;
        inputLitros.placeholder = "Máx: " + selectedOption.getAttribute('data-disponible');
    }

    function copyToken() {
        const t = document.getElementById('tokenInvitacion').innerText.trim();
        navigator.clipboard.writeText(t).then(() => alert('¡Token copiado!'));
    }

    function filtrarLista(inputId, listaId) {
        const filtro = document.getElementById(inputId).value.toUpperCase();
        const items  = document.getElementById(listaId).children;
        for (let i = 0; i < items.length; i++) {
            const txt = items[i].textContent || items[i].innerText;
            items[i].style.display = txt.toUpperCase().includes(filtro) ? '' : 'none';
        }
    }
</script>
@endsection