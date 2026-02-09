@extends('layouts.app')

@section('title', 'Crear Almacén')

@section('page-title', 'Crear Nuevo Almacén')

@section('content')
    @if ($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-md shadow-sm">
            <p class="font-bold">¡Hubo un error!</p>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>- {{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('almacenes.store') }}" method="POST">
        @csrf
        <div class="mb-4">
            <label for="nombre" class="block text-gray-700 font-semibold mb-2">Nombre del Almacén</label>
            <input type="text" name="nombre" id="nombre" value="{{ old('nombre') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
        </div>
        <div class="mb-6">
            <label for="direccion" class="block text-gray-700 font-semibold mb-2">Dirección</label>
            <input type="text" name="direccion" id="direccion" value="{{ old('direccion') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <div class="flex justify-between items-center">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-full shadow-md transition-transform transform hover:scale-105">
                Guardar
            </button>
            <a href="{{ route('almacenes.index') }}" class="text-gray-500 hover:text-gray-700 font-semibold transition-colors duration-200">
                Cancelar
            </a>
        </div>
    </form>
@endsection