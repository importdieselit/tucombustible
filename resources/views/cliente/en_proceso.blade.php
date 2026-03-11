@extends('layouts.app')

@section('content')
<div class="container mx-auto py-8 px-4">
    <div class="max-w-6xl mx-auto">
        
        {{-- ENCABEZADO SIMÉTRICO --}}
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 bg-white p-4 shadow-sm rounded-lg border-l-4 border-orange-impordiesel border border-gray-200">
            <div>
                <h3 class="text-xl font-bold mb-0 uppercase flex items-center text-gray-800">
                    <i class="fas fa-clipboard-check text-orange-impordiesel mr-3"></i>
                    Estado de tu Registro
                </h3>
                <p class="text-gray-500 text-sm mt-1 uppercase font-bold tracking-tighter">Gestión de expediente y validación de documentos.</p>
            </div>
            <div class="mt-4 md:mt-0">
                <span class="bg-gray-industrial text-white px-4 py-2 rounded text-xs font-black uppercase tracking-wider shadow-sm">
                    Paso {{ $cliente->registro_paso }}: {{ $cliente->nombre_paso_actual }}
                </span>
            </div>
        </div>

        {{-- ROUTE MAP DINÁMICO (Sustituye a la barra de progreso anterior) --}}
        <div class="bg-white p-8 rounded-lg shadow-md border-t-4 border-orange-impordiesel mb-8 border border-gray-200">
             <x-route-map :pasoActual="$cliente->registro_paso" />
        </div>

        @if($cliente->registro_paso == 2)
            
            {{-- BLOQUE DE DESCARGA DE PLANILLAS CORREGIDO --}}
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6 border-2 border-gray-industrial">
                <div class="p-6 flex flex-col md:flex-row items-center bg-gray-50">
                    <div class="md:mr-6 mb-4 md:mb-0 text-orange-impordiesel">
                        <i class="fas fa-file-archive fa-4x"></i>
                    </div>
                    <div class="flex-grow text-center md:text-left">
                        <h5 class="text-lg font-black text-gray-800 uppercase tracking-tight mb-2">Formatos Requeridos</h5>
                        <p class="text-gray-600 text-sm mb-5 font-bold uppercase leading-tight">Descargue el paquete comprimido (.zip), complete las planillas físicamente, fírrmelas y escanee cada una para subirlas.</p>
                        {{-- BOTÓN ZIP: Gris Industrial para máximo contraste contra el fondo claro --}}
                        <a href="{{ route('portal.clientes.descargar.formatos') }}" 
                           class="inline-flex items-center bg-gray-industrial hover:bg-black text-white font-black py-4 px-8 rounded shadow-lg transition duration-300 uppercase text-xs tracking-widest border-b-4 border-black">
                            <i class="fas fa-download mr-3 text-orange-impordiesel text-lg"></i> DESCARGAR TODAS LAS PLANILLAS (.ZIP)
                        </a>
                    </div>
                </div>
            </div>

            <div class="bg-blue-600 border-l-8 border-blue-900 p-4 mb-6 shadow-md">
                <div class="flex items-center">
                    <div class="flex-shrink-0 text-white">
                        <i class="fas fa-info-circle text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-white font-black uppercase tracking-tighter">
                            Instrucciones: Por favor, suba los 12 documentos requeridos. El sistema habilitará el envío a revisión automáticamente.
                        </p>
                    </div>
                </div>
            </div>

            {{-- INCLUSIÓN DEL PARTIAL --}}
            @include('cliente.partials.formulario_carga_docs')

        @else
            {{-- MENSAJE PARA PASOS DE REVISIÓN O ESPERA --}}
            <div class="bg-white rounded-lg shadow-sm p-12 text-center border-t-8 border-gray-industrial border border-gray-200">
                <div class="mb-6">
                    @if($cliente->registro_paso == 3)
                        <div class="w-24 h-24 bg-orange-100 text-orange-impordiesel rounded-full flex items-center justify-center mx-auto shadow-inner border border-orange-200">
                            <i class="fas fa-file-medical fa-3x"></i>
                        </div>
                    @else
                        <div class="w-24 h-24 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center mx-auto shadow-inner border border-gray-200">
                            <i class="fas fa-user-clock fa-3x"></i>
                        </div>
                    @endif
                </div>
                
                <h3 class="text-2xl font-black text-gray-800 uppercase mb-2">{{ $cliente->nombre_paso_actual }}</h3>
                <p class="text-gray-500 max-w-md mx-auto mb-8 font-bold uppercase text-sm">
                    Actualmente tu expediente se encuentra en la etapa de <span class="text-orange-impordiesel">"{{ $cliente->nombre_paso_actual }}"</span>. 
                    Nuestro equipo administrativo está procesando la información.
                </p>
                
                <button class="inline-flex items-center px-8 py-3 bg-orange-impordiesel text-white font-black rounded shadow-lg hover:bg-orange-700 transition duration-300 text-sm uppercase tracking-widest border-b-4 border-orange-900" 
                        onclick="window.location.reload()">
                    <i class="fas fa-sync-alt mr-2"></i> Refrescar Estatus
                </button>
            </div>
        @endif

        <div class="text-center mt-12">
            <small class="text-gray-400 uppercase tracking-widest text-xs font-black">Portal de Clientes - ImporDiesel &copy; {{ date('Y') }}</small>
        </div>
    </div>
</div>
@endsection