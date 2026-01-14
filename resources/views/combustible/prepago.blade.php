@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <div class="card shadow-sm border-start border-primary" style="border-left: 5px solid !important;">
        <div class="card-body">
            <h4 class="card-title"><i class="ti-wallet text-primary"></i> Registrar Recarga Prepago</h4>
            <hr>
            <form action="{{ route('combustible.storePrepago') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Cliente</label>
                        <select name="cliente_id" class="form-select select2" required>
                            <option value="">Seleccione Cliente</option>
                            @foreach($clientes as $c)
                                <option value="{{ $c->id }}">{{ $c->nombre }} (Saldo: {{ $c->saldo_litros ?? 0 }} L)</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Litros a Comprar</label>
                        <input type="number" step="0.01" name="cantidad_litros" class="form-control" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Fecha de Pago</label>
                        <input type="datetime-local" name="fecha" class="form-control" value="{{ $hoy }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Referencia de Pago / Banco</label>
                        <input type="text" name="referencia" class="form-control" placeholder="Ej: Transf. Banesco #1234">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Observaciones</label>
                        <input type="text" name="observaciones" class="form-control">
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary px-4">Confirmar Abono de Saldo</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection