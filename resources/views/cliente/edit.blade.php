@extends('layouts.app')

@section('content')
<div class="container mx-auto py-8 px-4">
    <div class="max-w-4xl mx-auto">
        {{-- ENCABEZADO --}}
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-gray-800 uppercase tracking-tight italic">Editar: {{ $cliente->nombre }}</h1>
                <p class="text-gray-500 text-xs font-bold uppercase tracking-widest mt-1">Modificación de expediente técnico</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('clientes.show', $cliente->id) }}" class="bg-gray-100 text-gray-600 px-4 py-2 rounded text-[10px] font-black uppercase hover:bg-gray-200 transition">
                    Cancelar
                </a>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-t-4 border-gray-industrial overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <span class="text-xs font-black text-gray-700 uppercase tracking-widest">Actualizar Datos Generales</span>
                <span class="text-[10px] font-bold text-orange-impordiesel uppercase italic">RIF: {{ $cliente->rif }}</span>
            </div>
            
            <form action="{{ route('clientes.update', $cliente->id) }}" method="POST" class="p-8">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- RAZÓN SOCIAL --}}
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Razón Social / Nombre <span class="text-red-500">*</span></label>
                        <input type="text" name="nombre" value="{{ old('nombre', $cliente->nombre) }}" required
                               class="w-full text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel focus:ring-1 focus:ring-orange-impordiesel uppercase p-3">
                    </div>

                    {{-- RIF --}}
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">RIF / Identificación <span class="text-red-500">*</span></label>
                        <input type="text" name="rif" value="{{ old('rif', $cliente->rif) }}" required
                               class="w-full text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel focus:ring-1 focus:ring-orange-impordiesel p-3">
                    </div>

                    {{-- EMAIL --}}
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Correo Electrónico <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $cliente->email) }}" required
                               class="w-full text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel focus:ring-1 focus:ring-orange-impordiesel p-3">
                    </div>

                    {{-- DIRECCIONES --}}
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Dirección Fiscal</label>
                        <textarea name="direccion" rows="2"
                                  class="w-full text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel focus:ring-1 focus:ring-orange-impordiesel uppercase p-3">{{ old('direccion', $cliente->direccion) }}</textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 text-orange-impordiesel font-black">Dirección Operativa (Obligatorio) <span class="text-red-500">*</span></label>
                        <textarea name="direccion_operativa" rows="2" required
                                  class="w-full border-orange-200 text-xs font-bold border rounded focus:border-orange-impordiesel focus:ring-1 focus:ring-orange-impordiesel uppercase p-3">{{ old('direccion_operativa', $cliente->direccion_operativa) }}</textarea>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button type="submit" class="w-full md:w-auto bg-gray-industrial hover:bg-black text-white font-black py-4 px-12 rounded shadow-lg transition-all uppercase text-xs tracking-widest">
                        <i class="fas fa-sync-alt mr-2"></i> Actualizar Información
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection