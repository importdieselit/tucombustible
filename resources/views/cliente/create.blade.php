@extends('layouts.app')

@section('content')
<div class="container mx-auto py-8 px-4">
    <div class="max-w-4xl mx-auto">
        {{-- ENCABEZADO --}}
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-gray-800 uppercase tracking-tight">Crear Nuevo Cliente</h1>
                <p class="text-gray-500 text-xs font-bold uppercase tracking-widest mt-1">Apertura de expediente técnico en base de datos</p>
            </div>
            <a href="{{ route('clientes.index') }}" class="bg-gray-100 text-gray-600 px-4 py-2 rounded text-[10px] font-black uppercase hover:bg-gray-200 transition">
                <i class="fas fa-list mr-1"></i> Volver al Listado
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-t-4 border-orange-impordiesel overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-100">
                <span class="text-xs font-black text-gray-700 uppercase tracking-widest">Formulario de Registro de Cliente</span>
            </div>
            
            <form action="{{ route('clientes.store') }}" method="POST" class="p-8">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- RAZÓN SOCIAL --}}
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Razón Social / Nombre <span class="text-red-500">*</span></label>
                        <input type="text" name="nombre" value="{{ old('nombre') }}" required placeholder="EJ: DISTRIBUIDORA GASOLÍN C.A."
                               class="w-full text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel focus:ring-1 focus:ring-orange-impordiesel uppercase p-3">
                        @error('nombre') <p class="text-red-500 text-[10px] mt-1 font-bold italic uppercase">{{ $message }}</p> @enderror
                    </div>

                    {{-- RIF --}}
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">RIF <span class="text-red-500">*</span></label>
                        <input type="text" name="rif" value="{{ old('rif') }}" required placeholder="J-12345678-9"
                               class="w-full text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel focus:ring-1 focus:ring-orange-impordiesel p-3">
                        @error('rif') <p class="text-red-500 text-[10px] mt-1 font-bold italic uppercase">{{ $message }}</p> @enderror
                    </div>

                    {{-- EMAIL --}}
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Correo Electrónico <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" required placeholder="cliente@ejemplo.com"
                               class="w-full text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel focus:ring-1 focus:ring-orange-impordiesel p-3">
                        @error('email') <p class="text-red-500 text-[10px] mt-1 font-bold italic uppercase">{{ $message }}</p> @enderror
                    </div>

                    {{-- DIRECCIONES --}}
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Dirección Fiscal <span class="text-red-500">*</span></label>
                        <textarea name="direccion" rows="2" required placeholder="DIRECCIÓN SEGÚN RIF..."
                                  class="w-full text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel focus:ring-1 focus:ring-orange-impordiesel uppercase p-3">{{ old('direccion') }}</textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 text-orange-impordiesel">Dirección Operativa (Lugar de Despacho) <span class="text-red-500">*</span></label>
                        <textarea name="direccion_operativa" rows="2" required placeholder="LUGAR DONDE SE REALIZA LA ACTIVIDAD..."
                                  class="w-full border-orange-200 text-xs font-bold border rounded focus:border-orange-impordiesel focus:ring-1 focus:ring-orange-impordiesel uppercase p-3">{{ old('direccion_operativa') }}</textarea>
                    </div>
                </div>

                <div class="mt-8 flex justify-end">
                    <button type="submit" class="w-full md:w-auto bg-orange-impordiesel hover:bg-orange-600 text-white font-black py-4 px-12 rounded shadow-lg transition-all uppercase text-xs tracking-widest">
                        <i class="fas fa-save mr-2"></i> Guardar Nuevo Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection