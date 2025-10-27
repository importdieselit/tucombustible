@extends('layouts.app')

@push('styles')
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .container-fluid {
            padding-left: 1rem;
            padding-right: 1rem;
        }
        @media (min-width: 640px) {
            .container-fluid {
                padding-left: 2rem;
                padding-right: 2rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="bg-slate-900 text-slate-100 font-sans p-4 min-h-screen">
        <div class="container mx-auto max-w-7xl">
            <h1 class="text-3xl font-bold mb-6 text-center text-slate-200">Listado de Movimientos de Combustible</h1>
            
            <div class="bg-slate-800 p-6 rounded-lg shadow-lg">
                <div class="overflow-x-auto rounded-lg">
                    <table class="min-w-full divide-y divide-slate-700">
                        <thead class="bg-slate-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">
                                    Fecha y Hora
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">
                                    Tipo
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">
                                    Depósito
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">
                                    Cantidad (L)
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">
                                    Detalle
                                </th>
                            </tr>
                        </thead>
                        <tbody id="movimientos-listado" class="bg-slate-800 divide-y divide-slate-700">
                            {{-- Bucle para iterar sobre los movimientos. La variable $movimientos debe ser pasada desde el controlador. --}}
                            @if(isset($movimientos) && count($movimientos) > 0)
                                @foreach ($movimientos as $movimiento)
                                <tr class="hover:bg-slate-700 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-slate-200">{{ \Carbon\Carbon::parse($movimiento->created_at)->format('d/m/Y H:i') }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{-- Muestra una etiqueta de tipo de movimiento --}}
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            @if($movimiento->tipo == 'entrada') bg-green-500 text-white
                                            @elseif($movimiento->tipo == 'salida') bg-red-500 text-white
                                            @else bg-gray-500 text-white
                                            @endif">
                                            {{ ucfirst($movimiento->tipo_movimientos) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-slate-300">{{ $movimiento->deposito->serial }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-slate-300">{{ $movimiento->cantidad_litros }} L</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-300">
                                        {{-- Lógica condicional para mostrar los detalles del movimiento --}}
                
                                        @if($movimiento->tipo_movimiento == 'salida')
                                            @if($movimiento->cliente)
                                                <p>Cliente: {{ $movimiento->cliente->nombre }}</p>
                                            @endif
                                            @if($movimiento->cisterna)
                                                {{dd($movimiento->cisterna)}}
                                                <p>Cisterna: {{ $movimiento->cisterna()->flota }}</p>
                                            @endif
                                            @if($movimiento->vehiculo)
                                                <p>Vehículo: {{ $movimiento->vehiculo->placa }}</p>
                                            @endif
                                        @elseif($movimiento->tipo_movimiento == 'entrada')
                                            @if($movimiento->proveedor)
                                                <p>Proveedor: {{ $movimiento->proveedor->nombre }}</p>
                                            @endif
                                            {{-- @if($movimiento->responsable)
                                                <p>Responsable: {{ $movimiento->responsable->nombre }}</p>
                                            @endif --}}
                                        @else
                                            <p>No se encontraron detalles.</p>
                                        @endif
                                        {{ $movimiento->observaciones }}
                                    </td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-slate-400">
                                        No hay movimientos para mostrar.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
