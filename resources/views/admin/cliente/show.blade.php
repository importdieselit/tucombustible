@extends('layouts.app')

@section('content')
<div class="container mx-auto py-6 px-4">
    {{-- BARRA SUPERIOR --}}
    <div class="flex items-center justify-between mb-6 no-print bg-white p-3 rounded-lg border border-gray-300 shadow-sm">
        <a href="{{ route('clientes.index') }}" class="bg-gray-industrial text-white px-5 py-2.5 rounded text-xs font-black uppercase hover:bg-black transition flex items-center shadow-md">
            <i class="fas fa-chevron-left mr-2 text-orange-impordiesel"></i> VOLVER AL LISTADO
        </a>
        
        <button onclick="window.print();" class="bg-white border-2 border-gray-800 text-gray-800 px-5 py-2 rounded text-xs font-black uppercase hover:bg-800 hover:text-white transition shadow-sm">
            <i class="fas fa-print mr-2"></i> IMPRIMIR EXPEDIENTE
        </button>
    </div>

    {{-- ROUTE MAP DINÁMICO --}}
    <div class="bg-white p-6 rounded-lg shadow-md border-2 border-gray-300 mb-8 no-print">
        <h6 class="text-[10px] font-black uppercase text-gray-400 mb-6 tracking-widest border-b pb-2">Línea de Tiempo del Registro (10 Pasos)</h6>
        <x-route-map :pasoActual="$cliente->registro_paso" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            {{-- INFORMACIÓN PRINCIPAL Y DOCUMENTOS --}}
            <div class="bg-white rounded border-2 border-gray-300 shadow-md overflow-hidden">
                <div class="bg-gray-800 p-5">
                    <h2 class="text-2xl font-black text-white uppercase tracking-tighter">{{ $cliente->nombre }}</h2>
                    <p class="text-orange-impordiesel text-sm font-black uppercase tracking-widest">Expediente Digital | RIF: {{ $cliente->rif }}</p>
                </div>

                <div class="p-6">
                    <h5 class="text-xs font-black uppercase text-gray-500 mb-6 border-b-2 border-orange-impordiesel pb-2">Documentos Adjuntos en Expediente</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($cliente->documentos as $doc)
                        <div class="flex items-center justify-between p-4 bg-gray-100 border-2 border-gray-200 rounded-lg shadow-sm">
                            <span class="text-[11px] font-black text-gray-800 uppercase truncate mr-2">{{ str_replace('_', ' ', $doc->nombre_documento) }}</span>
                            <a href="{{ asset('storage/' . $doc->ruta) }}" target="_blank" 
                               class="bg-blue-700 text-white px-4 py-2 rounded text-[10px] font-black uppercase hover:bg-blue-900 transition shadow-md border-b-2 border-blue-900">
                                <i class="fas fa-file-pdf mr-1"></i> VER Documento
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- SECCIÓN DE ACTIVOS CON SCROLL Y FILTROS (NUEVO) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 no-print">
                <div class="bg-white rounded border-2 border-gray-300 shadow-md flex flex-col h-[400px]">
                    <div class="bg-gray-800 p-3 flex justify-between items-center">
                        <h5 class="text-[10px] font-black uppercase text-orange-impordiesel italic">
                            <i class="fas fa-truck-moving mr-2"></i> Placas Autorizadas
                        </h5>
                        <span class="bg-orange-impordiesel text-white text-[9px] px-2 py-0.5 rounded-full font-black">
                            {{ count($placas) }}
                        </span>
                    </div>
                    <div class="p-2 border-b">
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-[10px]"></i>
                            <input type="text" id="filterPlacas" onkeyup="filterActivos('filterPlacas', 'containerPlacas')" 
                                   class="w-full pl-8 pr-4 py-2 bg-gray-100 border-none rounded text-[10px] font-black uppercase outline-none focus:ring-1 focus:ring-orange-impordiesel" 
                                   placeholder="BUSCAR PLACA...">
                        </div>
                    </div>
                    <div class="flex-1 overflow-y-auto p-2 custom-scrollbar" id="containerPlacas">
                        @forelse($placas as $placa)
                            <div class="activo-item flex justify-between items-center p-2 mb-1 bg-gray-50 border border-gray-200 rounded hover:border-orange-impordiesel transition">
                                <span class="text-xs font-black text-gray-700 tracking-widest">{{ $placa->placa }}</span>
                                <i class="fas fa-check-circle text-green-500 text-[10px]"></i>
                            </div>
                        @empty
                            <p class="text-[10px] text-gray-400 text-center mt-10 font-bold uppercase italic">Sin placas registradas</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white rounded border-2 border-gray-300 shadow-md flex flex-col h-[400px]">
                    <div class="bg-gray-800 p-3 flex justify-between items-center">
                        <h5 class="text-[10px] font-black uppercase text-blue-400 italic">
                            <i class="fas fa-id-card mr-2"></i> Personal Autorizado
                        </h5>
                        <span class="bg-blue-600 text-white text-[9px] px-2 py-0.5 rounded-full font-black">
                            {{ count($choferes) }}
                        </span>
                    </div>
                    <div class="p-2 border-b">
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-[10px]"></i>
                            <input type="text" id="filterChoferes" onkeyup="filterActivos('filterChoferes', 'containerChoferes')" 
                                   class="w-full pl-8 pr-4 py-2 bg-gray-100 border-none rounded text-[10px] font-black uppercase outline-none focus:ring-1 focus:ring-blue-600" 
                                   placeholder="BUSCAR POR NOMBRE O CÉDULA...">
                        </div>
                    </div>
                    <div class="flex-1 overflow-y-auto p-2 custom-scrollbar" id="containerChoferes">
                        @forelse($choferes as $chofer)
                            <div class="activo-item p-2 mb-1 bg-gray-50 border border-gray-200 rounded hover:border-blue-600 transition">
                                <div class="text-[10px] font-black text-gray-800 uppercase">{{ $chofer->nombre_completo }}</div>
                                <div class="text-[9px] text-gray-500 font-bold">C.I: {{ $chofer->cedula }}</div>
                            </div>
                        @empty
                            <p class="text-[10px] text-gray-400 text-center mt-10 font-bold uppercase italic">Sin choferes registrados</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            {{-- CONTROL ADMINISTRATIVO --}}
            <div class="bg-white rounded border-2 border-gray-300 p-6 shadow-md">
                <h5 class="text-xs font-black uppercase text-gray-800 mb-6 flex items-center border-b-2 border-orange-impordiesel pb-2">
                    <i class="fas fa-cog mr-2 text-orange-impordiesel"></i> Gestión de Cupo y Estatus
                </h5>
                
                <form action="{{ route('clientes.avanzar', $cliente->id) }}" method="POST" class="space-y-6">
                    @csrf
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="text-[10px] font-black text-gray-500 uppercase block mb-1 tracking-widest">Litros Mensuales Aprobados:</label>
                            <input type="number" 
                                   name="cupo" 
                                   value="{{ old('cupo', $cliente->cupo) }}" 
                                   class="w-full text-sm font-black border-2 border-gray-400 rounded p-2 outline-none focus:border-orange-impordiesel" 
                                   placeholder="0.00" 
                                   required>
                            @if($cliente->disponible > 0)
                                <p class="text-[9px] text-green-600 font-bold mt-1 uppercase italic">
                                    <i class="fas fa-check mr-1"></i> Disponible actual: {{ number_format($cliente->disponible, 0) }} L
                                </p>
                            @endif
                        </div>
                        <div class="col-span-2">
                            <label class="text-[10px] font-black text-gray-500 uppercase block mb-1 tracking-widest">Tipo de Combustible Autorizado:</label>
                            <select name="tipo_combustible_id" class="w-full text-xs font-black border-2 border-gray-400 rounded p-2 outline-none focus:border-orange-impordiesel bg-gray-50" required>
                                <option value="" disabled {{ is_null($cliente->tipo_servicio) ? 'selected' : '' }}>SELECCIONE UNO...</option>
                                <option value="1" {{ (old('tipo_combustible_id', ($cliente->litros_diesel > 0 ? 1 : 0)) == 1) ? 'selected' : '' }}>DIESEL</option>
                                <option value="2" {{ (old('tipo_combustible_id', ($cliente->litros_mgo > 0 ? 2 : 0)) == 2) ? 'selected' : '' }}>MGO</option>
                            </select>
                        </div>
                    </div>

                    <hr class="border-gray-300">

                    <div>
                        <label class="text-[10px] font-black text-gray-500 uppercase block mb-2 tracking-widest">Cambiar Etapa de Registro:</label>
                        <select name="paso" class="w-full text-xs font-black border-2 border-gray-400 rounded p-3 outline-none focus:border-orange-impordiesel bg-gray-50 uppercase">
                            @foreach(App\Models\Cliente::PASOS_REGISTRO as $valor => $nombre)
                                <option value="{{ $valor }}" {{ $cliente->registro_paso == $valor ? 'selected' : '' }}>
                                    Paso {{ $valor }}: {{ $nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="w-full bg-orange-impordiesel text-white font-black py-4 rounded text-sm uppercase hover:bg-orange-700 transition shadow-xl border-b-4 border-orange-900 tracking-widest">
                        ACTUALIZAR EXPEDIENTE
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- ESTILOS Y SCRIPTS INTEGRADOS --}}
<style>
    .custom-scrollbar::-webkit-scrollbar { width: 5px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #gray-400; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #f97316; }

    @media print {
        .no-print { display: none !important; }
        .custom-scrollbar { overflow: visible !important; height: auto !important; }
    }
</style>

<script>
    function filterActivos(inputId, containerId) {
        const input = document.getElementById(inputId);
        const filter = input.value.toUpperCase();
        const container = document.getElementById(containerId);
        const items = container.getElementsByClassName('activo-item');

        for (let i = 0; i < items.length; i++) {
            const textContent = items[i].textContent || items[i].innerText;
            if (textContent.toUpperCase().indexOf(filter) > -1) {
                items[i].style.display = "";
            } else {
                items[i].style.display = "none";
            }
        }
    }
</script>
@endsection