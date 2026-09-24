@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestor de Parámetros (SuperAdmin)</h2>
        <a href="{{ route('parametros.manager.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Nuevo Parámetro
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Grupo</th>
                        <th>Clave</th>
                        <th>Etiqueta UI</th>
                        <th>Tipo (UI Type)</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($parametros as $param)
                        @php $schema = is_array($param->valor) ? ($param->valor['schema'] ?? []) : []; @endphp
                        <tr>
                            <td><span class="badge bg-secondary">{{ $param->grupo }}</span></td>
                            <td class="fw-bold">{{ $param->clave }}</td>
                            <td>{{ $schema['label'] ?? 'Sin Etiqueta' }}</td>
                            <td>
                                <span class="badge bg-info text-dark">{{ $schema['ui_type'] ?? 'Desconocido' }}</span>
                                @if(isset($schema['source']))
                                    <small class="d-block text-muted">Tabla: {{ $schema['source']['table'] }}</small>
                                @endif
                            </td>
                            <td>
                                <form action="{{ route('parametros.manager.destroy', $param) }}" method="POST" onsubmit="return confirm('¿Eliminar parámetro?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection