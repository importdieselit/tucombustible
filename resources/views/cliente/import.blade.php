@extends('layouts.app')

@section('title', 'Importar Clientes')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card mt-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Importar Clientes desde CSV</h5>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif
                    
                    <form action="{{ route('clientes.handleImport') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="file" class="form-label">Selecciona el archivo CSV</label>
                            <input class="form-control" type="file" id="file" name="file" required>
                            <div class="form-text">
                                Formato: .csv o .txt. Tamaño máximo: 2MB.
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload"></i> Subir e Importar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
