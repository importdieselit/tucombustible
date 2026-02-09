{{-- resources/views/reportes/create.blade.php --}}

@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Reportar Nueva Reporte</h2>
    <div class="card">
        <div class="card-body">
            {{-- Importante: El enctype es necesario para subir archivos --}}
            <form method="POST" action="{{ route('reportes.store') }}" enctype="multipart/form-data">
                @csrf

                {{-- Tipo de Reporte (Clasificación) --}}
                <div class="mb-3">
                    <label for="id_tipo_reporte" class="form-label">Tipo de Falla/Reporte <span class="text-danger">*</span></label>
                    <select name="id_tipo_reporte" id="id_tipo_reporte" class="form-control @error('id_tipo_reporte') is-invalid @enderror" required>
                        <option value="">Seleccione un tipo...</option>
                        {{-- $tiposReporte viene del método create() en el controlador --}}
                        @foreach ($tiposReporte as $id => $nombre)
                            <option value="{{ $id }}" {{ old('id_tipo_reporte') == $id ? 'selected' : '' }}>
                                {{ $nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('id_tipo_reporte')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Lugar del Reporte --}}
                <div class="mb-3">
                    <label for="lugar_reporte" class="form-label">Lugar/Ubicación del Reporte <span class="text-danger">*</span></label>
                    <input type="text" name="lugar_reporte" id="lugar_reporte" class="form-control @error('lugar_reporte') is-invalid @enderror" value="{{ old('lugar_reporte') }}" required>
                    @error('lugar_reporte')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Descripción Detallada --}}
                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción de la Falla <span class="text-danger">*</span></label>
                    <textarea name="descripcion" id="descripcion" class="form-control @error('descripcion') is-invalid @enderror" rows="5" required>{{ old('descripcion') }}</textarea>
                    @error('descripcion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Registro Gráfico Opcional --}}
                <div class="mb-3">
                    <label for="imagen_evidencia" class="form-label">Registro Gráfico (Opcional)</label>
                    <input type="file" name="imagen_evidencia" id="imagen_evidencia" class="form-control @error('imagen_evidencia') is-invalid @enderror" accept="image/png, image/jpeg, image/jpg">
                    <small class="form-text text-muted">Formatos permitidos: JPG, PNG. Máx. 2MB.</small>
                    @error('imagen_evidencia')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-success">Guardar Reporte</button>
                <a href="{{ route('reportes.index') }}" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
@endsection