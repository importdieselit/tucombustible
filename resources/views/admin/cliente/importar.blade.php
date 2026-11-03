@extends('layouts.app') {{-- O el layout maestro que utilices en tu panel --}}

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Carga Masiva de Clientes / Agencias</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Importar Clientes</li>
    </ol>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>¡Éxito!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>¡Atención!</strong> Hay errores en el proceso:
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('errores_importacion') && count(session('errores_importacion')) > 0)
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Observaciones durante la importación:</strong>
            <ul class="mb-0 mt-2" style="max-height: 150px; overflow-y: auto;">
                @foreach (session('errores_importacion') as $errFila)
                    <li>{{ $errFila }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-file-excel me-1"></i>
            Seleccionar archivo Excel con el Directorio de Agencias
        </div>
        <div class="card-body">
            <form action="{{ route('clientes.import.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="archivo_excel" class="form-label">Archivo Excel (.xlsx, .xls, .csv)</label>
                    <input class="form-control" type="file" id="archivo_excel" name="archivo_excel" accept=".xlsx, .xls, .csv" required>
                    <div class="form-text">El sistema buscará automáticamente la hoja "Directorio" y procesará la data aplicando las validaciones correspondientes.</div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-1"></i> Procesar e Importar Clientes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection