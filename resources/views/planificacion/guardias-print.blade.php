<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Guardias - IMPORDIESEL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-size: 13px; background-color: #fff; color: #333; }
        .text-corporate { color: #0f2d59; }
        .bg-corporate { background-color: #0f2d59 !important; color: white; }
        .table-custom { border: 2px solid #0f2d59; }
        .table-custom th { background-color: #0f2d59; color: white; border: 1px solid #dee2e6; text-align: center; }
        .table-custom td { border: 1px solid #dee2e6; vertical-align: top; height: 120px; }
        .rol-badge { font-size: 10px; font-weight: bold; text-transform: uppercase; padding: 2px 4px; border-radius: 4px; display: inline-block; }
        .badge-cho { background-color: #dbeafe; color: #1e40af; }
        .badge-ayu { background-color: #fef3c7; color: #92400e; }
        .badge-mec { background-color: #f3e8ff; color: #6b21a8; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body class="p-4">

    <div class="d-flex justify-content-between align-items-center mb-4 no-print border-bottom pb-3">
        <span class="text-muted">Vista previa del Reporte de Guardias Semanales</span>
        <button onclick="window.print()" class="btn btn-primary bg-corporate"><i class="fa fa-print"></i> Confirmar Impresión / Guardar PDF</button>
    </div>

    <!-- Encabezado del Reporte -->
    <div class="row align-items-center border-bottom pb-3 mb-4">
        <div class="col-8">
            <h2 class="text-corporate fw-bold mb-1">IMPORDIESEL, C.A.</h2>
            <h5 class="text-muted mb-0">Cronograma de Operación de Guardias</h5>
        </div>
        <div class="col-4 text-end">
            <h6 class="fw-bold mb-1">Semana Programada</h6>
            <span class="badge bg-corporate fs-6">{{ $startOfWeek->format('d/m/Y') }} al {{ $startOfWeek->copy()->addDays(6)->format('d/m/Y') }}</span>
        </div>
    </div>

    <!-- Tabla Semanal de Guardias -->
    <table class="table table-bordered table-custom">
        <thead>
            <tr>
                @foreach($semanaDias as $dia)
                    <th style="width: 14.28%;">
                        {{ $dia->isoFormat('dddd') }}<br>
                        <span class="fs-5">{{ $dia->format('d/m') }}</span>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                @foreach($semanaDias as $dia)
                    @php 
                        $fechaString = $dia->toDateString();
                        $guardiasDelDia = $guardias->get($fechaString) ?? collect();
                    @endphp
                    <td>
                        @forelse($guardiasDelDia as $g)
                            <div class="mb-2 p-2 rounded border" style="background-color: #f8fafc;">
                                @php 
                                    $badgeClass = strtolower(substr($g->rol_guardia, 0, 3)); 
                                @endphp
                                <span class="rol-badge badge-{{ $badgeClass }}">{{ $g->rol_guardia }}</span>
                                <div class="fw-bold mt-1" style="font-size: 11px;">{{ $g->personal->persona->nombre }}</div>
                            </div>
                        @empty
                            <div class="text-center text-muted small mt-4">Sin guardias</div>
                        @endforelse
                    </td>
                @endforeach
            </tr>
        </tbody>
    </table>

    <!-- Firmas / Control Interno -->
    <div class="row mt-5 pt-4 text-center">
        <div class="col-4 offset-1 border-top pt-2">
            <strong>Elaborado por:</strong><br>
            <span class="text-muted">Operaciones y Logística</span>
        </div>
        <div class="col-4 offset-2 border-top pt-2">
            <strong>Aprobado por:</strong><br>
            <span class="text-muted">Dirección General</span>
        </div>
    </div>

</body>
</html>