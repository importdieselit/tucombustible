@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        {{-- Cabecera del Expediente --}}
        <div class="col-12 mb-4">
            <a href="{{ route('clientes.index') }}" class="btn btn-link p-0 mb-2 text-muted">
                <i class="fas fa-arrow-left"></i> Volver al listado de registro
            </a>
            <div class="card shadow-sm border-0">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="font-weight-bold mb-0">{{ $cliente->razon_social }}</h2>
                        <span class="badge badge-primary">RIF: {{ $cliente->rif }}</span>
                    </div>
                    <div class="text-right">
                        <small class="text-uppercase text-muted d-block font-weight-bold">Estatus Actual</small>
                        <h4 class="text-primary mb-0">Paso {{ $cliente->registro_paso }}: {{ $cliente->nombre_paso_actual }}</h4>
                    </div>
                </div>
            </div>
        </div>

        {{-- Documentos Cargados --}}
        <div class="col-md-8">
            <div class="card shadow border-0">
                <div class="card-header bg-white font-weight-bold py-3">
                    <i class="fas fa-copy mr-2"></i> Documentación Recibida
                </div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Documento</th>
                                <th class="text-center">Archivo</th>
                                <th class="text-right">Validación</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cliente->documentos as $doc)
                            <tr>
                                <td class="align-middle font-weight-bold">{{ strtoupper(str_replace('_', ' ', $doc->tipo_documento)) }}</td>
                                <td class="text-center">
                                    <a href="{{ asset('storage/' . $doc->ruta_archivo) }}" target="_blank" class="btn btn-sm btn-outline-info">
                                        <i class="fas fa-external-link-alt mr-1"></i> Ver PDF
                                    </a>
                                </td>
                                <td class="text-right">
                                    <span class="badge badge-success px-3 py-2">Recibido</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">No se han cargado documentos aún.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Panel de Control de Avance --}}
        <div class="col-md-4">
            <div class="card shadow border-0">
                <div class="card-header bg-dark text-white font-weight-bold">
                    <i class="fas fa-tasks mr-2"></i> Gestión de Pasos
                </div>
                <div class="card-body">
                    <form action="{{ route('clientes.avanzar', $cliente->id) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        
                        <div class="form-group">
                            <label class="font-weight-bold small text-uppercase">Cambiar a Estatus:</label>
                            <select name="paso" class="form-control">
                                @for($i = 1; $i <= 10; $i++)
                                    <option value="{{ $i }}" {{ $cliente->registro_paso == $i ? 'selected' : '' }}>
                                        Paso {{ $i }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold small text-uppercase">Observaciones (Opcional):</label>
                            <textarea name="observaciones" class="form-control" rows="3" placeholder="Indique si hay errores en los documentos..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block font-weight-bold shadow-sm py-2">
                            ACTUALIZAR PROGRESO
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection