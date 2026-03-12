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

       {{-- Contenedor de Tarjetas de Acceso --}}
    <div class="row g-4 mb-4 justify-content-center">
        @foreach($cards as $card)
            {{-- Verificamos si el módulo es nulo (libre acceso) o si el usuario tiene el permiso --}}
            @if(is_null($card['modulo']) || Auth::user()->canAccess($card['permiso'], $card['modulo']))
                <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                    @include('partials.access_card', [
                        'route'      => $card['route'],
                        'icon'       => $card['icon'],
                        'title'      => $card['title'],
                        'color'      => $card['color'],
                        'bg_opacity' => 'rgba(0, 123, 255, 0.15)',
                        'target'     => $card['target'] ?? '_self'
                    ])
                </div>
            @endif
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-lg-6" style="display:none">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Órdenes por Estatus</h5>
                </div>
                <div class="card-body">
                    <canvas id="ordenesEstadoChart" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Alertas recientes</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        {{-- Aquí iría la lógica para mostrar alertas dinámicas --}}
                          @foreach ($alertasRecientes as $alerta)
                        <li class="list-group-item d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                            {{ $alerta->observacion }}
                        </li>
                        @endforeach
                        @if($alertasRecientes->isEmpty())
                        <li class="list-group-item">No hay alertas recientes.</li>
                        @endif
                        
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="row g-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Órdenes recientes</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Vehículo</th>
                                <th>Responsable</th>
                                <th>Estado</th>
                                <th>Fecha</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ordenesRecientes as $orden)
                                <tr>
                                    <td>{{ $orden->nro_orden }}</td>
                                    <td>{{ $orden->vehiculo()->placa ?? 'N/A' }}</td>
                                    <td>{{ $orden->responsable ?? 'N/A' }}</td>
                                    <td>
                                        @if ($orden->estatus == 3)
                                            <span class="badge bg-success">Completada</span>
                                        @elseif ($orden->estatus == 2)
                                            <span class="badge bg-warning text-dark">Pendiente</span>
                                        @elseif ($orden->estatus == 1)
                                            <span class="badge bg-danger">En Proceso</span>
                                        @else
                                            <span class="badge bg-secondary">Desconocido</span>
                                        @endif
                                    </td>
                                    <td> @php
                                        // Verifica si la variable existe y si no está vacía
                                        if (isset($orden->created_at) && !empty($orden->created_at)) {
                                            // Convierte la cadena de fecha a una marca de tiempo y luego la formatea
                                            $fecha_formateada = date('Y-m-d', strtotime($orden->created_at));
                                            echo $fecha_formateada;
                                        } else {
                                            // Si la fecha no existe, muestra 'N/A' o algún otro valor por defecto
                                            echo 'N/A';
                                        }
                                    @endphp
                                    </td>
                                    <td><a href="{{ route('ordenes.show', $orden->id) }}" class="btn btn-sm btn-outline-primary">Ver</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
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