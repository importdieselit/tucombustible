@extends('layouts.app')

@section('title', 'Importar Usuarios y Clientes')

@section('content')
<div class="container-fluid mt-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="mb-2">Importar Usuarios y Clientes</h1>
            <p class="text-muted">Utiliza este formulario para subir el archivo CSV con la lista de usuarios autorizados. </p>
        </div>
    </div>

    @if(Session::has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ Session::get('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(Session::has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ Session::get('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="card-title m-0">Seleccionar Archivo de Importación</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('usuarios.importarprocess') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="file" class="form-label">Archivo CSV</label>
                    <input class="form-control" type="file" id="file" name="file" required>
                    <small class="form-text text-muted">Asegúrate de que el archivo sea del tipo .CSV y contenga las columnas correctas.</small>
                </div>
                <button type="submit" class="btn btn-primary mt-3">
                    <i class="fas fa-upload"></i> Subir y Procesar
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
