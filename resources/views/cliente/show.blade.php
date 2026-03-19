@extends('layouts.app')
@section('title', 'Mi Expediente - ImporDiesel')

@section('content')
<div class="container mx-auto py-6 px-4">
    <div class="max-w-3xl mx-auto">

        {{-- ENCABEZADO --}}
        <div class="flex items-center justify-between mb-6 bg-white p-4 rounded-lg border-l-4 border-orange-impordiesel shadow-sm border border-gray-200">
            <div>
                <h1 class="text-xl font-black text-gray-800 uppercase tracking-tight">Mi Expediente</h1>
                <p class="text-gray-500 text-xs font-bold uppercase tracking-widest mt-1">
                    Datos registrados en el sistema
                </p>
            </div>
            <a href="{{ route('portal.clientes.index') }}"
               class="bg-gray-industrial text-white px-4 py-2 rounded text-xs font-black uppercase hover:bg-black transition">
                <i class="fas fa-chevron-left mr-1"></i> Volver
            </a>
        </div>

        {{-- DATOS DEL CLIENTE --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
            <div class="bg-gray-800 px-6 py-4">
                <h2 class="text-lg font-black text-white uppercase tracking-tight">{{ $cliente->nombre }}</h2>
                <p class="text-orange-impordiesel text-xs font-black uppercase tracking-widest">RIF: {{ $cliente->rif }}</p>
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
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Correo Electrónico</p>
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
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Fecha de Registro</p>
                    <p class="font-black text-gray-700 mt-1">{{ $cliente->created_at?->format('d/m/Y') ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Fecha de Aprobación</p>
                    <p class="font-black text-gray-700 mt-1">{{ $cliente->fecha_aprobacion?->format('d/m/Y') ?? 'Pendiente' }}</p>
                </div>
            </div>
        </div>

        {{-- CUPOS --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
            <div class="bg-gray-50 px-6 py-3 border-b border-gray-100">
                <p class="text-[10px] font-black uppercase text-gray-700 tracking-widest">Cupo Mensual Asignado</p>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse($cliente->cupos as $cupo)
                    <div class="border-l-4 border-orange-impordiesel pl-4">
                        <p class="text-[10px] font-black uppercase text-orange-impordiesel tracking-widest">
                            {{ $cupo->tipoCombustible->nombre }}
                        </p>
                        <p class="text-2xl font-black text-gray-800">
                            {{ number_format($cupo->litros_aprobados, 0, ',', '.') }}
                            <small class="text-xs text-gray-500 uppercase">L/mes</small>
                        </p>
                    </div>
                @empty
                    <p class="text-xs text-gray-400 font-bold uppercase italic col-span-2">Sin cupo asignado aún.</p>
                @endforelse
            </div>
        </div>

        <div class="text-center mt-8">
            <small class="text-gray-400 uppercase tracking-widest text-xs font-black">
                Portal de Clientes - ImporDiesel &copy; {{ date('Y') }}
            </small>
        </div>
    </div>
</div>
@endsection