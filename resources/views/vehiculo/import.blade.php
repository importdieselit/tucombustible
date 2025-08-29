@extends('layouts.app')

@section('title', 'Importar Vehículos')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1 class="mb-2">Importar Vehículos desde Excel</h1>
        <p class="text-muted">Carga un archivo .xlsx, .xls o .csv para insertar múltiples registros de vehículos de forma masiva.</p>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h5 class="card-title m-0">Seleccionar Archivo</h5>
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

        <form action="{{ route('vehiculos.import.save') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="file" class="form-label">Archivo de Vehículos</label>
                <input type="file" class="form-control" id="file" name="file" required>
            </div>
            <button type="submit" class="btn btn-primary d-flex align-items-center">
                <i class="bi bi-cloud-upload me-1"></i>
                Cargar e Importar
            </button>
        </form>
    </div>
</div>
@endsection
