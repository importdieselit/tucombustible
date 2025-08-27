@extends('layouts.app')

@section('title', 'Importar Choferes')

@section('content')
<div class="container-fluid mt-4">
    <div class="row page-titles">
        <div class="col-md-6 align-self-center">
            <h3 class="text-themecolor">Importar Choferes</h3>
        </div>
        <div class="col-md-6 align-self-center">
            <div class="d-flex justify-content-end">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('choferes.index') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Importar</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="card-title m-0">Subir Archivo CSV</h5>
        </div>
        <div class="card-body">
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

            <p class="text-muted">Por favor, asegúrate de que tu archivo CSV tenga el siguiente formato de columnas: <code>NOMBRES,APELLIDOS,CEDULA DE IDENTIDAD,CARGO,GRADO DE LICENCIA,VENCIMIENTO,CERTIFICADO MEDICO,VENCIMIENTO,CERTIFICADO DE APROBACION (PDVSA)</code>.</p>
            
            <form action="{{ route('choferes.importar') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="file" class="form-label">Archivo de Choferes (.csv)</label>
                    <input class="form-control" type="file" id="file" name="file" required>
                    @error('file')
                        <div class="text-danger mt-2">{{ $message }}</div>
                    @enderror
                </div>
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-1"></i> Importar
                    </button>
                    <a href="{{ route('choferes.list') }}" class="btn btn-secondary ms-2">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
