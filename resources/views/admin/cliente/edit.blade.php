@extends('layouts.app')
@section('title', 'Editar Cliente')

@section('content')
<div class="container mx-auto py-8 px-4">
    <div class="max-w-4xl mx-auto">

        {{-- ENCABEZADO --}}
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-gray-800 uppercase tracking-tight italic">
                    Editar: {{ $cliente->nombre }}
                </h1>
                <p class="text-gray-500 text-xs font-bold uppercase tracking-widest mt-1">
                    Modificación de datos del expediente
                </p>
            </div>
            <a href="{{ route('clientes.show', $cliente->id) }}"
               class="bg-gray-100 text-gray-600 px-4 py-2 rounded text-[10px] font-black uppercase hover:bg-gray-200 transition">
                Cancelar
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-t-4 border-gray-industrial overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <span class="text-xs font-black text-gray-700 uppercase tracking-widest">Actualizar Datos Generales</span>
                <span class="text-[10px] font-bold text-orange-impordiesel uppercase italic">RIF actual: {{ $cliente->rif }}</span>
            </div>

            <form action="{{ route('clientes.update', $cliente->id) }}" method="POST" class="p-8">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    {{-- RAZÓN SOCIAL --}}
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Razón Social <span class="text-red-500">*</span></label>
                        <input type="text" name="nombre" value="{{ old('nombre', $cliente->nombre) }}" required
                               class="w-full text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel focus:ring-1 focus:ring-orange-impordiesel uppercase p-3">
                        @error('nombre') <p class="text-red-500 text-[10px] mt-1 font-bold italic uppercase">{{ $message }}</p> @enderror
                    </div>

                    {{-- RIF SEPARADO --}}
                    @php
                        // Separar el RIF almacenado (ej: J-12345678) para los inputs
                        $rifPartes = explode('-', $cliente->rif);
                        $letraBase = $rifPartes[0] ?? 'V';
                        $numeroBase = $rifPartes[1] ?? '';
                    @endphp
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">RIF <span class="text-red-500">*</span></label>
                        <div class="flex gap-2">
                            <select name="rif_tipo" required class="w-1/3 text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel p-3 bg-white uppercase">
                                @foreach(['V', 'E', 'J', 'P', 'G', 'C'] as $letra)
                                    <option value="{{ $letra }}" {{ old('rif_tipo', $letraBase) == $letra ? 'selected' : '' }}>{{ $letra }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="rif_numero" value="{{ old('rif_numero', $numeroBase) }}" required
                                placeholder="12345678" maxlength="10"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                class="w-2/3 text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel focus:ring-1 focus:ring-orange-impordiesel p-3">
                        </div>
                        @error('rif') <p class="text-red-500 text-[10px] mt-1 font-bold italic uppercase">{{ $message }}</p> @enderror
                    </div>

                    {{-- EMAIL (Validación de @ integrada) --}}
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Correo Electrónico <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $cliente->email) }}" required
                               placeholder="cliente@ejemplo.com"
                               class="w-full text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel focus:ring-1 focus:ring-orange-impordiesel p-3">
                        @error('email') <p class="text-red-500 text-[10px] mt-1 font-bold italic uppercase">{{ $message }}</p> @enderror
                    </div>

                    {{-- CONTACTO (Solo letras y espacios) --}}
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Persona de Contacto <span class="text-red-500">*</span></label>
                        <input type="text" name="contacto" value="{{ old('contacto', $cliente->contacto) }}" required
                               oninput="this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '')"
                               placeholder="Nombre del responsable"
                               class="w-full text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel focus:ring-1 focus:ring-orange-impordiesel uppercase p-3">
                        @error('contacto') <p class="text-red-500 text-[10px] mt-1 font-bold italic uppercase">{{ $message }}</p> @enderror
                    </div>

                    {{-- TELÉFONO (Solo números) --}}
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Teléfono</label>
                        <input type="text" name="telefono" value="{{ old('telefono', $cliente->telefono) }}"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                               placeholder="04XXXXXXXXX" maxlength="11"
                               class="w-full text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel focus:ring-1 focus:ring-orange-impordiesel p-3">
                        @error('telefono') <p class="text-red-500 text-[10px] mt-1 font-bold italic uppercase">{{ $message }}</p> @enderror
                    </div>

                    {{-- ESTADO --}}
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Estado</label>
                        <select name="estado_id" id="estado_id" onchange="cargarCiudades(this.value)"
                                class="w-full text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel p-3 bg-white uppercase">
                            <option value="">Seleccione...</option>
                            @foreach($estados as $estado)
                                <option value="{{ $estado->id }}"
                                    {{ old('estado_id', $cliente->estado_id) == $estado->id ? 'selected' : '' }}>
                                    {{ $estado->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- CIUDAD --}}
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Ciudad</label>
                        <select name="ciudad_id" id="ciudad_id"
                                class="w-full text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel p-3 bg-white uppercase">
                            @if($cliente->ciudad)
                                <option value="{{ $cliente->ciudad_id }}" selected>{{ $cliente->ciudad->nombre }}</option>
                            @else
                                <option value="">Seleccione primero un estado...</option>
                            @endif
                        </select>
                    </div>

                    {{-- DIRECCIÓN FISCAL --}}
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Dirección Fiscal</label>
                        <textarea name="direccion" rows="2"
                                  class="w-full text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel focus:ring-1 focus:ring-orange-impordiesel uppercase p-3">{{ old('direccion', $cliente->direccion) }}</textarea>
                    </div>

                    {{-- DIRECCIÓN OPERATIVA --}}
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-orange-impordiesel uppercase mb-2 font-black">
                            Dirección Operativa <span class="text-red-500">*</span>
                        </label>
                        <textarea name="direccion_operativa" rows="2" required
                                  class="w-full border-orange-200 text-xs font-bold border rounded focus:border-orange-impordiesel focus:ring-1 focus:ring-orange-impordiesel uppercase p-3">{{ old('direccion_operativa', $cliente->direccion_operativa) }}</textarea>
                        @error('direccion_operativa') <p class="text-red-500 text-[10px] mt-1 font-bold italic uppercase">{{ $message }}</p> @enderror
                    </div>

                </div>

                @if ($errors->any())
                    <div class="mt-6 bg-red-50 border border-red-200 rounded p-4">
                        <ul class="text-red-600 text-xs font-bold uppercase space-y-1">
                            @foreach ($errors->all() as $error)
                                <li><i class="fas fa-exclamation-circle mr-1"></i> {{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mt-8 flex justify-end">
                    <button type="submit"
                            class="w-full md:w-auto bg-gray-industrial hover:bg-black text-white font-black py-4 px-12 rounded shadow-lg transition-all uppercase text-xs tracking-widest border-b-4 border-black">
                        <i class="fas fa-sync-alt mr-2"></i> Actualizar Información
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function cargarCiudades(estadoId) {
        const select = document.getElementById('ciudad_id');
        select.innerHTML = '<option value="">Cargando...</option>';

        if (!estadoId) {
            select.innerHTML = '<option value="">Seleccione primero un estado...</option>';
            return;
        }

        fetch(`{{ route('ciudades.get',${estadoId}) }}`)
            .then(r => r.json())
            .then(ciudades => {
                select.innerHTML = '<option value="">Seleccione una ciudad...</option>';
                ciudades.forEach(c => {
                    select.innerHTML += `<option value="${c.id}">${c.nombre}</option>`;
                });
            })
            .catch(() => {
                select.innerHTML = '<option value="">Error al cargar ciudades</option>';
            });
    }
</script>
@endsection