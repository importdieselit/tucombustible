@extends('layouts.app') {{-- O el nombre de tu layout principal --}}

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-0 text-gray-800">Panel de Control Principal</h1>
            <p>Bienvenido al sistema de gestión operativa de ImporDiesel.</p>
        </div>
    </div>

    {{-- FILA 1: Estadísticas de Gestión de Clientes (Acceso al Módulo) --}}
    <div class="row mb-4">
        <div class="col-md-6 col-xl-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Clientes en Registro (Pasos 1-9)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['clientes_en_registro'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users-cog fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    <hr>
                    <a href="{{ route('clientes.index') }}" class="btn btn-sm btn-primary btn-block">
                        Gestionar Módulo Clientes
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Clientes Activos (Paso 10)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['clientes_activos'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FILA 2: Resumen Operativo de Otros Módulos --}}
    <div class="row">
        {{-- Aquí irían los widgets de Vehículos, Tanques, Órdenes, etc. --}}
        <div class="col-xl-3 col-md-6 mb-4">
            {{-- Widget de Vehículos --}}
            <div class="card bg-light shadow h-100">
                <div class="card-body">
                    <div class="text-muted small">Total Vehículos (Flota Propia)</div>
                    <div class="h4 font-weight-bold">{{ $stats['totalVehiculos'] }}</div>
                </div>
            </div>
        </div>
        {{-- ... Resto de widgets usando los datos del Service --}}
    </div>
</div>
@endsection