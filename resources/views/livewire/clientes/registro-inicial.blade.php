<div class="p-6 bg-white shadow-md rounded-lg">
    <form wire:submit.prevent="registrar" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Razón Social</label>
            <input wire:model="razon_social" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            @error('razon_social') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Estado</label>
                <select wire:model="estado_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">Seleccione...</option>
                    @foreach($estados as $estado)
                        <option value="{{ $estado->id }}">{{ $estado->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Ciudad</label>
                <select wire:model="ciudad_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" {{ empty($ciudades) ? 'disabled' : '' }}>
                    <option value="">Seleccione...</option>
                    @foreach($ciudades as $ciudad)
                        <option value="{{ $ciudad->id }}">{{ $ciudad->nombre }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition">
            Siguiente Paso
        </button>
    </form>
</div>