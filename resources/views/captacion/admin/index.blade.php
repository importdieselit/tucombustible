@extends('layouts.app')
@section('content')
<div class="container">
    <h2>Solicitudes de Captación</h2>

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
