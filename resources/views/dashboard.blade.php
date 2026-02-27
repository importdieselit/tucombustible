@extends('layouts.app')

@section('title', 'Panel de Control Administrativo')

@section('content')
<div class="container mx-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Panel de Control</h1>
        <p class="text-gray-500 text-sm">Bienvenido, {{ Auth::user()->name }}. Resumen operativo del sistema.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
            <div class="p-3 rounded-lg bg-blue-50 text-blue-600 mr-4">
                <i class="fas fa-truck text-2xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Vehículos</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $stats['unidades_disponibles'] }}</h3>
                <p class="text-xs text-green-500 font-semibold">Operativos</p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
            <div class="p-3 rounded-lg bg-yellow-50 text-yellow-600 mr-4">
                <i class="fas fa-clipboard-list text-2xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Mantenimiento</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $stats['totalOrdenesAbiertas'] }}</h3>
                <p class="text-xs text-yellow-600 font-semibold">Órdenes activas</p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
            <div class="p-3 rounded-lg bg-green-50 text-green-600 mr-4">
                <i class="fas fa-boxes text-2xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Suministros</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $stats['suministros_compra'] }}</h3>
                <p class="text-xs text-gray-400 font-semibold">Pendientes de compra</p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center">
            <div class="p-3 rounded-lg bg-indigo-50 text-indigo-600 mr-4">
                <i class="fas fa-chart-line text-2xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Estatus Hoy</p>
                <h3 class="text-2xl font-bold text-gray-800">{{ $stats['programadosHoy'] }}</h3>
                <p class="text-xs text-indigo-500 font-semibold">Prog. Mantenimiento</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 h-80 flex flex-col items-center justify-center text-gray-400">
            <i class="fas fa-chart-pie text-4xl mb-4 opacity-20"></i>
            <p>Área reservada para Gráficos de Operación</p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 h-80 flex flex-col items-center justify-center text-gray-400">
            <i class="fas fa-chart-bar text-4xl mb-4 opacity-20"></i>
            <p>Área reservada para KPIs Financieros (Profit)</p>
        </div>
    </div>
</div>
@endsection