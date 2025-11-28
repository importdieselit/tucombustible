@extends('layouts.app')
@section('content')
<div class="container">
    <h2>Solicitudes de Captación</h2>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="{{ route('captacion.create') }}" class="btn btn-danger" >
                    <i class="fa fa-plus me-2"></i> Cargar Nueva Solicitud
                </a>
                <a href="{{ url()->previous() }}" class="btn btn-secondary">
                    <i class="fa fa-arrow-left me-2"></i> Volver
                </a>
            </div>
        </div>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th><th>Razón</th><th>Correo</th><th>Estatus</th><th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($list as $c)
                <tr>
                    <td>{{ $c->id }}</td>
                    <td>{{ $c->razon_social }}</td>
                    <td>{{ $c->correo }}</td>
                    <td>{{ $c->estatus_captacion }}</td>
                    <td>
                        <a href="{{ route('captacion.admin.show', $c->id) }}" class="btn btn-sm btn-primary">Ver</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $list->links() }}
</div>
@endsection
