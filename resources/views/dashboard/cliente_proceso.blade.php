@extends('layouts.app')

@section('title', 'Estado de mi Solicitud')

@section('content')
<div class="max-w-4xl mx-auto py-8">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-blue-600 p-8 text-white">
            <h1 class="text-2xl font-bold">¡Hola, {{ Auth::user()->name }}!</h1>
            <p class="opacity-90">Estamos gestionando tu cupo de combustible. Aquí puedes ver tu avance.</p>
            
            <div class="mt-6">
                <div class="flex justify-between mb-2 text-sm font-medium">
                    <span>Progreso General</span>
                    <span>{{ $porcentaje }}%</span>
                </div>
                <div class="w-full bg-blue-400/30 rounded-full h-3">
                    <div class="bg-white h-3 rounded-full transition-all duration-500" style="width: {{ $porcentaje }}%"></div>
                </div>
            </div>
        </div>

        <div class="p-8">
            @if($paso_actual == 3)
                <div class="mb-10 p-6 bg-blue-50 rounded-xl border border-blue-100">
                    <h3 class="text-blue-800 font-bold mb-4 flex items-center">
                        <i class="fas fa-file-upload mr-2"></i> Documentación Requerida (Paso 3)
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($requisitos as $req)
                            <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                                <p class="text-sm font-bold text-gray-700">{{ $req->nombre }}</p>
                                <form action="{{ route('captacion.upload') }}" method="POST" enctype="multipart/form-data" class="mt-2">
                                    @csrf
                                    <input type="hidden" name="requisito_id" value="{{ $req->id }}">
                                    <input type="file" name="archivo" class="block w-full text-xs text-gray-500 file:mr-4 file:py-1 file:px-2 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 mb-2">
                                    <button class="w-full bg-blue-600 text-white py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider">Subir Archivo</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-8 pt-6 border-t border-blue-200 flex justify-center">
                        <form action="{{ route('captacion.finalizarCarga') }}" method="POST">
                            @csrf
                            <button type="submit" class="bg-green-600 text-white px-10 py-3 rounded-xl font-bold shadow-lg hover:bg-green-700 transition transform hover:scale-105">
                                <i class="fas fa-paper-plane mr-2"></i> ENVIAR EXPEDIENTE PARA REVISIÓN
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            <h3 class="text-lg font-bold text-gray-800 mb-6 border-b pb-2">Etapas del Proceso</h3>
            <div class="space-y-6">
                @for ($i = 1; $i <= 10; $i++)
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center font-bold {{ $paso_actual >= $i ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400' }}">
                            @if($paso_actual > $i)
                                <i class="fas fa-check"></i>
                            @else
                                {{ $i }}
                            @endif
                        </div>
                        <div class="ml-4 text-sm">
                            <h4 class="font-semibold {{ $paso_actual >= $i ? 'text-gray-900' : 'text-gray-400' }}">
                                {{ $dashboardService->getNombrePaso($i) }}
                            </h4>
                            @if($paso_actual == $i)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 mt-1 uppercase">
                                    En curso actualmente
                                </span>
                            @endif
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    </div>
</div>
@endsection