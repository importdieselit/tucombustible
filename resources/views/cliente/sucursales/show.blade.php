@extends('layouts.app')
@section('title', 'Expediente de Sucursal - ' . $sucursal->nombre)

@section('content')
<div class="container mx-auto py-6 px-4">
    {{-- Encabezado con navegación --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 bg-white p-6 rounded-xl shadow-sm border-t-4 border-orange-impordiesel">
        <div class="flex items-center">
            <a href="{{ route('portal.clientes.index') }}" class="mr-4 text-gray-400 hover:text-orange-impordiesel transition">
                <i class="fas fa-arrow-left fa-lg"></i>
            </a>
            <div>
                <h1 class="text-xl font-black text-gray-800 uppercase tracking-tighter">
                    Sucursal: {{ $sucursal->nombre }}
                </h1>
                <p class="text-gray-500 text-[10px] font-black uppercase tracking-widest">
                    RIF: {{ $sucursal->rif }} | Ubicación: {{ $sucursal->ciudad->nombre }}, {{ $sucursal->estado->nombre }}
                </p>
            </div>
        </div>
        <div class="mt-4 md:mt-0 flex gap-2">
            <span class="px-4 py-2 rounded-lg text-xs font-black uppercase border-2 {{ $sucursal->status == 1 ? 'border-green-500 text-green-600 bg-green-50' : 'border-red-500 text-red-600 bg-red-50' }}">
                <i class="fas {{ $sucursal->status == 1 ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                {{ $sucursal->status == 1 ? 'Cuenta Operativa' : 'Cuenta Inactiva' }}
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Columna Izquierda: Detalles e Información --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-gray-800 p-6 rounded-xl shadow-lg text-white">
                <h3 class="text-orange-impordiesel font-black text-xs uppercase tracking-widest mb-4 italic">Estatus de Registro</h3>
                <div class="relative pt-1">
                    <div class="flex mb-2 items-center justify-between">
                        <div>
                            <span class="text-[10px] font-black inline-block py-1 px-2 uppercase rounded-full bg-orange-impordiesel text-white">
                                Paso {{ $sucursal->registro_paso }} de 10
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-black inline-block text-orange-impordiesel">
                                {{ ($sucursal->registro_paso / 10) * 100 }}%
                            </span>
                        </div>
                    </div>
                    <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-gray-700">
                        <div style="width:{{ ($sucursal->registro_paso / 10) * 100 }}%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-orange-impordiesel transition-all duration-500"></div>
                    </div>
                </div>
                <p class="text-[10px] text-gray-400 font-bold uppercase leading-tight">
                    Fase Actual: <span class="text-white">{{ \App\Models\Cliente::PASOS_REGISTRO[$sucursal->registro_paso] ?? 'Revisión' }}</span>
                </p>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h3 class="text-gray-800 font-black text-xs uppercase tracking-widest mb-4 border-b pb-2">Datos de Contacto</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-[9px] text-gray-400 font-black uppercase">Representante / Contacto</p>
                        <p class="text-sm font-bold text-gray-700 uppercase">{{ $sucursal->contacto }}</p>
                    </div>
                    <div>
                        <p class="text-[9px] text-gray-400 font-black uppercase">Teléfono</p>
                        <p class="text-sm font-bold text-gray-700">{{ $sucursal->telefono }}</p>
                    </div>
                    <div>
                        <p class="text-[9px] text-gray-400 font-black uppercase">Dirección Operativa</p>
                        <p class="text-xs font-bold text-gray-600 uppercase">{{ $sucursal->direccion_operativa }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Columna Derecha: Documentación --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="text-gray-800 font-black text-xs uppercase tracking-widest">Expediente Digital de Sucursal</h3>
                    <span class="bg-gray-200 text-gray-600 px-2 py-1 rounded text-[10px] font-black uppercase">
                        {{ $sucursal->documentos->count() }} Archivos
                    </span>
                </div>
                <div class="p-0">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-100 text-[9px] font-black text-gray-500 uppercase">
                                <th class="px-6 py-3">Documento / Requisito</th>
                                <th class="px-6 py-3">Fecha de Carga</th>
                                <th class="px-6 py-3 text-center">Estado</th>
                                <th class="px-6 py-3 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($sucursal->documentos as $doc)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <i class="far fa-file-pdf text-red-500 fa-lg mr-3"></i>
                                            <div>
                                                <p class="text-xs font-black text-gray-700 uppercase">{{ str_replace('_', ' ', $doc->nombre_documento) }}</p>
                                                <p class="text-[9px] text-gray-400 font-bold uppercase italic">Requisito ID: #{{ $doc->requisito_id }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase">
                                        {{ $doc->created_at->format('d/m/Y h:i A') }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($doc->validado)
                                            <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-[9px] font-black uppercase">Validado</span>
                                        @else
                                            <span class="bg-orange-100 text-orange-700 px-2 py-0.5 rounded text-[9px] font-black uppercase">En Revisión</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ asset('storage/' . $doc->ruta) }}" target="_blank" class="text-gray-800 hover:text-orange-impordiesel transition">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center">
                                        <i class="fas fa-folder-open text-gray-200 text-4xl mb-3"></i>
                                        <p class="text-[10px] font-black text-gray-400 uppercase">Esta sucursal aún no ha cargado documentos.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Botón de Acción Rápida para el Padre --}}
            <div class="mt-6 flex justify-end">
                <form action="{{ route('portal.clientes.sucursales.toggle', $sucursal->id) }}" method="POST" onsubmit="return confirm('¿Está seguro de cambiar el estatus operativo de esta sucursal?')">
                    @csrf
                    <button type="submit" class="px-6 py-3 {{ $sucursal->status == 1 ? 'bg-red-600' : 'bg-green-600' }} text-white rounded-lg font-black text-xs uppercase shadow-lg hover:opacity-90 transition">
                        <i class="fas {{ $sucursal->status == 1 ? 'fa-power-off' : 'fa-play' }} mr-2"></i>
                        {{ $sucursal->status == 1 ? 'Suspender Operaciones de Sucursal' : 'Habilitar Operaciones de Sucursal' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection