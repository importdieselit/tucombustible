@extends('layouts.app')

@section('title', 'Gestión de Depósitos de Combustible')

@section('content')
<h2>Tabla de Aforo Teórico - Tanque {{ $deposito->serial }} ({{ number_format($deposito->diametro, 0) }} cm)</h2>
<p class="text-muted">Rango por Columna: {{ $rangoPorColumna }} cm</p>

<a href="{{ route('depositos.aforo.export', ['deposito' => $deposito->id ]) }}" class="btn btn-success">
    <i class="fas fa-file-excel"></i> Exportar a Excel
</a>
<table class="table table-bordered table-sm table-striped">
    <thead>
        <tr>
            @for ($j = 0; $j < $numColumnasRango; $j++)
                <th class="text-center" colspan="2">Rango {{ $j + 1 }}</th>
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
                        $celda = $tablaCondensada[$i][$j] ?? null;
                    @endphp

                    @if ($celda)
                        <td class="text-right table-secondary" style="width: 70px;">
                            {{ number_format($celda['cm'], 1) }}
                        </td>
                        <td class="text-right">
                            {{ number_format($celda['litros'], 2) }}
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