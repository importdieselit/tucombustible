@extends('layouts.app')
@section('title', 'Clientes Lubricantes')

@section('content')
<div class="container mx-auto py-6 px-4">

    {{-- ENCABEZADO --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-gray-800 uppercase tracking-tight">Clientes — Lubricantes</h1>
            <p class="text-gray-500 text-xs font-bold uppercase tracking-widest mt-1">
                Registro de clientes que adquieren lubricantes
            </p>
        </div>
        <a href="{{ route('clientes.index') }}"
           class="bg-gray-100 text-gray-600 px-4 py-2 rounded text-[10px] font-black uppercase hover:bg-gray-200 transition">
            <i class="fas fa-gas-pump mr-1"></i> Ver Clientes Combustible
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- FORMULARIO REGISTRO --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border-t-4 border-orange-impordiesel overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-100">
                    <span class="text-xs font-black text-gray-700 uppercase tracking-widest">
                        <i class="fas fa-plus mr-1 text-orange-impordiesel"></i> Nuevo Cliente
                    </span>
                </div>
                <form action="{{ route('clientes.lubricantes.store') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Razón Social <span class="text-red-500">*</span></label>
                        <input type="text" name="razon_social" value="{{ old('razon_social') }}" required
                               placeholder="EMPRESA C.A."
                               class="w-full text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel focus:ring-1 focus:ring-orange-impordiesel p-3">
                        @error('razon_social') <p class="text-red-500 text-[10px] mt-1 font-bold italic uppercase">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">RIF <span class="text-red-500">*</span></label>
                        <div class="flex gap-2">
                            <select name="rif_tipo" required class="w-1/3 text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel p-3 bg-white uppercase">
                                @foreach(['V', 'E', 'J', 'P', 'G', 'C'] as $letra)
                                    <option value="{{ $letra }}" {{ old('rif_tipo') == $letra ? 'selected' : '' }}>{{ $letra }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="rif_numero" value="{{ old('rif_numero') }}" required
                                placeholder="123456789" maxlength="10"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                class="w-2/3 text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel focus:ring-1 focus:ring-orange-impordiesel p-3">
                        </div>
                        @error('rif') <p class="text-red-500 text-[10px] mt-1 font-bold italic uppercase">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Correo Electrónico <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               placeholder="cliente@ejemplo.com"
                               class="w-full text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel focus:ring-1 focus:ring-orange-impordiesel p-3">
                        @error('email') <p class="text-red-500 text-[10px] mt-1 font-bold italic uppercase">{{ $message }}</p> @enderror
                    </div>

                    @if ($errors->any())
                        <div class="bg-red-50 border border-red-200 rounded p-3">
                            <ul class="text-red-600 text-[10px] font-bold uppercase space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li><i class="fas fa-exclamation-circle mr-1"></i> {{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <button type="submit"
                            class="w-full bg-orange-impordiesel hover:bg-orange-700 text-white font-black py-3 rounded text-xs uppercase tracking-widest shadow-lg border-b-4 border-orange-900 transition">
                        <i class="fas fa-save mr-2"></i> Registrar Cliente
                    </button>
                </form>
            </div>
        </div>

        {{-- LISTADO --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                    <span class="text-xs font-black text-gray-700 uppercase tracking-widest">
                        Listado de Clientes Registrados
                    </span>
                    <span class="bg-gray-industrial text-white text-[10px] px-3 py-1 rounded-full font-black">
                        {{ $clientes->total() }} registros
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-industrial text-white text-xs font-black uppercase tracking-widest">
                                <th class="px-6 py-3">Razón Social / RIF</th>
                                <th class="px-6 py-3">Correo</th>
                                <th class="px-6 py-3 text-center">Fecha</th>
                                <th class="px-6 py-3 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($clientes as $cl)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3">
                                    <div class="font-black text-gray-800 uppercase text-xs">{{ $cl->razon_social }}</div>
                                    <div class="text-[10px] font-bold text-gray-500 mt-0.5">{{ $cl->rif }}</div>
                                </td>
                                <td class="px-6 py-3 text-xs font-bold text-gray-600">{{ $cl->email }}</td>
                                <td class="px-6 py-3 text-center text-xs font-bold text-gray-500">
                                    {{ $cl->created_at?->format('d/m/Y') ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <form action="{{ route('clientes.lubricantes.destroy', $cl->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="bg-red-600 text-white px-3 py-1.5 rounded text-[10px] font-black uppercase hover:bg-red-800 transition border-b-2 border-red-900"
                                                onclick="return confirm('¿Eliminar este cliente lubricante?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-400 font-black uppercase text-xs tracking-widest">
                                    No hay clientes lubricantes registrados aún.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 bg-gray-50 border-t">
                    {{ $clientes->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection