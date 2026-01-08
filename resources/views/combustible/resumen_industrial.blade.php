@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <div class="row mb-3">
        <div class="col-md-6">
            <h3>📊 Resumen de Consumo Industrial</h3>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group shadow-sm">
                <a href="{{ route('combustible.resumenDesp', ['periodo' => 'diario']) }}" 
                   class="btn btn-{{ $periodo == 'diario' ? 'primary' : 'outline-primary' }}">Hoy</a>
                <a href="{{ route('combustible.resumenDesp', ['periodo' => 'semanal']) }}" 
                   class="btn btn-{{ $periodo == 'semanal' ? 'primary' : 'outline-primary' }}">Semana</a>
                <a href="{{ route('combustible.resumenDesp', ['periodo' => 'mensual']) }}" 
                   class="btn btn-{{ $periodo == 'mensual' ? 'primary' : 'outline-primary' }}">Mes</a>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Cliente</th>
                            <th class="text-center">Cant. Despachos</th>
                            <th class="text-end">Total Litros</th>
                            <th class="text-end">Promedio x Carga</th>
                            <th>Última Actividad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($resumen as $item)
                        <tr>
                            <td><strong>{{ $item->cliente->nombre ?? 'Desconocido' }}</strong></td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-info text-dark">
                                    {{ $item->total_despachos }}
                                </span>
                            </td>
                            <td class="text-end font-weight-bold">
                                {{ number_format($item->total_litros, 2) }} L
                            </td>
                            <td class="text-end text-muted">
                                {{ number_format($item->total_litros / $item->total_despachos, 2) }} L
                            </td>
                            <td>
                                <small>{{ \Carbon\Carbon::parse($item->ultimo_despacho)->diffForHumans() }}</small>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">No hay consumos registrados en este periodo.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($resumen->count() > 0)
                    <tfoot class="table-secondary">
                        <tr>
                            <th>TOTAL GENERAL</th>
                            <th class="text-center">{{ $resumen->sum('total_despachos') }}</th>
                            <th class="text-end">{{ number_format($resumen->sum('total_litros'), 2) }} L</th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection