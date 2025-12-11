<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Gerencial {{ $report_dates['start_date'] }} a {{ $report_dates['end_date'] }}</title>
    <style>
        body { font-family: sans-serif; margin: 30px; }
        h1, h2, h3 { color: #0d6efd; }
        .card { border: 1px solid #ccc; border-radius: 5px; padding: 10px; margin-bottom: 20px; }
        .card-header { background-color: #f8f9fa; padding: 10px; border-bottom: 1px solid #ccc; }
        .card-body { padding: 15px; }
        .badge { display: inline-block; padding: 0.35em 0.65em; font-size: 0.75em; font-weight: 700; line-height: 1; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: 0.375rem; color: #fff; }
        .bg-primary { background-color: #0d6efd !important; }
        .bg-success { background-color: #198754 !important; }
        .bg-warning { background-color: #ffc107 !important; color: #000; }
        .table { width: 100%; margin-top: 15px; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #dee2e6; padding: 8px; text-align: left; font-size: 10px; }
        .table th { background-color: #e9ecef; }
        .display-6 { font-size: 24px; font-weight: bold; }
        .d-flex { display: flex; justify-content: space-between; align-items: center; }
        .row { display: flex; flex-wrap: wrap; margin-left: -10px; margin-right: -10px; }
        .col-4, .col-6, .col-12 { padding-left: 10px; padding-right: 10px; box-sizing: border-box; }
        .col-4 { flex: 0 0 33.33333%; max-width: 33.33333%; }
        .col-6 { flex: 0 0 50%; max-width: 50%; }
        .col-12 { flex: 0 0 100%; max-width: 100%; }
    </style>
</head>
<body>

    <h1 style="text-align: center; margin-bottom: 20px;">Reporte Gerencial</h1>
    <p style="text-align: center; font-size: 14px; margin-top: -10px;">
        **Período:** {{ Carbon\Carbon::parse($report_dates['start_date'])->format('d/m/Y') }} 
        al {{ Carbon\Carbon::parse($report_dates['end_date'])->format('d/m/Y') }}
    </p>

    <h2 style="margin-top: 30px;">Indicadores Clave</h2>
    <div class="row">
        @foreach ($totals as $indicator => $value)
            @php
                $title = ''; $unit = ''; $color = 'bg-secondary';
                switch ($indicator) {
                    case 'ventas_litros': $title = 'Ventas (Litros)'; $unit = ' Lts'; $color = 'bg-primary'; break;
                    case 'compras_litros': $title = 'Compras (Litros)'; $unit = ' Lts'; $color = 'bg-success'; break;
                    case 'ordenes_abiertas': $title = 'Órdenes Abiertas'; $unit = ''; $color = 'bg-warning'; break;
                    case 'reportes_falla': $title = 'Reportes de Falla'; $unit = ''; $color = 'bg-danger'; break;
                    // Agrega aquí el resto de tus indicadores
                    default: continue;
                }
            @endphp
            <div class="col-4">
                <div class="card {{ $color }} text-white" style="color: white; height: 100px;">
                    <div class="card-body">
                        <p style="margin: 0; font-size: 12px; opacity: 0.8;">{{ $title }}</p>
                        <p class="display-6" style="margin: 0; font-size: 20px;">
                            {{ number_format($value, is_numeric($value) && floor($value) != $value ? 2 : 0, ',', '.') }}{{ $unit }}
                        </p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if(isset($details['reportes_falla_data']) && count($details['reportes_falla_data']) > 0)
        <h2 style="margin-top: 30px;">Detalle de Reportes de Falla ({{ count($details['reportes_falla_data']) }} Órdenes)</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>Nro. Orden</th>
                    <th>Tipo</th>
                    <th>Fecha Creación</th>
                    <th>Unidad</th>
                    <th>Estatus</th>
                </tr>
            </thead>
            <tbody>
                @foreach($details['reportes_falla_data'] as $orden)
                    @php
                        $unidadNombre = $orden->vehiculoBelong ? $orden->vehiculoBelong->flota . ' (' . $orden->vehiculoBelong->placa . ')' : 'N/A (Sin Unidad)';
                        $estatusText = $orden->estatus == 2 ? 'Abierta' : ($orden->estatus == 1 ? 'Pendiente' : 'Cerrada');
                        $estatusColor = $orden->estatus == 2 ? 'bg-warning' : ($orden->estatus == 1 ? 'bg-primary' : 'bg-success');
                    @endphp
                    <tr>
                        <td>{{ $orden->nro_orden }}</td>
                        <td>{{ $orden->tipo }}</td>
                        <td>{{ Carbon\Carbon::parse($orden->created_at)->format('d/m/Y') }}</td>
                        <td>{{ $unidadNombre }}</td>
                        <td><span class="badge {{ $estatusColor }}">{{ $estatusText }}</span></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    
    @if(isset($details['reportes_falla_grouped']) && count($details['reportes_falla_grouped']) > 0)
        <h3 style="margin-top: 20px;">Fallas Agrupadas por Unidad</h3>
        <ul style="list-style: none; padding: 0;">
            @foreach($details['reportes_falla_grouped'] as $key => $data)
                <li style="margin-bottom: 5px; border: 1px solid #eee; padding: 5px;">
                    {{ $key }}: <strong>{{ $data['count'] }} Órdenes</strong>
                </li>
            @endforeach
        </ul>
    @endif
    
    </body>
</html>