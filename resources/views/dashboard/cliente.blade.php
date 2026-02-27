@extends('layouts.app')

@section('title', 'Mi Panel de Combustible')

@section('content')
<div class="container mx-auto">
    <div class="bg-gradient-to-r from-blue-700 to-blue-900 p-8 rounded-2xl text-white mb-8 shadow-lg">
        <h1 class="text-3xl font-bold">Bienvenido, {{ $cliente->nombre }}</h1>
        <p class="opacity-80 mt-2 text-lg">Tu cupo de combustible está activo y listo para usar.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <p class="text-sm text-gray-500 font-bold uppercase">Cupo Mensual</p>
            <h3 class="text-3xl font-black text-gray-800">{{ number_format($cliente->cupo, 0, ',', '.') }} Lts</h3>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <p class="text-sm text-gray-500 font-bold uppercase">Consumo del Mes</p>
            <h3 class="text-3xl font-black text-blue-600">0 Lts</h3>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 font-bold uppercase">Estatus Cuenta</p>
                <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold uppercase">Activa</span>
            </div>
            <i class="fas fa-check-circle text-4xl text-green-500 opacity-20"></i>
        </div>
    </div>
</div>
@endsection