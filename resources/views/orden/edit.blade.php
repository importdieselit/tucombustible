@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h4>Editar Orden #{{ $orden->id }}</h4>
                </div>
                <div class="card-body">

                    <form action="{{ route('ordenes.update', $orden->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cliente</label>
                                <select name="cliente_id" class="form-control" required>
                                    <option value="">Seleccione</option>
                                    @foreach($clientes as $c)
                                        <option value="{{ $c->id }}" {{ $orden->cliente_id == $c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cantidad Solicitada (L)</label>
                                <input type="number" name="cantidad_solicitada" step="0.01" class="form-control" value="{{ $orden->cantidad_solicitada }}" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tipo de Combustible</label>
                                <select name="combustible_id" class="form-control" required>
                                    @foreach($combustibles as $comb)
                                        <option value="{{ $comb->id }}" {{ $orden->combustible_id == $comb->id ? 'selected' : '' }}>{{ $comb->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fecha de Solicitud</label>
                                <input type="datetime-local" name="fecha_solicitud" class="form-control" value="{{ $orden->fecha_solicitud->format('Y-m-d\TH:i') }}" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Observaciones</label>
                            <textarea name="observaciones" class="form-control" rows="3">{{ $orden->observaciones }}</textarea>
                        </div>

                        <hr>
                        <h5>Insumos Solicitados</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Insumo</th>
                                        <th>Cantidad</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="insumos-table">
                                    @foreach($orden->insumos as $i => $insumo)
                                        <tr>
                                            <td>
                                                <select name="insumos[{{ $i }}][id]" class="form-control" required>
                                                    @foreach($insumos as $ins)
                                                        <option value="{{ $ins->id }}" {{ $insumo->pivot->insumo_id == $ins->id ? 'selected' : '' }}>{{ $ins->nombre }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" name="insumos[{{ $i }}][cantidad]" value="{{ $insumo->pivot->cantidad }}" class="form-control" required>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm remove-insumo">Eliminar</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <button type="button" id="add-insumo" class="btn btn-primary btn-sm">Agregar Insumo</button>
                        </div>

                        <hr>
                        <h5>Fotos Relacionadas</h5>

                        <div class="row">
                            @foreach($orden->fotos as $foto)
                                <div class="col-md-3 text-center mb-3">
                                    <img src="{{ asset('storage/' . $foto->ruta_archivo) }}" class="img-fluid rounded border">
                                    <p class="small mt-2">{{ $foto->descripcion }}</p>
                                </div>
                            @endforeach
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Agregar Nuevas Fotos</label>
                            <input type="file" name="fotos_orden[]" class="form-control" multiple>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="submit" class="btn btn-success">Actualizar Orden</button>
                            <a href="{{ route('ordenes.index') }}" class="btn btn-secondary">Cancelar</a>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('insumos-table');
    const addBtn = document.getElementById('add-insumo');

    let index = {{ count($orden->insumos) }};

    addBtn.addEventListener('click', function() {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <select name="insumos[${index}][id]" class="form-control" required>
                    @foreach($insumos as $ins)
                        <option value="{{ $ins->id }}">{{ $ins->nombre }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="number" step="0.01" name="insumos[${index}][cantidad]" class="form-control" required>
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm remove-insumo">Eliminar</button>
            </td>`;
        table.appendChild(row);
        index++;
    });

    table.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-insumo')) {
            e.target.closest('tr').remove();
        }
    });
});
</script>
