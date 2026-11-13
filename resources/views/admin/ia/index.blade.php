@extends('layouts.app') <!-- Ajusta a tu layout principal -->

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6 text-gray-800">Panel de Control: C.O.R.E.</h1>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- BLOQUE 1: CONFIGURACIÓN COGNITIVA -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4 border-b pb-2">Ajustes del Cerebro</h2>
            <form action="{{ route('admin.ia.settings.update') }}" method="POST">
                @csrf
                @foreach($settings as $setting)
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ strtoupper(str_replace('_', ' ', $setting->key)) }}
                            <span class="text-xs text-gray-400 block">{{ $setting->description }}</span>
                        </label>
                        
                        @if($setting->key == 'system_prompt')
                            <textarea name="settings[{{ $setting->key }}]" rows="6" 
                                      class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ $setting->value }}</textarea>
                        @else
                            <input type="text" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}" 
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        @endif
                    </div>
                @endforeach
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700">Guardar Cambios</button>
            </form>
        </div>

        <!-- BLOQUE 2: CATÁLOGO DE HERRAMIENTAS -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4 border-b pb-2">Herramientas Conectadas</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="py-2 px-4 text-left text-xs font-semibold text-gray-600 uppercase">Nombre</th>
                            <th class="py-2 px-4 text-left text-xs font-semibold text-gray-600 uppercase">Estado</th>
                            <th class="py-2 px-4 text-center text-xs font-semibold text-gray-600 uppercase">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tools as $tool)
                        <tr class="border-b">
                            <td class="py-3 px-4">
                                <span class="font-medium text-gray-800">{{ $tool->name }}</span>
                                <span class="block text-xs text-gray-500" title="{{ $tool->api_endpoint }}">{{ $tool->http_method }}</span>
                            </td>
                            <td class="py-3 px-4">
                                @if($tool->is_active)
                                    <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">Activa</span>
                                @else
                                    <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">Inactiva</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                <form action="{{ route('admin.ia.tools.toggle', $tool->id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="text-sm px-3 py-1 rounded border {{ $tool->is_active ? 'border-red-500 text-red-500 hover:bg-red-50' : 'border-green-500 text-green-500 hover:bg-green-50' }}">
                                        {{ $tool->is_active ? 'Apagar' : 'Encender' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection