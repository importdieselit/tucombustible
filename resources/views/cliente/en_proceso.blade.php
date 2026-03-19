@extends('layouts.app')

@section('content')
<div class="container mx-auto py-8 px-4">
    <div class="max-w-4xl mx-auto">

        {{-- ENCABEZADO --}}
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 bg-white p-4 shadow-sm rounded-lg border-l-4 border-orange-impordiesel border border-gray-200">
            <div>
                <h3 class="text-xl font-bold mb-0 uppercase flex items-center text-gray-800">
                    <i class="fas fa-clipboard-check text-orange-impordiesel mr-3"></i>
                    Estado de tu Registro
                </h3>
                <p class="text-gray-500 text-sm mt-1 uppercase font-bold tracking-tighter">
                    Nuestro equipo administrativo está procesando tu solicitud.
                </p>
            </div>
            <div class="mt-4 md:mt-0">
                <span class="bg-gray-industrial text-white px-4 py-2 rounded text-xs font-black uppercase tracking-wider shadow-sm">
                    Paso {{ $paso_actual }} de 5: {{ $nombre_paso }}
                </span>
            </div>
        </div>

        {{-- LÍNEA DE TIEMPO --}}
        <div class="bg-white p-8 rounded-lg shadow-md border-t-4 border-orange-impordiesel mb-8 border border-gray-200">
            <p class="text-[10px] font-black uppercase text-gray-400 tracking-widest mb-8">
                Línea de Tiempo del Registro (5 Etapas)
            </p>

            <div class="flex items-start justify-between relative">
                {{-- Línea de fondo --}}
                <div class="absolute top-5 left-0 right-0 h-1 bg-gray-200 z-0"></div>
                {{-- Línea de progreso --}}
                <div class="absolute top-5 left-0 h-1 bg-orange-impordiesel z-0 transition-all duration-500"
                     style="width: {{ $porcentaje }}%"></div>

                @foreach($pasos as $paso)
                    @php
                        $completado = $paso->id < $paso_actual;
                        $actual     = $paso->id == $paso_actual;
                    @endphp
                    <div class="flex flex-col items-center z-10 flex-1">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-black text-sm border-2 transition-all
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

        {{-- MENSAJE DE ESTADO --}}
        <div class="bg-white rounded-lg shadow-sm p-12 text-center border-t-8 border-gray-industrial border border-gray-200">
            <div class="mb-6">
                @if($paso_actual == 3)
                    <div class="w-24 h-24 bg-orange-100 text-orange-impordiesel rounded-full flex items-center justify-center mx-auto shadow-inner border border-orange-200">
                        <i class="fas fa-calendar-check fa-3x"></i>
                    </div>
                @else
                    <div class="w-24 h-24 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center mx-auto shadow-inner border border-gray-200">
                        <i class="fas fa-user-clock fa-3x"></i>
                    </div>
                @endif
            </div>

            <h3 class="text-2xl font-black text-gray-800 uppercase mb-2">{{ $nombre_paso }}</h3>
            <p class="text-gray-500 max-w-md mx-auto mb-8 font-bold uppercase text-sm">
                Tu expediente se encuentra en la etapa
                <span class="text-orange-impordiesel">"{{ $nombre_paso }}"</span>.
                Nuestro equipo administrativo está procesando la información.
            </p>

            <button onclick="window.location.reload()"
                    class="inline-flex items-center px-8 py-3 bg-orange-impordiesel text-white font-black rounded shadow-lg hover:bg-orange-700 transition duration-300 text-sm uppercase tracking-widest border-b-4 border-orange-900">
                <i class="fas fa-sync-alt mr-2"></i> Refrescar Estatus
            </button>
        </div>

        <div class="text-center mt-12">
            <small class="text-gray-400 uppercase tracking-widest text-xs font-black">
                Portal de Clientes - ImporDiesel &copy; {{ date('Y') }}
            </small>
        </div>
    </div>
</div>
@endsection