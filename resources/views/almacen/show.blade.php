@extends('layouts.app')

@section('title', 'Detalles del Almacén')

@section('page-title', 'Detalles del Almacén')

@section('content')
    <div class="space-y-4">
        <div class="border-b border-gray-200 pb-2">
            <p class="text-gray-500 text-sm">ID:</p>
            <p class="text-gray-800 text-lg font-semibold">{{ $almacen->id }}</p>
        </div>
        <div class="border-b border-gray-200 pb-2">
            <p class="text-gray-500 text-sm">Nombre:</p>
            <p class="text-gray-800 text-lg font-semibold">{{ $almacen->nombre }}</p>
        </div>
        <div class="border-b border-gray-200 pb-2">
            <p class="text-gray-500 text-sm">Dirección:</p>
            <p class="text-gray-800 text-lg font-semibold">{{ $almacen->direccion ?? 'N/A' }}</p>
        </div>
        <div class="flex justify-between items-center pt-4">
            <a href="{{ route('almacenes.index') }}" class="text-blue-600 hover:text-blue-800 font-semibold transition-colors duration-200">
                &larr; Volver a la Lista
            </a>
            <a href="{{ route('almacenes.edit', $almacen->id) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-2 px-4 rounded-full shadow-md transition-transform transform hover:scale-105">
                Editar
            </a>
        </div>
    </div>
@endsection
