@extends('layouts.app')
@section('title', 'Nuevo Cliente')

@section('content')
<div class="container mx-auto py-8 px-4">
    <div class="max-w-4xl mx-auto">

        {{-- ENCABEZADO --}}
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-gray-800 uppercase tracking-tight">Crear Nuevo Cliente</h1>
                <p class="text-gray-500 text-xs font-bold uppercase tracking-widest mt-1">
                    Apertura de expediente — Combustible
                </p>
            </div>
            <a href="{{ route('clientes.index') }}"
               class="bg-gray-100 text-gray-600 px-4 py-2 rounded text-[10px] font-black uppercase hover:bg-gray-200 transition">
                <i class="fas fa-list mr-1"></i> Volver al Listado
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-sm border-t-4 border-orange-impordiesel overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-100">
                <span class="text-xs font-black text-gray-700 uppercase tracking-widest">
                    Formulario de Registro de Cliente
                </span>
            </div>

            <form action="{{ route('clientes.store') }}" method="POST" class="p-8">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    {{-- TIPO DE CLIENTE --}}
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Tipo de Cliente <span class="text-red-500">*</span></label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="tipo_cliente" value="padre"
                                       {{ old('tipo_cliente', 'padre') == 'padre' ? 'checked' : '' }}
                                       onchange="toggleTokenPadre(this.value)"
                                       class="text-orange-impordiesel">
                                <span class="text-xs font-black uppercase text-gray-700">Cliente Padre (Sede Principal)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="tipo_cliente" value="sucursal"
                                       {{ old('tipo_cliente') == 'sucursal' ? 'checked' : '' }}
                                       onchange="toggleTokenPadre(this.value)"
                                       class="text-orange-impordiesel">
                                <span class="text-xs font-black uppercase text-gray-700">Sucursal</span>
                            </label>
                        </div>
                    </div>

                    {{-- TOKEN PADRE (solo sucursal) --}}
                    <div class="md:col-span-2" id="campoTokenPadre" style="display: {{ old('tipo_cliente') == 'sucursal' ? 'block' : 'none' }}">
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Token de Empresa Principal <span class="text-red-500">*</span></label>
                        <input type="text" name="token_padre" value="{{ old('token_padre') }}"
                               class="w-full text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel focus:ring-1 focus:ring-orange-impordiesel uppercase p-3"
                               placeholder="TOKEN DEL CLIENTE PADRE">
                        @error('token_padre') <p class="text-red-500 text-[10px] mt-1 font-bold italic uppercase">{{ $message }}</p> @enderror
                    </div>

                    {{-- RAZÓN SOCIAL --}}
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Razón Social <span class="text-red-500">*</span></label>
                        <input type="text" name="nombre" value="{{ old('nombre') }}" required
                               placeholder="EJ: DISTRIBUIDORA GASOLÍN C.A."
                               class="w-full text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel focus:ring-1 focus:ring-orange-impordiesel uppercase p-3">
                        @error('nombre') <p class="text-red-500 text-[10px] mt-1 font-bold italic uppercase">{{ $message }}</p> @enderror
                    </div>

                    {{-- RIF --}}
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

                    {{-- EMAIL --}}
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Correo Electrónico <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               placeholder="cliente@ejemplo.com"
                               class="w-full text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel focus:ring-1 focus:ring-orange-impordiesel p-3">
                        @error('email') <p class="text-red-500 text-[10px] mt-1 font-bold italic uppercase">{{ $message }}</p> @enderror
                    </div>

                    {{-- CONTACTO --}}
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Persona de Contacto <span class="text-red-500">*</span></label>
                        <input type="text" name="contacto" value="{{ old('contacto') }}" required
                               oninput="this.value = this.value.replace(/[^a-zA-Z\s]/g, '')"
                               placeholder="Nombre del responsable"
                               class="w-full text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel focus:ring-1 focus:ring-orange-impordiesel uppercase p-3">
                        @error('contacto') <p class="text-red-500 text-[10px] mt-1 font-bold italic">{{ $message }}</p> @enderror
                    </div>

                    {{-- TELÉFONO --}}
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Teléfono</label>
                        <input type="text" name="telefono" value="{{ old('telefono') }}"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                               placeholder="04XX-XXXXXXX" maxlength="11"
                               class="w-full text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel focus:ring-1 focus:ring-orange-impordiesel p-3">
                    </div>

                    {{-- ESTADO --}}
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Estado <span class="text-red-500">*</span></label>
                        <select name="estado_id" id="estado_id" required
                                onchange="cargarCiudades(this.value)"
                                class="w-full text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel p-3 bg-white uppercase">
                            <option value="">Seleccione un estado...</option>
                            @foreach($estados as $estado)
                                <option value="{{ $estado->id }}" {{ old('estado_id') == $estado->id ? 'selected' : '' }}>
                                    {{ $estado->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('estado_id') <p class="text-red-500 text-[10px] mt-1 font-bold italic uppercase">{{ $message }}</p> @enderror
                    </div>

                    {{-- CIUDAD --}}
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Ciudad <span class="text-red-500">*</span></label>
                        <select name="ciudad_id" id="ciudad_id" required
                                class="w-full text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel p-3 bg-white uppercase">
                            <option value="">Seleccione primero un estado...</option>
                        </select>
                        @error('ciudad_id') <p class="text-red-500 text-[10px] mt-1 font-bold italic uppercase">{{ $message }}</p> @enderror
                    </div>

                    {{-- DIRECCIÓN FISCAL --}}
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Dirección Fiscal</label>
                        <textarea name="direccion" rows="2"
                                  placeholder="Dirección según RIF..."
                                  class="w-full text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel focus:ring-1 focus:ring-orange-impordiesel p-3">{{ old('direccion') }}</textarea>
                    </div>

                    {{-- DIRECCIÓN OPERATIVA --}}
                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-orange-impordiesel uppercase mb-2 font-black">
                            Dirección Operativa (Lugar de Despacho) <span class="text-red-500">*</span>
                        </label>
                        <textarea name="direccion_operativa" rows="2" required
                                  placeholder="Lugar donde se realiza la actividad..."
                                  class="w-full border-orange-200 text-xs font-bold border rounded focus:border-orange-impordiesel focus:ring-1 focus:ring-orange-impordiesel  p-3">{{ old('direccion_operativa') }}</textarea>
                        @error('direccion_operativa') <p class="text-red-500 text-[10px] mt-1 font-bold italic uppercase">{{ $message }}</p> @enderror
                    </div>

                    {{-- TIPO DE COMBUSTIBLE --}}
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Tipo de Combustible <span class="text-red-500">*</span></label>
                        <select name="tipo_combustible_id" required
                                class="w-full text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel p-3 bg-white uppercase">
                            <option value="" disabled selected>Seleccione...</option>
                            @foreach($tiposCombustible as $tipo)
                                <option value="{{ $tipo->id }}" {{ old('tipo_combustible_id') == $tipo->id ? 'selected' : '' }}>
                                    {{ $tipo->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('tipo_combustible_id') <p class="text-red-500 text-[10px] mt-1 font-bold italic uppercase">{{ $message }}</p> @enderror
                    </div>

                    {{-- LITROS SOLICITADOS --}}
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2">Litros Solicitados / Mes <span class="text-red-500">*</span></label>
                        <input type="number" name="litros_solicitados" value="{{ old('litros_solicitados') }}" min="1" required
                               placeholder="Ej: 10000"
                               class="w-full text-xs font-bold border-gray-300 rounded focus:border-orange-impordiesel focus:ring-1 focus:ring-orange-impordiesel p-3">
                        @error('litros_solicitados') <p class="text-red-500 text-[10px] mt-1 font-bold italic uppercase">{{ $message }}</p> @enderror
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
                            class="w-full md:w-auto bg-orange-impordiesel hover:bg-orange-600 text-white font-black py-4 px-12 rounded shadow-lg transition-all uppercase text-xs tracking-widest border-b-4 border-orange-900">
                        <i class="fas fa-save mr-2"></i> Guardar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleTokenPadre(valor) {
        document.getElementById('campoTokenPadre').style.display = valor === 'sucursal' ? 'block' : 'none';
    }

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