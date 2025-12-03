@extends('layouts.app')

@section('title', 'Dashboard de Captación')

@section('content')
<div class="container-fluid py-4">

    {{-- ======== ENCABEZADO ======== --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">📊 Panel de Captación de Clientes</h3>
        <a href="{{ route('captacion.create') }}" class="btn btn-primary">
            <i class="ri-user-add-line"></i> Nuevo Cliente
        </a>
    </div>

    {{-- ======== TARJETAS RESUMEN ======== --}}
    <div class="row">

        @php
            $cards = [
                'nuevo' => ['title' => 'Nuevos', 'color' => 'primary', 'icon' => 'ri-user-star-line'],
                'solicitud' => ['title' => 'Solicitud Recibida', 'color' => 'info', 'icon' => 'ri-mail-open-line'],
                'migracion' => ['title' => 'Migración', 'color' => 'warning', 'icon' => 'ri-refresh-line'],
                'espera' => ['title' => 'En Espera', 'color' => 'secondary', 'icon' => 'ri-time-line'],
                'planillas_enviadas' => ['title' => 'Planillas Enviadas', 'color' => 'success', 'icon' => 'ri-file-list-3-line'],
                'falta_documentacion' => ['title' => 'Esperando Documentos', 'color' => 'danger', 'icon' => 'ri-folder-warning-line'],
                'esperando_inspeccion' => ['title' => 'Esperando Inspección', 'color' => 'dark', 'icon' => 'ri-search-eye-line'],
                'aprobado' => ['title' => 'Aprobados', 'color' => 'success', 'icon' => 'ri-checkbox-circle-line'],
            ];
        @endphp

        @foreach($cards as $status => $info)
            <div class="col-md-3 mb-4">
                <div class="card border-{{ $info['color'] }} shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase text-{{ $info['color'] }}">{{ $info['title'] }}</h6>
                            <h2 class="fw-bold">{{ $estadisticas[$status] ?? 0 }}</h2>
                        </div>
                        <i class="{{ $info['icon'] }} text-{{ $info['color'] }}" style="font-size: 40px;"></i>
                    </div>
                </div>
            </div>
        @endforeach

    </div>

    {{-- ======== FILTRO GENERAL ======== --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">

            <form method="GET" action="{{ route('captacion.admin.index') }}" class="row g-3">
                
                <div class="col-md-4">
                    <label class="form-label">Buscar por nombre o RIF</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Estatus</label>
                    <select name="estatus_captacion" class="form-select">
                        <option value="">Todos</option>
                        @foreach($cards as $key => $val)
                            <option value="{{ $key }}" {{ request('estatus_captacion') == $key ? 'selected' : '' }}>
                                {{ $val['title'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Fecha</label>
                    <input type="date" name="fecha" class="form-control" value="{{ request('fecha') }}">
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-dark w-100">
                        <i class="ri-filter-2-line"></i> Filtrar
                    </button>
                </div>

            </form>

        </div>
    </div>

    {{-- ======== TABLA DE CLIENTES ======== --}}
    <div class="card shadow-sm">
        <div class="card-body table-responsive">

            <table class="table table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Nombre / Empresa</th>
                        <th>RIF</th>
                        <th>Correo</th>
                        <th>Teléfono</th>
                        <th>Estatus</th>
                        <th>Fecha</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clientes as $cliente)
                        <tr>
                            <td>{{ $cliente->id }}</td>
                            <td>{{ $cliente->razon_social }}</td>
                            <td>{{ $cliente->rif }}</td>
                            <td>{{ $cliente->correo }}</td>
                            <td>{{ $cliente->telefono }}</td>

                            <td>
                                <span class="badge bg-{{ $cards[$cliente->estatus_captacion]['color'] ?? 'secondary' }}">
                                    {{ $cards[$cliente->estatus_captacion]['title'] ?? 'Sin definir' }}
                                </span>
                            </td>

                            <td>{{ $cliente->created_at->format('d/m/Y') }}</td>

                            <td class="text-end">
                                <a href="{{ route('captacion.admin.show', $cliente) }}" class="btn btn-sm btn-primary">
                                    <i class="ri-eye-line"></i>
                                </a>

                                <a href="{{ route('captacion.edit', $cliente) }}" class="btn btn-sm btn-warning">
                                    <i class="ri-pencil-line"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="ri-file-search-line" style="font-size: 40px;"></i>
                                <p class="mt-2">No se encontraron registros</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-3">
                {{ $clientes->links() }}
            </div>

        </div>
    </div>

</div>
@endsection
