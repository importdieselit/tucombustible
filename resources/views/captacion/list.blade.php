@extends('layouts.app')

@section('title', 'Captaciones - Listado de Solicitudes')

@section('content')
<div class="container-fluid">
    <div class="row page-titles mb-4">
        <div class="col-12">
            <h3 class="text-themecolor mb-0">Solicitudes de Clientes</h3>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Inicio</a></li>
                <li class="breadcrumb-item active">Captacion</li>
            </ol>
            <div>
                <a href="{{ route('captacion.create') }}" class="btn btn-danger" >
                    <i class="fa fa-plus me-2"></i> Cargar Nueva Solicitud
                </a>
                <a href="{{ url()->previous() }}" class="btn btn-secondary">
                    <i class="fa fa-arrow-left me-2"></i> Volver
                </a>
            </div>
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
                        <a href="{{ route('captacion.show', $c->id) }}" class="btn btn-sm btn-primary">Ver</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>


            </div>
            
            {{-- Enlaces de paginación --}}
            <div class="d-flex justify-content-center mt-3">
                {{ $list->links() }}
            </div>
        </div>
    </div>
</div>
@endsection