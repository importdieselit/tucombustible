@extends('layouts.app')

@section('title', 'Gestión de Depósitos de Combustible')

@section('content')
<h2>Tabla de Aforo Teórico - {{ $deposito->nombre }} ({{ number_format($deposito->diametro, 0) }} cm)</h2>
<p class="text-muted">Rango por Columna: {{ $rangoPorColumna }} cm</p>

<table class="table table-bordered table-sm table-striped">
    <thead>
        <tr>
            @for ($j = 0; $j < $numColumnasRango; $j++)
                @php
                    $inicio = $j * $rangoPorColumna;
                    $fin = min($deposito->diametro, ($j + 1) * $rangoPorColumna - $pasoAforo);
                @endphp
                <th colspan="2" class="text-center">
                    {{ number_format($inicio, 0) }} cm a {{ number_format($fin, 1) }} cm
                </th>
            @endfor
        </tr>
        <tr>
            @for ($j = 0; $j < $numColumnasRango; $j++)
                <th class="text-center">CM</th>
                <th class="text-center">LITROS</th>
            @endfor
        </tr>
    </thead>
    <tbody>
        @for ($i = 0; $i <= $maxFilas; $i++)
            <tr>
                @for ($j = 0; $j < $numColumnasRango; $j++)
                    @php
                        // Calcular la Profundidad para esta celda
                        $profundidad = $j * $rangoPorColumna + ($i * $pasoAforo);
                        $volumen = $tablaCondensada[$i][$j] ?? null;
                    @endphp
                    
                    @if ($profundidad <= $deposito->diametro)
                        <td class="text-right">{{ number_format($profundidad, 1) }}</td>
                        <td class="text-right">
                            {{ $volumen !== null ? number_format($volumen, 2) : '-' }}
                        </td>
                    @else
                        <td class="text-muted text-center">-</td>
                        <td class="text-muted text-center">-</td>
                    @endif
                @endfor
            </tr>
        @endfor
    </tbody>
</table>
@endsection