@extends('layouts.app')
@section('content')
<div class="container">
    <h2>Solicitud de Cupo - Registro</h2>

    <form method="POST" action="{{ route('captacion.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label>Razón social</label>
            <input type="text" name="razon_social" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>RIF</label>
            <input type="text" name="rif" class="form-control">
        </div>

        <div class="mb-3">
            <label>Correo</label>
            <input type="email" name="correo" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Teléfono</label>
            <input type="text" name="telefono" class="form-control">
        </div>

        <div class="mb-3">
            <label>Dirección</label>
            <textarea name="direccion" class="form-control"></textarea>
        </div>

        <div class="mb-3">
            <label>Documentos (PDF / JPG) — ANEXOS</label>
            <input type="file" name="documentos[]" multiple class="form-control">
            <small class="text-muted">Subir RIF, documentos de propiedad, etc.</small>
        </div>

        <button class="btn btn-primary">Enviar Solicitud</button>
    </form>
</div>
@endsection
