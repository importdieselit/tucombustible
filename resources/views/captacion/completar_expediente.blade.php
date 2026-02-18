@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            {{-- Eliminamos un bloque de alertas si ves que se repite, deja solo este: --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert">×</button>
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                </div>
            @endif

            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="card-title text-bold">Expediente Digital: {{ $captacion->razon_social }}</h3>
                    <div class="card-tools">
                        <span class="badge" style="background-color: #343a40; color: #fff; padding: 5px 10px;">
                            Estatus: {{ ucfirst($captacion->estatus_captacion) }}
                        </span>
                    </div>
                </div>

                <div class="card-body">
                    <p class="text-muted">Formatos permitidos: <strong>PDF, JPG, PNG</strong> (Máx. 10MB).</p>

                    <div class="row">
                        @foreach($requisitos as $requisito)
                            @php
                                $yaSubido = in_array($requisito->id, $documentosSubidos);
                            @endphp
                            
                            <div class="col-md-6 mb-3">
                                <div class="card card-outline {{ $yaSubido ? 'card-success' : 'card-primary' }}">
                                    <div class="card-header" style="background-color: #001f3f; color: white;">
                                        <h3 class="card-title" style="font-size: 0.9rem;">{{ $requisito->descripcion }}</h3>
                                        @if($yaSubido)
                                            <div class="card-tools"><i class="fas fa-check-circle text-success"></i></div>
                                        @endif
                                    </div>
                                    <div class="card-body">
                                        <form action="{{ route('captacion.subir_documento', $captacion->id) }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" name="requisito_id" value="{{ $requisito->id }}">
                                            
                                            <div class="form-group mb-0">
                                                <div class="custom-file">
                                                    <input type="file" name="archivo" class="custom-file-input" id="file-{{ $requisito->id }}" accept=".pdf,.jpg,.jpeg,.png" required onchange="this.form.submit()">
                                                    {{--Si ya subió, mostramos "Documento listo" --}}
                                                    <label class="custom-file-label text-truncate" for="file-{{ $requisito->id }}">
                                                        {{ $yaSubido ? '✅ Archivo cargado correctamente' : 'Seleccionar archivo...' }}
                                                    </label>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="card-footer text-center">
                    <form action="{{ route('captacion.finalizar_carga', $captacion->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success btn-lg px-5 shadow" {{ count($documentosSubidos) < count($requisitos) ? 'disabled' : '' }}>
                            <i class="fas fa-paper-plane"></i> Enviar Expediente para Validación
                        </button>
                    </form>
                    @if(count($documentosSubidos) < count($requisitos))
                        <p class="mt-2 text-danger small">Debes cargar todos los documentos para poder enviar el expediente.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection