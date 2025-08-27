@extends('layouts.app')

@section('title', 'Almacenes')

@section('page-title', 'Lista de Almacenes')

@section('content')
    <div class="flex justify-end items-center mb-6">
        <a href="{{ route('almacenes.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-full shadow-md transition-transform transform hover:scale-105">
            Crear Nuevo Almacén
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white rounded-xl shadow-md overflow-hidden">
            <thead class="bg-gray-200">
                <tr>
                    <th class="py-3 px-6 text-left text-sm font-semibold text-gray-700">ID</th>
                    <th class="py-3 px-6 text-left text-sm font-semibold text-gray-700">Nombre</th>
                    <th class="py-3 px-6 text-left text-sm font-semibold text-gray-700">Dirección</th>
                    <th class="py-3 px-6 text-sm font-semibold text-gray-700 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="text-gray-600">
                @foreach ($data as $almacen)
                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                        <td class="py-4 px-6 text-sm">{{ $almacen->id }}</td>
                        <td class="py-4 px-6 text-sm">{{ $almacen->nombre }}</td>
                        <td class="py-4 px-6 text-sm">{{ $almacen->direccion }}</td>
                        <td class="py-4 px-6 text-center text-sm">
                            <div class="flex justify-center space-x-2">
                                <a href="{{ route('almacenes.edit', $almacen->id) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white py-1 px-3 rounded-md transition-colors duration-200">
                                    Editar
                                </a>
                                <form action="{{ route('almacenes.destroy', $almacen->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este almacén?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white py-1 px-3 rounded-md transition-colors duration-200">
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection