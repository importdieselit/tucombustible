@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            
            <div class="mb-3">
                <a href="{{ route('pedidos.index') }}" class="btn btn-sm btn-outline-secondary">
                    &larr; Volver al listado
                </a>
            </div>

            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Solicitar Despacho de Combustible</h4>
                </div>
                
                <div class="card-body p-4">
                    <div class="alert {{ $disponibleGasco > 0 ? 'alert-info' : 'alert-danger' }} mb-4">
                        <h5 class="alert-heading"><i class="fas fa-info-circle"></i> Disponibilidad del Mes (GASCO)</h5>
                        <p class="mb-0">Actualmente dispone de <strong>{{ number_format($disponibleGasco, 2, ',', '.') }} Litros</strong> autorizados para solicitar en el mes en curso.</p>
                        @if($disponibleGasco <= 0)
                            <hr>
                            <p class="mb-0 text-danger"><small>No posee cupo suficiente para realizar nuevas solicitudes. Por favor, contacte a su asesor de ImporDiesel si considera que esto es un error.</small></p>
                        @endif
                    </div>

                    <form action="{{ route('pedidos.store') }}" method="POST">
                        @csrf

                        @if($cliente->es_padre && $cliente->sucursales->count() > 0)
                            <div class="form-group mb-3">
                                <label for="cliente_id" class="form-label text-dark font-weight-bold">Seleccione la Empresa/Sucursal destino</label>
                                <select name="cliente_id" id="cliente_id" class="form-control" required>
                                    <option value="{{ $cliente->id }}">Sede Principal ({{ $cliente->nombre }})</option>
                                    @foreach($cliente->sucursales as $sucursal)
                                        <option value="{{ $sucursal->id }}">Sucursal: {{ $sucursal->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <input type="hidden" name="cliente_id" value="{{ $cliente->id }}">
                        @endif

                        <div class="form-group mb-3">
                            <label for="cantidad" class="form-label text-dark font-weight-bold">Cantidad Solicitada (Litros) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="1" max="{{ $disponibleGasco > 0 ? $disponibleGasco : 0 }}" 
                                   class="form-control form-control-lg @error('cantidad') is-invalid @enderror" 
                                   id="cantidad" name="cantidad" value="{{ old('cantidad') }}" 
                                   placeholder="Ej. 5000" required {{ $disponibleGasco <= 0 ? 'disabled' : '' }}>
                            @error('cantidad')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Ingrese la cantidad exacta de litros que requiere en este despacho.</small>
                        </div>

                        <div class="form-group mb-4">
                            <label for="observaciones" class="form-label text-dark font-weight-bold">Observaciones (Opcional)</label>
                            <textarea class="form-control @error('observaciones') is-invalid @enderror" 
                                      id="observaciones" name="observaciones" rows="3" 
                                      placeholder="Instrucciones especiales para la entrega, horarios, etc."
                                      {{ $disponibleGasco <= 0 ? 'disabled' : '' }}>{{ old('observaciones') }}</textarea>
                            @error('observaciones')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2 text-right text-end">
                            <button type="submit" class="btn btn-primary btn-lg" {{ $disponibleGasco <= 0 ? 'disabled' : '' }}>
                                <i class="fas fa-paper-plane"></i> Enviar Solicitud
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection