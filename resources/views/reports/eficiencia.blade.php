<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Reporte de Eficiencia - Checklist</h2>
        <form action="{{ route('reporte.eficiencia.cerrar') }}" method="POST" onsubmit="return confirm('¿Estás seguro de cerrar el periodo actual? Se moverá a históricos.')">
            @csrf
            <button type="submit" class="btn btn-danger">Realizar Cierre de Mes</button>
        </form>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white">Resumen Mes en Curso</div>
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
        <div class="card-header bg-secondary text-white">Histórico de Cierres</div>
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