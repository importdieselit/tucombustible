@extends('layouts.app')

@section('title', 'Perfil de Usuario - ' . $item->persona->nombre)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <div class="card card-primary card-outline">
                <div class="card-body box-profile">
                    {{-- <div class="text-center">
                        <img class="profile-user-img img-fluid img-circle"
                             src="{{ $item->avatar ? asset('storage/'.$item->avatar) : asset('img/default-user.png') }}"
                             alt="User profile picture">
                    </div> --}}
                    <h3 class="profile-username text-center">{{ $item->name }}</h3>
                    <p class="text-muted text-center">{{ $item->perfil->nombre ?? 'Sin Rol Asignado' }}</p>

                    <ul class="list-group list-group-unbordered mb-3">
                        <li class="list-group-item">
                            <b>Email</b> <a class="float-right">{{ $item->email }}</a>
                        </li>
                        <li class="list-group-item">
                            <b>Miembro desde</b> <a class="float-right">{{ !is_null($item->created_at) ? $item->created_at->format('d/m/Y') : 'Fecha no disponible' }}</a>
                        </li>
                    </ul>

                    @if(Auth::user()->canAccess('update', 51))
                        <a href="{{ route('usuarios.edit', $item->id) }}" class="btn btn-primary btn-block"><b>Editar Perfil</b></a>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header p-2">
                    <ul class="nav nav-pills">
                        <li class="nav-item"><a class="nav-link active" href="#permisos" data-toggle="tab">Permisos</a></li>
                        <li class="nav-item"><a class="nav-link" href="#actividad" data-toggle="tab">Actividad Reciente</a></li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <div class="active tab-pane" id="permisos">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Módulo</th>
                                        <th>Lectura</th>
                                        <th>Escritura</th>
                                        <th>Eliminación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($modulos as $modulo)
                                        <tr>
                                            <td>{{ $modulo->nombre }}</td>
                                            <td>{!! $item->canAccess('read', $modulo->id) ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>' !!}</td>
                                            <td>{!! $item->canAccess('update', $modulo->id) ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>' !!}</td>
                                            <td>{!! $item->canAccess('delete', $modulo->id) ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>' !!}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="tab-pane" id="actividad">
                            <p class="text-muted">Próximamente: Log de auditoría del sistema.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@endpush