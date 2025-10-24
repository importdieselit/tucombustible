@extends('layouts.app')

@section('title', 'Listado de Inspecciones')

@section('content')
<div class="container-fluid">
    <div class="row page-titles mb-4">
        <div class="col-12">
            <h3 class="text-themecolor mb-0">Registro de Inspecciones de Vehículos</h3>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
                <li class="breadcrumb-item active">Inspecciones</li>
            </ol>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th>ID</th>
                            <th>Placa</th>
                            <th>Vehículo</th>
                            <th>Estatus General</th>
                            <th>Inspeccionado Por</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($inspecciones as $inspeccion)
                            <tr>
                                <td>{{ $inspeccion->id }}</td>
                                <td>
                                    <span class="badge bg-info text-dark">{{ $inspeccion->vehiculo->placa ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    {{$inspeccion->vehiculo->flota}} {{ $inspeccion->vehiculo->marca()->marca ?? 'Desconocida' }} {{ $inspeccion->vehiculo->modelo()->modelo ?? '' }}
                                </td>
                                <td>
                                    @php
                                        // Usamos los colores definidos en el controlador
                                        $color = $estatusColores[$inspeccion->estatus_general] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $color }}">{{ $inspeccion->estatus_general }}</span>
                                </td>
                                <td>
                                    
                                    {{ dd($inspeccion->getResponsableInspeccionAttribute())}}  ?? 'Sistema' 
                                </td>
                                <td>
                                    {{ $inspeccion->created_at->format('d/m/Y H:i A') }}
                                </td>
                                <td>
                                    <a href="{{ route('inspeccion.show', $inspeccion->id) }}" class="btn btn-sm btn-outline-info" title="Revisar Detalles">
                                        <i class="fa-solid fa-search"></i>
                                    </a>
                                    <a href="{{ route('inspeccion.pdf', $inspeccion->id) }}" class="btn btn-sm btn-outline-danger" target="_blank" title="Imprimir PDF">
                                        <i class="fa-solid fa-file-pdf"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No se encontraron inspecciones registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{-- Enlaces de paginación --}}
            <div class="d-flex justify-content-center mt-3">
                {{ $inspecciones->links() }}
            </div>
        </div>
    </div>
</div>
@endsection