@extends('layouts.app')

@section('title', 'Editar Cuadro de Viáticos - Viaje #'.$viaje->id)

@section('content')
<div class="container mt-4">
    <h2>Cuadro de Viáticos para Viaje a {{ $viaje->destino_ciudad }}</h2>
    <p>Chofer Asignado: **{{ $viaje->chofer->persona->nombre ?? 'N/A' }}** | Fecha Salida: {{ $viaje->fecha_salida }}</p>

    <form action="{{ route('viajes.viaticos.update', $viaje->id) }}" method="POST">
        @csrf
        @method('PUT')

        <table class="table table-bordered table-striped">
            <thead class="bg-primary text-white">
                <tr>
                    <th>Concepto</th>
                    <th class="text-end">Monto Base ({{ $viaje->ayudante }} Ay.)</th>
                    <th class="text-end">Cantidad (Unidades)</th>
                    <th class="text-end">Total Base ($)</th>
                    <th class="text-end" style="width: 15%;">Monto Ajustado ($)</th>
                </tr>
            </thead>
            <tbody>
                @php $totalBase = 0; $totalAjustado = 0; @endphp
                @foreach ($viaje->viaticos as $viatico)
                    @php 
                        $subTotalBase = $viatico->monto_base * $viatico->cantidad; 
                        $montoFinal = $viatico->monto_ajustado ?? $subTotalBase;
                        $totalBase += $subTotalBase;
                        $totalAjustado += $montoFinal;
                    @endphp
                    <tr>
                        <td>{{ $viatico->concepto }}</td>
                        <td class="text-end">${{ number_format($viatico->monto_base, 2) }}</td>
                        <td class="text-end">{{ $viatico->cantidad }}</td>
                        <td class="text-end fw-bold">${{ number_format($subTotalBase, 2) }}</td>
                        
                        <td class="text-end">
                            @if ($viatico->es_editable)
                                <!-- Campo editable para el Coordinador Administrativo -->
                                <input type="number" 
                                       step="0.01" 
                                       name="ajustes[{{ $viatico->id }}]"
                                       value="{{ number_format($montoFinal, 2, '.', '') }}"
                                       class="form-control form-control-sm text-end"
                                       required>
                                @else
                                <!-- Campos fijos (ej. Pago Chofer) -->
                                <span class="form-control-plaintext text-end fw-bold">${{ number_format($montoFinal, 2) }}</span>
                                <input type="hidden" name="ajustes[{{ $viatico->id }}]" value="{{ number_format($montoFinal, 2, '.', '') }}">
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="table-info">
                    <td colspan="3" class="text-end fw-bold">TOTALES</td>
                    <td class="text-end fw-bold">${{ number_format($totalBase, 2) }}</td>
                    <td class="text-end fw-bold">${{ number_format($totalAjustado, 2) }}</td>
                </tr>
            </tfoot>
        </table>
        
        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-success">Guardar Ajustes y Aprobar Viáticos</button>
        </div>
    </form>
</div>
@endsection
