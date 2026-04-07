@props(['pasoActual'])

@php
    $pasos = [
        1 => 'Registro de Datos',
        2 => 'Carga de Documentos y Adjuntos',
        3 => 'Recepción de Documentos',
        4 => 'Revisión de Documentos',
        5 => 'Carpeta de Documentos Realizada',
        6 => 'Carpeta Enviada al Ministerio',
        7 => 'Espera Respuesta Ministerio',
        8 => 'Fecha de Inspección Asignada',
        9 => 'Espera Respuesta Ministerio',
        10 => 'Cliente Aprobado'
    ];
@endphp

<div class="w-full py-6 no-print">
    <div class="flex items-start justify-between relative">
        {{-- LÍNEA DE FONDO --}}
        <div class="absolute top-5 left-0 w-full h-1 bg-gray-300 z-0"></div>
        
        {{-- LÍNEA DE PROGRESO ACTIVA --}}
        <div class="absolute top-5 left-0 h-1 bg-orange-impordiesel z-0 transition-all duration-500" 
             style="width: {{ (($pasoActual - 1) / 9) * 100 }}%;"></div>

        @foreach($pasos as $numero => $nombre)
            <div class="relative z-10 flex flex-col items-center flex-1">
                {{-- CÍRCULO --}}
                <div class="w-10 h-10 flex items-center justify-center rounded-full border-4 transition-colors duration-300
                    {{ $numero < $pasoActual ? 'bg-gray-industrial border-gray-industrial text-white' : '' }}
                    {{ $numero == $pasoActual ? 'bg-orange-impordiesel border-orange-impordiesel text-white shadow-lg scale-110' : '' }}
                    {{ $numero > $pasoActual ? 'bg-white border-gray-300 text-gray-400' : '' }}">
                    
                    @if($numero < $pasoActual)
                        <i class="fas fa-check text-xs"></i>
                    @else
                        <span class="text-xs font-black">{{ $numero }}</span>
                    @endif
                </div>

                {{-- NOMBRE DEL PASO (Texto invisible en móviles pequeños para evitar desorden) --}}
                <div class="mt-3 px-1 text-center hidden md:block">
                    <span class="text-[9px] font-black uppercase tracking-tighter leading-none
                        {{ $numero == $pasoActual ? 'text-orange-impordiesel' : 'text-gray-500' }}">
                        {{ $nombre }}
                    </span>
                </div>
            </div>
        @endforeach
    </div>
</div>