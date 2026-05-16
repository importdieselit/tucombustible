    @extends('layouts.app')

  @push('styles')
<style>
    /* Estándar Visual TuCombustible - Versión High-Visibility */
    .bg-chutos { background-color: #ff6600 !important; color: white; }
    .bg-camiones { background-color: #ffc107 !important; color: #212529; }
    .bg-cisternas { background-color: #198754 !important; color: white; }
    .bg-camionetas { background-color: #2c3e50 !important; color: white; }
    
    .badge-std {
        display: inline-flex;
        align-items: center;
        padding: 8px 16px; /* Más grande */
        border-radius: 20px;
        font-size: 14px; /* Aumentado */
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .card-master {
        border-radius: 20px; /* Más redondeado */
        box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        border: none;
        margin-bottom: 1.5rem;
    }

    /* Tipografía de Lectura Rápida */
    .mega-text {
        font-size: 3.5rem !important; /* Impacto total */
        font-weight: 900 !important;
        line-height: 1;
        margin: 10px 0;
    }

    .kpi-label {
        font-size: 1.1rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .sub-kpi {
        font-size: 1.2rem;
        font-weight: 600;
    }

    .table-text {
        font-size: 1.1rem !important;
    }
</style>
@endpush


    @section('content')

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Reporte de Eficiencia - Checklist</h2>
        <form action="{{ route('reporte.eficiencia.cerrar') }}" method="POST" onsubmit="return confirm('¿Estás seguro de cerrar el periodo actual? Se moverá a históricos.')">
            @csrf
            <button type="submit" class="btn btn-danger">Realizar Cierre de Mes</button>
        </form>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-dark">Resumen Mes en Curso</div>
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Auditor</th>
                        <th>Total</th>
                        <th>Salidas > Tiempo</th>
                        <th>Entradas > 60min</th>
                        <th>Eficiencia %</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reporteActual as $row)
                        @php $tardios = $row->salidas_tardias + $row->entradas_tardias; @endphp
                        <tr>
                            <td>{{ $row->name }}</td>
                            <td>{{ $row->total_realizados }}</td>
                            <td><span class="badge badge-warning">{{ $row->salidas_tardias }}</span></td>
                            <td><span class="badge badge-danger">{{ $row->entradas_tardias }}</span></td>
                            <td>{{ round((($row->total_realizados - $tardios) / max($row->total_realizados, 1)) * 100) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header bg-secondary text-dark">Histórico de Cierres</div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Periodo</th>
                        <th>Auditor</th>
                        <th>Total</th>
                        <th>Eficiencia Final</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($historico as $h)
                        <tr>
                            <td><strong>{{ $h->periodo }}</strong></td>
                            <td>{{ $h->name }}</td>
                            <td>{{ $h->total_realizados }}</td>
                            <td>{{ round((($h->total_realizados - ($h->salidas_tardias + $h->entradas_tardias)) / max($h->total_realizados, 1)) * 100) }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection