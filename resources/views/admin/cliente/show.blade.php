@extends('layouts.app')

@section('content')
<div class="container mx-auto py-6 px-4">
    {{-- BARRA SUPERIOR: Botón Volver con fondo gris oscuro --}}
    <div class="flex items-center justify-between mb-6 no-print bg-white p-3 rounded-lg border border-gray-300 shadow-sm">
        <a href="{{ route('clientes.index') }}" class="bg-gray-industrial text-white px-5 py-2.5 rounded text-xs font-black uppercase hover:bg-black transition flex items-center shadow-md">
            <i class="fas fa-chevron-left mr-2 text-orange-impordiesel"></i> VOLVER AL LISTADO
        </a>
        
        <button onclick="window.print();" class="bg-white border-2 border-gray-800 text-gray-800 px-5 py-2 rounded text-xs font-black uppercase hover:bg-gray-800 hover:text-white transition shadow-sm">
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
                            {{-- BOTÓN VER: Azul fuerte para contraste absoluto --}}
                            <a href="{{ asset('storage/' . $doc->ruta) }}" target="_blank" 
                               class="bg-blue-700 text-white px-4 py-2 rounded text-[10px] font-black uppercase hover:bg-blue-900 transition shadow-md border-b-2 border-blue-900">
                                <i class="fas fa-file-pdf mr-1"></i> VER Documento
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded border-2 border-gray-300 p-6 shadow-md">
                <h5 class="text-xs font-black uppercase text-gray-800 mb-6 flex items-center border-b-2 border-orange-impordiesel pb-2">
                    <i class="fas fa-cog mr-2 text-orange-impordiesel"></i> Control Administrativo
                </h5>
                
                <form action="{{ route('clientes.avanzar', $cliente->id) }}" method="POST" class="space-y-6">
                    @csrf
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
                        GUARDAR CAMBIOS
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection