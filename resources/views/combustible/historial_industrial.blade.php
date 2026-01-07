@extends('layouts.app')

@section('title', 'Historial de Despachos Industriales')

@section('content')
<div class="container-fluid mt-4">
    <div class="row page-titles">
        <div class="col-md-6">
            <h3 class="text-themecolor">Historial Tanque 00 (Industrial)</h3>
        </div>
        <div class="col-md-6 d-flex justify-content-end align-items-center">
            <a href="{{ route('combustible.createDespachoIndustrial') }}" class="btn btn-success">
                <i class="fa fa-plus-circle"></i> Nuevo Despacho
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover contact-list" id="historialTable">
                    <thead>
                        <tr class="table-dark">
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Vehículo / Placa</th>
                            <th>Cant. (Lts)</th>
                            <th>Stock Inicial</th>
                            <th>Stock Final</th>
                            <th>Obs.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($historial as $mov)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($mov->created_at)->format('d/m/Y H:i') }}</td>
                            <td><b>{{ $mov->cliente->nombre ?? 'N/A' }}</b></td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ $mov->vehiculo->placa ?? 'Sin Placa' }}
                                </span>
                                <small class="text-muted d-block">{{ $mov->vehiculo->alias ?? '' }}</small>
                            </td>
                            <td class="text-danger font-weight-bold">- {{ number_format($mov->cantidad_litros, 2) }} L</td>
                            <td>{{ number_format($mov->cant_inicial, 2) }}</td>
                            <td><span class="text-primary">{{ number_format($mov->cant_final, 2) }}</span></td>
                            <td><small>{{ $mov->observaciones }}</small></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center mt-3">
                {{ $historial->links() }}
            </div>
        </div>
    </div>
</div>
@endsection